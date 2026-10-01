import { performance } from 'node:perf_hooks';
import { setTimeout as sleep } from 'node:timers/promises';

const args = parseArgs(process.argv.slice(2));
const baseUrl = args.url || process.env.LOAD_TEST_URL || 'http://127.0.0.1:8000';
const users = toPositiveInteger(args.users || process.env.LOAD_TEST_USERS || 5000, 'users');
const durationMs = parseDuration(args.duration || process.env.LOAD_TEST_DURATION || '2m');
const rampMs = parseDuration(args.ramp || process.env.LOAD_TEST_RAMP || '30s');
const timeoutMs = parseDuration(args.timeout || process.env.LOAD_TEST_TIMEOUT || '10s');
const maxFailureRate = Number(args['max-failure-rate'] || process.env.LOAD_TEST_MAX_FAILURE_RATE || 5);
const paths = parsePaths(args.paths || process.env.LOAD_TEST_PATHS);
const [thinkMinMs, thinkMaxMs] = parseThinkTime(args.think || process.env.LOAD_TEST_THINK || '250-1500');
const cookie = args.cookie || process.env.LOAD_TEST_COOKIE || '';
const uniqueClientIps = String(args['unique-client-ips'] || process.env.LOAD_TEST_UNIQUE_CLIENT_IPS || 'false') === 'true';

const stats = {
    total: 0,
    ok: 0,
    failed: 0,
    inFlight: 0,
    maxInFlight: 0,
    latencies: [],
    statuses: new Map(),
    errors: new Map(),
};

const startedAt = performance.now();
const fullEndAt = startedAt + rampMs + durationMs;

console.log(`EPSAS load test`);
console.log(`Target: ${baseUrl}`);
console.log(`Users: ${users} | Ramp: ${formatMs(rampMs)} | Sustained: ${formatMs(durationMs)} | Think: ${thinkMinMs}-${thinkMaxMs}ms`);
console.log(`Paths: ${paths.join(', ')}`);
console.log(`Unique client IPs: ${uniqueClientIps ? 'enabled' : 'disabled'}`);

const progressTimer = setInterval(() => {
    const elapsedSeconds = Math.max((performance.now() - startedAt) / 1000, 1);
    console.log(
        `progress ${elapsedSeconds.toFixed(0)}s | req=${stats.total} | ok=${stats.ok} | failed=${stats.failed} | rps=${(stats.total / elapsedSeconds).toFixed(1)} | in-flight=${stats.inFlight}`
    );
}, 5000);

await Promise.all(Array.from({ length: users }, (_, index) => virtualUser(index + 1)));
clearInterval(progressTimer);

const elapsedMs = performance.now() - startedAt;
const result = buildSummary(elapsedMs);

console.log('\nSummary');
console.log(`Requests: ${result.requests.total} total, ${result.requests.ok} ok, ${result.requests.failed} failed`);
console.log(`Throughput: ${result.requests.perSecond} req/s`);
console.log(`Max concurrent in-flight: ${result.requests.maxInFlight}`);
console.log(`Latency ms: avg=${result.latency.avg} p50=${result.latency.p50} p90=${result.latency.p90} p95=${result.latency.p95} p99=${result.latency.p99} max=${result.latency.max}`);
console.log(`Failure rate: ${result.failureRate}%`);
console.log(`Statuses: ${JSON.stringify(result.statuses)}`);

if (Object.keys(result.errors).length > 0) {
    console.log(`Errors: ${JSON.stringify(result.errors)}`);
}

console.log('\nJSON');
console.log(JSON.stringify(result, null, 2));

if (result.failureRate > maxFailureRate) {
    console.error(`Failure rate exceeded ${maxFailureRate}%.`);
    process.exitCode = 1;
}

async function virtualUser(userId) {
    const startDelay = rampMs > 0 ? Math.floor((userId - 1) * (rampMs / users)) : 0;
    await sleep(startDelay);

    let iteration = 0;
    while (performance.now() < fullEndAt) {
        const path = paths[(userId + iteration) % paths.length];
        await requestPath(path, userId);
        iteration++;

        if (thinkMaxMs > 0) {
            await sleep(randomBetween(thinkMinMs, thinkMaxMs));
        }
    }
}

async function requestPath(path, userId) {
    const url = new URL(path, baseUrl).toString();
    const requestStarted = performance.now();
    stats.inFlight++;
    stats.maxInFlight = Math.max(stats.maxInFlight, stats.inFlight);

    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), timeoutMs);

    try {
        const response = await fetch(url, {
            method: 'GET',
            redirect: 'manual',
            signal: controller.signal,
            headers: {
                Accept: 'text/html,application/xhtml+xml,application/json;q=0.9,*/*;q=0.8',
                'User-Agent': 'epsas-load-test/1.0',
                ...(cookie ? { Cookie: cookie } : {}),
                ...(uniqueClientIps ? { 'X-Forwarded-For': syntheticIp(userId) } : {}),
            },
        });

        await response.arrayBuffer();
        recordStatus(response.status, performance.now() - requestStarted);
    } catch (error) {
        recordError(error);
    } finally {
        clearTimeout(timeout);
        stats.inFlight--;
    }
}

function syntheticIp(userId) {
    const offset = Math.max(userId - 1, 0);
    const second = Math.floor(offset / (254 * 254)) % 254;
    const third = Math.floor(offset / 254) % 254;
    const fourth = (offset % 254) + 1;

    return `10.${second}.${third}.${fourth}`;
}

function recordStatus(status, latencyMs) {
    stats.total++;
    stats.latencies.push(latencyMs);
    stats.statuses.set(status, (stats.statuses.get(status) || 0) + 1);

    if (status >= 200 && status < 400) {
        stats.ok++;
    } else {
        stats.failed++;
    }
}

function recordError(error) {
    stats.total++;
    stats.failed++;
    const name = error?.name || 'Error';
    stats.errors.set(name, (stats.errors.get(name) || 0) + 1);
}

function buildSummary(elapsedMs) {
    const latencies = [...stats.latencies].sort((a, b) => a - b);
    const avg = latencies.length
        ? latencies.reduce((sum, value) => sum + value, 0) / latencies.length
        : 0;

    return {
        target: baseUrl,
        users,
        ramp: formatMs(rampMs),
        duration: formatMs(durationMs),
        elapsedSeconds: round(elapsedMs / 1000),
        failureRate: stats.total ? round((stats.failed / stats.total) * 100) : 0,
        requests: {
            total: stats.total,
            ok: stats.ok,
            failed: stats.failed,
            perSecond: round(stats.total / Math.max(elapsedMs / 1000, 1)),
            maxInFlight: stats.maxInFlight,
        },
        latency: {
            avg: round(avg),
            p50: round(percentile(latencies, 50)),
            p90: round(percentile(latencies, 90)),
            p95: round(percentile(latencies, 95)),
            p99: round(percentile(latencies, 99)),
            max: round(latencies.at(-1) || 0),
        },
        statuses: Object.fromEntries(stats.statuses),
        errors: Object.fromEntries(stats.errors),
    };
}

function percentile(sortedValues, percentileValue) {
    if (sortedValues.length === 0) {
        return 0;
    }

    const index = Math.ceil((percentileValue / 100) * sortedValues.length) - 1;
    return sortedValues[Math.max(0, Math.min(index, sortedValues.length - 1))];
}

function parseArgs(argv) {
    return argv.reduce((parsed, arg) => {
        if (!arg.startsWith('--')) {
            return parsed;
        }

        const [key, ...valueParts] = arg.slice(2).split('=');
        parsed[key] = valueParts.length ? valueParts.join('=') : 'true';

        return parsed;
    }, {});
}

function parsePaths(value) {
    if (!value) {
        return [
            '/login',
            '/up',
        ];
    }

    return value
        .split(',')
        .map((path) => path.trim())
        .filter(Boolean);
}

function parseThinkTime(value) {
    const [min, max] = String(value).split('-').map((part) => Number(part));

    if (!Number.isFinite(min) || !Number.isFinite(max) || min < 0 || max < min) {
        throw new Error(`Invalid think time: ${value}. Use something like 250-1500.`);
    }

    return [min, max];
}

function parseDuration(value) {
    const match = String(value).trim().match(/^(\d+(?:\.\d+)?)(ms|s|m)?$/);

    if (!match) {
        throw new Error(`Invalid duration: ${value}. Use ms, s, or m.`);
    }

    const amount = Number(match[1]);
    const unit = match[2] || 'ms';

    return Math.round(amount * ({ ms: 1, s: 1000, m: 60000 }[unit]));
}

function toPositiveInteger(value, name) {
    const parsed = Number(value);

    if (!Number.isInteger(parsed) || parsed <= 0) {
        throw new Error(`${name} must be a positive integer.`);
    }

    return parsed;
}

function randomBetween(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

function round(value) {
    return Math.round(value * 100) / 100;
}

function formatMs(ms) {
    if (ms % 60000 === 0) {
        return `${ms / 60000}m`;
    }

    if (ms % 1000 === 0) {
        return `${ms / 1000}s`;
    }

    return `${ms}ms`;
}
