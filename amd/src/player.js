// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

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
