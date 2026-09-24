# ProELTS Teleport production changelog

This append-only log excludes credentials, participant information, answer content, and private media access material.

## 24 September 2026 — CMID 115 alpha deployment

### Scope

- Moodle 5.0.3, quiz activity CMID 115 (quiz instance 107).
- Installed `local_proelts_teleport` 0.1.1-alpha, version `2026092400`.
- Configured only CMID 115 with media revision `109-listening-v001`, duration `1739496` ms, wrapper selector `.proelts-listening`, and a 30-second server checkpoint interval.
- Patched only question ID 49286's audio wrapper. All 40 Cloze tokens were preserved.

### Change sequence

1. Installed 0.1.0-alpha while disabled and applied the reversible question markup patch.
2. Moodle rendering inspection showed that its media filter strips the custom data attribute and wraps the authored audio in VideoJS markup.
3. Disabled the plugin and restored the exact original question text before any candidate attempt used Teleport.
4. Built and staged 0.1.1-alpha, which treats administrator configuration as authoritative and replaces the generated media wrapper at runtime.
5. Reapplied the reversible question markup patch while disabled, verified the database and rendered output, then enabled Teleport only for CMID 115.

### Verification evidence

- Release ZIP SHA-256: `9641381fd1e6fe074322758ddde1218623dec1952bc9d54ebd3cbf831b563fc0`.
- Original question-text SHA-256: `70db543326498fe28d3a97e627a438b3512ddb6ac41b0c3fb037433b4e73a987`.
- Patched question-text SHA-256: `a000c4a108c5b6cad5527ffe7050c9faaf56bb3b0adb910bf659153a7c79011f`.
- Exact reverse transformation reproduced the original hash.
- Production PHP lint passed for every packaged PHP file.
- Moodle upgrade completed successfully and a second run reported no upgrade required.
- The plugin table exists, both AJAX functions are registered, and the JavaScript asset returned HTTP 200 from Moodle's configured origin.
- Post-activation audit showed plugin enabled, installed version `2026092400`, zero Teleport session rows, and zero active attempts for quiz 107.
- Rendered content retained one authored audio marker and one audio element inside Moodle's generated media wrapper. Absence of the stripped data attribute matches the 0.1.1 compatibility design.

### Private rollback material

- A byte-exact original question-text backup is stored outside the web root at `/home/ulbcedxs/.proelts-teleport-cmid115-q49286-pre-20260924.html`, mode `0600`, SHA-256 matching the original question text.
- The superseded 0.1.0 plugin directory is stored outside the web root at `/home/ulbcedxs/.proelts_teleport_backup_20260924_v010`.
- The verified 0.1.1 release archive is stored outside the web root at `/home/ulbcedxs/.proelts_teleport_20260924_v011.zip`.
- Routine rollback is to disable the plugin, confirm there are no active attempts, restore the exact question text, and purge Moodle caches. Do not uninstall the plugin as routine rollback because uninstall may remove plugin-owned recovery records.

### Remaining release gates

Deployment and server-side production checks are complete. No candidate attempt was created during deployment. The end-to-end candidate flow, interruption recovery on the live site, shared-host load under concurrency, and the actual Windows SEB clients remain unverified. Do not describe the alpha as exam-ready until those gates pass.

## 24 September 2026 — VideoJS lifecycle correction

### Incident and mitigation

- A live test exposed VideoJS's `The element or ID supplied is not valid` error before controlled playback began.
- Teleport was disabled immediately and Moodle caches were purged. The question markup remained patched and valid; no Teleport session row had been created.
- Inspection of Moodle 5.0.3's deployed `media_videojs/loader` showed that Moodle initializes the generated audio player asynchronously. Teleport 0.1.1 removed the authored element before that initializer used its generated ID.
- Teleport 0.1.2 waits for Moodle to register the VideoJS player, disposes it through VideoJS's supported lifecycle, and only then replaces the generated wrapper with the controlled audio element.

### Validation and deployment

- Added a browser regression that delays VideoJS registration and fails unless Teleport waits, disposes the player, removes the wrapper, completes controlled playback, and records completion. The regression passed in headless Chromium.
- Release ZIP SHA-256: `dcbcc7eb3cf48a4e34eb0bab2da5ad984cea1b92e11b2345f11544839826f289`.
- Production AMD asset SHA-256 matched the tested local build: `0d3aa0b347265ca48078a536db0d0a6281d246b66e03ed49d9221d4f094c6774`.
- Production PHP lint passed for every packaged PHP file.
- Deployment waited until the reported test attempt was closed and the active-attempt count was zero.
- Moodle upgraded successfully to plugin version `2026092401`; a second upgrade check reported no upgrade required.
- The production asset returned HTTP 200. Post-activation audit showed enabled state `1`, CMID allowlist `115`, two registered AJAX functions, zero active attempts, zero Teleport session rows, and all 40 Cloze tokens intact.
- The superseded 0.1.1 plugin directory is stored outside the web root at `/home/ulbcedxs/.proelts_teleport_backup_20260924_v011`.

The server-side defect is corrected and deployed. A fresh authenticated candidate attempt is still required to verify the complete live browser flow; actual Windows SEB validation remains a separate release gate.
