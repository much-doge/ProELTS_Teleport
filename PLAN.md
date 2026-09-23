# ProELTS Teleport — design and delivery plan

Date: 23 September 2026
Status: proposal; planning only

## Outcome and scope

Provide a lightweight, controlled Listening player for existing Moodle quizzes, while retaining normal Moodle upgrades and independent candidate start times. Modify the quiz's audio HTML and install a separate plugin; preserve question content, Cloze definitions, marks, answers, and other quiz behaviour.

The supplied authoring HTML contains one external MP3, four initially open collapsible sections, and 40 embedded answer fields. It currently requests native audio controls and no preloading. This is source evidence, not verification of the live rendered player or settings. The Reading operations handoff describes Moodle 5.0.3; the Listening target, installed media filters, SEB version, and current Moodle version require live inspection.

## Confirmed requirements

- Candidate enters the quiz password and begins the normal Moodle attempt.
- The existing generous quiz allowance remains; Teleport does not replace it with a fixed IELTS duration.
- Audio loads silently inside the attempt and does not autoplay on first entry.
- Candidate deliberately clicks Play to start the recording.
- No candidate seeking, replay, speed control, or pause control; retain accessible volume adjustment.
- Returning must not skip unheard audio. Resume from saved playback progress; elapsed quiz time never advances the recording. Moodle attempt status/deadline still controls whether listening is allowed.
- Shared hosting must not require a worker, media proxy through PHP, or constant polling.
- Both question HTML and the plugin may change; Moodle core must remain untouched.
- Connection failures, browser crashes, hangs, and unheard sound cannot all be reliably diagnosed automatically.

The earlier proposal to require a headphone check before the attempt and coordinate quiz creation with Play is superseded by the user's chosen normal Moodle start flow. A separate optional sound-check resource may be discussed later.

## Candidate interface

On entry: a compact audio card shows loading/readiness, Play, and volume. There is no seekable timeline. A static status or elapsed-time label may be shown without making it interactive. The questions retain their present layout.

Recommended first-play gate: enable Play after the full recording has been obtained and validated as usable by the browser. Loading time consumes the existing quiz allowance under the chosen flow. Slow connections may therefore still need an invigilator remedy; this is a deliberate consequence to review, not a promise that preloading removes all timing risk.

After Play: show Playing and volume controls. At completion show Recording finished. If the browser requires a gesture after re-entry, show Resume listening; resume from the recovered playback checkpoint, without adding time spent away or loading.

Proposed HTML contract: `.proelts-listening` wrapper, `.proelts-listening-audio` media element, and an accessible fallback message. Keep a stable media revision identifier validated against plugin configuration. No inline JavaScript or event handlers. The plugin supplies buttons and binds to these explicit markers only in configured eligible attempts. No native `controls` fallback in a controlled exam.

## Timing and recovery: confirmed no-skip policy

User decision: returning must not skip unheard audio. This supersedes the earlier wall-clock proposal.

    resume position = last valid saved playback checkpoint

Leaving at approximately 10:00 and returning two minutes later resumes at approximately 10:00, not 12:00. Moodle's quiz timer continues independently. Buffering, recovery loading, and time away must not advance the saved audio position. If preloaded audio continues playing during a network outage, save its actual advancing position locally; loss of network alone does not mean playback stopped.

Use frequent inexpensive browser-local checkpoints of actual playback progress plus infrequent server checkpoints for device-loss recovery. Proposed starting intervals for the spike: local saves around once per second while playback advances, server saves at most once per 30 seconds while advancing, plus bounded significant-event saves. These are tuning targets, not approved recovery precision or guaranteed performance. Lifecycle saves are best effort and cannot be relied on after a crash.

No browser can prove what the student heard. After a crash, an older checkpoint may repeat a short segment. Prefer this conservative overlap to guessing forward and skipping content. After loss of both the device and its unsynced local records, the overlap can be longer, especially during an outage. Do not promise exact cross-device recovery or a fixed overlap bound while offline. If state is missing or conflicting, require supervised recovery rather than silently restarting or jumping forward.

Validate checkpoints against attempt/media identity, playback progression, revision and ordering. Never accept a client offset as unrestricted seek authority. Define local/server reconciliation, stale-write rejection, duplicate-tab/device ownership, and completion consistency in the spike. Browser reports remain advisory, not tamper-proof evidence.

Read Moodle's effective attempt status/deadline through supported APIs, not the visible countdown. Timer awareness means respecting the attempt deadline, not using elapsed time to calculate audio position. A quiz time extension does not rewind audio. If the quiz expires before the recording finishes, normal Moodle rules still apply; the plugin does not grant extra time. Accommodation and supervised recovery rules remain to be defined.

This policy prioritizes continuity: leaving can effectively interrupt audio, even though no Pause button is provided and quiz time continues. Record repeated interruptions for review; do not compensate by skipping content. No automated method can reliably distinguish deliberate exits from all genuine failures.

Playback ends when the recording reaches its end or the attempt ceases to permit listening. Moodle alone enforces submission. Do not automatically add two minutes, shorten the generous allowance, or alter the recording in this release.

The first-play handshake must be tested: obtain server authorization and establish one session atomically, then begin playback promptly. Browser rejection or delayed startup must surface clearly and must not fabricate playback progress. Retries retain the same session and valid checkpoint rather than resetting it.

## Moodle architecture

Provisional component: `local_proelts_teleport`, subject to the integration spike.

- Supported output hooks load compiled AMD JavaScript and CSS only for configured Listening attempts.
- Authenticated Moodle external/AJAX functions validate session protection, attempt ownership, context/capabilities, eligible state, and configured media.
- One plugin-owned session record per attempt/media revision: unique identity, server first-play timestamp for audit, latest accepted playback checkpoint, completion state, revision/ordering fields, and minimal recovery metadata. Atomic uniqueness makes repeated Play requests idempotent.
- Media duration and revision are administrator-controlled configuration, not trusted client values.
- Plugin-owned sparse incident records contain event type, server receipt time, and bounded technical details. Client-reported timestamps/positions are advisory evidence, not proof.
- Implement Moodle privacy support and appropriate deletion/retention behaviour for attempt-linked records. Never copy candidate answers into Teleport records.
- No direct writes to quiz attempts, question-engine tables, answers, grades, or deadlines.
- No changes to Glow. Restrict this feature to explicitly enabled Listening quizzes.

A companion `quizaccess` plugin may be needed if supported local-plugin hooks cannot block new controlled attempts when configuration is invalid. Confirm this before promising a single-plugin package. Avoid brittle DOM interception of Moodle's Start attempt action.

## Preloading and media delivery

`preload="auto"` is a hint, not a readiness guarantee. Prototype complete download into a browser Blob using Fetch, with progress when a trustworthy content length is available, then load that Blob into the audio element. The external origin must permit the necessary cross-origin fetch. Test authentication/URL lifetime, MIME type, file size, integrity/version, decoder readiness, memory use, and SEB behaviour.

If cross-origin fetch is unavailable, choose a supported media-origin configuration or delivery location before implementation. Do not proxy the full MP3 through a new PHP endpoint on shared hosting. If only native buffering is possible, describe its weaker guarantees honestly rather than labelling partial buffering as fully ready.

An in-memory Blob can protect current-page playback from a later network interruption but is lost on page destruction. Browser cache reuse on reload is not guaranteed. Persistent offline storage/service workers are outside initial scope unless the spike proves necessary. On re-entry, loading may consume quiz time, but does not advance the recovered playback position.

## Resource budget and operational behaviour

Design targets, to verify under representative concurrency:

- One state read per page load, preferably supplied in the page bootstrap.
- One atomic first-play write per attempt; retries are idempotent.
- No per-second server requests, continuous polling, cron job, or dedicated service. Infrequent checkpoint writes replace the original start-write-only proposal. At a 30-second interval, 100 active candidates average about 3.3 checkpoint requests/second; jitter and load-test these writes before accepting the budget on shared hosting.
- Local event handling for seek/rate changes and playback stalls; low-frequency checks only where events are insufficient.
- Sparse, deduplicated incident writes with request/payload limits; one optional completion event.
- Audio bytes travel directly from the media origin to browsers.

Local checkpoints support same-browser recovery; server checkpoints provide a conservative fallback after device loss. Neither proves that sound was heard. Duplicate tabs/devices must not race to overwrite progress; prototype session ownership and versioned writes, and test local coordination where supported. Existing exam access/session policy and SEB remain relevant.

Browser-side playback guards discourage ordinary misuse but are not DRM. Do not claim that hiding controls prevents extraction of media delivered to a browser.

## Failure handling and upgrades

Show an accessible unavailable message if initialization fails; do not silently restore an unrestricted player. Because Moodle's timer may already be running, this state needs an invigilator procedure. Stop new controlled attempts through a supported access gate if available; active-attempt recovery remains a separate operational action.

Declare supported Moodle/PHP versions, use plugin install/upgrade mechanisms and plugin-owned schema migrations, ship compiled assets, and test upcoming Moodle upgrades on staging. Compatibility requires maintenance; automatic adaptation is not guaranteed.

Disabling the plugin should leave Moodle healthy and historical attempt data intact. Controlled audio markup will remain unavailable until deliberately restored. Rollback therefore includes both the plugin/configuration and the exact authored audio block. Avoid uninstall as a routine rollback because plugin data may be removed.

## Delivery milestones and effort estimate

Estimates are engineering effort for one implementer after access is available, not elapsed-time guarantees. They exclude waiting for decisions, credentials, the lab, and hosting changes.

| Milestone | Deliverable and acceptance gate | Effort |
| --- | --- | --- |
| 1. Inspect and resolve policy | Listening identifiers/settings, rendered player, media duration/origin behaviour, target SEB versions, no-skip recovery precision and loading policy | 0.5–1 day |
| 2. Compatibility and preload spike | Prove hooks, first-play handshake, media loading, checkpoint persistence/reconciliation, access-gate approach on a non-production fixture | 1–2 days |
| 3. Core plugin | Scoped player, atomic session state, checkpoint recovery, volume/accessibility, own schema/privacy support, compiled build | 2–3 days |
| 4. Hardening and validation | Automated behavioural tests, network/reload cases, realistic concurrency, actual Windows SEB pilot, defect fixes | 1.5–2.5 days |
| 5. Release preparation and pilot | Installable package, operator guide, HTML patch/backup, rollback rehearsal, authorized deployment and evidence | 0.5–1 day |

Expected total: **5.5–9.5 engineering days (roughly 44–76 hours)**. A demonstrable prototype may be available after milestones 1–2; it is not exam-ready. A companion access plugin, persistent offline media, or a full invigilator recovery console could add approximately 1–3 days each and should be separately scoped. This range is provisional after the no-skip decision: checkpoint reconciliation and device-loss testing replace the simpler timestamp-only design. Re-estimate after the spike, including the measured server write load.

## Verification gates

- Two candidates start at different times and retain isolated state.
- Initial entry never autoplays; first Play is idempotent across retries/tabs.
- Mouse/keyboard/media controls cannot provide ordinary pause/seek/rate bypasses in supported clients.
- Reload, page navigation, sleep/wake, expired login, failed initial Play, media stall, and disconnected return follow the agreed policy.
- Return after two minutes away does not add two minutes to the audio offset; buffering and recovery loading likewise never advance it.
- Crash, missing local state, out-of-order writes, duplicate devices, and offline playback use conservative recovery without inferred forward jumps.
- Completed media cannot restart; expired/submitted attempts cannot gain listening access.
- Quiz time extensions and accommodations have documented audio behaviour.
- HTML edits preserve every Cloze token and grading definition exactly.
- Answers, autosave, navigation, timer, grading, and submission pass regression checks.
- Actual deployed SEB versions pass a controlled pilot with an authorized test account.
- Disabled/unconfigured quizzes, Reading, Writing, and reviews are unaffected.
- Network trace confirms the request budget; server checks confirm authorization and attempt isolation.
- Upgrade and rollback rehearsals pass on staging before deployment.

## Decisions required before implementation

1. Tune checkpoint intervals and define acceptable conservative replay after lost state; the no-skip recovery policy is confirmed.
2. Approve complete preload before Play inside the already running quiz, including its loading-time consequence.
3. Define the invigilator remedy for genuine failures, including whether audio continuation/replay or Moodle time adjustment is permitted and who may authorize it.
4. Identify target Listening quizzes and actual SEB clients; inspect their current state before selecting integration points.

## Release and evidence

Deliver source, plugin ZIP(s), reproducible compiled assets, install/configuration guide, exact minimal HTML patch instructions, validation results, recovery procedure, and rollback guide. Record production mutations only when they occur. Keep production backups/private incidents outside Git. Report source-complete, deployed, and production-verified separately.

## References and evidence boundaries

- User-supplied Listening HTML and this discussion establish the requested workflow; do not commit the supplied answer-bearing HTML as a public fixture.
- ProELTS Glow's local operations handoff provides the existing deployment routing, subject to live revalidation.
- Official IELTS format reference: https://ielts.org/take-a-test/test-types/ielts-academic-test/ielts-academic-format-listening
- Official IDP computer-listening reference: https://ielts.idp.com/bangladesh/about/which-test-do-i-take/ielts-on-computer/listening-preparation
- IELTS references inform the one-pass listening goal; this institutional workflow deliberately retains independent starts and the existing generous Moodle allowance.
