# 1Plugin

`1Plugin` este un plugin WordPress pentru site-uri construite in principal cu Divi. Centralizeaza datele companiei/site-ului, le expune prin shortcodes, adauga instrumente front-end utile, include module native Divi 5 pentru meniu si FAQ si se poate actualiza din GitHub Releases.

Versiunea curenta din acest repo este `2.9.72-dev`.

Pentru descrierea completa a implementarii existente, vezi [DESCRIERE-PLUGIN.md](DESCRIERE-PLUGIN.md).

## Functionalitati principale

- pagina de administrare `1Plugin` pentru identitatea proiectului, date companie, social links, logo, favicon si titlu site;
- shortcodes pentru datele companiei, contact, adresa, harta, website si profiluri sociale;
- suport pentru shortcodes in continut si in atributele unor module Divi;
- campuri keyword/SEO pe pagini, cu shortcodes dedicate;
- sticky mobile footer cu pana la trei actiuni configurabile;
- optiuni front-end pentru imagini, galerii masonry, header, footer, Formidable Forms si meniu activ dupa sectiune;
- paleta de culori salvata in admin si exportata ca variabile CSS;
- cod custom CSS, JavaScript si PHP pentru `head`, `body` si `footer`;
- sistem FAQ cu custom post type, grupuri, import/export JSON, quick add si schema FAQPage;
- module native Divi 5 pentru meniu si FAQ;
- rute REST pentru status, setari, meniuri, preview meniu si preview FAQ;
- updater GitHub integrat cu sistemul nativ WordPress de plugin updates.

## Shortcodes utile

Date simple:

```text
[foretag]
[gata]
[postkod]
[ort]
[mobil1]
[orgnr]
[mail]
```

Contact si linkuri:

```text
[kontakt]
[kundens_mail]
[kundens_foretag]
[kundens_adress]
[kundens_telefon]
[karta]
[hemsida]
[kundens_hemsida]
[kundens_facebook]
[kundens_instagram]
[kundens_linkedin]
[kundens_youtube]
[kundens_x]
[kundens_reddit]
[kundens_bokadirekt]
```

Campuri keyword pe pagini:

```text
[sokordets_tjanst_rubrik]
[sokordets_ort_rubrik]
[sokordets_tjanst_brodtext]
[sokordets_ort_brodtext]
```

Meniu:

```text
[oneplugin2_menu]
[oneplugin_menu]
```

## Module Divi 5

Modulele Divi 5 sunt in `modules/`.

- `modules/menu` defineste modulul `1Plugin Menu`, folosit pentru randarea si stilizarea unui meniu WordPress in Divi 5.
- `modules/faq` defineste modulul `1Plugin FAQ`, folosit pentru afisarea FAQ-urilor gestionate de plugin.

Modulele sunt incarcate doar daca Divi 5 este disponibil si daca sunt activate din setarile pluginului.

## Sistem FAQ

Pluginul foloseste:

```text
Custom post type: oneplugin2_faq
Taxonomy:         oneplugin2_faq_group
Meta key:         _oneplugin2_include_schema
```

FAQ-urile pot fi grupate, ordonate, exportate/importate ca JSON si randate cu schema `FAQPage`. Modulul Divi 5 permite filtrare dupa grup, limita de itemi, iconite, animatie si comportament accordion.

## GitHub updater

Pluginul verifica ultimul release GitHub si foloseste asset-ul numit exact:

```text
1plugin-light.zip
```

Configuratia implicita este:

```php
define('ONEPLUGIN_LIGHT_GITHUB_OWNER', 'crim13');
define('ONEPLUGIN_LIGHT_GITHUB_REPO', '1Plugin');
define('ONEPLUGIN_LIGHT_GITHUB_ASSET', '1plugin-light.zip');
```

Pentru release-uri private sau rate limits, se poate defini optional:

```php
define('ONEPLUGIN_LIGHT_GITHUB_TOKEN', 'github-token');
```

Update-ul manual ramane disponibil prin sistemul nativ WordPress de plugin updates. Auto-update-ul nativ este opt-in si se activeaza explicit din setarile `1Plugin`, prin optiunea GitHub auto-updates.

## Release flow

1. Actualizeaza versiunea in header-ul pluginului din `1plugin-light.php`.
2. Actualizeaza constanta `ONEPLUGIN_LIGHT_VERSION`.
3. Construieste `1plugin-light.zip` cu directorul pluginului la radacina arhivei.
4. Creeaza un GitHub release cu tag-ul versiunii stabile testate.
5. Incarca asset-ul `1plugin-light.zip` in release.

Numele asset-ului trebuie pastrat neschimbat pentru compatibilitatea updaterului.

## Structura repo

```text
1plugin-light.php
README.md
DESCRIERE-PLUGIN.md
assets/
  css/
  js/
includes/
modules/
  faq/
  menu/
```

## Note de siguranta

Functionalitatea de cod PHP custom salveaza codul in setari, dar executia este dezactivata implicit. Executia PHP trebuie activata explicit din `Custom Code` si accesul la pagina `1Plugin` trebuie pastrat doar pentru administratori de incredere.
