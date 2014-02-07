
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
    
function showLevelEditor(elem) {
    var panel = document.getElementById('editor-panel'),
        actions = document.getElementById('editor-actions'),
        name = document.getElementById('nombre'),
        createButton = document.getElementById('create-level'),
        cancelButton = document.getElementById('cancel-create'),
        editing = elem !== undefined,
        url = 'levels/',
        method = 'POST';

    modalBox.modal('show');
    modalTitle.innerHTML = (editing) ? 'Editar nivel' : 'Nuevo nivel';
    if (editing) {
        name.value = elem.getAttribute('data-name');
        url += 'edit/' + elem.getAttribute('data-id');
        method = 'PUT';
        createButton.innerHTML = 'Editar nivel';
    } else {
        name.value = '';
        url += 'new';
        createButton.innerHTML = 'Nuevo nivel';
    }
    panel.className = '';
    actions.className = '';
    createButton.onclick = function(e) {
        if (name.value.length < 3) {
            displayModalAlert('El nombre del nivel debe tener al menos 3 caracteres', 'danger');
        } else {
            hideModalAlert();
            
            $.ajax({
                url: baseUrl + url,
                data: "nombre=" + name.value + '&' + getCsrfToken(),
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

function deleteLevel(elem) {
    var panel = document.getElementById('delete-panel'),
        actions = document.getElementById('delete-actions'),
        deleteButton = document.getElementById('delete-level'),
        cancelButton = document.getElementById('cancel-delete');

    modalBox.modal('show');
    modalTitle.innerHTML = 'Borrar nivel';
    panel.className = '';
    actions.className = '';
    panel.innerHTML = '¿Deseas borrar el nivel "<b>' + elem.getAttribute('data-name') + '</b>"?';
    deleteButton.onclick = function(e) {
        hideModalAlert();
        $.ajax({
            url: baseUrl + 'levels/delete/' + elem.getAttribute('data-id'),
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

function showSubjectEditor(elem) {
    var panel = document.getElementById('editor-panel'),
        actions = document.getElementById('editor-actions'),
        name = document.getElementById('nombre'),
        level = $('#nivel'),
        createButton = document.getElementById('create-subject'),
        cancelButton = document.getElementById('cancel-create'),
        editing = elem !== undefined,
        url = 'subjects/',
        method = 'POST';
        
    $level = level.selectize({
        valueField: 'id',
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

    modalBox.modal('show');
    modalTitle.innerHTML = (editing) ? 'Editar asignatura' : 'Nueva asignatura';
    if (editing) {
        name.value = elem.getAttribute('data-name');
        url += 'edit/' + elem.getAttribute('data-id');
        method = 'PUT';
        createButton.innerHTML = 'Editar asignatura';
    } else {
        name.value = '';
        url += 'new';
        createButton.innerHTML = 'Nueva asignatura';
    }
    panel.className = '';
    actions.className = '';
    createButton.onclick = function(e) {
        if (name.value.length < 3) {
            displayModalAlert('El nombre de la asignatura debe tener al menos 3 caracteres', 'danger');
        } else {
            hideModalAlert();
            
            $.ajax({
                url: baseUrl + url,
                data: "nombre=" + name.value + '&nivel=' + level.val() +  '&' + getCsrfToken(),
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

function deleteSubject(elem) {
    var panel = document.getElementById('delete-panel'),
        actions = document.getElementById('delete-actions'),
        deleteButton = document.getElementById('delete-subject'),
        cancelButton = document.getElementById('cancel-delete');

    modalBox.modal('show');
    modalTitle.innerHTML = 'Borrar asignatura';
    panel.className = '';
    actions.className = '';
    panel.innerHTML = '¿Deseas borrar la asignatura "<b>' + elem.getAttribute('data-name') + '</b>"?';
    deleteButton.onclick = function(e) {
        hideModalAlert();
        $.ajax({
            url: baseUrl + 'subjects/delete/' + elem.getAttribute('data-id'),
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

function showStudentEditor(elem) {
    var panel = document.getElementById('editor-panel'),
        actions = document.getElementById('editor-actions'),
        name = document.getElementById('nombre'),
        nie = document.getElementById('nie'),
        surname = document.getElementById('apellidos'),
        phone = document.getElementById('telefono'),
        createButton = document.getElementById('create-student'),
        cancelButton = document.getElementById('cancel-create'),
        editing = elem !== undefined,
        url = 'students/',
        method = 'POST';

    modalBox.modal('show');
    modalTitle.innerHTML = (editing) ? 'Editar alumno' : 'Nuevo alumno';
    if (editing) {
        name.value = elem.getAttribute('data-name');
        nie.value = elem.getAttribute('data-id');
        nie.disabled = true;
        surname.value = elem.getAttribute('data-surname');
        phone.value = elem.getAttribute('data-phone');
        url += 'edit/' + elem.getAttribute('data-id');
        method = 'PUT';
        createButton.innerHTML = 'Editar alumno';
    } else {
        name.value = '';
        url += 'new';
        createButton.innerHTML = 'Nuevo alumno';
    }
    panel.className = '';
    actions.className = '';
    createButton.onclick = function(e) {
        if (name.value.length < 3) {
            displayModalAlert('El nombre del alumno debe tener al menos 3 caracteres.', 'danger');
        } else if (nie.value.length < 1) {
            displayModalAlert('Debes rellenar el NIE.', 'danger');
        } else if (phone.value.length > 0 && !/[0-9]{9}/gi.test(phone.value)) {
            displayModalAlert('El teléfono no es válido.', 'danger');
        } else {
            hideModalAlert();
            
            $.ajax({
                url: baseUrl + url,
                data: "nombre=" + name.value + '&apellidos=' + surname.value + '&telefono='
                    + phone.value + '&nie=' + nie.value + '&' + getCsrfToken(),
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

function deleteStudent(elem) {
    var panel = document.getElementById('delete-panel'),
        actions = document.getElementById('delete-actions'),
        deleteButton = document.getElementById('delete-student'),
        cancelButton = document.getElementById('cancel-delete');

    modalBox.modal('show');
    modalTitle.innerHTML = 'Borrar alumno';
    panel.className = '';
    actions.className = '';
    panel.innerHTML = '¿Deseas borrar el alumno "<b>' + elem.getAttribute('data-name') + '</b>"?';
    deleteButton.onclick = function(e) {
        hideModalAlert();
        $.ajax({
            url: baseUrl + 'students/delete/' + elem.getAttribute('data-id'),
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