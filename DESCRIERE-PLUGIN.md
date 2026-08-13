# 1Plugin - descriere tehnica si functionala

## Rezumat

`1Plugin` este un plugin WordPress orientat spre site-uri construite cu Divi. Pluginul centralizeaza datele de identitate ale clientului/site-ului, expune aceste date prin shortcodes, adauga instrumente vizuale pentru front-end, introduce module native Divi 5 pentru meniu si FAQ, permite injectarea controlata de cod custom si include un mecanism de update din GitHub Releases.

Versiunea curenta din repo este `2.9.82`, definita atat in header-ul pluginului din `1plugin-light.php`, cat si in constanta `ONEPLUGIN_LIGHT_VERSION`.

Pluginul este incarcat din fisierul principal `1plugin-light.php`. Acesta defineste constantele de baza, include clasele din `includes/`, incarca serverele modulelor Divi 5 din `modules/` daca sunt activate si initializeaza updaterul GitHub, sistemul FAQ si pagina principala de administrare.

## Scop principal

Pluginul rezolva cateva nevoi recurente pentru site-uri WordPress/Divi:

- pastrarea datelor companiei intr-un singur loc;
- reutilizarea acestor date in pagini, module Divi si template-uri prin shortcodes;
- configurarea rapida a logo-ului, faviconului, titlului site-ului si linkurilor sociale;
- adaugarea unui footer mobil sticky cu actiuni de contact;
- ajustari front-end utile pentru imagini, galerii, footer, header si formulare;
- gestionarea intrebarilor frecvente ca tip de continut separat;
- disponibilitatea unor module Divi 5 native pentru meniu si FAQ;
- export/import pentru setari si FAQ-uri;
- actualizare manuala dintr-un release GitHub, cu auto-update nativ doar opt-in.

## Fisier principal si bootstrap

Fisierul `1plugin-light.php`:

- declara pluginul WordPress cu numele `1Plugin`;
- seteaza `ONEPLUGIN_LIGHT_VERSION`, `ONEPLUGIN_LIGHT_PATH` si `ONEPLUGIN_LIGHT_URL`;
- configureaza updaterul GitHub cu owner-ul `crim13`, repo-ul `1Plugin` si asset-ul `1plugin-light.zip`;
- include clasele principale:
  - `OnePlugin_Light_Menu_Module`;
  - `OnePlugin_Light_Divi_Shortcode_Support`;
  - `OnePlugin_Light_Divi_Compatibility`;
  - `OnePlugin_Light_Keyword_Meta`;
  - `OnePlugin_Light_Shortcodes`;
  - `OnePlugin_Light_FAQ`;
  - `OnePlugin_Light_Site_Tools`;
  - `OnePlugin_Light_GitHub_Updater`;
- incarca modulele Divi 5 `menu` si `faq` doar daca setarile lor sunt active;
- inregistreaza build-urile JavaScript pentru Visual Builder prin `PackageBuildManager`;
- ruleaza hook-ul de activare pentru migrarea setarilor vechi si crearea unui UUID de site;
- initializeaza updaterul, FAQ-ul si instrumentele principale.

## Pagina de administrare `1Plugin`

Clasa principala este `OnePlugin_Light_Site_Tools`, definita in `includes/class-oneplugin-light-site-tools.php`.

Pluginul adauga in admin meniul principal `1Plugin`, disponibil pentru utilizatori cu `manage_options`. Setarile sunt salvate in optiunea WordPress:

```text
oneplugin_light_site_tools_settings
```

Pentru compatibilitate cu instalari existente, pluginul foloseste si optiuni legacy:

```text
oneplugin_light_site_tools_settings_legacy
oneplugin_light_site_tools_uuid_legacy
```

Pagina admin contine sectiuni pentru:

- identitatea proiectului;
- date companie;
- linkuri sociale;
- footer mobil sticky;
- activarea/dezactivarea modulelor Divi 5;
- optiuni vizuale pentru front-end;
- paleta de culori a proiectului;
- cod custom CSS, JavaScript si PHP;
- import/export setari.

## Date de identitate si contact

Setarile principale gestionate de plugin includ:

- `company_name`;
- `organization_number`;
- `street_address`;
- `postal_code`;
- `city`;
- `phone_primary`;
- `email`;
- `website`;
- `site_title`;
- `site_icon_id`;
- `site_logo_id`;
- `facebook_url`;
- `instagram_url`;
- `linkedin_url`;
- `youtube_url`;
- `x_url`;
- `reddit_url`;
- `booking_url`.

La salvarea setarilor, pluginul sincronizeaza:

- titlul WordPress al site-ului prin `blogname`;
- faviconul prin `site_icon`;
- logo-ul temei prin `custom_logo`;
- logo-ul Divi, prin logica interna de sincronizare.

## Shortcodes pentru date companie

Clasa `OnePlugin_Light_Shortcodes` inregistreaza shortcodes pentru campurile configurate in admin.

Shortcodes simple:

```text
[foretag]   -> numele companiei
[gata]      -> adresa stradala
[postkod]   -> cod postal
[ort]       -> oras/localitate
[mobil1]    -> telefon
[orgnr]     -> numar organizatie
[mail]      -> email
```

Shortcodes compuse:

```text
[kontakt]              -> bloc HTML cu companie, adresa, telefon si email
[kundens_mail]         -> link mailto
[kundens_foretag]      -> numele companiei
[kundens_adress]       -> adresa completa
[kundens_telefon]      -> telefon
[karta]                -> iframe Google Maps pentru adresa
[hemsida]              -> buton catre website
[kundens_hemsida]      -> link catre website
[kundens_facebook]     -> link Facebook
[kundens_instagram]    -> link Instagram
[kundens_linkedin]     -> link LinkedIn
[kundens_youtube]      -> link YouTube
[kundens_x]            -> link X/Twitter
[kundens_reddit]       -> link Reddit
[kundens_bokadirekt]   -> link BokaDirekt
```

Output-ul este sanitizat cu functii WordPress precum `esc_html()` si `esc_url()`.

## Suport shortcodes in module Divi

Pluginul extinde executarea shortcode-urilor in continut Divi prin doua mecanisme:

- adauga `do_shortcode` pe continutul unor module Divi comune, precum text, button, blurb, code, slider si fullwidth header;
- proceseaza shortcode-uri aflate in atributele unor module Divi, prin `OnePlugin_Light_Divi_Shortcode_Support`.

Clasa suporta campuri pentru module precum:

- `et_pb_blurb`;
- `et_pb_button`;
- `et_pb_image`;
- `et_pb_slide`;
- `et_pb_fullwidth_header`;
- `dipi_dual_heading`;
- `dipi_text_highlighter`;
- alte module Divi/Divi Pixel listate in `supported_fields()`.

Procesarea este evitata in request-uri async, AJAX, REST sau Visual Builder pentru a reduce riscul de interferenta cu editorul.

## Campuri SEO/keyword pe pagini

Clasa `OnePlugin_Light_Keyword_Meta` adauga un meta box pe paginile WordPress cu patru campuri:

```text
sokordets_tjanst_rubrik
sokordets_ort_rubrik
sokordets_tjanst_brodtext
sokordets_ort_brodtext
```

Pentru fiecare camp exista shortcode corespunzator:

```text
[sokordets_tjanst_rubrik]
[sokordets_ort_rubrik]
[sokordets_tjanst_brodtext]
[sokordets_ort_brodtext]
```

Valorile sunt salvate ca post meta si sunt afisate sanitizat.

## Footer mobil sticky

Pluginul poate afisa un footer sticky pe mobil, randat in `wp_footer` cu prioritate mare.

Setarile principale sunt:

- activare/dezactivare footer;
- culoare fundal;
- culoare iconite;
- culoare text;
- trei pozitii configurabile;
- linkuri custom pentru fiecare pozitie;
- iconite Font Awesome custom.

Optiunile disponibile pentru fiecare pozitie sunt:

- none;
- custom;
- phone;
- email;
- facebook;
- instagram;
- linkedin;
- youtube;
- x;
- reddit;
- booking;
- website.

Footerul se afiseaza doar daca exista cel putin un item cu link valid. Pentru iconite este incarcat Font Awesome 6.5.1 de pe CDN.

## Optiuni vizuale front-end

Pluginul poate genera CSS/JS front-end in functie de setari:

- ascunderea interactiunii cu imaginile prin `pointer-events: none`;
- inlocuirea atributelor `alt` si `title` pentru imagini cu numele companiei;
- efect glass pentru headerul Divi fix;
- clasa `cover-img` pentru imagini cu `object-fit: cover`;
- ajustari pentru imagini in tab-uri;
- layout pentru galerii masonry Divi Pixel, inclusiv varianta patrata sau asimetrica;
- activarea elementului de meniu in functie de sectiunea vizibila;
- ascunderea footerului Divi default;
- stilizare pentru checkbox-uri Formidable Forms;
- variabile CSS generate din paleta proiectului.

Assets relevante:

```text
assets/css/admin.css
assets/css/mobile-footer.css
assets/css/menu.css
assets/css/faq.css
assets/css/faq-admin.css
assets/js/admin.js
assets/js/menu.js
assets/js/faq.js
assets/js/faq-admin.js
assets/js/image-alt-fix.js
assets/js/tabs-image-cover.js
assets/js/masonry-gallery-layout.js
assets/js/active-menu-item-by-section.js
```

## Cod custom

Pluginul permite salvarea de cod custom din admin:

- CSS in `wp_head`;
- JavaScript in `wp_footer`;
- PHP in `wp_head`;
- PHP in `wp_body_open`;
- PHP in `wp_footer`.

CSS-ul si JavaScript-ul ruleaza ca in setarile existente. Codul PHP este salvat, dar nu ruleaza decat daca optiunea `custom_php_enabled` este activata explicit. Cand este activ, codul PHP este executat prin `eval("?>$code")`, intr-un bloc `try/catch`, iar erorile sunt trimise in `error_log` doar cand `WP_DEBUG` este activ.

Aceasta functionalitate este puternica, dar sensibila: accesul trebuie pastrat strict pentru administratori de incredere, deoarece codul PHP custom poate executa logica arbitrara in site.

## Sistem FAQ

Clasa `OnePlugin_Light_FAQ` implementeaza sistemul FAQ.

Pluginul inregistreaza:

```text
Custom post type: oneplugin2_faq
Taxonomy:         oneplugin2_faq_group
Meta key:         _oneplugin2_include_schema
```

FAQ-urile sunt non-publice, dar vizibile in admin si REST. Ele suporta:

- titlu;
- editor;
- `page-attributes` pentru ordine;
- grupuri ierarhice;
- includere/excludere individuala din schema FAQPage.

In meniul `1Plugin`, pluginul adauga submeniuri pentru:

- lista FAQ;
- grupuri FAQ.

Functionalitati admin FAQ:

- quick add FAQ direct din lista;
- import JSON;
- export toate FAQ-urile;
- export FAQ-uri selectate;
- filtre dupa grup;
- coloane custom;
- sortare;
- editare rapida;
- integrare cu taxonomy screen.

Pe front-end, randarea FAQ produce un accordion accesibil cu:

- butoane cu `aria-expanded`;
- panouri cu `role="region"`;
- mod single-open sau independent;
- animatie slide sau fara animatie;
- optiune de deschidere a primului item;
- iconite text, Font Awesome sau iconite Divi;
- suport pentru JSON-LD `FAQPage`.

Parametri folositi la randare:

```text
group
limit
columns
rows
orderby
order
schema
open_icon
close_icon
use_same_icon
animation
animation_duration
accordion_mode
open_first
class
```

## Modul Divi 5: 1Plugin Menu

Modulul Divi 5 pentru meniu este in:

```text
modules/menu/
```

Componente principale:

```text
modules/menu/module.json
modules/menu/server/index.php
modules/menu/visual-builder/src/index.jsx
modules/menu/visual-builder/build/oneplugin-divi5-menu.js
```

Modulul este definit ca:

```text
name: oneplugin/menu-module
d4Shortcode: oneplugin_divi5_menu
title: 1Plugin Menu
```

Serverul Divi 5 inregistreaza modulul prin `ModuleRegistration::register_module()` doar cand Divi 5 este activ. Randarea foloseste clasa `OnePlugin_Light_Menu_Module`.

Functionalitati:

- selectarea unui meniu WordPress existent;
- randare prin `wp_nav_menu`;
- walker custom pentru itemi cu submeniuri;
- layout horizontal sau vertical in shortcode;
- aliniere stanga, centru, dreapta sau space-between;
- offcanvas sau dropdown pe mobil;
- submeniuri la hover sau click;
- efecte hover: none, underline, fill, lift;
- indicator pentru submeniuri;
- inchidere la click in afara;
- culori si dimensiuni prin variabile CSS inline;
- styling prin grupurile Divi pentru menu item, submenu, submenu item si mobile toggle.

Shortcodes echivalente pentru meniu:

```text
[oneplugin2_menu]
[oneplugin_menu]
```

## Modul Divi 5: 1Plugin FAQ

Modulul Divi 5 pentru FAQ este in:

```text
modules/faq/
```

Componente principale:

```text
modules/faq/module.json
modules/faq/server/index.php
modules/faq/visual-builder/src/index.jsx
modules/faq/visual-builder/build/oneplugin-divi5-faq.js
```

Modulul este definit ca:

```text
name: oneplugin/faq-module
d4Shortcode: oneplugin_divi5_faq
title: 1Plugin FAQ
```

Serverul Divi 5 apeleaza `OnePlugin_Light_FAQ::instance()->render()` cu proprietatile extrase din atributele Divi.

Functionalitati disponibile in Visual Builder:

- filtrare dupa grup FAQ;
- limitarea numarului de FAQ-uri;
- activare/dezactivare schema;
- iconita pentru stare inchisa;
- iconita pentru stare deschisa;
- folosirea aceleiasi iconite pentru ambele stari;
- mod accordion single sau independent;
- deschiderea primului item;
- animatie slide sau none;
- durata animatiei;
- styling pentru item, question, answer si icon.

## API REST

Pluginul inregistreaza rute REST in doua namespace-uri, pentru compatibilitate:

```text
oneplugin2/v1
oneplugin/v1
```

Rute disponibile:

```text
GET  /status
GET  /menus
GET  /menu-preview
GET  /faq-groups
GET  /faq-preview
GET  /settings
POST /settings
```

Permisiunile sunt separate:

- rutele administrative folosesc verificari de tip `manage_options`;
- rutele folosite de builder permit acces pentru utilizatori care pot lucra in editor/builder.

Aceste rute sunt folosite pentru status, listarea meniurilor, preview-uri pentru module si citirea/salvarea setarilor.

## Import/export

Setarile pluginului pot fi exportate si importate ca JSON din pagina principala `1Plugin`.

Payload-ul de export pentru setari include:

```text
plugin
version
exported_at
settings
```

FAQ-urile pot fi exportate/importate separat. Exportul FAQ include:

```text
plugin
type
version
exported_at
selection
faq.groups
faq.items
```

La import, pluginul creeaza sau actualizeaza grupuri si FAQ-uri pe baza slug-urilor.

## Migrare si compatibilitate legacy

Pluginul contine logica pentru:

- migrarea datelor din chei vechi precum `tcx_company`, `custom_foretag`, `tcx_adress`, `custom_gata`, `tcx_email`, `custom_mail` etc.;
- oglindirea setarilor in optiunea legacy;
- pastrarea namespace-ului REST vechi `oneplugin/v1`;
- pastrarea shortcode-urilor vechi pentru compatibilitate;
- activarea implicita a modulului FAQ pe instalari existente unde setarea lipseste.

## Compatibilitate Divi

Clasa `OnePlugin_Light_Divi_Compatibility`:

- elimina anumite cookie-uri Divi A/B testing/test cookies pe request-uri front-end normale;
- aplica filtrul `dbdse_et_pb_layout_content` pe continutul global modules de tip `et_pb_layout`;
- evita modificarile in request-uri AJAX, REST sau Visual Builder.

## Updater GitHub

Clasa `OnePlugin_Light_GitHub_Updater` integreaza pluginul cu sistemul nativ de update WordPress.

Configuratie implicita:

```text
Owner: crim13
Repo:  1Plugin
Asset: 1plugin-light.zip
```

Mecanism:

- citeste ultimul release prin API-ul GitHub;
- cauta asset-ul cu numele exact `1plugin-light.zip`;
- normalizeaza versiunea din tag-ul release-ului;
- compara versiunea release-ului cu `ONEPLUGIN_LIGHT_VERSION`;
- adauga update-ul in transientul WordPress `update_plugins`;
- expune informatii in modalul nativ `View details`;
- cache-uieste release-ul timp de 6 ore;
- sterge cache-ul dupa upgrade sau la forced update check;
- poate adauga header `Authorization` daca este definit `ONEPLUGIN_LIGHT_GITHUB_TOKEN`;
- poate sincroniza auto-update-ul nativ pentru plugin doar cand optiunea `github_auto_updates_enabled` este activata explicit.

## Structura repo-ului

Structura principala:

```text
1plugin-light.php
README.md
DESCRIERE-PLUGIN.md
assets/
  css/
  js/
includes/
  class-oneplugin-light-divi-compatibility.php
  class-oneplugin-light-divi-shortcode-support.php
  class-oneplugin-light-faq.php
  class-oneplugin-light-github-updater.php
  class-oneplugin-light-keyword-meta.php
  class-oneplugin-light-menu-module.php
  class-oneplugin-light-shortcodes.php
  class-oneplugin-light-site-tools.php
modules/
  faq/
    server/
    visual-builder/
  menu/
    server/
    visual-builder/
```

Arhivele de release trebuie generate doar din fisiere runtime, cu structura flat compatibila cu release-ul 2.8.

## Dependinte externe

Pluginul depinde functional de WordPress. Anumite functionalitati sunt conditionale:

- Divi 5 este necesar pentru modulele native din `modules/`;
- API-urile Divi `ET\Builder\...` sunt verificate inainte de inregistrare;
- Font Awesome 6.5.1 este incarcat de pe CDN pentru footerul mobil si iconite FAQ cand este necesar;
- Google Maps este folosit prin iframe in shortcode-ul `[karta]`;
- Formidable Forms este vizat de CSS-ul optional pentru checkbox-uri;
- extensia Form & email performance este activa implicit; submiturile Formidable si emailurile esuate sunt agregate zilnic pe formular/pagina si raportate catre dashboard fara continutul formularelor sau alte date personale, iar colectarea poate fi oprita din tab-ul Extensions;
- Divi Pixel pare vizat de unele ajustari pentru masonry gallery si module specifice.

## Observatii importante

- Pluginul este orientat catre un flux WordPress + Divi, nu este un plugin generic izolat de tema.
- Multe etichete si shortcode-uri sunt in suedeza, ceea ce indica o utilizare pentru site-uri/clienti in piata suedeza.
- Codul pastreaza compatibilitate cu chei si namespace-uri mai vechi, ceea ce este important pentru instalari existente.
- Functionalitatea de cod PHP custom este riscanta si ramane dezactivata implicit; accesul admin trebuie controlat strict.
- Modulele Divi 5 au build-uri JavaScript deja incluse in repo, dar sursele Visual Builder au si `package.json`/`package-lock.json` pentru rebuild.
