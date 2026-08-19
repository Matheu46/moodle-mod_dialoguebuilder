/**
 * Player controls JS module for dialoguebuilder.
 *
 * @module     mod_dialoguebuilder/player_controls
 * @copyright  2026 Matheus Mathias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['mod_dialoguebuilder/html2canvas', 'core/notification'], function(html2canvas, notification) {

    return {
        /**
         * Initialize the player controls.
         *
         * @param {String} chatId The chat container ID.
         */
        init: function(chatId) {
            var target = document.getElementById(chatId);
            var controls = document.querySelector('div[data-target="' + chatId + '"]');

            if (!target || !controls) {
                return;
            }

            var playBtn = controls.querySelector('.mod-dialoguebuilder__play-btn');
            var pauseBtn = controls.querySelector('.mod-dialoguebuilder__pause-btn');
            var replayBtn = controls.querySelector('.mod-dialoguebuilder__replay-btn');
            var downloadBtn = controls.querySelector('.mod-dialoguebuilder__download-btn');

            if (!playBtn || !pauseBtn || !replayBtn) {
                return;
            }

            playBtn.addEventListener('click', function() {
                playBtn.disabled = true;
                pauseBtn.disabled = false;
                replayBtn.disabled = false;
                target.dispatchEvent(new CustomEvent('dialogue:play'));
            });

            pauseBtn.addEventListener('click', function() {
                playBtn.disabled = false;
                pauseBtn.disabled = true;
                target.dispatchEvent(new CustomEvent('dialogue:pause'));
            });

            replayBtn.addEventListener('click', function() {
                playBtn.disabled = true;
                pauseBtn.disabled = false;
                target.dispatchEvent(new CustomEvent('dialogue:replay'));
            });

            target.addEventListener('dialogue:ended', function() {
                playBtn.disabled = true;
                pauseBtn.disabled = true;
                replayBtn.disabled = false;
            });

            if (downloadBtn) {
                downloadBtn.addEventListener('click', function() {
                    // Clone the container to show all messages without breaking the current view
                    var clone = target.cloneNode(true);

                    // Force clone styles to be fully visible and rendered
                    clone.style.position = 'absolute';
                    clone.style.left = '-9999px';
                    clone.style.top = '0';
                    clone.style.width = target.offsetWidth + 'px';
                    clone.style.height = 'auto'; // allow expanding
                    clone.style.overflow = 'visible';
                    clone.style.maxHeight = 'none';

                    // Make all messages visible
                    var messages = clone.querySelectorAll('.mod-dialoguebuilder__chat-message');
                    messages.forEach(function(msg) {
                        msg.classList.remove('mod-dialoguebuilder__typing');
                        msg.classList.add('mod-dialoguebuilder__show');
                    });

                    document.body.appendChild(clone);

                    // Temporary change icon to indicate loading
                    var icon = downloadBtn.querySelector('i');
                    var oldClass = icon.className;
                    icon.className = 'fa fa-spinner fa-spin';
                    downloadBtn.disabled = true;

                    // Execute html2canvas
                    html2canvas(clone, {
                        backgroundColor: '#f8f9fa', // ensure background is solid
                        scale: 2 // better quality
                    }).then(function(canvas) {
                        var link = document.createElement('a');
                        link.download = 'dialogue.png';
                        link.href = canvas.toDataURL('image/png');
                        link.click();
                    }).catch(notification.exception).finally(function() {
                        // Restore button and remove clone
                        icon.className = oldClass;
                        downloadBtn.disabled = false;
                        document.body.removeChild(clone);
                    });
                });
            }
        }
    };
});
