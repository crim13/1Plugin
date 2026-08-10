(function() {
  var config = window.OnePluginLightFAQAdmin || {};

  var getSelectedFaqIds = function() {
    return Array.prototype.slice.call(document.querySelectorAll('tbody th.check-column input[type="checkbox"][name="post[]"]:checked'))
      .map(function(input) { return parseInt(input.value, 10) || 0; })
      .filter(Boolean);
  };

  var syncExportSelected = function() {
    var form = document.querySelector('[data-faq-export-selected-form]');
    var idsInput = document.querySelector('[data-faq-export-selected-ids]');
    var button = document.querySelector('[data-faq-export-selected]');
    if (!form || !idsInput || !button) { return; }

    var ids = getSelectedFaqIds();
    idsInput.value = ids.join(',');
    button.disabled = ids.length === 0;
  };

  var setupExportSelected = function() {
    var form = document.querySelector('[data-faq-export-selected-form]');
    if (!form) { return; }

    document.addEventListener('change', function(event) {
      if (event.target && event.target.matches('input[type="checkbox"]')) {
        window.setTimeout(syncExportSelected, 0);
      }
    });

    form.addEventListener('submit', function(event) {
      syncExportSelected();
      if (!getSelectedFaqIds().length) {
        event.preventDefault();
        window.alert(config.exportSelectedEmpty || 'Select at least one FAQ before exporting selected items.');
      }
    });

    syncExportSelected();
  };

  var setupImportButton = function() {
    var form = document.querySelector('[data-faq-import-form]');
    var input = form ? form.querySelector('[data-faq-import-file]') : null;
    var button = form ? form.querySelector('[data-faq-import-button]') : null;
    if (!form || !input || !button) { return; }

    var submitFile = function(file) {
      if (!file || !/\.json$/i.test(file.name || '')) { return; }
      if (typeof DataTransfer === 'undefined') {
        input.click();
        return;
      }

      var transfer = new DataTransfer();
      transfer.items.add(file);
      input.files = transfer.files;
      form.submit();
    };

    button.addEventListener('click', function(event) {
      event.preventDefault();
      input.click();
    });

    input.addEventListener('change', function() {
      if (input.files && input.files[0]) {
        form.submit();
      }
    });

    ['dragenter', 'dragover'].forEach(function(eventName) {
      button.addEventListener(eventName, function(event) {
        event.preventDefault();
        button.classList.add('is-dragover');
      });
    });

    ['dragleave', 'drop'].forEach(function(eventName) {
      button.addEventListener(eventName, function(event) {
        event.preventDefault();
        button.classList.remove('is-dragover');
      });
    });

    button.addEventListener('drop', function(event) {
      var file = event.dataTransfer && event.dataTransfer.files ? event.dataTransfer.files[0] : null;
      submitFile(file);
    });
  };

  var cleanQuickEditFields = function(row) {
    if (!row) { return; }

    var titleLabel = row.querySelector('label.inline-edit-title .title');
    if (titleLabel) {
      titleLabel.textContent = config.questionLabel || 'Question';
    }

    row.querySelectorAll('select[name="_status"] option[value="private"]').forEach(function(option) {
      option.remove();
    });
  };

  var setupQuickEdit = function() {
    if (typeof window.inlineEditPost === 'undefined' || !window.inlineEditPost.edit) { return; }

    var originalEdit = window.inlineEditPost.edit;
    window.inlineEditPost.edit = function(id) {
      originalEdit.apply(window.inlineEditPost, arguments);

      var postId = typeof id === 'object' ? parseInt(window.inlineEditPost.getId(id), 10) : parseInt(id, 10);
      if (!postId) { return; }

      var postRow = document.getElementById('post-' + postId);
      var editRow = document.getElementById('edit-' + postId);
      if (!postRow || !editRow) { return; }

      var data = postRow.querySelector('.oneplugin-faq-inline-data');
      var answer = editRow.querySelector('[data-oneplugin-faq-quick-answer]');
      if (data && answer) {
        answer.value = data.getAttribute('data-answer') || '';
      }

      cleanQuickEditFields(editRow);
    };
  };

  var setupQuickAdd = function() {
    var panel = document.querySelector('[data-faq-quick-add-panel]');
    var button = panel ? panel.querySelector('[data-faq-quick-add-toggle]') : null;
    var form = panel ? panel.querySelector('[data-faq-quick-add-form]') : null;
    if (!panel || !button || !form) { return; }

    button.addEventListener('click', function(event) {
      event.preventDefault();
      var isOpen = !form.hidden;
      form.hidden = isOpen;
      panel.classList.toggle('is-open', !isOpen);
      button.setAttribute('aria-expanded', isOpen ? 'false' : 'true');

      if (isOpen) { return; }
      var question = form.querySelector('input[name="oneplugin_light_faq_question"]');
      if (question) { question.focus(); }
    });
  };

  document.addEventListener('DOMContentLoaded', function() {
    setupExportSelected();
    setupImportButton();
    setupQuickAdd();
    setupQuickEdit();
    cleanQuickEditFields(document);
  });
})();
