/**
 * Editor JS module for dialoguebuilder.
 *
 * @module     mod_dialoguebuilder/editor
 * @copyright  2026 Matheus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {

    return {
        /**
         * Initialize the editor.
         *
         * @param {Number} cmid The course module ID.
         * @param {String} initialDataJson The initial JSON data.
         */
        init: function(cmid, initialDataJson) {
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

            var $charList = $('#db-character-list');
            var $linesContainer = $('#db-dialogue-lines');
            var $emptyMsg = $('#db-empty-lines-msg');
            var $addLineBtn = $('#db-add-line-btn');
            var $submitForm = $('#db-submit-form');
            var $dataInput = $('#db-dialoguedata');

            /**
             * Renders the characters list.
             */
            function renderCharacters() {
                $charList.empty();
                characters.forEach(function(c) {
                    var $li = $('<li class="list-group-item d-flex gap-2 align-items-center"></li>');

                    var $input = $('<input type="text" class="form-control form-control-sm">')
                        .attr('placeholder', 'Nome do personagem...')
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

                    $li.append($input).append($delBtn);
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
                        var $opt = $('<option></option>').attr('value', c.id).text(c.name || 'Sem nome');
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
                $linesContainer.find('.db-line-item').remove();

                if (lines.length > 0) {
                    $emptyMsg.hide();
                } else if (characters.length > 0) {
                    $emptyMsg.show();
                }

                lines.forEach(function(line, index) {
                    var $row = $('<div class="db-line-item row mb-2 align-items-start"></div>');

                    var $colSelect = $('<div class="col-md-3"></div>');
                    var $select = $('<select class="form-control form-control-sm char-select"></select>');
                    characters.forEach(function(c) {
                        var $opt = $('<option></option>').attr('value', c.id).text(c.name || 'Sem nome');
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
                        .attr('placeholder', 'Escreva a fala aqui...')
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

            $('#db-add-char-btn').on('click', function(e) {
                e.preventDefault();
                characters.push({
                    id: charIdCounter++,
                    name: 'Personagem ' + charIdCounter
                });
                renderCharacters();
                renderLines();
            });

            $('#db-add-line-btn').on('click', function(e) {
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
        }
    };
});
