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
                    clone.style.height = 'auto'; // Allow expanding.
                    clone.style.overflow = 'visible';
                    clone.style.maxHeight = 'none';

                    // Force static mode so animation opacity doesn't cause a washed out look
                    clone.classList.add('mod-dialoguebuilder__static');

                    // Html2canvas struggles with object-fit: cover on images, causing them to squish.
                    // Convert img avatars to divs with background-image on the clone.
                    var avatars = clone.querySelectorAll('img.mod-dialoguebuilder__avatar');
                    avatars.forEach(function(img) {
                        var div = document.createElement('div');
                        div.className = img.className;
                        div.style.backgroundImage = 'url("' + img.src + '")';
                        div.style.backgroundSize = 'cover';
                        div.style.backgroundPosition = 'center';
                        div.style.width = '40px';
                        div.style.height = '40px';
                        div.style.borderRadius = '50%';
                        img.parentNode.replaceChild(div, img);
                    });

                    document.body.appendChild(clone);

                    // Temporary change icon to indicate loading
                    var icon = downloadBtn.querySelector('i');
                    var oldClass = icon.className;
                    icon.className = 'fa fa-spinner fa-spin';
                    downloadBtn.disabled = true;

                    // Execute html2canvas
                    html2canvas(clone, {
                        backgroundColor: '#f8f9fa', // Ensure background is solid.
                        scale: 2 // Better quality.
                    }).then(function(canvas) {
                        var link = document.createElement('a');
                        link.download = 'dialogue.png';
                        link.href = canvas.toDataURL('image/png');
                        link.click();
                        return true;
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
