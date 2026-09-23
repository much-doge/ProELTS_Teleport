// This file is part of Moodle - http://moodle.org/.

/**
 * Controlled, recoverable Listening playback.
 *
 * @module local_proelts_teleport/player
 */
define(['core/ajax', 'core/notification'], function(Ajax, Notification) {
    const LOCAL_SAVE_INTERVAL_MS = 1000;
    const DURATION_TOLERANCE_MS = 2500;

    const text = {
        loading: 'Loading audio…',
        ready: 'Audio ready',
        starting: 'Starting…',
        playing: 'Audio playing',
        resume: 'Resume listening',
        finished: 'Recording finished',
        unavailable: 'Audio is unavailable. Contact the invigilator.',
    };

    const storageFor = (key) => {
        for (const storage of [window.localStorage, window.sessionStorage]) {
            try {
                storage.setItem(key + ':probe', '1');
                storage.removeItem(key + ':probe');
                return storage;
            } catch (error) {
                // Try the next browser-owned store.
            }
        }
        return null;
    };

    const request = (methodname, args) => Ajax.call([{methodname, args}])[0];

    const sourceUrl = (audio) => {
        const source = audio.querySelector('source');
        return source ? source.src : audio.src;
    };

    const waitForMetadata = (audio) => new Promise((resolve, reject) => {
        if (audio.readyState >= HTMLMediaElement.HAVE_METADATA) {
            resolve();
            return;
        }
        const loaded = () => {
            cleanup();
            resolve();
        };
        const failed = () => {
            cleanup();
            reject(new Error('Audio metadata could not be decoded'));
        };
        const cleanup = () => {
            audio.removeEventListener('loadedmetadata', loaded);
            audio.removeEventListener('error', failed);
        };
        audio.addEventListener('loadedmetadata', loaded);
        audio.addEventListener('error', failed);
    });

    const waitForFullBuffer = (audio, onProgress) => new Promise((resolve, reject) => {
        const startedAt = Date.now();
        const inspect = () => {
            let covered = 0;
            for (let index = 0; index < audio.buffered.length; index++) {
                if (audio.buffered.start(index) <= covered + 0.25) {
                    covered = Math.max(covered, audio.buffered.end(index));
                }
            }
            const duration = Number.isFinite(audio.duration) ? audio.duration : 0;
            onProgress(covered, duration);
            if (duration > 0 && covered >= duration - 0.5) {
                cleanup();
                resolve();
            } else if (Date.now() - startedAt > 600000) {
                cleanup();
                reject(new Error('Audio did not fully buffer within ten minutes'));
            }
        };
        const failed = () => {
            cleanup();
            reject(new Error('Audio buffering failed'));
        };
        const cleanup = () => {
            window.clearInterval(timer);
            audio.removeEventListener('progress', inspect);
            audio.removeEventListener('error', failed);
        };
        const timer = window.setInterval(inspect, 250);
        audio.addEventListener('progress', inspect);
        audio.addEventListener('error', failed);
        inspect();
    });

    const downloadAudio = async(url, onProgress) => {
        const response = await fetch(url, {credentials: 'same-origin', cache: 'force-cache'});
        if (!response.ok) {
            throw new Error('Audio download returned HTTP ' + response.status);
        }
        const total = Number(response.headers.get('content-length')) || 0;
        if (!response.body || !response.body.getReader) {
            const blob = await response.blob();
            onProgress(blob.size, total || blob.size);
            return blob;
        }
        const reader = response.body.getReader();
        const chunks = [];
        let received = 0;
        while (true) {
            const result = await reader.read();
            if (result.done) {
                break;
            }
            chunks.push(result.value);
            received += result.value.byteLength;
            onProgress(received, total);
        }
        return new Blob(chunks, {type: response.headers.get('content-type') || 'audio/mpeg'});
    };

    const buildUi = (wrapper) => {
        const panel = document.createElement('div');
        panel.className = 'proelts-teleport-player';
        panel.innerHTML =
            '<p class="proelts-teleport-status" role="status" aria-live="polite"></p>' +
            '<progress class="proelts-teleport-load" max="1" value="0"></progress>' +
            '<div class="proelts-teleport-controls">' +
            '<button type="button" class="btn btn-primary proelts-teleport-play" disabled>Play</button>' +
            '<label class="proelts-teleport-volume">Volume ' +
            '<input type="range" min="0" max="1" step="0.05" value="1" aria-label="Volume"></label>' +
            '</div>';
        wrapper.appendChild(panel);
        return {
            status: panel.querySelector('.proelts-teleport-status'),
            load: panel.querySelector('.proelts-teleport-load'),
            play: panel.querySelector('.proelts-teleport-play'),
            volume: panel.querySelector('input[type="range"]'),
        };
    };

    const initialisePlayer = async(config, wrapper) => {
        const audio = wrapper.querySelector('audio.proelts-listening-audio');
        const authoredMediaId = wrapper.dataset.proeltsMediaId || '';
        if (!audio || authoredMediaId !== config.mediaid) {
            throw new Error('Controlled audio marker or media revision does not match configuration');
        }

        audio.controls = false;
        audio.removeAttribute('controls');
        audio.setAttribute('controlsList', 'nodownload noplaybackrate nofullscreen');
        audio.disableRemotePlayback = true;
        audio.playbackRate = 1;
        audio.defaultPlaybackRate = 1;

        const ui = buildUi(wrapper);
        ui.status.textContent = text.loading;
        const key = `local_proelts_teleport:${config.cmid}:${config.attemptid}:${config.mediaid}`;
        const storage = storageFor(key);
        let localState = null;
        if (storage) {
            try {
                localState = JSON.parse(storage.getItem(key));
            } catch (error) {
                localState = null;
            }
        }

        const originalUrl = sourceUrl(audio);
        if (!originalUrl) {
            throw new Error('Audio source is missing');
        }
        let objectUrl = null;
        try {
            const blob = await downloadAudio(originalUrl, (received, total) => {
                if (total > 0) {
                    ui.load.value = Math.min(1, received / total);
                    ui.status.textContent = `Loading audio… ${Math.floor((received / total) * 100)}%`;
                }
            });
            objectUrl = URL.createObjectURL(blob);
            audio.querySelectorAll('source').forEach((node) => node.remove());
            audio.src = objectUrl;
            audio.preload = 'auto';
            audio.load();
            await waitForMetadata(audio);
        } catch (fetchError) {
            // Cross-origin media may be playable while its origin blocks Fetch.
            // In that case keep the original source and require full buffered coverage.
            audio.src = originalUrl;
            audio.preload = 'auto';
            audio.load();
            await waitForMetadata(audio);
            await waitForFullBuffer(audio, (covered, duration) => {
                if (duration > 0) {
                    ui.load.value = Math.min(1, covered / duration);
                    ui.status.textContent = `Loading audio… ${Math.floor((covered / duration) * 100)}%`;
                }
            });
        }
        const decodedDuration = Math.round(audio.duration * 1000);
        if (!Number.isFinite(decodedDuration) ||
                Math.abs(decodedDuration - config.durationms) > DURATION_TOLERANCE_MS) {
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
            }
            throw new Error('Decoded audio duration does not match configuration');
        }

        ui.load.value = 1;
        ui.load.hidden = true;
        ui.status.textContent = text.ready;
        ui.play.disabled = false;
        ui.volume.addEventListener('input', () => {
            audio.volume = Number(ui.volume.value);
        });

        let started = false;
        let completed = false;
        let restoring = false;
        let trustedPositionMs = 0;
        let sequence = 0;
        let lastLocalSave = 0;
        let lastServerSave = 0;
        let saveInFlight = false;
        let savePending = false;
        let retryDelayMs = 5000;

        const persistLocal = (force = false) => {
            const now = Date.now();
            if (!storage || (!force && now - lastLocalSave < LOCAL_SAVE_INTERVAL_MS)) {
                return;
            }
            storage.setItem(key, JSON.stringify({
                attemptid: config.attemptid,
                mediaid: config.mediaid,
                positionms: trustedPositionMs,
                sequence,
                completed,
            }));
            lastLocalSave = now;
        };

        const saveServer = async(force = false) => {
            const now = Date.now();
            if (!started || saveInFlight || (!force && now - lastServerSave < config.checkpointseconds * 1000)) {
                savePending = savePending || force;
                return;
            }
            saveInFlight = true;
            savePending = false;
            let failed = false;
            sequence += 1;
            try {
                const state = await request('local_proelts_teleport_checkpoint', {
                    attemptid: config.attemptid,
                    cmid: config.cmid,
                    mediaid: config.mediaid,
                    positionms: trustedPositionMs,
                    sequence,
                    completed,
                });
                sequence = Math.max(sequence, state.sequence);
                lastServerSave = Date.now();
                retryDelayMs = 5000;
                persistLocal(true);
            } catch (error) {
                // Keep the local checkpoint and retry on later progress/significant events.
                failed = true;
                savePending = true;
            } finally {
                saveInFlight = false;
                if (savePending) {
                    const delay = failed ? retryDelayMs : 1000;
                    if (failed) {
                        retryDelayMs = Math.min(60000, retryDelayMs * 2);
                    }
                    window.setTimeout(() => saveServer(true), delay);
                }
            }
        };

        const setPosition = (positionms) => {
            restoring = true;
            trustedPositionMs = Math.max(0, Math.min(config.durationms, Math.floor(positionms)));
            audio.currentTime = trustedPositionMs / 1000;
            restoring = false;
        };

        const play = async() => {
            ui.play.disabled = true;
            ui.status.textContent = started ? text.resume : text.starting;
            try {
                if (!started) {
                    const server = await request('local_proelts_teleport_start', {
                        attemptid: config.attemptid,
                        cmid: config.cmid,
                        mediaid: config.mediaid,
                    });
                    sequence = server.sequence;
                    completed = server.completed;
                    let recover = server.positionms;
                    if (localState && localState.attemptid === config.attemptid &&
                            localState.mediaid === config.mediaid && !localState.completed &&
                            Number.isInteger(localState.positionms)) {
                        recover = Math.max(recover, Math.min(config.durationms, localState.positionms));
                        sequence = Math.max(sequence, Number(localState.sequence) || 0);
                    }
                    started = true;
                    setPosition(recover);
                }
                if (completed) {
                    ui.status.textContent = text.finished;
                    ui.play.hidden = true;
                    return;
                }
                audio.playbackRate = 1;
                await audio.play();
                ui.status.textContent = text.playing;
                ui.play.textContent = text.resume;
                ui.play.hidden = true;
            } catch (error) {
                ui.status.textContent = text.resume;
                ui.play.disabled = false;
                ui.play.hidden = false;
                Notification.exception(error);
            }
        };

        ui.play.addEventListener('click', play);
        audio.addEventListener('timeupdate', () => {
            if (!started || completed || restoring || audio.seeking || audio.paused) {
                return;
            }
            const observed = Math.floor(audio.currentTime * 1000);
            if (observed >= trustedPositionMs && observed <= trustedPositionMs + 2000) {
                trustedPositionMs = observed;
                persistLocal();
                saveServer(false);
            }
        });
        audio.addEventListener('seeking', () => {
            if (!restoring && Math.abs(audio.currentTime * 1000 - trustedPositionMs) > 500) {
                setPosition(trustedPositionMs);
            }
        });
        audio.addEventListener('ratechange', () => {
            if (audio.playbackRate !== 1) {
                audio.playbackRate = 1;
            }
        });
        audio.addEventListener('pause', () => {
            if (!started || completed || audio.ended) {
                return;
            }
            persistLocal(true);
            saveServer(true);
            audio.play().catch(() => {
                ui.status.textContent = text.resume;
                ui.play.disabled = false;
                ui.play.hidden = false;
            });
        });
        audio.addEventListener('ended', () => {
            trustedPositionMs = config.durationms;
            completed = true;
            ui.status.textContent = text.finished;
            ui.play.hidden = true;
            persistLocal(true);
            saveServer(true);
        });
        window.addEventListener('pagehide', () => {
            persistLocal(true);
            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
            }
        });
    };

    return {
        init: function(config) {
            const wrappers = document.querySelectorAll(config.selector);
            if (wrappers.length !== 1) {
                wrappers.forEach((wrapper) => {
                    wrapper.querySelectorAll('audio').forEach((audio) => {
                        audio.pause();
                        audio.removeAttribute('controls');
                        audio.hidden = true;
                    });
                    const status = document.createElement('p');
                    status.className = 'proelts-teleport-status alert alert-danger';
                    status.setAttribute('role', 'alert');
                    status.textContent = text.unavailable;
                    wrapper.appendChild(status);
                });
                return;
            }
            wrappers.forEach((wrapper) => {
                initialisePlayer(config, wrapper).catch((error) => {
                    const status = wrapper.querySelector('.proelts-teleport-status') || document.createElement('p');
                    status.className = 'proelts-teleport-status alert alert-danger';
                    status.setAttribute('role', 'alert');
                    status.textContent = text.unavailable;
                    if (!status.parentNode) {
                        wrapper.appendChild(status);
                    }
                    wrapper.querySelectorAll('audio').forEach((audio) => {
                        audio.pause();
                        audio.removeAttribute('controls');
                        audio.hidden = true;
                    });
                    window.console.error('ProELTS Teleport initialization failed', error);
                });
            });
        },
    };
});
