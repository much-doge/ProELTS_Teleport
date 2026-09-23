# ProELTS Teleport

An independent Moodle plugin project for controlled Listening playback in ProELTS quizzes.

Status: **planning only — no plugin implementation or production deployment**.

Candidates enter the quiz normally, preload audio silently, and click Play to begin. Moodle retains ownership of the quiz timer, answers, autosave, grading, and submission. Teleport provides audio controls and attempt-specific playback state without changing Moodle core. Returning resumes from saved playback progress without skipping unheard audio; the Moodle timer continues independently. Conservative checkpoint recovery may repeat a short segment after a failure.

Read [PLAN.md](PLAN.md) for design, delivery milestones, estimates, risks, and decisions still required. Read [AGENTS.md](AGENTS.md) before making changes.

This is a bounded development project. ProELTS Workflows remains the operational coordination repository; MDL_Practice remains the package/source cabinet, and ProELTS Glow remains the Reading highlighter project. This plan does not move or modify those repositories.
