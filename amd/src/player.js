/**
 * Player JS module for dialoguebuilder.
 *
 * @module     mod_dialoguebuilder/player
 * @copyright  2026 Matheus Mathias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {

    return {
        /**
         * Initialize the player.
         *
         * @param {String} containerId The chat container ID.
         */
        init: function(containerId) {
            var container = document.getElementById(containerId);
            if (!container) {
                return;
            }

            var messages = container.querySelectorAll('.mod-dialoguebuilder__chat-message');
            var index = 0;
            var currentTimeout = null;
            var isPlaying = false;

            /**
             * Shows the next message in the dialogue.
             */
            function showNextMessage() {
                if (!isPlaying) {
                    return;
                }
                if (index >= messages.length) {
                    isPlaying = false;
                    container.dispatchEvent(new CustomEvent('dialogue:ended'));
                    return;
                }

                var el = messages[index];

                el.classList.add('mod-dialoguebuilder__typing');
                container.scrollTo({top: container.scrollHeight, behavior: 'smooth'});

                currentTimeout = setTimeout(function() {
                    if (!isPlaying) {
                        return; // Check again in case paused during typing.
                    }

                    el.classList.remove('mod-dialoguebuilder__typing');
                    el.classList.add('mod-dialoguebuilder__show');
                    container.scrollTo({top: container.scrollHeight, behavior: 'smooth'});

                    index++;

                    currentTimeout = setTimeout(showNextMessage, 600);
                }, 1200);
            }

            container.addEventListener('dialogue:play', function() {
                if (isPlaying) {
                    return;
                }
                isPlaying = true;
                showNextMessage();
            });

            container.addEventListener('dialogue:pause', function() {
                isPlaying = false;
                if (currentTimeout) {
                    clearTimeout(currentTimeout);
                    currentTimeout = null;
                }
            });

            container.addEventListener('dialogue:replay', function() {
                isPlaying = false;
                if (currentTimeout) {
                    clearTimeout(currentTimeout);
                    currentTimeout = null;
                }

                // Remove static mode if it was applied for grading read-mode
                container.classList.remove('mod-dialoguebuilder__static');

                messages.forEach(function(msg) {
                    msg.classList.remove('mod-dialoguebuilder__typing', 'mod-dialoguebuilder__show');
                });
                index = 0;
                container.scrollTo({top: 0, behavior: 'smooth'});

                setTimeout(function() {
                    container.dispatchEvent(new CustomEvent('dialogue:play'));
                }, 200);
            });
        }
    };
});
