import React from 'react';
import metadataJson from '../../module.json';

const { addAction } = window?.vendor?.wp?.hooks || {};
const { ModuleContainer, StyleContainer, elementClassnames } = window?.divi?.module || {};
const { registerModule } = window?.divi?.moduleLibrary || {};

const STYLE_ATTRS = ['module', 'faqItem', 'faqQuestion', 'faqAnswer', 'faqIcon'];

const GROUP_CACHE = {
  promise: null,
  groups: [],
};

const getRestRoot = () => {
  const root = window?.wpApiSettings?.root || '/wp-json/';
  return root.endsWith('/') ? root : `${root}/`;
};

const getRequestHeaders = () => {
  const headers = {
    'Content-Type': 'application/json',
  };

  if (window?.wpApiSettings?.nonce) {
    headers['X-WP-Nonce'] = window.wpApiSettings.nonce;
  }

  return headers;
};

const getFaqSettings = (attrs) => attrs?.faqSettings?.innerContent?.desktop?.value || {};

const getResponsiveValue = (value) => {
  if (value && typeof value === 'object') {
    if (value?.desktop && Object.prototype.hasOwnProperty.call(value.desktop, 'value')) {
      return value.desktop.value;
    }

    if (Object.prototype.hasOwnProperty.call(value, 'value')) {
      return value.value;
    }
  }

  return value;
};

const normalizeSettingValue = (value) => {
  const responsiveValue = getResponsiveValue(value);

  if (Array.isArray(responsiveValue)) {
    return collectSettingValues(responsiveValue).join(',');
  }

  if (typeof responsiveValue === 'string' && responsiveValue !== '') {
    return responsiveValue;
  }

  if (typeof responsiveValue === 'number' || typeof responsiveValue === 'boolean') {
    return String(responsiveValue);
  }

  return '';
};

const collectSettingValues = (value) => {
  if (typeof value === 'string' && value !== '') {
    return [value];
  }

  if (typeof value === 'number' || typeof value === 'boolean') {
    return [String(value)];
  }

  if (!value || typeof value !== 'object') {
    return [];
  }

  const values = [];
  ['desktop', 'value'].forEach((key) => {
    if (Object.prototype.hasOwnProperty.call(value, key)) {
      values.push(...collectSettingValues(value[key]));
    }
  });

  Object.keys(value).forEach((key) => {
    if (key === 'desktop' || key === 'value') {
      return;
    }

    values.push(...collectSettingValues(value[key]));
  });

  return [...new Set(values)];
};

const serializeSettingValue = (value) => {
  const responsiveValue = getResponsiveValue(value);

  if (typeof responsiveValue === 'string' && responsiveValue !== '') {
    return responsiveValue;
  }

  if (typeof responsiveValue === 'number' || typeof responsiveValue === 'boolean') {
    return String(responsiveValue);
  }

  if (responsiveValue && typeof responsiveValue === 'object') {
    try {
      return JSON.stringify(responsiveValue);
    } catch (error) {
      return '';
    }
  }

  return '';
};

const getIconAttrValue = (attrs, attrName) => {
  const value = serializeSettingValue(attrs?.[attrName]?.innerContent);
  if (value !== '') {
    return value;
  }

  return '';
};

const getFaqSettingValue = (attrs, key, fallback = '') => {
  const value = normalizeSettingValue(getFaqSettings(attrs)?.[key]);
  return value !== '' ? value : fallback;
};

const setFaqIcon = (button, expanded) => {
  const iconWrap = button.querySelector('.oneplugin2-faq__icon');
  if (!iconWrap) {
    return;
  }

  const activeState = expanded ? 'close' : 'open';
  iconWrap.querySelectorAll('[data-oneplugin2-faq-icon-state]').forEach((icon) => {
    icon.hidden = icon.getAttribute('data-oneplugin2-faq-icon-state') !== activeState;
    icon.setAttribute('aria-hidden', 'true');
  });
};

const collapsePanel = (button, panel, duration) => {
  if (panel.__onePluginFaqTimer) {
    window.clearTimeout(panel.__onePluginFaqTimer);
  }

  button.setAttribute('aria-expanded', 'false');
  setFaqIcon(button, false);
  panel.style.height = `${panel.scrollHeight}px`;
  panel.offsetHeight;
  panel.style.height = '0px';
  panel.__onePluginFaqTimer = window.setTimeout(() => {
    panel.hidden = true;
    panel.style.height = '';
    panel.__onePluginFaqTimer = null;
  }, duration);
};

const initFaqPreview = (context) => {
  if (!context || typeof context.querySelectorAll !== 'function') {
    return;
  }

  context.querySelectorAll('[data-oneplugin2-faq] .oneplugin2-faq__trigger').forEach((button) => {
    setFaqIcon(button, button.getAttribute('aria-expanded') === 'true');
  });

  context.querySelectorAll('[data-oneplugin2-faq]').forEach((wrapper) => {
    if (wrapper.__onePluginFaqPreviewReady) {
      return;
    }

    wrapper.__onePluginFaqPreviewReady = true;
    wrapper.addEventListener('click', (event) => {
      const button = event.target.closest('.oneplugin2-faq__trigger');
      if (!button || !wrapper.contains(button)) {
        return;
      }

      const panel = document.getElementById(button.getAttribute('aria-controls'));
      if (!panel) {
        return;
      }

      const nextExpanded = button.getAttribute('aria-expanded') !== 'true';
      const animation = panel.getAttribute('data-animation') || 'slide';
      let duration = parseInt(panel.getAttribute('data-duration') || '220', 10);
      const accordionMode = button.getAttribute('data-accordion-mode') || 'single';

      if (!duration || duration < 0) {
        duration = 220;
      }

      button.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
      setFaqIcon(button, nextExpanded);

      if (animation !== 'slide') {
        if (nextExpanded && accordionMode === 'single') {
          wrapper.querySelectorAll('.oneplugin2-faq__trigger[aria-expanded="true"]').forEach((otherButton) => {
            if (otherButton === button) {
              return;
            }
            const otherPanel = document.getElementById(otherButton.getAttribute('aria-controls'));
            if (otherPanel) {
              otherButton.setAttribute('aria-expanded', 'false');
              setFaqIcon(otherButton, false);
              otherPanel.hidden = true;
            }
          });
        }
        panel.hidden = !nextExpanded;
        return;
      }

      panel.style.transition = `height ${duration}ms ease`;
      panel.style.overflow = 'hidden';
      if (panel.__onePluginFaqTimer) {
        window.clearTimeout(panel.__onePluginFaqTimer);
        panel.__onePluginFaqTimer = null;
      }

      if (nextExpanded && accordionMode === 'single') {
        wrapper.querySelectorAll('.oneplugin2-faq__trigger[aria-expanded="true"]').forEach((otherButton) => {
          if (otherButton === button) {
            return;
          }
          const otherPanel = document.getElementById(otherButton.getAttribute('aria-controls'));
          if (otherPanel) {
            collapsePanel(otherButton, otherPanel, duration);
          }
        });
      }

      if (nextExpanded) {
        panel.hidden = false;
        panel.style.height = '0px';
        panel.offsetHeight;
        panel.style.height = `${panel.scrollHeight}px`;
        panel.__onePluginFaqTimer = window.setTimeout(() => {
          panel.style.height = '';
          panel.__onePluginFaqTimer = null;
        }, duration);
        return;
      }

      panel.style.height = `${panel.scrollHeight}px`;
      panel.offsetHeight;
      panel.style.height = '0px';
      panel.__onePluginFaqTimer = window.setTimeout(() => {
        panel.hidden = true;
        panel.style.height = '';
        panel.__onePluginFaqTimer = null;
      }, duration);
    });
  });
};

const buildGroupOptions = (groups) => {
  const options = {
    '': {
      label: 'All groups',
    },
  };

  groups.forEach((group) => {
    options[String(group.slug)] = {
      label: group.name || group.slug,
      name: group.slug || '',
    };
  });

  return options;
};

const createMetadata = (groups) => {
  const nextMetadata = JSON.parse(JSON.stringify(metadataJson));
  nextMetadata.attributes.faqSettings.settings.innerContent.items.group.component.props.options =
    buildGroupOptions(groups);

  return nextMetadata;
};

const loadGroups = () => {
  if (GROUP_CACHE.promise) {
    return GROUP_CACHE.promise;
  }

  GROUP_CACHE.promise = window
    .fetch(`${getRestRoot()}oneplugin2/v1/faq-groups`, {
      credentials: 'same-origin',
      headers: getRequestHeaders(),
    })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`Failed to fetch FAQ groups: ${response.status}`);
      }

      return response.json();
    })
    .then((payload) => {
      GROUP_CACHE.groups = Array.isArray(payload?.data?.groups) ? payload.data.groups : [];
      return GROUP_CACHE.groups;
    })
    .catch(() => {
      GROUP_CACHE.groups = [];
      return GROUP_CACHE.groups;
    });

  return GROUP_CACHE.promise;
};

const previewParamsFromAttrs = (attrs) => {
  const keys = {
    group: 'group',
    limit: 'limit',
    columns: 'columns',
    rows: 'rows',
    orderby: 'orderby',
    order: 'order',
    schema: 'schema',
    useSameIcon: 'use_same_icon',
    animation: 'animation',
    animationDuration: 'animation_duration',
    accordionMode: 'accordion_mode',
    openFirst: 'open_first',
  };

  const params = new URLSearchParams();
  Object.keys(keys).forEach((attrKey) => {
    params.set(keys[attrKey], getFaqSettingValue(attrs, attrKey, ''));
  });
  params.set('open_icon', getIconAttrValue(attrs, 'faqOpenIcon') || getFaqSettingValue(attrs, 'openIcon', ''));
  params.set('close_icon', getIconAttrValue(attrs, 'faqCloseIcon') || getFaqSettingValue(attrs, 'closeIcon', ''));
  params.set('schema', 'false');

  return params;
};

const getPreviewKey = (attrs) => previewParamsFromAttrs(attrs).toString();

const loadPreviewHtml = (attrs) => {
  const params = previewParamsFromAttrs(attrs);

  return window
    .fetch(`${getRestRoot()}oneplugin2/v1/faq-preview?${params.toString()}`, {
      credentials: 'same-origin',
      headers: getRequestHeaders(),
    })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`Failed to fetch FAQ preview: ${response.status}`);
      }

      return response.json();
    })
    .then((payload) => ({
      html: typeof payload?.data?.html === 'string' ? payload.data.html : '',
      failed: false,
    }))
    .catch(() => ({
      html: '',
      failed: true,
    }));
};

const getStyleProps = (attrName, attrs, settings) => {
  if (attrName === 'module') {
    return {
      disabledOn: {
        disabledModuleVisibility: settings?.disabledModuleVisibility,
      },
    };
  }

  if (attrName === 'faqIcon') {
    return {
      advancedStyles: [
        {
          componentName: 'divi/common',
          props: {
            attr: attrs?.faqIcon?.advanced?.color,
            property: 'color',
          },
        },
        {
          componentName: 'divi/common',
          props: {
            attr: attrs?.faqIcon?.advanced?.size,
            property: 'font-size',
          },
        },
      ],
    };
  }

  return {};
};

const ModuleStyles = ({ attrs, elements, settings, mode, state, noStyleTag }) => (
  <StyleContainer mode={mode} state={state} noStyleTag={noStyleTag}>
    {STYLE_ATTRS.map((attrName) =>
      elements.style({
        attrName,
        styleProps: getStyleProps(attrName, attrs, settings),
      }),
    )}
  </StyleContainer>
);

const ModuleScriptData = ({ elements }) => (
  <React.Fragment>
    {elements.scriptData({
      attrName: 'module',
    })}
  </React.Fragment>
);

const moduleClassnames = ({ classnamesInstance, attrs }) => {
  classnamesInstance.add(
    elementClassnames({
      attrs: attrs?.module?.decoration ?? {},
    }),
  );
};

const renderStyleComponents = (elements) => (
  <React.Fragment>
    {STYLE_ATTRS.map((attrName) => elements.styleComponents({ attrName }))}
  </React.Fragment>
);

const PreviewFallback = ({ failed, loading }) => {
  if (failed) {
    return (
      <div style={{ color: '#6b7280', fontSize: '14px', lineHeight: 1.5 }}>
        FAQ preview could not be loaded.
      </div>
    );
  }

  if (loading) {
    return (
      <div style={{ color: '#6b7280', fontSize: '14px', lineHeight: 1.5 }}>
        FAQ preview is loading.
      </div>
    );
  }

  return (
    <div style={{ color: '#6b7280', fontSize: '14px', lineHeight: 1.5 }}>
      Add published FAQ items in 1Plugin FAQ.
    </div>
  );
};

class FAQModuleEdit extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      previewHtml: '',
      loading: false,
      previewFailed: false,
    };

    this._isMounted = false;
    this._requestKey = '';
    this.previewRef = React.createRef();
  }

  componentDidMount() {
    this._isMounted = true;
    this.syncPreview(this.props);
  }

  componentDidUpdate(prevProps) {
    const previousKey = getPreviewKey(prevProps.attrs);
    const nextKey = getPreviewKey(this.props.attrs);

    if (previousKey !== nextKey) {
      this.syncPreview(this.props);
    }
  }

  componentWillUnmount() {
    this._isMounted = false;
  }

  syncPreview(props) {
    const requestKey = getPreviewKey(props.attrs);
    this._requestKey = requestKey;

    this.setState({
      loading: true,
      previewFailed: false,
    });

    loadPreviewHtml(props.attrs).then((preview) => {
      if (!this._isMounted || this._requestKey !== requestKey) {
        return;
      }

      this.setState(
        {
          previewHtml: preview.html,
          loading: false,
          previewFailed: preview.failed,
        },
        () => initFaqPreview(this.previewRef.current),
      );
    });
  }

  render() {
    const { attrs, id, name, elements } = this.props;
    const { previewHtml, loading, previewFailed } = this.state;

    return (
      <ModuleContainer
        attrs={attrs}
        elements={elements}
        id={id}
        moduleClassName="oneplugin_divi5_faq_module"
        name={name}
        classnamesFunction={moduleClassnames}
        scriptDataComponent={ModuleScriptData}
        stylesComponent={ModuleStyles}
      >
        {renderStyleComponents(elements)}
        {previewHtml ? (
          <div ref={this.previewRef} dangerouslySetInnerHTML={{ __html: previewHtml }} />
        ) : (
          <PreviewFallback failed={previewFailed} loading={loading} />
        )}
      </ModuleContainer>
    );
  }
}

const registerOnePluginFAQModule = () => {
  loadGroups().then((groups) => {
    const metadata = createMetadata(groups);

    registerModule(metadata, {
      metadata,
      renderers: {
        edit: (props) => <FAQModuleEdit {...props} />,
      },
    });
  });
};

if (typeof addAction === 'function' && typeof registerModule === 'function') {
  addAction(
    'divi.moduleLibrary.registerModuleLibraryStore.after',
    'oneplugin.faqModule',
    registerOnePluginFAQModule,
  );
}
