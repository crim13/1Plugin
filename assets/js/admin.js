(function() {
  var config = window.OnePluginLightAdmin || {};
  var presets = config.presets || {};
  var allChoices = config.stickyItemChoices || {};
  var get = function(id) { return document.getElementById(id); };
  var sanitizePhone = function(value) { return value.replace(/\s+/g, ''); };
  var escapeHtml = function(value) {
    return String(value || '').replace(/[&<>"']/g, function(match) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[match];
    });
  };
  var previewRoot = get('oneplugin-preview-root');
  var setupMedia = function() {
    var openMedia = function(input, target, title, buttonText, sizeName, emptyText) {
      if (input.value) {
        input.value = '';
        target.innerHTML = '<span>' + emptyText + '</span>';
        return;
      }
      var frame = wp.media({ title: title, button: { text: buttonText }, library: { type: 'image' }, multiple: false });
      frame.on('select', function() {
        var attachment = frame.state().get('selection').first().toJSON();
        input.value = attachment.id || '';
        var url = attachment.sizes && attachment.sizes[sizeName] ? attachment.sizes[sizeName].url : attachment.url;
        target.innerHTML = url ? '<img src="' + url + '" alt="" style="max-width:100%;max-height:100%;" />' : '<span>' + emptyText + '</span>';
      });
      frame.open();
    };
    if (typeof wp === 'undefined' || !wp.media) { return; }
    document.querySelectorAll('[data-media-preview]').forEach(function(preview) {
      var input = get(preview.getAttribute('data-media-preview') || '');
      if (!input) { return; }
      preview.addEventListener('click', function(e) {
        e.preventDefault();
        var size = input.getAttribute('data-media-size') || 'medium';
        var empty = input.getAttribute('data-media-empty') || 'Image';
        openMedia(input, preview, 'Select image', 'Use image', size, empty);
      });
    });
  };
  var setupBrandAssetFilters = function() {
    document.querySelectorAll('[data-brand-white-toggle]').forEach(function(toggle) {
      var key = toggle.getAttribute('data-brand-white-toggle') || '';
      var preview = key ? document.querySelector('[data-brand-white-preview="' + key + '"]') : null;
      if (!preview) { return; }
      var sync = function() {
        preview.classList.toggle('is-white-preview', !!toggle.checked);
      };
      toggle.addEventListener('change', sync);
      sync();
    });
  };
  var setupSaveShortcut = function() {
    document.addEventListener('keydown', function(e) {
      if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
        e.preventDefault();
        var btn = get('oneplugin2-save-button');
        if (btn) { btn.click(); }
      }
    });
  };
  var setupShortcodeCopy = function() {
    var buttons = document.querySelectorAll('.oneplugin-shortcode-copy');
    buttons.forEach(function(btn) {
      btn.addEventListener('click', function() {
        var shortcode = btn.getAttribute('data-shortcode') || '';
        if (!shortcode || !navigator.clipboard) { return; }
        navigator.clipboard.writeText(shortcode).then(function() {
          btn.classList.add('is-copied');
          var original = btn.textContent;
          btn.textContent = 'Copied';
          window.setTimeout(function() {
            btn.classList.remove('is-copied');
            btn.textContent = original;
          }, 900);
        });
      });
    });
  };
  var setupTabs = function() {
    var containers = Array.prototype.slice.call(document.querySelectorAll('[data-tabs]'));
    var persistActiveTabs = function() {
      containers.forEach(function(container, index) {
        var activeTab = Array.prototype.filter.call(container.querySelectorAll('[data-tab-trigger]'), function(tab) {
          return tab.closest('[data-tabs]') === container && tab.classList.contains('is-active');
        })[0];
        if (!activeTab) { return; }
        try {
          localStorage.setItem('onepluginActiveTab:' + index, activeTab.getAttribute('data-tab-trigger') || '');
        } catch (err) {}
      });
    };
    containers.forEach(function(container, containerIndex) {
      var tabs = Array.prototype.filter.call(container.querySelectorAll('[data-tab-trigger]'), function(tab) {
        return tab.closest('[data-tabs]') === container;
      });
      var panels = Array.prototype.filter.call(container.querySelectorAll('[data-tab-panel]'), function(panel) {
        return panel.closest('[data-tabs]') === container;
      });
      var storageKey = 'onepluginActiveTab:' + containerIndex;
      var activateTab = function(tab, persist) {
        var target = tab.getAttribute('data-tab-trigger') || '';
        tabs.forEach(function(item) {
          var active = item === tab;
          item.classList.toggle('is-active', active);
          item.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panels.forEach(function(panel) {
          var active = panel.getAttribute('data-tab-panel') === target;
          panel.classList.toggle('is-active', active);
          panel.hidden = !active;
        });
        if (persist) {
          try {
            localStorage.setItem(storageKey, target);
          } catch (err) {}
        }
      };
      try {
        var savedTarget = localStorage.getItem(storageKey);
        var savedTab = savedTarget ? tabs.filter(function(tab) { return tab.getAttribute('data-tab-trigger') === savedTarget; })[0] : null;
        if (savedTab) { activateTab(savedTab, false); }
      } catch (err) {}
      tabs.forEach(function(tab) {
        tab.addEventListener('click', function(e) {
          if (e.target && e.target.matches && e.target.matches('input, select, textarea')) { return; }
          activateTab(tab, true);
        });
      });
    });
    var form = document.querySelector('.oneplugin-admin-form');
    if (form) {
      form.addEventListener('submit', persistActiveTabs);
    }
  };
  var setupDimensionControls = function() {
    document.querySelectorAll('[data-dimension-field]').forEach(function(wrap) {
      var valueInput = wrap.querySelector('[data-dimension-value]');
      var numberInput = wrap.querySelector('[data-dimension-number]');
      var unitSelect = wrap.querySelector('[data-dimension-unit]');
      if (!valueInput || !numberInput || !unitSelect) { return; }
      var sync = function() {
        var number = numberInput.value;
        if (number === '') { return; }
        number = String(Math.max(0, Math.round(Number(number) || 0)));
        numberInput.value = number;
        valueInput.value = number + unitSelect.value;
        valueInput.dispatchEvent(new Event('input', { bubbles: true }));
        valueInput.dispatchEvent(new Event('change', { bubbles: true }));
      };
      numberInput.addEventListener('keydown', function(e) {
        if (e.key !== 'ArrowUp' && e.key !== 'ArrowDown') { return; }
        e.preventDefault();
        var direction = e.key === 'ArrowUp' ? 1 : -1;
        var step = e.shiftKey ? 5 : 1;
        var current = Math.round(Number(numberInput.value) || 0);
        numberInput.value = String(Math.max(0, current + (direction * step)));
        sync();
      });
      numberInput.addEventListener('input', sync);
      numberInput.addEventListener('change', sync);
      unitSelect.addEventListener('change', sync);
    });
  };
  var setupSocialLinks = function() {
    var addSelect = get('oneplugin-add-social-link');
    if (!addSelect) { return; }
    addSelect.addEventListener('change', function() {
      var key = addSelect.value;
      if (!key) { return; }
      var field = document.querySelector('[data-social-field="' + key + '"]');
      var input = get(key);
      if (field) { field.hidden = false; }
      if (input) { input.focus(); }
      var selectedOption = addSelect.querySelector('option[value="' + key + '"]');
      if (selectedOption) { selectedOption.hidden = true; }
      addSelect.value = '';
    });
  };
  var setupHeaderImport = function() {
    var form = document.querySelector('.oneplugin-hero-import-form');
    var button = get('oneplugin-light-import-button');
    var input = get('oneplugin-light-import-file');
    if (!form || !button || !input) { return; }
    var submitIfJson = function(file) {
      if (!file) { return; }
      if (!/\.json$/i.test(file.name || '')) { return; }
      if (typeof DataTransfer === 'undefined') {
        input.click();
        return;
      }
      var transfer = new DataTransfer();
      transfer.items.add(file);
      input.files = transfer.files;
      form.submit();
    };
    button.addEventListener('click', function(e) {
      e.preventDefault();
      input.click();
    });
    input.addEventListener('change', function() {
      if (input.files && input.files[0]) { form.submit(); }
    });
    ['dragenter','dragover'].forEach(function(eventName) {
      button.addEventListener(eventName, function(e) {
        e.preventDefault();
        button.classList.add('is-dragover');
      });
    });
    ['dragleave','drop'].forEach(function(eventName) {
      button.addEventListener(eventName, function(e) {
        e.preventDefault();
        button.classList.remove('is-dragover');
      });
    });
    button.addEventListener('drop', function(e) {
      var file = e.dataTransfer && e.dataTransfer.files ? e.dataTransfer.files[0] : null;
      submitIfJson(file);
    });
  };
  var setupCollapsibles = function() {
    document.querySelectorAll('[data-collapsible-toggle]').forEach(function(toggle) {
      toggle.addEventListener('click', function() {
        var card = toggle.closest('.oneplugin-collapsible');
        var content = card ? card.querySelector('[data-collapsible-content]') : null;
        if (!card || !content) { return; }
        var isOpen = card.classList.contains('is-open');
        card.classList.toggle('is-open', !isOpen);
        content.hidden = isOpen;
        toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
      });
    });
  };
  var setupDeveloperMode = function() {
    document.querySelectorAll('[data-dev-mode]').forEach(function(wrapper) {
      var locked = wrapper.querySelector('[data-dev-mode-locked]');
      var content = wrapper.querySelector('[data-dev-mode-content]');
      var unlock = wrapper.querySelector('[data-dev-mode-unlock]');
      if (!locked || !content || !unlock) { return; }

      unlock.addEventListener('click', function() {
        var confirmed = window.confirm('Custom code can break the site if invalid code is saved. Unlock editors for this session?');
        if (!confirmed) { return; }

        locked.hidden = true;
        content.hidden = false;
      });
    });
  };
  var setupMasonryGalleryLayout = function() {
    var checkbox = get('masonry_gallery_enabled');
    var layoutWrap = get('oneplugin-masonry-gallery-layout-wrap');
    if (!checkbox || !layoutWrap) { return; }
    var sync = function() { layoutWrap.hidden = !checkbox.checked; };
    checkbox.addEventListener('change', sync);
    sync();
  };
  var setupStyleFormidableAccent = function() {
    var checkbox = get('style_formidable');
    var accentWrap = get('oneplugin-formidable-accent-wrap');
    if (!checkbox || !accentWrap) { return; }
    var sync = function() { accentWrap.hidden = !checkbox.checked; };
    checkbox.addEventListener('change', sync);
    sync();
  };
  var setupHeaderOptionDependencies = function() {
    var bind = function(toggleId, targetId) {
      var checkbox = get(toggleId);
      var target = get(targetId);
      if (!checkbox || !target) { return; }
      var sync = function() { target.hidden = !checkbox.checked; };
      checkbox.addEventListener('change', sync);
      sync();
    };

    bind('header_glass_effect', 'oneplugin-header-glass-mobile-wrap');
    bind('transparent_fixed_header', 'oneplugin-transparent-header-options-wrap');
    bind('header_animation_enabled', 'oneplugin-header-animation-options-wrap');
  };
  var normalizeHex = function(value) {
    value = String(value || '').trim();
    if (/^#[0-9a-fA-F]{6}$/.test(value)) { return value.toLowerCase(); }
    if (/^#[0-9a-fA-F]{3}$/.test(value)) {
      return ('#' + value.charAt(1) + value.charAt(1) + value.charAt(2) + value.charAt(2) + value.charAt(3) + value.charAt(3)).toLowerCase();
    }
    return '';
  };
  var rgbToHex = function(value) {
    var match = String(value || '').trim().match(/^rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})/i);
    if (!match) { return ''; }
    var parts = [match[1], match[2], match[3]].map(function(part) {
      var number = Math.max(0, Math.min(255, parseInt(part, 10) || 0));
      return number.toString(16).padStart(2, '0');
    });
    return '#' + parts.join('');
  };
  var getCssVariableParts = function(value) {
    value = String(value || '').trim();
    if (/^--[a-zA-Z0-9_-]+$/.test(value)) {
      return { name: value, fallback: '' };
    }
    var match = value.match(/^var\(\s*(--[a-zA-Z0-9_-]+)(?:\s*,\s*(.+))?\s*\)$/);
    return match ? { name: match[1], fallback: match[2] || '' } : { name: '', fallback: '' };
  };
  var getCssVariableName = function(value) {
    return getCssVariableParts(value).name;
  };
  var resolveCssVariableColor = function(variable) {
    if (!variable) { return ''; }
    var value = getComputedStyle(document.documentElement).getPropertyValue(variable).trim();
    return normalizeHex(value) || rgbToHex(value) || value;
  };
  var getSwatchBackground = function(value) {
    value = String(value || '').trim();
    var variable = getCssVariableParts(value);
    if (variable.name) {
      var resolved = resolveCssVariableColor(variable.name) || normalizeHex(variable.fallback) || rgbToHex(variable.fallback) || variable.fallback;
      return 'var(' + variable.name + ', ' + (resolved || '#000000') + ')';
    }
    return normalizeHex(value) || value || '#000000';
  };
  var setPickerValue = function(picker, value) {
    if (!picker) { return; }
    var hex = normalizeHex(value);
    if (!hex) {
      hex = rgbToHex(value);
    }
    if (!hex) {
      var variable = getCssVariableParts(value);
      if (variable.name) {
        hex = normalizeHex(resolveCssVariableColor(variable.name)) || normalizeHex(variable.fallback) || rgbToHex(variable.fallback);
      }
    }
    if (hex) { picker.value = hex; }
  };
  var updateColorSwatch = function(swatch, value) {
    if (!swatch) { return; }
    swatch.style.setProperty('--oneplugin-admin-swatch', getSwatchBackground(value));
  };
  var refreshAdminColorFields = function() {
    document.querySelectorAll('[data-color-field]').forEach(function(field) {
      field.dispatchEvent(new CustomEvent('oneplugin:refresh-color'));
    });
  };
  var closeColorPopups = function() {
    document.querySelectorAll('.oneplugin-color-popover').forEach(function(popover) {
      popover.remove();
    });
  };
  var buildColorPopover = function(field, value, sourceInput, swatch) {
    closeColorPopups();
    var palette = Array.isArray(config.projectPalette) ? config.projectPalette : Object.keys(config.projectPalette || {}).map(function(key) { return config.projectPalette[key]; });
    var popover = document.createElement('div');
    popover.className = 'oneplugin-color-popover';
    var grid = document.createElement('div');
    grid.className = 'oneplugin-color-popover__grid';
    palette.forEach(function(color) {
      if (!color || !color.variable) { return; }
      var button = document.createElement('button');
      button.type = 'button';
      button.className = 'oneplugin-color-popover__swatch';
      button.style.setProperty('--oneplugin-admin-swatch', color.value || '#000000');
      button.title = color.variable;
      button.setAttribute('aria-label', color.variable);
      button.addEventListener('click', function() {
        value.value = 'var(' + color.variable + ')';
        sourceInput.value = value.value;
        sourceInput.readOnly = true;
        value.dispatchEvent(new Event('input', { bubbles: true }));
        closeColorPopups();
      });
      grid.appendChild(button);
    });
    var reset = document.createElement('button');
    reset.type = 'button';
    reset.className = 'button button-secondary oneplugin-color-popover__reset';
    reset.textContent = 'Custom';
    reset.addEventListener('click', function() {
      var customValue = getCssVariableName(value.value) ? '#000000' : value.value;
      value.value = customValue || '#000000';
      sourceInput.value = value.value;
      sourceInput.readOnly = false;
      value.dispatchEvent(new Event('input', { bubbles: true }));
      sourceInput.focus();
      closeColorPopups();
    });
    popover.appendChild(grid);
    popover.appendChild(reset);
    field.appendChild(popover);
  };
  var setupColorFields = function() {
    document.querySelectorAll('[data-color-field]').forEach(function(field) {
      if (field.__onePluginColorReady) { return; }
      field.__onePluginColorReady = true;
      var picker = field.querySelector('[data-color-picker]');
      var swatch = field.querySelector('[data-color-swatch]');
      var value = field.querySelector('[data-color-value]');
      var sourceInput = field.querySelector('[data-color-mode]');
      if (!picker || !swatch || !value || !sourceInput) { return; }
      var refresh = function() {
        var variableName = getCssVariableName(value.value);
        var isCustom = !variableName;
        var nextSource = isCustom ? value.value : 'var(' + variableName + ')';
        if (sourceInput.value !== nextSource) {
          sourceInput.value = nextSource;
        }
        sourceInput.readOnly = !isCustom;
        updateColorSwatch(swatch, value.value);
        setPickerValue(picker, value.value);
      };
      swatch.addEventListener('click', function(e) {
        e.preventDefault();
        buildColorPopover(field, value, sourceInput, swatch);
      });
      picker.addEventListener('input', function() {
        value.value = picker.value;
        sourceInput.value = picker.value;
        value.dispatchEvent(new Event('input', { bubbles: true }));
        refresh();
      });
      value.addEventListener('input', refresh);
      value.addEventListener('change', refresh);
      sourceInput.addEventListener('input', function() {
        var sourceValue = sourceInput.value.trim();
        value.value = sourceValue;
        value.dispatchEvent(new Event('input', { bubbles: true }));
        refresh();
      });
      field.addEventListener('oneplugin:refresh-color', refresh);
      refresh();
    });
    document.addEventListener('click', function(e) {
      if (!e.target.closest('.oneplugin-color-field')) { closeColorPopups(); }
    });
  };
  var setupProjectPalette = function() {
    var palette = document.querySelector('[data-project-palette]');
    if (!palette) { return; }
    var grid = palette.querySelector('.oneplugin-palette-fields__grid');
    var addButton = palette.querySelector('[data-palette-add]');
    var optionKey = palette.getAttribute('data-option-key') || 'oneplugin_light_site_tools_settings';
    if (!grid) { return; }
    var makeInput = function(type, name, value, className, attrs) {
      var input = document.createElement('input');
      input.type = type;
      input.name = name;
      input.value = value || '';
      input.className = className || '';
      Object.keys(attrs || {}).forEach(function(key) {
        input.setAttribute(key, attrs[key]);
      });
      return input;
    };
    var syncRow = function(row) {
      var color = row.querySelector('[data-palette-color]');
      var value = row.querySelector('[data-palette-value]');
      var swatch = row.querySelector('[data-palette-swatch]');
      var variable = row.querySelector('.oneplugin-palette-field__variable');
      var copy = row.querySelector('[data-palette-copy]');
      if (!color || !value || !swatch) { return; }
      var syncVariable = function() {
        if (!variable || !/^--[a-zA-Z0-9_-]+$/.test(variable.value)) { return; }
        document.documentElement.style.setProperty(variable.value, value.value || '#000000');
      };
      var refresh = function() {
        syncVariable();
        updateColorSwatch(swatch, value.value);
        setPickerValue(color, value.value);
      };
      swatch.addEventListener('click', function(e) {
        e.preventDefault();
        color.click();
      });
      color.addEventListener('input', function() {
        value.value = color.value;
        value.dispatchEvent(new Event('input', { bubbles: true }));
        refresh();
        refreshAdminColorFields();
      });
      value.addEventListener('input', function() {
        refresh();
        refreshAdminColorFields();
      });
      if (variable) {
        variable.addEventListener('input', function() {
          refresh();
          refreshAdminColorFields();
        });
      }
      if (copy && variable) {
        copy.addEventListener('click', function(e) {
          e.preventDefault();
          var variableValue = variable.value || copy.getAttribute('data-palette-copy') || '';
          if (!variableValue) { return; }
          if (navigator.clipboard) {
            navigator.clipboard.writeText(variableValue);
          }
          copy.classList.add('is-copied');
          window.setTimeout(function() { copy.classList.remove('is-copied'); }, 900);
        });
      }
      refresh();
    };
    var buildRow = function() {
      var key = 'custom_' + Date.now().toString(36);
      var row = document.createElement('div');
      row.className = 'oneplugin-palette-field';
      row.setAttribute('data-palette-row', '');

      var picker = document.createElement('input');
      picker.type = 'color';
      picker.value = '#000000';
      picker.className = 'oneplugin-palette-field__picker';
      picker.setAttribute('data-palette-color', '');
      picker.setAttribute('tabindex', '-1');
      picker.setAttribute('aria-hidden', 'true');
      picker.setAttribute('aria-label', 'Color picker');

      var swatch = document.createElement('button');
      swatch.type = 'button';
      swatch.className = 'oneplugin-palette-field__swatch';
      swatch.setAttribute('data-palette-swatch', '');
      swatch.setAttribute('aria-label', 'Choose color');

      var top = document.createElement('div');
      top.className = 'oneplugin-palette-field__top';
      top.appendChild(swatch);
      top.appendChild(makeInput('text', optionKey + '[project_palette][' + key + '][name]', 'New color', 'oneplugin-palette-field__name', { 'aria-label': 'Color name' }));
      var variableName = '--1pcv-custom-' + Date.now().toString(36);
      var copyButton = document.createElement('button');
      copyButton.type = 'button';
      copyButton.className = 'oneplugin-palette-copy';
      copyButton.setAttribute('data-palette-copy', variableName);
      copyButton.setAttribute('title', variableName);
      copyButton.setAttribute('aria-label', 'Copy ' + variableName);
      copyButton.textContent = 'Copy';

      var remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'button-link-delete oneplugin-palette-remove';
      remove.setAttribute('data-palette-remove', '');
      remove.setAttribute('aria-label', 'Remove color');
      remove.innerHTML = '&times;';

      var meta = document.createElement('div');
      meta.className = 'oneplugin-palette-field__meta';
      meta.appendChild(makeInput('text', optionKey + '[project_palette][' + key + '][value]', '#000000', 'oneplugin-palette-field__value', { 'data-palette-value': '', 'aria-label': 'Color value' }));
      meta.appendChild(makeInput('hidden', optionKey + '[project_palette][' + key + '][variable]', variableName, 'oneplugin-palette-field__variable', { 'aria-label': 'CSS variable' }));

      row.appendChild(picker);
      row.appendChild(top);
      row.appendChild(meta);
      row.appendChild(copyButton);
      row.appendChild(remove);
      syncRow(row);
      return row;
    };
    grid.querySelectorAll('[data-palette-row]').forEach(syncRow);
    if (addButton) {
      addButton.addEventListener('click', function(e) {
        e.preventDefault();
        var row = buildRow();
        grid.appendChild(row);
        var nameInput = row.querySelector('.oneplugin-palette-field__name');
        if (nameInput) {
          nameInput.focus();
          nameInput.select();
        }
      });
    }
    grid.addEventListener('click', function(e) {
      var button = e.target.closest('[data-palette-remove]');
      if (!button) { return; }
      e.preventDefault();
      var row = button.closest('[data-palette-row]');
      if (row) { row.remove(); }
    });
  };
  var refreshStickyItemSelects = function() {
    var selects = ['sticky_item_1', 'sticky_item_2', 'sticky_item_3'].map(get).filter(Boolean);
    var legacySelect = get('sticky_social_media');
    if (!selects.length && !legacySelect) { return; }
    var choices = [{ value: 'none', label: allChoices.none }];
    Object.keys(presets).forEach(function(key) {
      if (key === 'none') { return; }
      if (key === 'custom') {
        choices.push({ value: key, label: allChoices[key] || 'Custom' });
        return;
      }
      var field = presets[key].linkField ? get(presets[key].linkField) : null;
      if (field && field.value) { choices.push({ value: key, label: allChoices[key] }); }
    });
    selects.forEach(function(select) {
      var current = select.value;
      select.innerHTML = '';
      choices.forEach(function(choice) {
        var option = document.createElement('option');
        option.value = choice.value;
        option.textContent = choice.label || choice.value;
        select.appendChild(option);
      });
      select.value = choices.some(function(choice) { return choice.value === current; }) ? current : 'none';
    });
    if (legacySelect) {
      var legacyCurrent = legacySelect.value;
      legacySelect.innerHTML = '';
      choices.forEach(function(choice) {
        if (['none','facebook','instagram','linkedin','youtube','x','reddit','booking','website'].indexOf(choice.value) === -1) { return; }
        var option = document.createElement('option');
        option.value = choice.value;
        option.textContent = choice.label || choice.value;
        legacySelect.appendChild(option);
      });
      legacySelect.value = choices.some(function(choice) { return choice.value === legacyCurrent; }) ? legacyCurrent : 'none';
    }
  };
  var syncStickyCustomFields = function() {
    ['1', '2', '3'].forEach(function(position) {
      var select = get('sticky_item_' + position);
      var wrap = document.querySelector('[data-sticky-custom-position="' + position + '"]');
      if (!select || !wrap) { return; }
      wrap.hidden = select.value !== 'custom';
    });
  };
  var rebuild = function() {
    if (!previewRoot) { return; }
    refreshStickyItemSelects();
    syncStickyCustomFields();
    var bgEl = get('sticky_bg_color');
    var iconEl = get('sticky_icon_color');
    var textEl = get('sticky_text_color');
    var widthEl = get('sticky_width');
    var radiusEl = get('sticky_radius_top');
    var fontSizeEl = get('sticky_font_size');
    var iconSizeEl = get('sticky_icon_size');
    var bg = bgEl ? bgEl.value : '#0f0f0f';
    var iconColor = iconEl ? iconEl.value : '#ffffff';
    var textColor = textEl ? textEl.value : '#ffffff';
    var width = widthEl && widthEl.value ? widthEl.value : '90%';
    var radius = radiusEl && radiusEl.value ? radiusEl.value : '20px';
    var fontSize = fontSizeEl && fontSizeEl.value ? fontSizeEl.value : '12px';
    var iconSize = iconSizeEl && iconSizeEl.value ? iconSizeEl.value : '18px';
    var items = [];
    ['1', '2', '3'].forEach(function(position) {
      var key = 'sticky_item_' + position;
      var select = get(key);
      var itemKey = select ? select.value : 'none';
      var preset = presets[itemKey] || presets.none;
      if (itemKey === 'custom') {
        var customLink = get('sticky_custom_' + position + '_link');
        var customText = get('sticky_custom_' + position + '_text');
        var customIcon = get('sticky_custom_' + position + '_icon');
        var customLinkValue = customLink ? customLink.value : '';
        if (!customLinkValue) { return; }
        items.push({
          link: customLinkValue,
          text: customText && customText.value ? customText.value : 'Link',
          icon: customIcon && customIcon.value ? customIcon.value : 'fa-solid fa-link'
        });
        return;
      }
      if (!preset || !preset.linkField) { return; }
      var field = get(preset.linkField);
      var value = field ? field.value : '';
      if (!value || !preset.icon) { return; }
      var link = value;
      if (preset.linkPrefix === 'tel:') { link = 'tel:' + sanitizePhone(value); }
      else if (preset.linkPrefix === 'mailto:') { link = 'mailto:' + value; }
      items.push({ link: link, text: preset.text, icon: preset.icon });
    });
    var bar = get('oneplugin-preview-bar');
    if (!bar) { return; }
    bar.style.background = bg;
    bar.style.width = width;
    bar.style.maxWidth = '100%';
    bar.style.margin = '0 auto';
    bar.style.borderRadius = radius + ' ' + radius + ' 0 0';
    var inner = '';
    for (var j = 0; j < items.length; j++) {
      inner += '<a href="' + escapeHtml(items[j].link) + '" class="oneplugin-preview-item" title="' + escapeHtml(items[j].link) + '" style="text-decoration:none;display:flex;flex-direction:column;gap:6px;align-items:center;justify-content:center;min-width:64px;">';
      inner += '<i class="' + escapeHtml(items[j].icon) + '" aria-hidden="true" style="font-size:' + escapeHtml(iconSize) + ';line-height:1;color:' + iconColor + ';"></i>';
      inner += '<span style="font-size:' + escapeHtml(fontSize) + ';line-height:1;color:' + textColor + ' ;">' + escapeHtml(items[j].text) + '</span>';
      inner += '</a>';
    }
    bar.innerHTML = '<div style="display:flex; gap:10px; justify-content:space-around; align-items:center;">' + inner + '</div>';
  };
  var fields = ['phone_primary','email','website','facebook_url','instagram_url','linkedin_url','youtube_url','x_url','reddit_url','booking_url','sticky_bg_color','sticky_icon_color','sticky_text_color','sticky_width','sticky_radius_top','sticky_font_size','sticky_icon_size','sticky_social_media','sticky_item_1','sticky_item_2','sticky_item_3','sticky_custom_1_text','sticky_custom_1_link','sticky_custom_1_icon','sticky_custom_2_text','sticky_custom_2_link','sticky_custom_2_icon','sticky_custom_3_text','sticky_custom_3_link','sticky_custom_3_icon','masonry_gallery_enabled','masonry_gallery_layout'];
  for (var k = 0; k < fields.length; k++) {
    var el = get(fields[k]);
    if (!el) { continue; }
    el.addEventListener('input', rebuild);
    el.addEventListener('change', rebuild);
  }
  setupMedia();
  setupBrandAssetFilters();
  setupSaveShortcut();
  setupShortcodeCopy();
  setupTabs();
  setupDimensionControls();
  setupSocialLinks();
  setupHeaderImport();
  setupCollapsibles();
  setupDeveloperMode();
  setupMasonryGalleryLayout();
  setupStyleFormidableAccent();
  setupHeaderOptionDependencies();
  setupColorFields();
  setupProjectPalette();
  rebuild();
})();
