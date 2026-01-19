/**
 * Load Test
 *
 * Simulates normal and peak load conditions to verify
 * application performance under realistic traffic.
 *
 * Usage: k6 run tests/Load/load.js
 *        k6 run --env BASE_URL=https://staging.example.com tests/Load/load.js
 */

import { check, group, sleep } from 'k6';
import http from 'k6/http';
import { Rate, Trend } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');
const homepageDuration = new Trend('homepage_duration');
const healthDuration = new Trend('health_duration');

export const options = {
    stages: [
        // Ramp up
        { duration: '2m', target: 10 }, // Ramp up to 10 users over 2 minutes
        { duration: '5m', target: 10 }, // Stay at 10 users for 5 minutes
        { duration: '2m', target: 25 }, // Ramp up to 25 users
        { duration: '5m', target: 25 }, // Stay at 25 users for 5 minutes
        { duration: '2m', target: 50 }, // Ramp up to peak load
        { duration: '5m', target: 50 }, // Stay at peak for 5 minutes
        { duration: '2m', target: 0 }, // Ramp down to 0
    ],
    thresholds: {
        http_req_duration: ['p(95)<1000', 'p(99)<2000'], // 95% < 1s, 99% < 2s
        http_req_failed: ['rate<0.05'], // Less than 5% failure rate
        errors: ['rate<0.05'], // Custom error rate threshold
        homepage_duration: ['p(95)<800'], // Homepage specific threshold
    },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
    group('Public Pages', function () {
        // Homepage
        const homeRes = http.get(`${BASE_URL}/`);
        homepageDuration.add(homeRes.timings.duration);

        const homeCheck = check(homeRes, {
            'homepage status is 200': (r) => r.status === 200,
            'homepage has content': (r) => r.body.length > 0,
        });

        errorRate.add(!homeCheck);
        sleep(Math.random() * 3 + 1); // Random sleep 1-4 seconds
    });

    group('Health Checks', function () {
        // JSON health endpoint
        const healthRes = http.get(`${BASE_URL}/health/json`);
        healthDuration.add(healthRes.timings.duration);

        const healthCheck = check(healthRes, {
            'health status is 200': (r) => r.status === 200,
            'health returns valid JSON': (r) => {
                try {
                    JSON.parse(r.body);
                    return true;
                } catch {
                    return false;
                }
            },
        });

        errorRate.add(!healthCheck);
        sleep(1);
    });

    group('API Endpoints', function () {
        // Basic up check
        const upRes = http.get(`${BASE_URL}/up`);

        check(upRes, {
            'up endpoint responds': (r) => r.status === 200,
        });

        sleep(Math.random() * 2 + 1);
    });
}

export function handleSummary(data) {
    const summary = {
        timestamp: new Date().toISOString(),
        metrics: {
            http_req_duration_p95:
                data.metrics.http_req_duration?.values?.['p(95)'],
            http_req_duration_p99:
                data.metrics.http_req_duration?.values?.['p(99)'],
            http_req_failed_rate: data.metrics.http_req_failed?.values?.rate,
            error_rate: data.metrics.errors?.values?.rate,
            iterations: data.metrics.iterations?.values?.count,
            vus_max: data.metrics.vus_max?.values?.max,
        },
        thresholds: data.thresholds,
    };

    return {
        'tests/Load/results/load-summary.json': JSON.stringify(
            summary,
            null,
            2,
        ),
        stdout: JSON.stringify(summary, null, 2),
    };
}
