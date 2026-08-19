/**
 * Player controls JS module for dialoguebuilder.
 *
 * @module     mod_dialoguebuilder/player_controls
 * @copyright  2026 Matheus Mathias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {

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
        }
    };
});
