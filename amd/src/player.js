/**
 * Video player module for mod_completiontimed.
 *
 * @module     mod_completiontimed/player
 */

/* global YT */

define(['jquery', 'core/config'], function($, config) {

    /**
     * Helper to set up the MCQ popup logic with AJAX grading.
     *
     * @param {Number} cmid Course module ID.
     * @param {Object} player The video player instance (HTML5 or YT).
     * @param {Boolean} isYouTube Whether it's a YouTube player.
     */
    function setupMCQModal(cmid, player, isYouTube) {
        var submitBtn = document.getElementById('btn-mcq-submit');
        var radios = document.querySelectorAll('input[name="mcq_answer"]');
        var overlay = document.getElementById('mcq-overlay');
        var controls = document.getElementById('custom-controls');
        var feedback = document.getElementById('mcq-feedback-container');

        if (!submitBtn || !overlay) {
            return;
        }

        radios.forEach(function(radio) {
            radio.addEventListener('change', function() {
                submitBtn.removeAttribute('disabled');
            });
        });

        submitBtn.addEventListener('click', function() {
            var selectedAnswer = document.querySelector('input[name="mcq_answer"]:checked');
            if (!selectedAnswer) {
                return;
            }

            // Disable UI while grading
            submitBtn.setAttribute('disabled', 'disabled');
            submitBtn.innerText = 'Checking...';
            radios.forEach(function(r) {
                r.setAttribute('disabled', 'disabled');
            });

            // Send to server
            $.post(config.wwwroot + '/mod/completiontimed/save_attempt.php', {
                cmid: cmid,
                answer: selectedAnswer.value,
                sesskey: config.sesskey
            }).done(function(response) {
                var data = JSON.parse(response);
                feedback.style.display = 'block';

                if (data.correct) {
                    feedback.style.color = '#28a745'; // Green
                    feedback.innerText = 'Correct! Resuming video...';
                } else {
                    feedback.style.color = '#dc3545'; // Red
                    feedback.innerText = 'Incorrect! Resuming video...';
                }

                // Wait 2.5 seconds, hide modal, and play
                setTimeout(function() {
                    overlay.style.display = 'none';
                    controls.style.display = 'flex';
                    if (isYouTube) {
                        player.playVideo();
                    } else {
                        player.play();
                    }
                }, 2500);
            });
        });
    }

    /**
     * Helper to show the MCQ Overlay.
     */
    function showMCQModal() {
        var overlay = document.getElementById('mcq-overlay');
        var controls = document.getElementById('custom-controls');
        if (overlay) {
            overlay.style.display = 'flex';
            controls.style.display = 'none';
        }
    }

    return {
        /**
         * Initialize the HTML5 Local Video Player.
         *
         * @param {Number} cmid The course module ID.
         * @param {String} storageKey The key used to save progress.
         * @param {Object} mcqData Details about the MCQ.
         */
        initLocal: function(cmid, storageKey, mcqData) {
            var player = document.getElementById('custom-html5-player');
            var progressInterval;
            var mcqShown = false;

            if (!player) {
                return;
            }

            setupMCQModal(cmid, player, false);

            /**
             * Handles the logic once video metadata is available.
             */
            function handleMetadataLoaded() {
                var duration = Math.floor(player.duration);
                document.dispatchEvent(new CustomEvent('yt-ready', {detail: {duration: duration}}));
                var savedTime = localStorage.getItem(storageKey);
                if (savedTime !== null && savedTime > 0) {
                    player.currentTime = parseFloat(savedTime);
                    if (mcqData.enabled && player.currentTime >= mcqData.time) {
                        mcqShown = true;
                    }
                }
            }

            if (player.readyState >= 1) {
                handleMetadataLoaded();
            } else {
                player.addEventListener('loadedmetadata', handleMetadataLoaded);
            }

            document.getElementById('btn-vid-play').addEventListener('click', function() {
                player.play();
            });
            document.getElementById('btn-vid-pause').addEventListener('click', function() {
                player.pause();
            });

            player.addEventListener('play', function() {
                document.dispatchEvent(new Event('yt-playing'));
                progressInterval = setInterval(function() {
                    var current = player.currentTime;
                    localStorage.setItem(storageKey, current);
                    if (mcqData.enabled && !mcqShown && current >= mcqData.time) {
                        player.pause();
                        mcqShown = true;
                        showMCQModal();
                    }
                }, 250);
            });

            player.addEventListener('pause', function() {
                document.dispatchEvent(new Event('yt-paused'));
                clearInterval(progressInterval);
            });
            player.addEventListener('ended', function() {
                document.dispatchEvent(new Event('yt-paused'));
                clearInterval(progressInterval);
            });
        },

        /**
         * Initialize the YouTube Iframe Player.
         *
         * @param {Number} cmid The course module ID.
         * @param {String} youtubeid The parsed YouTube video ID.
         * @param {String} storageKey The key used to save progress.
         * @param {Object} mcqData Details about the MCQ.
         */
        initYouTube: function(cmid, youtubeid, storageKey, mcqData) {
            var player;
            var progressInterval;
            var mcqShown = false;

            /**
             * Sets up the YouTube player instance.
             */
            function setupPlayer() {
                player = new YT.Player('custom-yt-player', {
                    videoId: youtubeid,
                    playerVars: {'controls': 0, 'disablekb': 1, 'rel': 0, 'modestbranding': 1, 'playsinline': 1},
                    events: {'onReady': onPlayerReady, 'onStateChange': onPlayerStateChange}
                });
            }

            /**
             * Triggered when the YouTube player is fully loaded.
             */
            function onPlayerReady() {
                setupMCQModal(cmid, player, true);
                var duration = Math.floor(player.getDuration());
                document.dispatchEvent(new CustomEvent('yt-ready', {detail: {duration: duration}}));
                var savedTime = localStorage.getItem(storageKey);
                if (savedTime !== null && savedTime > 0) {
                    player.seekTo(parseFloat(savedTime));
                    if (mcqData.enabled && parseFloat(savedTime) >= mcqData.time) {
                        mcqShown = true;
                    }
                }
                document.getElementById('btn-yt-play').addEventListener('click', function() {
                    player.playVideo();
                });
                document.getElementById('btn-yt-pause').addEventListener('click', function() {
                    player.pauseVideo();
                });
            }

            /**
             * Triggered when the YouTube player changes state.
             *
             * @param {Object} event The YouTube player state change event.
             */
            function onPlayerStateChange(event) {
                if (event.data === YT.PlayerState.PLAYING) {
                    document.dispatchEvent(new Event('yt-playing'));
                    progressInterval = setInterval(function() {
                        var current = player.getCurrentTime();
                        localStorage.setItem(storageKey, current);
                        if (mcqData.enabled && !mcqShown && current >= mcqData.time) {
                            player.pauseVideo();
                            mcqShown = true;
                            showMCQModal();
                        }
                    }, 250);
                } else {
                    document.dispatchEvent(new Event('yt-paused'));
                    clearInterval(progressInterval);
                }
            }

            if (typeof YT === 'undefined' || typeof YT.Player === 'undefined') {
                var tag = document.createElement('script');
                tag.src = 'https://www.youtube.com/iframe_api';
                var firstScriptTag = document.getElementsByTagName('script')[0];
                firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
                window.onYouTubeIframeAPIReady = function() {
                    setupPlayer();
                };
            } else {
                setupPlayer();
            }
        }
    };
});