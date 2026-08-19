/**
 * Editor JS module for dialoguebuilder.
 *
 * @module     mod_dialoguebuilder/editor
 * @copyright  2026 Matheus Mathias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/str'], function(str) {

    return {
        /**
         * Initialize the editor.
         *
         * @param {Number} cmid The course module ID.
         */
        init: function(cmid) {
            var wrapper = document.getElementById('mod-dialoguebuilder__editor-' + cmid);
            var initialDataJson = wrapper ? wrapper.getAttribute('data-initialdata') : null;

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

                var charList = document.getElementById('mod-dialoguebuilder__character-list');
                var linesContainer = document.getElementById('mod-dialoguebuilder__dialogue-lines');
                var emptyMsg = document.getElementById('mod-dialoguebuilder__empty-lines-msg');
                var addLineBtn = document.getElementById('mod-dialoguebuilder__add-line-btn');
                var submitForm = document.getElementById('mod-dialoguebuilder__submit-form');
                var dataInput = document.getElementById('mod-dialoguebuilder__dialoguedata');

                /**
                 * Renders the characters list.
                 */
                function renderCharacters() {
                    charList.innerHTML = '';
                    characters.forEach(function(c) {
                        var li = document.createElement('li');
                        li.className = 'list-group-item d-flex gap-2 align-items-center';

                        // Avatar wrapper
                        var avatarSrc = c.avatarDataUrl || c.avatarurl || M.util.image_url('u/f2');
                        var avatarContainer = document.createElement('div');
                        avatarContainer.className = 'position-relative d-inline-block';
                        avatarContainer.style.width = '40px';
                        avatarContainer.style.height = '40px';
                        avatarContainer.style.cursor = 'pointer';
                        avatarContainer.setAttribute('title', langStrings.changeavatar);

                        var avatarImg = document.createElement('img');
                        avatarImg.className = 'rounded-circle';
                        avatarImg.style.width = '100%';
                        avatarImg.style.height = '100%';
                        avatarImg.style.objectFit = 'cover';
                        avatarImg.src = avatarSrc;

                        var avatarOverlay = document.createElement('div');
                        avatarOverlay.className = 'position-absolute d-flex align-items-center ' +
                            'justify-content-center text-white rounded-circle';
                        avatarOverlay.style.top = '0';
                        avatarOverlay.style.left = '0';
                        avatarOverlay.style.right = '0';
                        avatarOverlay.style.bottom = '0';
                        avatarOverlay.style.background = 'rgba(0,0,0,0.5)';
                        avatarOverlay.style.opacity = '0';
                        avatarOverlay.style.transition = 'opacity 0.2s';
                        avatarOverlay.innerHTML = '<i class="fa fa-camera" style="font-size: 14px;"></i>';

                        avatarContainer.appendChild(avatarImg);
                        avatarContainer.appendChild(avatarOverlay);

                        avatarContainer.addEventListener('mouseenter', function() {
                            avatarOverlay.style.opacity = '1';
                        });
                        avatarContainer.addEventListener('mouseleave', function() {
                            avatarOverlay.style.opacity = '0';
                        });

                        var avatarInput = document.createElement('input');
                        avatarInput.type = 'file';
                        avatarInput.name = 'avatars[' + c.id + ']';
                        avatarInput.accept = 'image/*';
                        avatarInput.className = 'd-none';

                        avatarContainer.addEventListener('click', function() {
                            avatarInput.click();
                        });

                        avatarInput.addEventListener('change', function(e) {
                            var file = e.target.files[0];
                            if (file) {
                                var reader = new FileReader();
                                reader.onload = function(evt) {
                                    c.avatarDataUrl = evt.target.result;
                                    avatarImg.src = evt.target.result;
                                };
                                reader.readAsDataURL(file);
                            }
                        });

                        var input = document.createElement('input');
                        input.type = 'text';
                        input.className = 'form-control form-control-sm';
                        input.placeholder = langStrings.characternameplaceholder;
                        input.value = c.name || '';

                        var updateName = function(e) {
                            c.name = e.target.value;
                            updateSelects(); // update names in selects without re-rendering everything
                        };
                        input.addEventListener('change', updateName);
                        input.addEventListener('keyup', updateName);

                        var delBtn = document.createElement('button');
                        delBtn.type = 'button';
                        delBtn.className = 'btn btn-sm btn-outline-danger';
                        delBtn.innerHTML = '<i class="fa fa-trash"></i>';
                        delBtn.addEventListener('click', function() {
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

                        li.appendChild(avatarContainer);
                        li.appendChild(avatarInput);
                        li.appendChild(input);
                        li.appendChild(delBtn);
                        charList.appendChild(li);
                    });

                    if (characters.length > 0) {
                        addLineBtn.disabled = false;
                        if (lines.length === 0) {
                            emptyMsg.style.display = '';
                        }
                    } else {
                        addLineBtn.disabled = true;
                        emptyMsg.style.display = '';
                    }
                }

                /**
                 * Updates the options in character selects.
                 */
                function updateSelects() {
                    var selects = linesContainer.querySelectorAll('select.char-select');
                    selects.forEach(function(select) {
                        var selectedVal = parseInt(select.value, 10);
                        select.innerHTML = '';
                        characters.forEach(function(c) {
                            var opt = document.createElement('option');
                            opt.value = c.id;
                            opt.textContent = c.name || langStrings.unnamed;
                            if (c.id === selectedVal) {
                                opt.selected = true;
                            }
                            select.appendChild(opt);
                        });
                    });
                }

                /**
                 * Renders the lines list.
                 */
                function renderLines() {
                    var lineItems = linesContainer.querySelectorAll('.mod-dialoguebuilder__line-item');
                    lineItems.forEach(function(item) {
                        item.remove();
                    });

                    if (lines.length > 0) {
                        emptyMsg.style.display = 'none';
                    } else if (characters.length > 0) {
                        emptyMsg.style.display = '';
                    }

                    lines.forEach(function(line, index) {
                        var row = document.createElement('div');
                        row.className = 'mod-dialoguebuilder__line-item row mb-2 align-items-start';

                        var colSelect = document.createElement('div');
                        colSelect.className = 'col-md-3';
                        var select = document.createElement('select');
                        select.className = 'form-control form-control-sm char-select';

                        characters.forEach(function(c) {
                            var opt = document.createElement('option');
                            opt.value = c.id;
                            opt.textContent = c.name || langStrings.unnamed;
                            if (c.id === line.characterid) {
                                opt.selected = true;
                            }
                            select.appendChild(opt);
                        });

                        select.addEventListener('change', function(e) {
                            line.characterid = parseInt(e.target.value, 10);
                        });
                        colSelect.appendChild(select);

                        var colText = document.createElement('div');
                        colText.className = 'col-md-8';
                        var textarea = document.createElement('textarea');
                        textarea.className = 'form-control';
                        textarea.rows = 2;
                        textarea.placeholder = langStrings.writelineplaceholder;
                        textarea.value = line.text || '';
                        textarea.addEventListener('change', function(e) {
                            line.text = e.target.value;
                        });
                        colText.appendChild(textarea);

                        var colDel = document.createElement('div');
                        colDel.className = 'col-md-1';
                        var delBtn = document.createElement('button');
                        delBtn.type = 'button';
                        delBtn.className = 'btn btn-sm btn-outline-danger w-100';
                        delBtn.innerHTML = '<i class="fa fa-times"></i>';
                        delBtn.addEventListener('click', function() {
                            lines.splice(index, 1);
                            renderLines();
                        });
                        colDel.appendChild(delBtn);

                        row.appendChild(colSelect);
                        row.appendChild(colText);
                        row.appendChild(colDel);
                        linesContainer.appendChild(row);
                    });
                }

                document.getElementById('mod-dialoguebuilder__add-char-btn').addEventListener('click', function(e) {
                    e.preventDefault();
                    characters.push({
                        id: charIdCounter++,
                        name: langStrings.character + ' ' + charIdCounter
                    });
                    renderCharacters();
                    renderLines();
                });

                document.getElementById('mod-dialoguebuilder__add-line-btn').addEventListener('click', function(e) {
                    e.preventDefault();
                    if (characters.length > 0) {
                        lines.push({
                            characterid: characters[0].id,
                            text: ''
                        });
                        renderLines();
                    }
                });

                submitForm.addEventListener('submit', function() {
                    var payload = {
                        characters: characters,
                        lines: lines
                    };
                    dataInput.value = JSON.stringify(payload);
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
