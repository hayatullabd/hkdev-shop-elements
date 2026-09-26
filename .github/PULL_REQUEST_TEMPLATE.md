## Summary

- What changed and why?
- Scope of impact (core/admin/widgets/docs/CI).

## Architecture / Migration Impact

- [ ] Uses canonical namespaces (`Includes\Core`, `Includes\Admin`, canonical widget names)
- [ ] Does not introduce new legacy runtime references
- [ ] Updates migration docs if architecture behavior changed

Notes:

<!-- Add legacy->canonical mapping notes if relevant -->

## Validation

- [ ] Ran migration audit: `php scripts/migration-audit.php`
- [ ] Ran strict migration audit: `php scripts/migration-audit.php --strict`
- [ ] Ran smoke test (when PHP CLI available): `php scripts/boot-smoke-test.php`

## Release Readiness

- [ ] If release-related, followed `docs/RELEASE-CHECKLIST.md`
- [ ] If release-related, notes align with `.github/RELEASE_NOTES_TEMPLATE.md`

## Screenshots / Output (if needed)

<!-- Paste relevant screenshots, logs, or command outputs -->

## Checklist

- [ ] Backward compatibility considered
- [ ] No sensitive data added
- [ ] Documentation updated where needed
