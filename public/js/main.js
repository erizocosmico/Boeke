
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
        console.log(elem);
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
        if (name.value.length < 5) {
            displayModalAlert('El nombre del nivel debe tener al menos 5 caracteres', 'danger');
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
                }, 2000);
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