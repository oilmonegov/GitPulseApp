# Document Lessons Learned

You are helping document lessons learned from recent development work.

## Your Task

1. **Review Recent Activity**: Look at the recent git commits and changes made in this session
2. **Identify Lessons**: Consider what went wrong, what went well, and key decisions made
3. **Update Documentation**: Add a new section to `docs/lessons/LESSONS.md` following the existing format

## Lessons Format

Use this template for new entries:

```markdown
## [Sprint/Feature Name]: [Brief Description]

### What went wrong?
- Issue description and root cause

### What went well?
- Success description and contributing factors

### Why we chose this direction
- **Decision**: What we decided
- **Reasoning**: Why this choice was best
```

## Guidelines

- Be specific and actionable - future developers should learn from this
- Include technical details that would help someone facing similar issues
- Reference file paths, error messages, or code patterns where relevant
- Keep entries concise but complete

## Steps

1. Run `git log --oneline -10` to see recent commits
2. Ask the user which commits/work to document (or use the most recent)
3. Discuss with the user what lessons they learned
4. Read the current `docs/lessons/LESSONS.md` to match the style
5. Add the new lessons section at the top (after the header, before existing entries)
6. Commit the changes with message: `docs: add [feature/sprint] lessons learned`

Start by asking what work should be documented.
