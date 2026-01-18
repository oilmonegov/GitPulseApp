## Summary

<!-- Provide a brief description of the changes in this PR (1-3 bullet points) -->

-
-

## Type of Change

<!-- Mark the relevant option with an "x" -->

- [ ] `feat`: New feature (non-breaking change that adds functionality)
- [ ] `fix`: Bug fix (non-breaking change that fixes an issue)
- [ ] `docs`: Documentation only changes
- [ ] `style`: Code style changes (formatting, no code change)
- [ ] `refactor`: Code refactoring (no functional changes)
- [ ] `perf`: Performance improvements
- [ ] `test`: Adding or updating tests
- [ ] `chore`: Maintenance tasks (dependencies, CI, etc.)
- [ ] `breaking`: Breaking change (fix or feature that would cause existing functionality to change)

## Related Issues

<!-- Link any related issues using "Fixes #123" or "Relates to #123" -->

Fixes #

## Changes Made

<!-- Describe the changes in detail. What did you change and why? -->

### Backend Changes
-

### Frontend Changes
-

### Database Changes
- [ ] No database changes
- [ ] New migration(s) added
- [ ] Migration is reversible (`down()` method works)

## Test Plan

<!-- Describe how you tested these changes -->

- [ ] Unit tests added/updated
- [ ] Feature tests added/updated
- [ ] Browser tests added/updated (if UI changes)
- [ ] Manual testing performed

### Test Commands Run
```bash
php artisan test --filter=YourTestName
```

## Checklist

<!-- Ensure all items are checked before requesting review -->

### Code Quality
- [ ] Code follows project conventions (checked sibling files)
- [ ] No debugging code left (dd, dump, console.log)
- [ ] PHPStan passes (`./vendor/bin/phpstan analyse`)
- [ ] Pint passes (`./vendor/bin/pint --test`)
- [ ] ESLint passes (`npm run lint:check`)
- [ ] TypeScript passes (`npm run type-check`)

### Testing
- [ ] All new code is covered by tests
- [ ] All tests pass (`php artisan test`)
- [ ] No flaky tests introduced

### Documentation
- [ ] Code is self-documenting or has necessary comments
- [ ] CLAUDE.md updated (if architectural changes)
- [ ] Lessons learned documented (if applicable)

### Security
- [ ] No secrets or credentials committed
- [ ] Input validation added for new endpoints
- [ ] Authorization checks in place

## Screenshots

<!-- If UI changes, add before/after screenshots -->

| Before | After |
|--------|-------|
|        |       |

## Additional Notes

<!-- Any additional context, concerns, or notes for reviewers -->

---

<!--
Reminder: Commits should follow Conventional Commits format:
  feat(scope): add new feature
  fix(scope): fix bug description
  docs(scope): update documentation
-->
