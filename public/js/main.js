
var modalBox = $('#modal-box'),
    modalBoxDOMObject = modalBox[0],
    modalTitle = modalBoxDOMObject.getElementsByClassName('modal-title')[0],
    modalContent = modalBoxDOMObject.getElementsByClassName('modal-body')[0],
    modalFooter = modalBoxDOMObject.getElementsByClassName('modal-footer')[0];
  
function refresh() {
    window.location.href = window.location.href;
}

function displayModalAlert(content, type) {
    var alert = document.getElementById('modal-alert');
    alert.className = alert.className.replace(' hidden', '');
    alert.className += ' alert-' + type;
    alert.innerHTML = content;
}

function hideModalAlert() {
    var alert = document.getElementById('modal-alert');
    alert.className = 'alert hidden';
    alert.innerHTML = '';
}

function getCsrfToken() {
    var csrf = document.getElementById('modal-csrf-token');
    return csrf.getAttribute('data-key') + '=' + csrf.value;
}

function isValidISBN(isbn) {
    if (/^[0-9-]{12,}[0-9]$/.test(isbn)) {
        var controlDigit = Number(isbn.charAt(isbn.length - 1)),
            isbn = isbn.replace(/[^\d]/g, '').substring(0, 12),
            check = 0;

        if (isbn.length < 12) {
            return false;
        }

        check = 0;
        for (var i = 0; i < 13; i += 2) {
            check += Number(isbn.charAt(i));
        }

        for (var i = 1; i < 12; i += 2) {
            check += 3 * Number(isbn.charAt(i));
        }

        return (check + controlDigit) % 10 === 0;
    } else if (/^[0-9-]{9,}[0-9xX]$/.test(isbn)) {
        var controlDigit = isbn.charAt(isbn.length - 1).toLowerCase(),
            isbn = isbn.replace(/[^\d]/g, '').substring(0, 9),
            sum = 0;

        if (isbn.length < 9) {
            return false;
        }

        for (var i = 0; i < 9; i++) {
            sum += Number(isbn.charAt(i)) * (i + 1);
        }
        
        var check = sum % 11;
        return (check === 10 && controlDigit === 'x')
            || (check < 10 && check === Number(controlDigit));
    } else {
        return false;
    }
}

function showGenericEditor(options) {
    var createButton = document.getElementById('create-button'),
        cancelButton = document.getElementById('cancel-create'),
        method = 'POST';
        
    hideModalAlert();
    $('#delete-panel').addClass('hidden');
    $('#delete-actions').addClass('hidden');
    $('#editor-panel').removeClass('hidden');
    $('#editor-actions').removeClass('hidden');
    modalBox.modal('show');
    modalTitle.innerHTML = (options.editing) ? options.editTitle : options.createTitle;

    if (options.editing) {
        method = 'PUT';
        createButton.innerHTML = options.editTitle;
    } else {
        createButton.innerHTML = options.createButton;
    }

    createButton.onclick = function(e) {
        if (options.callbackValidator()) {
            hideModalAlert();
            
            $.ajax({
                url: baseUrl + options.url,
                data: options.data() + '&' + getCsrfToken(),
                type: method
            }).done(function(data) {
                displayModalAlert(data.message, 'success');
                name.value = '';
                setTimeout(function() {
                    refresh();
                }, 1000);
            }).error(function(data) {
                if (data.readyState === 4) {
                    displayModalAlert(JSON.parse(data.responseText).error, 'danger');
                }
            });
        }
    };
    cancelButton.onclick = function(e) {
        modalBox.modal('hide');
    };
}

function genericDelete(options) {
    var panel = document.getElementById('delete-panel'),
        deleteButton = document.getElementById('delete-button'),
        cancelButton = document.getElementById('cancel-delete');
        
    $('#delete-panel').removeClass('hidden');
    $('#delete-actions').removeClass('hidden');
    $('#editor-panel').addClass('hidden');
    $('#editor-actions').addClass('hidden');
    modalBox.modal('show');
    modalTitle.innerHTML = options.title;
    panel.innerHTML = options.content;
    deleteButton.onclick = function(e) {
        hideModalAlert();
        $.ajax({
            url: baseUrl + options.url,
            data: getCsrfToken() + '&confirm=yes',
            type: 'DELETE'
        }).done(function(data) {
            if (data.deleted) {
                panel.className = 'hidden';
                displayModalAlert(data.message, 'success');
                setTimeout(function() {
                    refresh();
                }, 1000);
            } else {
                modalBox.modal('hide');
            }
        }).error(function(data) {
            console.log(data.responseText);
            if (data.readyState === 4) {
                panel.className = 'hidden';
                displayModalAlert(JSON.parse(data.responseText).error, 'danger');
            }
        });
    };
    cancelButton.onclick = function(e) {
        modalBox.modal('hide');
    };
}
    
function showLevelEditor(elem) {
    var name = document.getElementById('nombre'),
        editing = elem !== undefined,
        url = 'levels/';

    if (editing) {
        name.value = elem.getAttribute('data-name');
        url += 'edit/' + elem.getAttribute('data-id');
    } else {
        name.value = '';
        url += 'new';
    }
    
    showGenericEditor({
        data: function() {
            return "nombre=" + name.value;
        },
        editing: editing,
        editTitle: 'Editar nivel',
        createTitle: 'Nuevo nivel',
        createButton: 'Crear nivel',
        url: url,
        callbackValidator: function() {
            if (name.value.length < 3) {
                displayModalAlert('El nombre del nivel debe tener al menos 3 caracteres', 'danger');
            } else {
                return true;
            }
            
            return false;
        }  
    });
}

function deleteLevel(elem) {
    genericDelete({
        title: 'Borrar nivel',
        content: '¿Deseas borrar el nivel "<b>' + elem.getAttribute('data-name') + '</b>"?',
        url: 'levels/delete/' + elem.getAttribute('data-id')
    });
}

function showSubjectEditor(elem) {
    var name = document.getElementById('nombre'),
        level = $('#nivel'),
        editing = elem !== undefined,
        url = 'subjects/';
        
    $level = level.selectize({
        valueField: 'id',
        placeholder: 'Seleccione un nivel',
        labelField: 'name',
        searchField: 'name',
        preload: true,
        openOnFocus: true,
        create: false,
        render: {
            option: function(item, escape) {
                return '<div>' + escape(item.name) + '</div>';
            }
        },
        load: function(query, callback) {
            if (query != '') {
                return;
            }

            $.ajax({
                url: baseUrl + 'levels/all',
                type: 'GET',
                error: function() {
                    callback();
                },
                success: function(res) {
                    callback(res.levels);
                    
                    if (editing) {
                        var sel = $level[0].selectize;
                        sel.setValue(elem.getAttribute('data-level-id'));
                    }
                }
            });
        }
    });

    if (editing) {
        name.value = elem.getAttribute('data-name');
        url += 'edit/' + elem.getAttribute('data-id');
        method = 'PUT';
    } else {
        name.value = '';
        url += 'new';
    }
    
    showGenericEditor({
        data: function() {
            return "nombre=" + name.value + '&nivel=' + level.val();
        },
        editing: editing,
        editTitle: 'Editar asignatura',
        createTitle: 'Nueva asignatura',
        createButton: 'Crear asignatura',
        url: url,
        callbackValidator: function() {
            if (name.value.length < 3) {
                displayModalAlert('El nombre de la asignatura debe tener al menos 3 caracteres', 'danger');
            } else if (level.val() == '') {
                displayModalAlert('Debes seleccionar un nivel.', 'danger');
            } else {
                return true;
            }
            
            return false;
        }  
    });
}

function deleteSubject(elem) {
    genericDelete({
        title: 'Borrar asignatura',
        content: '¿Deseas borrar la asignatura "<b>' + elem.getAttribute('data-name') + '</b>"?',
        url: 'subjects/delete/' + elem.getAttribute('data-id')
    });
}

function showStudentEditor(elem) {
    var name = document.getElementById('nombre'),
        nie = document.getElementById('nie'),
        surname = document.getElementById('apellidos'),
        phone = document.getElementById('telefono'),
        editing = elem !== undefined,
        url = 'students/';

    if (editing) {
        name.value = elem.getAttribute('data-name');
        nie.value = elem.getAttribute('data-id');
        nie.disabled = true;
        surname.value = elem.getAttribute('data-surname');
        phone.value = elem.getAttribute('data-phone');
        url += 'edit/' + elem.getAttribute('data-id');
    } else {
        name.value = '';
        surname.value = '';
        nie.value = '';
        phone.value = '';
        nie.disabled = false;
        url += 'new';
    }
    
    showGenericEditor({
        data: function() {
            return "nombre=" + name.value + '&apellidos=' + surname.value + '&telefono='
            + phone.value + '&nie=' + nie.value;
        },
        editing: editing,
        editTitle: 'Editar alumno',
        createTitle: 'Nuevo alumno',
        createButton: 'Crear alumno',
        url: url,
        callbackValidator: function() {
            if (name.value.length < 3) {
                displayModalAlert('El nombre del alumno debe tener al menos 3 caracteres.', 'danger');
            } else if (nie.value.length < 1) {
                displayModalAlert('Debes rellenar el NIE.', 'danger');
            } else if (phone.value.length > 0 && !/^[0-9]{9}$/.test(phone.value)) {
                displayModalAlert('El teléfono no es válido.', 'danger');
            } else {
                return true;
            }
            
            return false;
        }  
    });
}

function deleteStudent(elem) {
    genericDelete({
        title: 'Borrar alumno',
        content: '¿Deseas borrar el alumno "<b>' + elem.getAttribute('data-name') + '</b>"?',
        url: 'students/delete/' + elem.getAttribute('data-id')
    });
}

function showBookEditor(elem) {
    var title = document.getElementById('titulo'),
        nie = document.getElementById('isbn'),
        author = document.getElementById('autor'),
        year = document.getElementById('anio'),
        subject = $('#asignatura'),
        editing = elem !== undefined,
        url = 'books/';
        
    $subject = subject.selectize({
        valueField: 'id',
        placeholder: 'Seleccione una asignatura',
        labelField: 'name',
        searchField: 'name',
        preload: true,
        openOnFocus: true,
        create: false,
        render: {
            option: function(item, escape) {
                return '<div>' + escape(item.name) + '</div>';
            }
        },
        load: function(query, callback) {
            if (query != '') {
                return;
            }

            $.ajax({
                url: baseUrl + 'subjects/all',
                type: 'GET',
                error: function(res) {
                    callback();
                },
                success: function(res) {
                    callback(res.subjects);
                
                    if (editing) {
                        var sel = $subject[0].selectize;
                        sel.setValue(elem.getAttribute('data-subject'));
                    }
                }
            });
        }
    });

    if (editing) {
        title.value = elem.getAttribute('data-title');
        isbn.value = elem.getAttribute('data-isbn');
        author.value = elem.getAttribute('data-author');
        year.value = elem.getAttribute('data-year');
        url += 'edit/' + elem.getAttribute('data-id');
    } else {
        title.value = '';
        isbn.value = '';
        year.value = '';
        author.value = '';
        url += 'new';
    }
    
    showGenericEditor({
        data: function() {
            return "titulo=" + title.value + '&isbn=' + isbn.value + '&autor='
            + author.value + '&anio=' + year.value + '&asignatura_id=' + subject.val();
        },
        editing: editing,
        editTitle: 'Editar libro',
        createTitle: 'Nuevo libro',
        createButton: 'Crear libro',
        url: url,
        callbackValidator: function() {
            if (title.value.length < 1) {
                displayModalAlert('Debes rellenar el título de libro.', 'danger');
            } else if (isbn.value.length < 1) {
                displayModalAlert('Debes rellenar el ISBN.', 'danger');
            } else if (!isValidISBN(isbn.value)) {
                displayModalAlert('El ISBN no es válido.', 'danger');
            } else if (year.value > Number(new Date().getFullYear()) || year.value <= 0) {
                displayModalAlert('Fecha de publicación no válida.', 'danger');
            } else if (subject.val() == '') {
                displayModalAlert('Debes seleccionar una asignatura.', 'danger');
            } else {
                return true;
            }
            
            return false;
        }  
    });
}

function deleteBook(elem) {
    genericDelete({
        title: 'Borrar libro',
        content: '¿Deseas borrar el libro "<b>' + elem.getAttribute('data-title') + '</b>"?',
        url: 'books/delete/' + elem.getAttribute('data-id')
    });
}

function showUserEditor(elem) {
    var username = document.getElementById('nombre_usuario'),
        name = document.getElementById('nombre_completo'),
        isAdmin = document.getElementById('es_admin'),
        password = document.getElementById('usuario_pass'),
        editing = elem !== undefined,
        url = 'users/';
        
    if (editing) {
        username.value = elem.getAttribute('data-username');
        name.value = elem.getAttribute('data-name');
        isAdmin.checked = elem.getAttribute('data-is-admin') == '1';
        password.value = '';
        password.placeholder = 'Deja en blanco para mantener la anterior...';
        url += 'edit/' + elem.getAttribute('data-id');
    } else {
        username.value = '';
        name.value = '';
        isAdmin.checked = false;
        password.value = '';
        url += 'new';
    }
    
    showGenericEditor({
        data: function() {
            return "nombre_usuario=" + username.value + '&nombre_completo=' + name.value + 
            '&es_admin=' + ((isAdmin.checked) ? 1 : 0) + '&usuario_pass=' + password.value
        },
        editing: editing,
        editTitle: 'Editar usuario',
        createTitle: 'Nuevo usuario',
        createButton: 'Crear usuario',
        url: url,
        callbackValidator: function() {
            if ((password.value.length < 6 && !editing) 
                || (editing && password.value.length > 0 && password.value.length < 6)) {
                displayModalAlert('La contraseña debe tener al menos 6 caracteres.', 'danger');
            } else if (username.value.length < 1) {
                displayModalAlert('Debes rellenar el nombre de usuario.', 'danger');
            } else if (name.value.length < 1) {
                displayModalAlert('Debes rellenar el nombre completo.', 'danger');
            } else {
                return true;
            }
            
            return false;
        }  
    });
}

function deleteUser(elem) {
    genericDelete({
        title: 'Borrar usuario',
        content: '¿Deseas borrar el usuario "<b>' + elem.getAttribute('data-username') + '</b>"?',
        url: 'users/delete/' + elem.getAttribute('data-id')
    });
}