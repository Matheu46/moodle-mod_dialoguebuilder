/**
 * Editor JS module for dialoguebuilder.
 *
 * @module     mod_dialoguebuilder/editor
 * @copyright  2026 Matheus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/str'], function($, str) {

    return {
        /**
         * Initialize the editor.
         *
         * @param {Number} cmid The course module ID.
         * @param {String} initialDataJson The initial JSON data.
         */
        init: function(cmid, initialDataJson) {
            str.get_strings([
                {key: 'changeavatar', component: 'mod_dialoguebuilder'},
                {key: 'characternameplaceholder', component: 'mod_dialoguebuilder'},
                {key: 'unnamed', component: 'mod_dialoguebuilder'},
                {key: 'writelineplaceholder', component: 'mod_dialoguebuilder'},
                {key: 'character', component: 'mod_dialoguebuilder'}
            ]).then(function(strings) {
                var langStrings = {
                    changeavatar: strings[0],
                    characternameplaceholder: strings[1],
                    unnamed: strings[2],
                    writelineplaceholder: strings[3],
                    character: strings[4]
                };

                var characters = [];
                var lines = [];
                var charIdCounter = 1;

                // Load existing data.
                if (initialDataJson) {
                    try {
                        var data = JSON.parse(initialDataJson);
                        if (data.characters && data.characters.length > 0) {
                            characters = data.characters;
                            // Find max ID to set counter
                            var maxId = Math.max.apply(Math, characters.map(function(c) {
                                return c.id;
                            }));
                            charIdCounter = maxId + 1;
                        }
                        if (data.lines) {
                            lines = data.lines;
                        }
                    } catch (e) {
                        // eslint-disable-next-line no-console
                        console.error("Failed to parse initial dialogue data", e);
                    }
                }

                var $charList = $('#mod-dialoguebuilder__character-list');
                var $linesContainer = $('#mod-dialoguebuilder__dialogue-lines');
                var $emptyMsg = $('#mod-dialoguebuilder__empty-lines-msg');
                var $addLineBtn = $('#mod-dialoguebuilder__add-line-btn');
                var $submitForm = $('#mod-dialoguebuilder__submit-form');
                var $dataInput = $('#mod-dialoguebuilder__dialoguedata');

                /**
                 * Renders the characters list.
                 */
                function renderCharacters() {
                    $charList.empty();
                    characters.forEach(function(c) {
                        var $li = $('<li class="list-group-item d-flex gap-2 align-items-center"></li>');

                        // Avatar wrapper
                        var avatarSrc = c.avatarDataUrl || c.avatarurl || M.util.image_url('u/f2');
                        var $avatarContainer = $('<div></div>')
                            .addClass('position-relative d-inline-block')
                            .css({
                                'width': '40px',
                                'height': '40px',
                                'cursor': 'pointer'
                            })
                            .attr('title', langStrings.changeavatar);

                        var $avatarImg = $('<img>')
                            .addClass('rounded-circle')
                            .css({
                                'width': '100%',
                                'height': '100%',
                                'object-fit': 'cover'
                            })
                            .attr('src', avatarSrc);

                        var $avatarOverlay = $('<div></div>')
                            .addClass('position-absolute d-flex align-items-center')
                            .addClass('justify-content-center text-white rounded-circle')
                            .css({
                                'top': '0', 'left': '0', 'right': '0', 'bottom': '0',
                                'background': 'rgba(0,0,0,0.5)',
                                'opacity': '0',
                                'transition': 'opacity 0.2s'
                            })
                            .html('<i class="fa fa-camera" style="font-size: 14px;"></i>');

                        $avatarContainer.append($avatarImg).append($avatarOverlay);

                        $avatarContainer.hover(function() {
                            $avatarOverlay.css('opacity', '1');
                        }, function() {
                            $avatarOverlay.css('opacity', '0');
                        });

                        var $avatarInput = $('<input type="file" name="avatars[' + c.id + ']" accept="image/*" class="d-none">');

                        $avatarContainer.on('click', function() {
                            $avatarInput.click();
                        });

                        $avatarInput.on('change', function(e) {
                            var file = e.target.files[0];
                            if (file) {
                                var reader = new FileReader();
                                reader.onload = function(evt) {
                                    c.avatarDataUrl = evt.target.result;
                                    $avatarImg.attr('src', evt.target.result);
                                };
                                reader.readAsDataURL(file);
                            }
                        });

                        var $input = $('<input type="text" class="form-control form-control-sm">')
                            .attr('placeholder', langStrings.characternameplaceholder)
                            .val(c.name)
                            .on('change keyup', function() {
                                c.name = $(this).val();
                                updateSelects(); // update names in selects without re-rendering everything
                            });

                        var delBtnHTML = '<button type="button" class="btn btn-sm btn-outline-danger">' +
                            '<i class="fa fa-trash"></i></button>';
                        var $delBtn = $(delBtnHTML)
                            .on('click', function() {
                                // Remove character and their lines
                                characters = characters.filter(function(char) {
                                    return char.id !== c.id;
                                });
                                lines = lines.filter(function(l) {
                                    return l.characterid !== c.id;
                                });
                                renderCharacters();
                                renderLines();
                            });

                        $li.append($avatarContainer).append($avatarInput).append($input).append($delBtn);
                        $charList.append($li);
                    });

                    if (characters.length > 0) {
                        $addLineBtn.prop('disabled', false);
                        if (lines.length === 0) {
                            $emptyMsg.show();
                        }
                    } else {
                        $addLineBtn.prop('disabled', true);
                        $emptyMsg.show();
                    }
                }

                /**
                 * Updates the options in character selects.
                 */
                function updateSelects() {
                    $linesContainer.find('select.char-select').each(function() {
                        var $select = $(this);
                        var selectedVal = parseInt($select.val(), 10);
                        $select.empty();
                        characters.forEach(function(c) {
                            var $opt = $('<option></option>').attr('value', c.id).text(c.name || langStrings.unnamed);
                            if (c.id === selectedVal) {
                                $opt.prop('selected', true);
                            }
                            $select.append($opt);
                        });
                    });
                }

                /**
                 * Renders the lines list.
                 */
                function renderLines() {
                    $linesContainer.find('.mod-dialoguebuilder__line-item').remove();

                    if (lines.length > 0) {
                        $emptyMsg.hide();
                    } else if (characters.length > 0) {
                        $emptyMsg.show();
                    }

                    lines.forEach(function(line, index) {
                        var $row = $('<div class="mod-dialoguebuilder__line-item row mb-2 align-items-start"></div>');

                        var $colSelect = $('<div class="col-md-3"></div>');
                        var $select = $('<select class="form-control form-control-sm char-select"></select>');
                        characters.forEach(function(c) {
                            var $opt = $('<option></option>').attr('value', c.id).text(c.name || langStrings.unnamed);
                            if (c.id === line.characterid) {
                                $opt.prop('selected', true);
                            }
                            $select.append($opt);
                        });
                        $select.on('change', function() {
                            line.characterid = parseInt($(this).val(), 10);
                        });
                        $colSelect.append($select);

                        var $colText = $('<div class="col-md-8"></div>');
                        var $textarea = $('<textarea class="form-control" rows="2"></textarea>')
                            .attr('placeholder', langStrings.writelineplaceholder)
                            .val(line.text)
                            .on('change', function() {
                                line.text = $(this).val();
                            });
                        $colText.append($textarea);

                        var $colDel = $('<div class="col-md-1"></div>');
                        var delBtnHTML = '<button type="button" class="btn btn-sm btn-outline-danger w-100">' +
                            '<i class="fa fa-times"></i></button>';
                        var $delBtn = $(delBtnHTML)
                            .on('click', function() {
                                lines.splice(index, 1);
                                renderLines();
                            });
                        $colDel.append($delBtn);

                        $row.append($colSelect).append($colText).append($colDel);
                        $linesContainer.append($row);
                    });
                }

                $('#mod-dialoguebuilder__add-char-btn').on('click', function(e) {
                    e.preventDefault();
                    characters.push({
                        id: charIdCounter++,
                        name: langStrings.character + ' ' + charIdCounter
                    });
                    renderCharacters();
                    renderLines();
                });

                $('#mod-dialoguebuilder__add-line-btn').on('click', function(e) {
                    e.preventDefault();
                    if (characters.length > 0) {
                        lines.push({
                            characterid: characters[0].id,
                            text: ''
                        });
                        renderLines();
                    }
                });

                $submitForm.on('submit', function() {
                    var payload = {
                        characters: characters,
                        lines: lines
                    };
                    $dataInput.val(JSON.stringify(payload));
                });

                // Initial render.
                renderCharacters();
                renderLines();

            }).catch(function(e) {
                // eslint-disable-next-line no-console
                console.error("Failed to load strings", e);
            });
        }
    };
});
