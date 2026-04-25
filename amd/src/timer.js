/**
 * Timer module for mod_completiontimed.
 *
 * @module     mod_completiontimed/timer
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/config'], function($, config) {
    return {
        /**
         * Initialize the timer logic.
         *
         * @param {Number} cmid The course module ID.
         * @param {Number} requiredTime The required time in seconds.
         * @param {Number} userid The current user ID.
         * @param {Boolean} hasVideo Whether a YouTube video is configured.
         * @param {Boolean} autoCalc Whether to auto-calculate time based on video length.
         */
        init: function(cmid, requiredTime, userid, hasVideo, autoCalc) {
            var storageKey = 'completiontimed_cm_' + cmid + '_user_' + userid;

            var isVideoPlaying = !hasVideo;
            var timeLeft;
            var interval;

            /**
             * Formats seconds into a MMmin SSsec string.
             *
             * @param {Number} totalSeconds The total seconds.
             * @return {String} Formatted time string.
             */
            function formatTime(totalSeconds) {
                var m = Math.floor(totalSeconds / 60);
                var s = totalSeconds % 60;
                var mStr = (m < 10) ? '0' + m : m;
                var sStr = (s < 10) ? '0' + s : s;
                return mStr + 'min ' + sStr + 'sec';
            }

            /**
             * Starts the countdown interval.
             *
             * @param {Number} duration The time in seconds to count down from.
             */
            function startCountdown(duration) {
                var savedTime = localStorage.getItem(storageKey);
                timeLeft = (savedTime !== null && savedTime > 0) ? parseInt(savedTime, 10) : duration;

                $('#time-left').text(formatTime(timeLeft));

                interval = setInterval(function() {
                    if (!document.hidden && isVideoPlaying) {
                        timeLeft--;

                        $('#time-left').text(formatTime(timeLeft));

                        localStorage.setItem(storageKey, timeLeft);

                        if (timeLeft <= 0) {
                            clearInterval(interval);
                            localStorage.removeItem(storageKey);

                            $('#timer-status').removeClass('alert-info text-muted').addClass('alert-success')
                                .text('Time requirement met! Marking as complete...');

                            $.post(config.wwwroot + '/mod/completiontimed/complete.php', {
                                cmid: cmid,
                                sesskey: config.sesskey
                            }).done(function() {
                                $('#timer-status').text('Activity complete! You may now proceed.');
                                window.location.reload();
                            });
                        }
                    }
                }, 1000);
            }

            document.addEventListener('yt-playing', function() {
                isVideoPlaying = true;
                $('#timer-status').removeClass('text-muted').addClass('alert-info');
            });

            document.addEventListener('yt-paused', function() {
                isVideoPlaying = false;
                $('#timer-status').removeClass('alert-info').addClass('text-muted');
            });

            if (autoCalc && hasVideo) {
                document.addEventListener('yt-ready', function(e) {
                    startCountdown(e.detail.duration);
                });
            } else {
                startCountdown(requiredTime);
            }

            document.addEventListener('visibilitychange', function() {
                if (document.hidden || !isVideoPlaying) {
                    $('#timer-status').addClass('text-muted');
                } else {
                    $('#timer-status').removeClass('text-muted');
                }
            });
        }
    };
});