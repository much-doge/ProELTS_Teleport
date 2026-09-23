# Existing implementation review

Research date: 23 September 2026. Scope: public documentation and selected source inspection, not execution, a full security audit, or proof of SEB compatibility. No third-party implementation was installed or copied into Teleport.

## Conclusion

Similar work exists. The reviewed examples divide into play-once interfaces and resumable learning activities. None reviewed establishes the complete Teleport contract: existing Moodle quiz/Cloze content, attempt-specific no-skip recovery, complete preload, minimal server load, and actual SEB validation. Retain the small dedicated plugin proposal, informed by these patterns. This is a bounded search finding, not a claim that no suitable product exists anywhere.

## 1. Poodll / Video Easy — closest restricted-player precedent

Poodll officially documents a Once Player JS and activity-level player selection. Its filter primarily handles media links, not authored HTML5 audio tags, so adoption would require changing the current embedding contract.

- https://support.poodll.com/support/solutions/articles/19000077074-creating-an-audio-player
- https://support.poodll.com/support/solutions/articles/19000077075-poodll-player-overview

Inspected Video Easy commit `9f349c091393301390a4048d131360e6afabb0e5`, preset `presets/onceplayerjs.txt`:
https://github.com/justinhunt/moodle-filter_videoeasy/blob/9f349c091393301390a4048d131360e6afabb0e5/presets/onceplayerjs.txt

The preset supplies one play button, defaults pause off, and refuses button playback after the media element reports ended. It contains no local/server progress persistence or quiz-attempt identity. Reload recovery is therefore not implemented by this preset. Its alternate markup contains native controls, its volume-slider code is commented out, and it requests external Font Awesome CSS. It includes an iOS/HTTPS-only Blob download workaround, not a universal full-preload gate.

Assessment: useful UI precedent, insufficient recovery model. These source findings apply to the inspected Video Easy preset, not every current Poodll product. Do not infer current commercial licensing or compatibility from this legacy template.

## 2. HLS Player — clearest inspected resume implementation

Repository: https://github.com/kackey621/moodle-mod_hlsplayer
Inspected commit: `d2ebc82ee0ac07dc8945b68f280dac1ee54dfafb`.

Client:
https://github.com/kackey621/moodle-mod_hlsplayer/blob/d2ebc82ee0ac07dc8945b68f280dac1ee54dfafb/hlsplayer/amd/src/player.js

Server:
https://github.com/kackey621/moodle-mod_hlsplayer/blob/d2ebc82ee0ac07dc8945b68f280dac1ee54dfafb/hlsplayer/classes/external.php

The client resumes from lastPosition, distinguishes last position from maximum viewed position, saves on timeupdate at ten-second intervals and on pause/end, and restricts forward seeking beyond viewed progress. Backward seeking remains available. The inspected module shows no local recovery queue or handling of failed save promises.

The server validates Moodle context and view capability, keys state by user/activity, and accepts integer client progress/percentage/position. The inspected endpoint has no progression plausibility check or revision-based stale-write rejection. Its last-position update could be overwritten by an older request. These are gaps relative to an exam policy, not a complete vulnerability assessment.

The version file declares Moodle 4.5 minimum, MATURITY_ALPHA, release v0.1.0. The project targets an HLS video activity, not MP3 inside a quiz. GPL-3.0 licensing is declared; preserve licensing obligations if code reuse is later chosen.

Assessment: borrow the conceptual distinction between last playback position and maximum progress, plus Moodle AJAX integration. Do not install it as a replacement for the Listening quiz or copy its trust/concurrency model unchanged. Ten-second saves imply roughly ten requests/second for 100 active candidates, before other quiz traffic; this arithmetic is not a hosting benchmark.

## 3. Video Time — maintained product candidate, different scope

https://marketplace.moodle.com/plugins/mod_videotime
https://github.com/bdecentgmbh/moodle-mod_videotime/releases

The listing describes video/audio as a separate activity and places tracking/resume in the Pro add-on. Published releases document fixes involving resume and prevention of fast-forwarding. This is useful evidence that combining these behaviours needs regression testing.

Assessment: worth evaluating for ordinary media learning activities. Reviewed documentation does not establish attempt-scoped recovery inside an existing Cloze quiz, complete preload, or the Teleport no-pause contract. Pro implementation and runtime behaviour were not audited. No pricing assumption made.

## 4. Play Audio Once for WordPress — simpler lockout model

https://wordpress.org/plugins/play-audio-once/

The maintainer describes recording the played state in browser-session storage when playback starts, preventing another play and seeking. This is not documented as a saved-position recovery model, nor is it Moodle-aware.

Assessment: useful illustration of why a played flag alone is insufficient. Teleport must distinguish not started, in progress, interrupted, and finished; starting must not consume the student's ability to finish after a failure.

## 5. PlayOnly / exam community request — corroboration only

A Canvas user describes the same requirement: one playback without pause or rewind, and difficulty embedding executable player code into quiz content:
https://community.instructure.com/en/discussion/comment/590840

Their linked repository, https://github.com/tvandervossen/playonly, could not be retrieved in this review; the GitHub main-tree request returned 404. Do not classify it as abandoned or recommend reuse without recovering its source and license. The discussion is an implementation lead, not technical proof.

## Implications for Teleport

- Separate UI restriction from durable attempt state; an ended flag does not survive page recreation.
- Save actual playback position, never derive it from elapsed quiz time under the confirmed no-skip policy.
- Local checkpoints plus throttled server writes need explicit reconciliation and stale-request handling.
- Scope records to quiz attempt and media revision, not simply user/course/activity.
- Preloading and offline continuity remain an unproven spike requirement; none of the reviewed material verifies our complete-preload contract.
- Avoid external runtime UI dependencies and unrestricted fallback players.
- Keep existing questions and Moodle core intact rather than migrating to a new media activity.
- Require live compatibility tests; declared minimum versions and marketplace support are not SEB or production verification.

The prior delivery estimate remains provisional. Finding small players does not remove the recovery, Moodle integration, concurrency, and SEB work that dominates the estimate.
