/**
 * Stress Test
 *
 * Pushes the application beyond normal load to find breaking points.
 * Use this to identify system limits and failure modes.
 *
 * WARNING: Only run against staging/test environments.
 *
 * Usage: k6 run tests/Load/stress.js
 */

import { check, group, sleep } from 'k6';
import http from 'k6/http';
import { Counter, Rate } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');
const timeouts = new Counter('timeouts');
const serverErrors = new Counter('server_errors');

export const options = {
    stages: [
        // Warm up
        { duration: '1m', target: 10 },
        // Normal load
        { duration: '2m', target: 50 },
        // Stress begins
        { duration: '2m', target: 100 },
        // Peak stress
        { duration: '3m', target: 200 },
        // Beyond capacity
        { duration: '2m', target: 300 },
        // Recovery
        { duration: '2m', target: 50 },
        // Cool down
        { duration: '1m', target: 0 },
    ],
    thresholds: {
        // Stress test thresholds are more lenient
        http_req_duration: ['p(95)<3000'], // 95% under 3 seconds
        http_req_failed: ['rate<0.15'], // Up to 15% failure acceptable under stress
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
    group('Stress: Public Endpoints', function () {
        const responses = http.batch([
            ['GET', `${BASE_URL}/`],
            ['GET', `${BASE_URL}/up`],
            ['GET', `${BASE_URL}/health/json`],
        ]);

        responses.forEach((res) => {
            const passed = check(res, {
                'status is not 5xx': (r) => r.status < 500,
                'response received': (r) => r.status !== 0,
            });

            if (!passed) {
                errorRate.add(1);
            }

            if (res.status >= 500) {
                serverErrors.add(1);
            }

            if (res.timings.duration > 10000) {
                timeouts.add(1);
            }
        });

        // Shorter sleep during stress to increase pressure
        sleep(Math.random() * 0.5);
    });
}

export function handleSummary(data) {
    const summary = {
        timestamp: new Date().toISOString(),
        test_type: 'stress',
        metrics: {
            http_req_duration_p50:
                data.metrics.http_req_duration?.values?.['p(50)'],
            http_req_duration_p95:
                data.metrics.http_req_duration?.values?.['p(95)'],
            http_req_duration_p99:
                data.metrics.http_req_duration?.values?.['p(99)'],
            http_req_failed_rate: data.metrics.http_req_failed?.values?.rate,
            error_rate: data.metrics.errors?.values?.rate,
            server_errors: data.metrics.server_errors?.values?.count,
            timeouts: data.metrics.timeouts?.values?.count,
            iterations: data.metrics.iterations?.values?.count,
            vus_max: data.metrics.vus_max?.values?.max,
        },
        breaking_point: {
            description:
                'VU count where p95 latency exceeds 3s or error rate exceeds 15%',
            // This would be determined by analyzing the results
        },
    };

    return {
        'tests/Load/results/stress-summary.json': JSON.stringify(
            summary,
            null,
            2,
        ),
        stdout: JSON.stringify(summary, null, 2),
    };
}
