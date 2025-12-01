# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Reporting a Vulnerability

We take security vulnerabilities seriously. If you discover a security issue, please report it responsibly.

### How to Report

1. **Do NOT** create a public GitHub issue for security vulnerabilities
2. Email the maintainer directly at: **methorz@spammerz.de**
3. Include:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

### What to Expect

- **Acknowledgment**: Within 48 hours
- **Initial Assessment**: Within 7 days
- **Resolution Timeline**: Depends on severity (critical: ASAP, high: 30 days, medium: 90 days)

### After Resolution

- Security fixes will be released as patch versions
- Credit will be given to reporters (unless anonymity is requested)
- A security advisory will be published for significant vulnerabilities

## Security Best Practices

When using this package:

- **Keep dependencies updated** - Run `composer update` regularly
- **Use latest PHP version** - Security fixes are backported to supported versions only
- **Review generated OpenAPI specs** - Ensure no sensitive information is exposed
- **Validate input** - The generator reads route configuration; ensure it's from trusted sources

## Known Security Considerations

This package:

- Reads PHP source files via reflection (ensure source files are trusted)
- Parses route configuration (validate configuration sources)
- Generates API documentation (review output for sensitive data exposure)

## Contact

- **Security Issues**: methorz@spammerz.de
- **General Issues**: [GitHub Issues](https://github.com/MethorZ/openapi-generator/issues)

---

Thank you for helping keep this project secure!
