import { chromium } from 'playwright-core';
import { writeFile } from 'node:fs/promises';

const args = Object.fromEntries(process.argv.slice(2).map((arg) => {
    const [key, ...value] = arg.replace(/^--/, '').split('=');

    return [key, value.join('=')];
}));

const baseUrl = args.url || 'http://127.0.0.1:8080';
const executablePath = args.browser || 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const maxPages = Number(args.max || 70);
const roles = ['admin', 'secretaria', 'tecnico']
    .filter((role) => args[role])
    .map((role) => {
        const [login, ...password] = args[role].split(':');

        return { role, login, password: password.join(':') };
    });

if (roles.length === 0) {
    throw new Error('Indica al menos un rol: --admin=correo:clave, --secretaria=correo:clave o --tecnico=correo:clave');
}

const viewports = [
    { name: 'escritorio', width: 1440, height: 1000 },
    { name: 'tablet', width: 768, height: 1024 },
    { name: 'celular', width: 390, height: 844 },
];
const excluded = /\/(export|pdf|imprimir)(\/|$)|\.(pdf|xlsx?|csv)$/i;
const browser = await chromium.launch({ executablePath, headless: true });
const report = { generatedAt: new Date().toISOString(), baseUrl, roles: [] };

try {
    for (const credentials of roles) {
        const context = await browser.newContext({ viewport: viewports[0] });
        const page = await context.newPage();
        page.setDefaultNavigationTimeout(10000);
        const roleReport = { role: credentials.role, pages: [], failures: [] };
        report.roles.push(roleReport);

        await page.goto(`${baseUrl}/login`, { waitUntil: 'domcontentloaded' });
        await page.locator('[name="login"]').fill(credentials.login);
        await page.locator('[name="password"]').fill(credentials.password);
        await Promise.all([
            page.waitForURL((url) => !url.pathname.endsWith('/login'), { timeout: 15000 }),
            page.locator('button[type="submit"]').click(),
        ]);

        const discovered = args.paths
            ? args.paths.split(',').map((path) => path.trim()).filter(Boolean)
            : await discoverPages(page, maxPages);

        for (const viewport of viewports) {
            for (let offset = 0; offset < discovered.length; offset += 5) {
                const batch = discovered.slice(offset, offset + 5);
                const items = await Promise.all(batch.map((path) => auditPath(context, viewport, path)));

                for (const item of items) {
                    roleReport.pages.push(item);

                    if (item.status >= 400 || item.bodyOverflow > 3) {
                        roleReport.failures.push(item);
                    }
                }
            }
        }

        await context.close();
    }
} finally {
    await browser.close();
}

const output = args.output || 'storage/responsive-audit.json';
await writeFile(output, JSON.stringify(report, null, 2));

for (const role of report.roles) {
    const slow = role.pages.filter((page) => page.milliseconds > 1000).length;
    console.log(`${role.role}: ${new Set(role.pages.map((page) => page.path)).size} pantallas, ${role.failures.length} fallos responsive/HTTP, ${slow} cargas mayores a 1 s`);
}
console.log(`Informe: ${output}`);

async function discoverPages(page, limit) {
    const queue = ['/dashboard'];
    const queuedPatterns = new Set(['/dashboard']);
    const visited = new Set();

    while (queue.length > 0 && visited.size < limit) {
        const path = queue.shift();
        if (visited.has(path)) continue;

        let response;
        try {
            response = await page.goto(`${baseUrl}${path}`, { waitUntil: 'domcontentloaded', timeout: 10000 });
        } catch {
            continue;
        }
        if (!response || response.status() >= 400 || page.url().includes('/login')) continue;

        visited.add(path);
        const links = await page.locator('a[href]').evaluateAll((anchors, origin) => anchors
            .map((anchor) => {
                try {
                    const url = new URL(anchor.href);

                    return url.origin === origin ? `${url.pathname}${url.search}` : null;
                } catch {
                    return null;
                }
            })
            .filter(Boolean), new URL(baseUrl).origin);

        for (const link of links) {
            const pattern = routePattern(link);
            if (
                !visited.has(link)
                && !queue.includes(link)
                && !queuedPatterns.has(pattern)
                && (link === '/dashboard' || link.startsWith('/admin'))
                && !excluded.test(link)
                && !link.includes('/logout')
            ) {
                queue.push(link);
                queuedPatterns.add(pattern);
            }
        }
    }

    return [...visited];
}

function routePattern(path) {
    return path
        .replace(/\/\d+(?=\/|$|\?)/g, '/{id}')
        .replace(/\/OP-[A-Z0-9-]+(?=\/|$|\?)/gi, '/{order}');
}

async function auditPath(context, viewport, path) {
    const page = await context.newPage();
    await page.setViewportSize(viewport);
    const startedAt = performance.now();

    try {
        const response = await page.goto(`${baseUrl}${path}`, {
            waitUntil: 'domcontentloaded',
            timeout: 10000,
        });
        await page.waitForTimeout(80);
        const layout = await page.evaluate(() => {
            const width = window.innerWidth;
            const bodyOverflow = Math.max(document.body.scrollWidth, document.documentElement.scrollWidth) - width;
            const offenders = [...document.querySelectorAll('body *')]
                .filter((element) => {
                    const style = getComputedStyle(element);
                    if (style.display === 'none' || style.visibility === 'hidden') return false;
                    const rect = element.getBoundingClientRect();

                            return rect.width > 0 && rect.right > width + 3;
                })
                .slice(0, 5)
                .map((element) => ({
                    tag: element.tagName.toLowerCase(),
                    class: String(element.className || '').slice(0, 120),
                }));

            return { bodyOverflow: Math.round(bodyOverflow), offenders };
        });

        return {
            viewport: viewport.name,
            path,
            status: response?.status() ?? 0,
            milliseconds: Math.round(performance.now() - startedAt),
            ...layout,
        };
    } catch (error) {
        return {
            viewport: viewport.name,
            path,
            status: 0,
            milliseconds: Math.round(performance.now() - startedAt),
            bodyOverflow: 0,
            offenders: [],
            error: error.message,
        };
    } finally {
        await page.close();
    }
}
