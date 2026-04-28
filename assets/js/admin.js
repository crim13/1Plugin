(function() {
  var config = window.OnePluginLightAdmin || {};
  var presets = config.presets || {};
  var allChoices = config.socialMediaChoices || {};
  var get = function(id) { return document.getElementById(id); };
  var sanitizePhone = function(value) { return value.replace(/\s+/g, ''); };
  var previewRoot = get('oneplugin-preview-root');
  var setupMedia = function() {
    var hiddenInput = get('site_icon_id');
    var preview = get('oneplugin-site-icon-preview');
    var logoInput = get('site_logo_id');
    var logoPreview = get('oneplugin-site-logo-preview');
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
    if (hiddenInput && preview && typeof wp !== 'undefined' && wp.media) {
      preview.addEventListener('click', function(e) {
        e.preventDefault();
        openMedia(hiddenInput, preview, 'Select favicon', 'Use favicon', 'thumbnail', 'Fav');
      });
    }
    if (logoInput && logoPreview && typeof wp !== 'undefined' && wp.media) {
      logoPreview.addEventListener('click', function(e) {
        e.preventDefault();
        openMedia(logoInput, logoPreview, 'Select logo', 'Use logo', 'medium', 'Logo');
      });
    }
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
    document.querySelectorAll('[data-tabs]').forEach(function(container) {
      var tabs = container.querySelectorAll('[data-tab-trigger]');
      var panels = container.querySelectorAll('[data-tab-panel]');
      tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
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
        });
      });
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
  var refreshSocialSelect = function() {
    var select = get('sticky_social_media');
    if (!select) { return; }
    var current = select.value;
    var choices = [{ value: 'none', label: allChoices.none }];
    Object.keys(presets).forEach(function(key) {
      if (key === 'none') { return; }
      var field = presets[key].linkField ? get(presets[key].linkField) : null;
      if (field && field.value) { choices.push({ value: key, label: allChoices[key] }); }
    });
    select.innerHTML = '';
    choices.forEach(function(choice) {
      var option = document.createElement('option');
      option.value = choice.value;
      option.textContent = choice.label;
      select.appendChild(option);
    });
    var hasCurrent = choices.some(function(choice) { return choice.value === current; });
    var preferred = 'none';
    if (get('instagram_url') && get('instagram_url').value) { preferred = 'instagram'; }
    else if (get('facebook_url') && get('facebook_url').value) { preferred = 'facebook'; }
    select.value = hasCurrent ? current : preferred;
  };
  var rebuild = function() {
    if (!previewRoot) { return; }
    refreshSocialSelect();
    var phoneEl = get('phone_primary');
    var emailEl = get('email');
    var bgEl = get('sticky_bg_color');
    var iconEl = get('sticky_icon_color');
    var textEl = get('sticky_text_color');
    var networkEl = get('sticky_social_media');
    var phone = phoneEl ? phoneEl.value : '';
    var email = emailEl ? emailEl.value : '';
    var bg = bgEl ? bgEl.value : '#0f0f0f';
    var iconColor = iconEl ? iconEl.value : '#ffffff';
    var textColor = textEl ? textEl.value : '#ffffff';
    var network = networkEl ? networkEl.value : 'instagram';
    var preset = presets[network] || presets.instagram;
    var socialField = preset.linkField ? get(preset.linkField) : null;
    var socialLink = socialField ? socialField.value : '';
    var items = [];
    if (phone) { items.push({ link: 'tel:' + sanitizePhone(phone), text: 'Ring', icon: 'fa-solid fa-phone' }); }
    if (email) { items.push({ link: 'mailto:' + email, text: 'Maila', icon: 'fa-solid fa-envelope' }); }
    if (socialLink && preset.icon) { items.push({ link: socialLink, text: preset.text, icon: preset.icon }); }
    var links = get('oneplugin-preview-links');
    if (links) {
      var linksHtml = '<strong>Links used</strong>';
      for (var i = 0; i < items.length; i++) { linksHtml += '<div><code>' + items[i].link + '</code></div>'; }
      links.innerHTML = linksHtml;
    }
    var bar = get('oneplugin-preview-bar');
    if (!bar) { return; }
    bar.style.background = bg;
    var inner = '';
    for (var j = 0; j < items.length; j++) {
      inner += '<a href="' + items[j].link + '" class="oneplugin-preview-item" style="text-decoration:none;display:flex;flex-direction:column;gap:6px;align-items:center;justify-content:center;min-width:64px;">';
      inner += '<i class="' + items[j].icon + '" aria-hidden="true" style="font-size:18px;line-height:1;color:' + iconColor + ';"></i>';
      inner += '<span style="font-size:12px;line-height:1;color:' + textColor + ' ;">' + items[j].text + '</span>';
      inner += '</a>';
    }
    bar.innerHTML = '<div style="display:flex; gap:10px; justify-content:space-around; align-items:center;">' + inner + '</div>';
  };
  var fields = ['phone_primary','email','website','facebook_url','instagram_url','linkedin_url','youtube_url','x_url','reddit_url','booking_url','sticky_bg_color','sticky_icon_color','sticky_text_color','sticky_social_media','masonry_gallery_enabled','masonry_gallery_layout'];
  for (var k = 0; k < fields.length; k++) {
    var el = get(fields[k]);
    if (!el) { continue; }
    el.addEventListener('input', rebuild);
    el.addEventListener('change', rebuild);
  }
  setupMedia();
  setupSaveShortcut();
  setupShortcodeCopy();
  setupTabs();
  setupSocialLinks();
  setupHeaderImport();
  setupCollapsibles();
  setupMasonryGalleryLayout();
  setupStyleFormidableAccent();
  rebuild();
})();
