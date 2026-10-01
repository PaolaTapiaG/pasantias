import { performance } from 'node:perf_hooks';

const args = Object.fromEntries(process.argv.slice(2).map((arg) => {
    const [key, ...parts] = arg.replace(/^--/, '').split('=');

    return [key, parts.join('=') || 'true'];
}));

const baseUrl = args.url || 'http://127.0.0.1:8080';
const login = args.login;
const password = args.password;
const paths = (args.paths || '/dashboard').split(',').map((path) => path.trim()).filter(Boolean);

if (!login || !password) {
    throw new Error('Usa --login=correo --password=clave y opcionalmente --paths=/dashboard,/admin/socios');
}

const jar = new Map();
const results = [];

const loginPage = await timedRequest('/login');
const token = loginPage.body.match(/name="_token" value="([^"]+)"/)?.[1];

if (!token) {
    throw new Error('No se encontro el token CSRF del formulario de acceso.');
}

if (args.debug === 'true') {
    console.error({
        csrfLength: token.length,
        cookies: [...jar.entries()].map(([name, value]) => [name, value.length]),
    });
}

const loginResult = await timedRequest('/login', {
    method: 'POST',
    redirect: 'manual',
    headers: { 'content-type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ _token: token, login, password }),
});

if (args.debug === 'true' && loginResult.status === 419) {
    console.error(loginResult.body.slice(0, 500));
}

results.push({
    route: 'POST /login',
    status: loginResult.status,
    milliseconds: loginResult.milliseconds,
    appMilliseconds: loginResult.appMilliseconds,
    databaseMilliseconds: loginResult.databaseMilliseconds,
    databaseQueries: loginResult.databaseQueries,
    databaseSql: loginResult.databaseSql,
    location: loginResult.location || '',
});

for (const path of paths) {
    const result = await timedRequest(path);
    results.push({
        route: path,
        status: result.status,
        milliseconds: result.milliseconds,
        appMilliseconds: result.appMilliseconds,
        databaseMilliseconds: result.databaseMilliseconds,
        databaseQueries: result.databaseQueries,
        databaseSql: result.databaseSql,
        location: result.location || '',
    });
}

console.table(results);
console.log(JSON.stringify(results, null, 2));

async function timedRequest(path, options = {}) {
    const startedAt = performance.now();
    const response = await fetch(new URL(path, baseUrl), {
        redirect: options.redirect || 'manual',
        ...options,
        headers: {
            Accept: 'text/html,application/xhtml+xml,application/json;q=0.9,*/*;q=0.8',
            Cookie: cookieHeader(),
            ...(args.profile === 'true' ? { 'X-Performance-Debug': '1' } : {}),
            ...(args.sql === 'true' ? { 'X-Performance-Sql': '1' } : {}),
            ...(jar.has('XSRF-TOKEN') ? { 'X-XSRF-TOKEN': decodeURIComponent(jar.get('XSRF-TOKEN')) } : {}),
            ...(options.headers || {}),
        },
    });

    rememberCookies(response);
    const body = await response.text();

    return {
        status: response.status,
        location: response.headers.get('location'),
        milliseconds: Math.round((performance.now() - startedAt) * 100) / 100,
        appMilliseconds: numberHeader(response, 'x-performance-app-ms'),
        databaseMilliseconds: numberHeader(response, 'x-performance-database-ms'),
        databaseQueries: numberHeader(response, 'x-performance-database-queries'),
        databaseSql: response.headers.get('x-performance-database-sql') || '',
        body,
    };
}

function numberHeader(response, name) {
    const value = response.headers.get(name);

    return value === null ? '' : Number(value);
}

function rememberCookies(response) {
    const setCookies = typeof response.headers.getSetCookie === 'function'
        ? response.headers.getSetCookie()
        : splitSetCookie(response.headers.get('set-cookie'));

    for (const cookie of setCookies) {
        const pair = cookie.split(';', 1)[0];
        const separator = pair.indexOf('=');

        if (separator > 0) {
            jar.set(pair.slice(0, separator), pair.slice(separator + 1));
        }
    }
}

function cookieHeader() {
    return [...jar.entries()].map(([name, value]) => `${name}=${value}`).join('; ');
}

function splitSetCookie(value) {
    if (!value) {
        return [];
    }

    return value.split(/,(?=[^;,]+=)/);
}
