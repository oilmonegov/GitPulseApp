/**
 * Smoke Test
 *
 * Basic sanity check to verify the application is responding.
 * Run this first before load tests to catch obvious issues.
 *
 * Usage: k6 run tests/Load/smoke.js
 */

import { check, sleep } from 'k6';
import http from 'k6/http';

export const options = {
    vus: 1,
    duration: '30s',
    thresholds: {
        http_req_duration: ['p(95)<500'], // 95% of requests should be under 500ms
        http_req_failed: ['rate<0.01'], // Less than 1% should fail
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
    // Test homepage
    const homeRes = http.get(`${BASE_URL}/`);
    check(homeRes, {
        'homepage status is 200': (r) => r.status === 200,
        'homepage loads in < 500ms': (r) => r.timings.duration < 500,
    });

    sleep(1);

    // Test health endpoint
    const healthRes = http.get(`${BASE_URL}/health/json`);
    check(healthRes, {
        'health endpoint status is 200': (r) => r.status === 200,
        'health endpoint returns JSON': (r) =>
            r.headers['Content-Type'].includes('application/json'),
    });

    sleep(1);

    // Test basic health check
    const upRes = http.get(`${BASE_URL}/up`);
    check(upRes, {
        'up endpoint status is 200': (r) => r.status === 200,
    });

    sleep(1);
}

export function handleSummary(data) {
    return {
        stdout: JSON.stringify(data, null, 2),
    };
}
