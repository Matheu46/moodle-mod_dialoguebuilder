/**
 * Editor JS module for dialoguebuilder.
 *
 * @module     mod_dialoguebuilder/editor
 * @copyright  2026 Matheus Mathias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/str', 'core/emoji/picker', 'core/notification'], function(str, EmojiPicker, Notification) {

    if (EmojiPicker && EmojiPicker.default) {
        EmojiPicker = EmojiPicker.default;
    }

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
                {key: 'character', component: 'mod_dialoguebuilder'},
                {key: 'emptyfieldwarning', component: 'mod_dialoguebuilder'}
            ]).then(function(strings) {
                var langStrings = {
                    changeavatar: strings[0],
                    characternameplaceholder: strings[1],
                    unnamed: strings[2],
                    writelineplaceholder: strings[3],
                    character: strings[4],
                    emptyfieldwarning: strings[5]
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

                var newLineContainer = document.getElementById('mod-dialoguebuilder__new-line-container');
                var newLineChar = document.getElementById('mod-dialoguebuilder__new-line-char');
                var newLineText = document.getElementById('mod-dialoguebuilder__new-line-text');
                var newLineEmojiBtn = document.getElementById('mod-dialoguebuilder__new-line-emoji-btn');

                var activeTextarea = null;
                var emojiPickerContainer = document.getElementById('mod-dialoguebuilder__emoji-picker-container');

                if (emojiPickerContainer) {
                    EmojiPicker(emojiPickerContainer, function(emoji) {
                        if (activeTextarea) {
                            var startPos = activeTextarea.selectionStart;
                            var endPos = activeTextarea.selectionEnd;
                            var text = activeTextarea.value;
                            activeTextarea.value = text.substring(0, startPos) + emoji + text.substring(endPos);

                            activeTextarea.focus();
                            // Trigger input to update state
                            activeTextarea.dispatchEvent(new Event('input'));
                        }
                        emojiPickerContainer.classList.add('d-none');
                    });

                    // Close emoji picker when clicking outside
                    document.addEventListener('click', function(e) {
                        if (!emojiPickerContainer.classList.contains('d-none')) {
                            if (!emojiPickerContainer.contains(e.target) && !e.target.closest('.emoji-toggle-btn')) {
                                emojiPickerContainer.classList.add('d-none');
                            }
                        }
                    });
                }

                if (newLineEmojiBtn) {
                    newLineEmojiBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        if (activeTextarea === newLineText && !emojiPickerContainer.classList.contains('d-none')) {
                            emojiPickerContainer.classList.add('d-none');
                        } else {
                            activeTextarea = newLineText;
                            emojiPickerContainer.classList.remove('d-none');

                            var wrapper = document.getElementById('mod-dialoguebuilder__editor-' + cmid);
                            var btnRect = newLineEmojiBtn.getBoundingClientRect();
                            var wrapperRect = wrapper.getBoundingClientRect();

                            emojiPickerContainer.style.top = (btnRect.bottom - wrapperRect.top + 5) + 'px';
                            var leftPos = btnRect.right - wrapperRect.left - 350; // default to right align
                            if (leftPos < 0) {
                                leftPos = 15;
                            }
                            emojiPickerContainer.style.left = leftPos + 'px';
                        }
                    });
                }

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
                                return char.id != c.id;
                            });
                            lines = lines.filter(function(l) {
                                return l.characterid != c.id;
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
                        if (newLineContainer) {
                            newLineContainer.style.display = '';
                        }
                        if (lines.length === 0) {
                            emptyMsg.style.display = '';
                        }
                    } else {
                        if (newLineContainer) {
                            newLineContainer.style.display = 'none';
                        }
                        emptyMsg.style.display = '';
                    }

                    updateSelects();
                }

                /**
                 * Updates the options in character selects.
                 */
                function updateSelects() {
                    var selects = linesContainer.querySelectorAll('select.char-select');

                    var updateSelect = function(select) {
                        var selectedVal = parseInt(select.value, 10);
                        select.innerHTML = '';
                        characters.forEach(function(c) {
                            var opt = document.createElement('option');
                            opt.value = c.id;
                            opt.textContent = c.name || langStrings.unnamed;
                            if (c.id == selectedVal) {
                                opt.selected = true;
                            }
                            select.appendChild(opt);
                        });
                    };

                    selects.forEach(updateSelect);
                    if (newLineChar) {
                        updateSelect(newLineChar);
                    }
                }

                /**
                 * Renders the lines list.
                 */
                function renderLines() {
                    var currentScroll = linesContainer.scrollTop;

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
                        row.className = 'mod-dialoguebuilder__line-item row mb-3 align-items-start';

                        var colSelect = document.createElement('div');
                        colSelect.className = 'col-10 col-md-3 mb-2 mb-md-0';
                        var select = document.createElement('select');
                        select.className = 'form-control form-control-sm char-select';

                        characters.forEach(function(c) {
                            var opt = document.createElement('option');
                            opt.value = c.id;
                            opt.textContent = c.name || langStrings.unnamed;
                            if (c.id == line.characterid) {
                                opt.selected = true;
                            }
                            select.appendChild(opt);
                        });

                        select.addEventListener('change', function(e) {
                            line.characterid = parseInt(e.target.value, 10);
                        });
                        colSelect.appendChild(select);

                        var colDel = document.createElement('div');
                        colDel.className = 'col-2 col-md-1 order-md-3 mb-2 mb-md-0 text-end';
                        var delBtn = document.createElement('button');
                        delBtn.type = 'button';
                        delBtn.className = 'btn btn-sm btn-outline-danger w-100 h-100';
                        delBtn.innerHTML = '<i class="fa fa-times"></i>';
                        delBtn.addEventListener('click', function() {
                            lines.splice(index, 1);
                            renderLines();
                        });
                        colDel.appendChild(delBtn);

                        var colText = document.createElement('div');
                        colText.className = 'col-12 col-md-8 order-md-2 position-relative';

                        var textarea = document.createElement('textarea');
                        textarea.className = 'form-control pe-5'; // Add padding to avoid text overlapping the button
                        textarea.rows = 2;
                        textarea.placeholder = langStrings.writelineplaceholder;
                        textarea.value = line.text || '';
                        textarea.addEventListener('input', function(e) {
                            line.text = e.target.value;
                            if (e.target.value.trim() !== '') {
                                e.target.classList.remove('is-invalid');
                            }
                        });

                        var toggleBtn = document.createElement('button');
                        toggleBtn.type = 'button';
                        toggleBtn.className = 'btn btn-link btn-sm position-absolute emoji-toggle-btn text-decoration-none';
                        toggleBtn.style.bottom = '5px';
                        toggleBtn.style.right = '20px';
                        toggleBtn.innerHTML = '<i class="fa fa-smile-o" style="font-size: 1.2rem; color: #6c757d;"></i>';
                        toggleBtn.addEventListener('click', function(e) {
                            e.preventDefault();
                            if (activeTextarea === textarea && !emojiPickerContainer.classList.contains('d-none')) {
                                emojiPickerContainer.classList.add('d-none');
                            } else {
                                activeTextarea = textarea;
                                emojiPickerContainer.classList.remove('d-none');

                                var wrapper = document.getElementById('mod-dialoguebuilder__editor-' + cmid);
                                var btnRect = toggleBtn.getBoundingClientRect();
                                var wrapperRect = wrapper.getBoundingClientRect();

                                emojiPickerContainer.style.top = (btnRect.bottom - wrapperRect.top + 5) + 'px';
                                var leftPos = btnRect.right - wrapperRect.left - 350; // default to right align
                                if (leftPos < 0) {
                                    leftPos = 15;
                                }
                                emojiPickerContainer.style.left = leftPos + 'px';
                            }
                        });

                        colText.appendChild(textarea);
                        colText.appendChild(toggleBtn);

                        row.appendChild(colSelect);
                        row.appendChild(colDel);
                        row.appendChild(colText);
                        linesContainer.appendChild(row);
                    });

                    linesContainer.scrollTop = currentScroll;
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

                if (addLineBtn && newLineText && newLineChar) {
                    addLineBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        if (characters.length > 0) {
                            var text = newLineText.value.trim();
                            var charId = parseInt(newLineChar.value, 10);

                            if (!charId) {
                                charId = characters[0].id;
                            }

                            if (text === '') {
                                newLineText.classList.add('is-invalid');
                                return;
                            }

                            newLineText.classList.remove('is-invalid');

                            lines.push({
                                characterid: charId,
                                text: text
                            });

                            newLineText.value = '';

                            renderLines();

                            // Scroll to bottom to show the new line
                            setTimeout(function() {
                                linesContainer.scrollTop = linesContainer.scrollHeight;
                            }, 10);
                        }
                    });

                    newLineText.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter' && !e.shiftKey) {
                            e.preventDefault();
                            addLineBtn.click();
                        }
                    });

                    newLineText.addEventListener('input', function(e) {
                        if (e.target.value.trim() !== '') {
                            e.target.classList.remove('is-invalid');
                        }
                    });
                }

                submitForm.addEventListener('submit', function(e) {
                    var hasEmptyLines = false;
                    var textareas = linesContainer.querySelectorAll('textarea');

                    textareas.forEach(function(textarea) {
                        if (textarea.value.trim() === '') {
                            hasEmptyLines = true;
                            textarea.classList.add('is-invalid');
                        } else {
                            textarea.classList.remove('is-invalid');
                        }
                    });

                    if (hasEmptyLines) {
                        e.preventDefault(); // Stop submission
                        Notification.addNotification({
                            message: langStrings.emptyfieldwarning,
                            type: 'error'
                        });
                        return;
                    }

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
