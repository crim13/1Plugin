import React from 'react';
import metadataJson from '../../module.json';

const { addAction } = window?.vendor?.wp?.hooks || {};
const { ModuleContainer, StyleContainer, elementClassnames } = window?.divi?.module || {};
const { registerModule } = window?.divi?.moduleLibrary || {};

const STYLE_ATTRS = ['module', 'submenuIndicator', 'menuItem', 'submenuPanel', 'submenuItem', 'mobileToggle'];

const MENU_CACHE = {
  promise: null,
  menus: [],
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

const getMenuSettings = (attrs) => attrs?.menuSettings?.innerContent?.desktop?.value || {};
const normalizeSettingValue = (value) => {
  if (typeof value === 'string' && value !== '') {
    return value;
  }

  if (typeof value === 'number' || typeof value === 'boolean') {
    return String(value);
  }

  if (value && typeof value === 'object') {
    const responsiveValue = value?.desktop?.value ?? value?.value;
    if (typeof responsiveValue === 'string' && responsiveValue !== '') {
      return responsiveValue;
    }

    if (typeof responsiveValue === 'number' || typeof responsiveValue === 'boolean') {
      return String(responsiveValue);
    }
  }

  return '';
};

const serializeSettingValue = (value) => {
  const responsiveValue = value?.desktop?.value ?? value?.value ?? value;

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

const getMenuSettingValue = (attrs, key, fallback = '') => {
  const value = normalizeSettingValue(getMenuSettings(attrs)?.[key]);
  return value !== '' ? value : fallback;
};

const getMenuId = (attrs) => {
  return getMenuSettingValue(attrs, 'menuId', '');
};

const getSubmenuIndicatorIcon = (attrs) => serializeSettingValue(attrs?.submenuIndicator?.innerContent) || '▾';

const MENU_PREVIEW_SETTINGS = [
  ['layout', 'layout', 'horizontal'],
  ['align', 'align', 'center'],
  ['mobileStyle', 'mobile_style', 'offcanvas'],
  ['mobileSide', 'mobile_side', 'right'],
  ['mobileBreakpoint', 'mobile_breakpoint', '980'],
  ['submenuTrigger', 'submenu_trigger', 'hover'],
  ['hoverEffect', 'hover_effect', 'underline'],
  ['showSubmenuIndicator', 'show_submenu_indicator', 'on'],
  ['closeOnOutsideClick', 'close_on_outside_click', 'on'],
  ['closeOnLinkClick', 'close_on_link_click', 'on'],
  ['toggleLabel', 'toggle_label', 'Menu'],
  ['menuTextColor', 'menu_text_color', '#111827'],
  ['menuHoverTextColor', 'menu_hover_text_color', '#111827'],
  ['menuHoverBackgroundColor', 'menu_hover_background_color', 'rgba(17,24,39,.08)'],
  ['itemActiveColor', 'item_active_color', '#111827'],
  ['itemActiveBackgroundColor', 'item_active_background_color', 'rgba(17,24,39,.12)'],
  ['submenuBackgroundColor', 'submenu_background_color', '#ffffff'],
  ['submenuTextColor', 'submenu_text_color', '#111827'],
  ['submenuHoverTextColor', 'submenu_hover_text_color', '#111827'],
  ['submenuHoverBackgroundColor', 'submenu_hover_background_color', 'rgba(17,24,39,.08)'],
  ['toggleColor', 'toggle_color', '#111827'],
  ['toggleBackgroundColor', 'toggle_background_color', '#ffffff'],
  ['borderColor', 'border_color', 'rgba(17,24,39,.10)'],
  ['shadowColor', 'shadow_color', 'rgba(17,24,39,.16)'],
  ['menuGap', 'menu_gap', '24'],
  ['submenuWidth', 'submenu_width', '240'],
  ['submenuRadius', 'submenu_radius', '16'],
  ['itemPaddingY', 'item_padding_y', '14'],
  ['itemPaddingX', 'item_padding_x', '18'],
  ['submenuPaddingY', 'submenu_padding_y', '12'],
  ['submenuPaddingX', 'submenu_padding_x', '16'],
  ['mobilePanelWidth', 'mobile_panel_width', '360'],
  ['mobilePanelOffset', 'mobile_panel_offset', '16'],
];

const findLayoutValue = (value) => {
  if (typeof value === 'string') {
    const normalized = value.toLowerCase();
    return ['flex', 'grid', 'block'].includes(normalized) ? normalized : '';
  }

  if (!value || typeof value !== 'object') {
    return '';
  }

  const preferredKeys = ['desktop', 'value', 'layout', 'display', 'mode', 'type'];
  for (const key of preferredKeys) {
    if (!Object.prototype.hasOwnProperty.call(value, key)) {
      continue;
    }

    const found = findLayoutValue(value[key]);
    if (found) {
      return found;
    }
  }

  for (const childValue of Object.values(value)) {
    const found = findLayoutValue(childValue);
    if (found) {
      return found;
    }
  }

  return '';
};

const getLayoutClass = (attrs) => {
  const layoutValue = findLayoutValue(attrs?.module?.decoration?.layout);

  if (layoutValue === 'grid') {
    return 'et_grid_module';
  }

  if (layoutValue === 'block') {
    return 'et_block_module';
  }

  return 'et_flex_module';
};

const buildMenuOptions = (menus) => {
  const options = {
    '': {
      label: 'Select a menu',
    },
  };

  menus.forEach((menu) => {
    options[String(menu.id)] = {
      label: menu.name || `Menu ${menu.id}`,
      name: menu.slug || '',
    };
  });

  return options;
};

const createMetadata = (menus) => {
  const nextMetadata = JSON.parse(JSON.stringify(metadataJson));
  nextMetadata.attributes.menuSettings.settings.innerContent.items.menuId.component.props.options = buildMenuOptions(menus);

  return nextMetadata;
};

const loadMenus = () => {
  if (MENU_CACHE.promise) {
    return MENU_CACHE.promise;
  }

  MENU_CACHE.promise = window
    .fetch(`${getRestRoot()}oneplugin2/v1/menus`, {
      credentials: 'same-origin',
      headers: getRequestHeaders(),
    })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`Failed to fetch menus: ${response.status}`);
      }

      return response.json();
    })
    .then((payload) => {
      MENU_CACHE.menus = Array.isArray(payload?.data?.menus) ? payload.data.menus : [];
      return MENU_CACHE.menus;
    })
    .catch(() => {
      MENU_CACHE.menus = [];
      return MENU_CACHE.menus;
    });

  return MENU_CACHE.promise;
};

const getPreviewParams = (attrs) => {
  const params = {
    menu_id: getMenuId(attrs),
    layout_class: getLayoutClass(attrs),
    submenu_indicator_icon: getSubmenuIndicatorIcon(attrs),
  };

  MENU_PREVIEW_SETTINGS.forEach(([settingKey, paramKey, fallback]) => {
    params[paramKey] = getMenuSettingValue(attrs, settingKey, fallback);
  });

  return params;
};

const getPreviewKey = (attrs) => JSON.stringify(getPreviewParams(attrs));

const loadPreviewHtml = (previewParams) => {
  const params = new URLSearchParams(previewParams);

  return window
    .fetch(`${getRestRoot()}oneplugin2/v1/menu-preview?${params.toString()}`, {
      credentials: 'same-origin',
      headers: getRequestHeaders(),
    })
    .then((response) => {
      if (!response.ok) {
        throw new Error(`Failed to fetch preview: ${response.status}`);
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

  if (attrName === 'submenuIndicator') {
    return {
      advancedStyles: [
        {
          componentName: 'divi/common',
          props: {
            attr: attrs?.submenuIndicator?.advanced?.color,
            property: 'color',
          },
        },
        {
          componentName: 'divi/common',
          props: {
            attr: attrs?.submenuIndicator?.advanced?.size,
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

const PreviewFallback = ({ failed, menuId }) => {
  if (failed) {
    return (
      <div style={{ color: '#6b7280', fontSize: '14px', lineHeight: 1.5 }}>
        Menu preview could not be loaded.
      </div>
    );
  }

  if (!menuId) {
    return (
      <div style={{ color: '#6b7280', fontSize: '14px', lineHeight: 1.5 }}>
        Select a WordPress menu from Content.
      </div>
    );
  }

  return (
    <div style={{ color: '#6b7280', fontSize: '14px', lineHeight: 1.5 }}>
      Menu preview is loading.
    </div>
  );
};

const renderStyleComponents = (elements) => (
  <React.Fragment>
    {STYLE_ATTRS.map((attrName) => elements.styleComponents({ attrName }))}
  </React.Fragment>
);

class MenuModuleEdit extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      previewHtml: '',
      loading: false,
      previewFailed: false,
    };

    this._isMounted = false;
    this._requestKey = '';
  }

  componentDidMount() {
    this._isMounted = true;
    this.syncPreview(this.props);
  }

  componentDidUpdate(prevProps) {
    if (getPreviewKey(prevProps.attrs) !== getPreviewKey(this.props.attrs)) {
      this.syncPreview(this.props);
    }
  }

  componentWillUnmount() {
    this._isMounted = false;
  }

  syncPreview(props) {
    const previewParams = getPreviewParams(props.attrs);
    const menuId = previewParams.menu_id;
    const requestKey = JSON.stringify(previewParams);

    this._requestKey = requestKey;

    if (!menuId) {
      this.setState({
        previewHtml: '',
        loading: false,
        previewFailed: false,
      });
      return;
    }

    this.setState({
      loading: true,
      previewFailed: false,
    });

    loadPreviewHtml(previewParams).then((preview) => {
      if (!this._isMounted || this._requestKey !== requestKey) {
        return;
      }

      this.setState({
        previewHtml: preview.html,
        loading: false,
        previewFailed: preview.failed,
      });
    });
  }

  render() {
    const { attrs, id, name, elements } = this.props;
    const { previewHtml, loading, previewFailed } = this.state;
    const menuId = getMenuId(attrs);

    return (
      <ModuleContainer
        attrs={attrs}
        elements={elements}
        id={id}
        moduleClassName="oneplugin_divi5_menu_module"
        name={name}
        classnamesFunction={moduleClassnames}
        scriptDataComponent={ModuleScriptData}
        stylesComponent={ModuleStyles}
      >
        {renderStyleComponents(elements)}
        {previewHtml ? (
          <div dangerouslySetInnerHTML={{ __html: previewHtml }} />
        ) : (
          <PreviewFallback failed={previewFailed} menuId={loading ? 'loading' : menuId} />
        )}
      </ModuleContainer>
    );
  }
}

const registerOnePluginMenuModule = () => {
  loadMenus().then((menus) => {
    const metadata = createMetadata(menus);

    registerModule(metadata, {
      metadata,
      renderers: {
        edit: (props) => <MenuModuleEdit {...props} />,
      },
    });
  });
};

if (typeof addAction === 'function' && typeof registerModule === 'function') {
  addAction(
    'divi.moduleLibrary.registerModuleLibraryStore.after',
    'oneplugin.menuModule',
    registerOnePluginMenuModule,
  );
}
