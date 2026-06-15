import React from 'react';
import metadataJson from '../../module.json';

const { addAction } = window?.vendor?.wp?.hooks || {};
const { ModuleContainer, StyleContainer, elementClassnames } = window?.divi?.module || {};
const { registerModule } = window?.divi?.moduleLibrary || {};

const STYLE_ATTRS = ['module', 'logoImage'];

// ─── REST helpers ────────────────────────────────────────────────────────────

const getRestRoot = () => {
  const root = window?.wpApiSettings?.root || '/wp-json/';
  return root.endsWith('/') ? root : `${root}/`;
};

const getRequestHeaders = () => {
  const headers = { 'Content-Type': 'application/json' };
  if (window?.wpApiSettings?.nonce) {
    headers['X-WP-Nonce'] = window.wpApiSettings.nonce;
  }
  return headers;
};

// ─── Logo data cache (single fetch for the whole page session) ────────────────

const LOGO_CACHE = { promise: null, data: null };

const loadLogoData = () => {
  if (LOGO_CACHE.promise) return LOGO_CACHE.promise;

  LOGO_CACHE.promise = window
    .fetch(`${getRestRoot()}oneplugin2/v1/logo-data`, {
      credentials: 'same-origin',
      headers: getRequestHeaders(),
    })
    .then((r) => (r.ok ? r.json() : Promise.reject(new Error(`HTTP ${r.status}`))))
    .then((payload) => {
      LOGO_CACHE.data = payload?.data || {};
      return LOGO_CACHE.data;
    })
    .catch(() => {
      LOGO_CACHE.data = {};
      return LOGO_CACHE.data;
    });

  return LOGO_CACHE.promise;
};

// ─── Attribute value helpers ──────────────────────────────────────────────────

const getLogoSettings = (attrs) =>
  attrs?.logoSettings?.innerContent?.desktop?.value || {};

const getImageContent = (attrs) =>
  attrs?.logoImage?.innerContent?.desktop?.value || {};

/**
 * Safely resolve a scalar value from a setting that may be stored as a plain
 * string OR as a Divi responsive object { desktop: { value: "..." } }.
 */
const resolveValue = (obj, key, fallback = '') => {
  const raw = obj?.[key];
  if (typeof raw === 'string' && raw !== '') return raw;
  if (typeof raw === 'number' || typeof raw === 'boolean') return String(raw);
  if (raw && typeof raw === 'object') {
    const v = raw?.desktop?.value ?? raw?.value;
    if (typeof v === 'string' && v !== '') return v;
    if (typeof v === 'number' || typeof v === 'boolean') return String(v);
  }
  return fallback;
};

/**
 * Pick the preview logo URL based on variant and loaded logo data.
 */
const resolvePreviewUrl = (logoData, variant, customSrc) => {
  if (customSrc) return customSrc;
  if (!logoData) return '';
  switch (variant) {
    case 'sticky': return logoData.stickyLogo || logoData.mainLogo || '';
    case 'mobile': return logoData.mobileLogo || logoData.mainLogo || '';
    default:       return logoData.mainLogo || '';
  }
};

// ─── Style helpers (mirrors server-side styleProps) ───────────────────────────

const getStyleProps = (attrName, attrs, settings) => {
  if (attrName === 'module') {
    return {
      disabledOn: {
        disabledModuleVisibility: settings?.disabledModuleVisibility,
      },
    };
  }
  return {};
};

// ─── Divi module components ───────────────────────────────────────────────────

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
    {elements.scriptData({ attrName: 'module' })}
  </React.Fragment>
);

const moduleClassnames = ({ classnamesInstance, attrs }) => {
  classnamesInstance.add(
    elementClassnames({ attrs: attrs?.module?.decoration ?? {} }),
  );
};

const renderStyleComponents = (elements) => (
  <React.Fragment>
    {STYLE_ATTRS.map((attrName) => elements.styleComponents({ attrName }))}
  </React.Fragment>
);

// ─── Preview placeholder ──────────────────────────────────────────────────────

const LogoPlaceholder = ({ message }) => (
  <div
    style={{
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      padding: '12px 16px',
      border: '1px dashed #d1d5db',
      borderRadius: '4px',
      color: '#6b7280',
      fontSize: '13px',
      lineHeight: 1.5,
      minHeight: '48px',
    }}
  >
    {message}
  </div>
);

// ─── Module edit component ────────────────────────────────────────────────────

class LogoModuleEdit extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      logoData: LOGO_CACHE.data, // may already be populated (cached)
      loading: LOGO_CACHE.data === null,
    };
    this._isMounted = false;
  }

  componentDidMount() {
    this._isMounted = true;

    if (this.state.logoData !== null) {
      // Already cached — nothing to load.
      return;
    }

    loadLogoData().then((data) => {
      if (this._isMounted) {
        this.setState({ logoData: data, loading: false });
      }
    });
  }

  componentWillUnmount() {
    this._isMounted = false;
  }

  render() {
    const { attrs, id, name, elements, ...rest } = this.props;
    const { logoData, loading } = this.state;

    const logoSettings = getLogoSettings(attrs);
    const imageContent = getImageContent(attrs);

    const variant   = resolveValue(logoSettings, 'logoVariant', 'auto');
    const customSrc = resolveValue(imageContent,  'src', '');
    const customAlt = resolveValue(imageContent,  'alt', '');
    const siteName  = logoData?.siteName || '';

    const logoUrl = resolvePreviewUrl(logoData, variant, customSrc);
    const altText = customAlt || siteName || 'Logo';

    let previewContent;

    if (loading && !customSrc) {
      previewContent = <LogoPlaceholder message="Loading logo…" />;
    } else if (logoUrl) {
      previewContent = (
        /* eslint-disable-next-line jsx-a11y/anchor-is-valid */
        <a
          href="#"
          onClick={(e) => e.preventDefault()}
          style={{ display: 'inline-block', lineHeight: 0 }}
          aria-label={altText}
        >
          <img
            src={logoUrl}
            alt={altText}
            style={{ display: 'block', maxWidth: '100%', height: 'auto' }}
          />
        </a>
      );
    } else {
      previewContent = (
        <LogoPlaceholder
          message={
            logoData !== null
              ? 'No logo set. Configure logos in 1Plugin → Project Data.'
              : 'Could not load logo data.'
          }
        />
      );
    }

    return (
      <ModuleContainer
        {...rest}
        attrs={attrs}
        elements={elements}
        id={id}
        moduleClassName="oneplugin_divi5_logo_module"
        name={name}
        classnamesFunction={moduleClassnames}
        scriptDataComponent={ModuleScriptData}
        stylesComponent={ModuleStyles}
      >
        {renderStyleComponents(elements)}
        {previewContent}
      </ModuleContainer>
    );
  }
}

// ─── Module registration ──────────────────────────────────────────────────────

const registerOnePluginLogoModule = () => {
  registerModule(metadataJson, {
    metadata: metadataJson,
    renderers: {
      edit: (props) => <LogoModuleEdit {...props} />,
    },
  });
};

if (typeof addAction === 'function' && typeof registerModule === 'function') {
  addAction(
    'divi.moduleLibrary.registerModuleLibraryStore.after',
    'oneplugin.logoModule',
    registerOnePluginLogoModule,
  );
}
