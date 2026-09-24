# ProELTS Teleport

An independent Moodle plugin project for controlled Listening playback in ProELTS quizzes.

Status: **0.1.3-alpha deployed to CMID 115 and server-verified; candidate-flow and Windows SEB verification pending**.

Candidates enter the quiz normally, preload audio silently, and click Play to begin. Moodle retains ownership of the quiz timer, answers, autosave, grading, and submission. Teleport provides audio controls and attempt-specific playback state without changing Moodle core. Returning resumes from saved playback progress without skipping unheard audio; the Moodle timer continues independently. Conservative checkpoint recovery may repeat a short segment after a failure.

Read [PLAN.md](PLAN.md) for design, delivery milestones, estimates, risks, and decisions still required. Read [AGENTS.md](AGENTS.md) before making changes.

Read [PRODUCTION_CHANGELOG.md](PRODUCTION_CHANGELOG.md) for the sanitized deployment record, verification evidence, and rollback boundaries.

This is a bounded development project. ProELTS Workflows remains the operational coordination repository; MDL_Practice remains the package/source cabinet, and ProELTS Glow remains the Reading highlighter project. This plan does not move or modify those repositories.

See [RESEARCH.md](RESEARCH.md) for the review of existing players, source findings, and reuse assessment.

## Current alpha behaviour

Teleport activates only for an enabled, allowlisted live quiz attempt owned by the current user. Moodle renders the authored element with `preload="none"`; after the page is ready, Teleport creates its controlled element with `preload="auto"`. It validates the decoded duration and enables Play after three minutes of continuous audio are buffered while the remainder keeps loading in the background. It replaces native controls with Play and volume, fixes playback at normal speed, prevents ordinary seeking and pausing, and saves no-skip recovery checkpoints locally and to one plugin-owned Moodle row per attempt.

Moodle remains responsible for the attempt timer, answers, autosave, grading, and submission. Waiting for audio readiness and time spent away continue to consume Moodle quiz time. Returning resumes from the greatest valid saved playback position; elapsed time away never advances the audio.

## Required authored HTML

Replace the current native-control wrapper with ordinary, non-executable markup. Preserve the existing source URL and surrounding question content:

```html
<div class="proelts-listening" data-proelts-media-id="109-listening-v001">
  <audio class="proelts-listening-audio" preload="none">
    <source src="https://archive.najala.org/file/najala-dumpster/ielts-package-cabinet/media/109/listening/109--listening--volume-4-test-1--audio--v001--b4fd8495ac3c.mp3"
            type="audio/mpeg">
  </audio>
  <noscript>Audio requires JavaScript. Contact the invigilator.</noscript>
</div>
```

Do not include `controls`, inline JavaScript, or event handlers. The explicit classes are the stable authoring contract. Moodle may strip the `data-proelts-media-id` attribute while cleaning content; Teleport therefore treats the administrator's CMID media definition as authoritative. If the attribute survives, it must match. Teleport removes Moodle's generated media-player wrapper before creating its controlled interface.

## Configuration for the supplied recording

The local cabinet copy measures 41,756,412 bytes and 1,739.495533 seconds. Configure:

```text
Allowed quiz activity IDs: TARGET_CMID
Controlled media definitions: TARGET_CMID|109-listening-v001|1739496
Listening wrapper selector: .proelts-listening
Server checkpoint interval: 30
```

Do not enable the plugin until the live Listening CMID and exact rendered HTML have been inspected and the HTML change has an exact private backup.

## Media-origin preload behaviour

The current `archive.najala.org` response supplies its content length and byte ranges but, when checked with the Moodle origin on 23 September 2026, did not include `Access-Control-Allow-Origin`. Browser JavaScript therefore cannot perform Teleport's preferred full-file Fetch into an in-memory Blob from `https://ulb.center`.

Teleport falls back to native media loading and keeps Play disabled until the browser reports at least three minutes of continuous buffered coverage. The browser continues loading the remainder during playback. Teleport still fails closed if that initial cushion is unavailable, decoding fails, the duration mismatches, or the ten-minute preparation timeout expires. CORS for `https://ulb.center` remains preferable because a complete Blob is a stronger readiness guarantee. Do not proxy the 41.8 MB file through PHP.

## Build and validation

`amd/src/player.js` is the source and `amd/build/player.min.js` is the committed production asset. Rebuild with Terser and confirm both files parse. Available checks:

```text
node --check amd/src/player.js
node --check amd/build/player.min.js
xmllint --noout db/install.xml
```

`tests/configuration_test.php` is a Moodle PHPUnit test. `tests/player-browser.html` is a local browser smoke test for full preload, one start, natural completion, completion checkpoint, and absence of native controls. PHP lint and Moodle PHPUnit still require a Moodle development runtime.
