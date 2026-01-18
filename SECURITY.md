# Security Policy

## Supported Versions

We take security seriously and provide security updates for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| latest  | :white_check_mark: |
| < 1.0   | :x:                |

## Reporting a Vulnerability

We appreciate your help in keeping GitPulse secure. If you discover a security vulnerability, please follow these steps:

### Do NOT

- Open a public GitHub issue for security vulnerabilities
- Disclose the vulnerability publicly before it has been addressed
- Exploit the vulnerability beyond what is necessary to demonstrate it

### Do

1. **Email us directly** at [security@example.com](mailto:security@example.com) with:
   - A description of the vulnerability
   - Steps to reproduce the issue
   - Potential impact of the vulnerability
   - Any suggested fixes (optional but appreciated)

2. **Use responsible disclosure** - Give us reasonable time to address the issue before any public disclosure (typically 90 days)

3. **Provide sufficient detail** - Include enough information for us to reproduce and understand the vulnerability

### What to Expect

- **Acknowledgment**: We will acknowledge receipt of your report within 48 hours
- **Assessment**: We will assess the vulnerability and determine its severity within 7 days
- **Updates**: We will keep you informed of our progress
- **Resolution**: We aim to resolve critical vulnerabilities within 30 days
- **Credit**: With your permission, we will credit you in our security advisories

## Security Best Practices for Contributors

When contributing to this project, please ensure:

### Authentication & Authorization
- Never commit credentials, API keys, or secrets
- Use environment variables for sensitive configuration
- Follow the principle of least privilege

### Data Validation
- Validate and sanitize all user input
- Use parameterized queries (Eloquent) to prevent SQL injection
- Escape output to prevent XSS attacks

### Dependencies
- Keep dependencies up to date
- Review security advisories for dependencies
- Use `composer audit` and `npm audit` before committing

### Code Review
- All code changes require review before merging
- Security-sensitive changes require additional scrutiny
- Use static analysis tools (PHPStan, ESLint)

## Security Features

This application implements the following security measures:

- **CSRF Protection**: All forms include CSRF tokens
- **XSS Prevention**: Output escaping via Vue/Blade
- **SQL Injection Prevention**: Eloquent ORM with parameterized queries
- **Authentication**: Laravel Fortify with secure session handling
- **Authorization**: Policy-based access control
- **HTTPS**: TLS encryption in production
- **Security Headers**: CSP, X-Frame-Options, etc.
- **Dependency Scanning**: Automated via Dependabot
- **Static Analysis**: PHPStan level 8

## Security Updates

Security updates are released as soon as possible after a vulnerability is confirmed. Updates are distributed through:

1. GitHub Security Advisories
2. Patch releases on the main branch
3. Notifications to known affected users (for critical issues)

---

Thank you for helping keep GitPulse and its users safe!
