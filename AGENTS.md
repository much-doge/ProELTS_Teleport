# Project rules

- Implementation is authorized. Production deployment remains a separate release step.
- Keep Moodle upgradeable: no core edits, theme patches, sanitizer bypasses, or inline executable question HTML.
- Use supported Moodle plugin APIs. Inspect the exact deployed version and rendered player before selecting integration points.
- Moodle owns attempt timing, responses, autosave, grading, and submission. Do not write directly to those core records.
- Scope behaviour to configured Listening quizzes and explicit authored HTML markers.
- Preserve Cloze answer definitions, marks, and question structure when changing audio markup.
- Treat interruption recovery and accommodation rules as explicit policy decisions, not inferred requirements.
- Keep secrets, participant information, answer keys, production exports, and private media out of Git.
- Production changes require inspection, scoped backups outside the web root, rollback instructions, verification, and a sanitized append-only production log.
- Test on the actual deployed Windows SEB clients before claiming compatibility.
- Commit each coherent change atomically using `type: short description`. Validate first, inspect the staged diff, and report the commit hash and working-tree status.
- Keep estimates conditional on inspection findings. Distinguish source-complete, deployed, and production-verified.
