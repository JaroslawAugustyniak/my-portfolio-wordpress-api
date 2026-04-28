# Flow: Jak działa eksport JSON

## 1. User workflow

```
Admin WordPress
    ↓
Wtyczki → Portfolio JSON Exporter
    ↓
Kliknij "Pobierz JSON + Zdjęcia"
    ↓
POST /wp-admin/admin-post.php?action=export_portfolio_json
    ↓
handle_export() sprawdzi nonce & uprawnienia
    ↓
generate_and_download_zip()
    ↓
Download portfolio-export.zip
    ↓
User rozpakuje ZIP
    ↓
Wgra foldery do /public/api/ w React
    ↓
npm run build
    ↓
Deploy
```

---

## 2. Wewnętrzny flow generowania JSON-ów

```
generate_all_json_files()
    │
    ├─→ Pobierz języki
    │   pll_languages_list(['fields' => 'all'])
    │   ↓
    │   languages.json
    │
    ├─→ Dla każdego języka:
    │   │
    │   ├─→ export_pages($lang)
    │   │   get_posts(['post_type' => 'page'])
    │   │   → filtruj po translations[$lang].id
    │   │   → każdy post mapuj na get_page_data()
    │   │   ↓
    │   │   pages.json
    │   │
    │   ├─→ export_posts($lang)
    │   │   get_posts(['post_type' => 'post'])
    │   │   → filtruj po translations[$lang].id
    │   │   → każdy post mapuj na get_post_data()
    │   │   ↓
    │   │   posts.json
    │   │
    │   ├─→ export_projects_recommended($lang)
    │   │   get_posts(['post_type' => 'post', 'tax_query' => category=recommended])
    │   │   → filtruj po translations[$lang].id
    │   │   → mapuj na get_post_data()
    │   │   ↓
    │   │   projects.json
    │   │
    │   ├─→ export_sections($lang)
    │   │   get_posts(['post_type' => 'section'])
    │   │   → filtruj po translations[$lang].id
    │   │   → mapuj na get_post_data()
    │   │   ↓
    │   │   sections.json
    │   │
    │   ├─→ export_menu($lang)
    │   │   get_nav_menu_locations() → find 'main'
    │   │   wp_get_nav_menu_items($menu_id)
    │   │   ↓
    │   │   menu.json
    │   │
    │   └─→ export_site_settings()
    │       get_bloginfo(), get_option()
    │       ↓
    │       siteSettings.json
    │
    ├─→ Pobierz wszystkie obrazy (dla wszystkich języków)
    │   get_posts(['post_type' => 'attachment'])
    │   get_attached_file($id)
    │   copy($file, images/)
    │   ↓
    │   images/ folder
    │
    └─→ Globalne siteSettings.json
        export_site_settings()
        ↓
        siteSettings.json (root)
```

---

## 3. get_page_data() / get_post_data()

```
Post WordPress
    ↓
Ekstrahuj dane:
    ├─ id
    ├─ post_title → title.rendered
    ├─ post_content → apply_filters('the_content', ...) → content.rendered
    ├─ post_excerpt (lub wycięty tekst) → excerpt.rendered
    ├─ post_name → slug
    ├─ post_date_gmt → date (ISO 8601)
    ├─ featured_image (jeśli istnieje):
    │  ├─ wp_get_attachment_url($featured_media_id) → source_url
    │  └─ get_post_meta($id, '_wp_attachment_image_alt', true) → alt_text
    ├─ categories (tylko posts)
    ├─ tags (tylko posts)
    └─ ACF fields (jeśli istnieją)
        └─ get_fields($post_id)
    ↓
Zwróć jako JSON object
```

---

## 4. Struktura plików w ZIP

```
📦 portfolio-export.zip
├── 📄 languages.json          ← Języki
├── 📄 siteSettings.json       ← Globalne settings
├── 📁 pl/                     ← Folder dla polskiego
│   ├── 📄 pages.json
│   ├── 📄 posts.json
│   ├── 📄 projects.json
│   ├── 📄 sections.json
│   ├── 📄 menu.json
│   └── 📄 siteSettings.json
├── 📁 en/                     ← Folder dla angielskiego
│   ├── 📄 pages.json
│   ├── 📄 posts.json
│   ├── 📄 projects.json
│   ├── 📄 sections.json
│   ├── 📄 menu.json
│   └── 📄 siteSettings.json
└── 📁 images/                 ← Wszystkie featured images
    ├── image-001.jpg
    ├── image-002.png
    └── ...
```

---

## 5. Filtrowanie po języku (Polylang)

```
User wybiera język: "en"
    ↓
export_posts('en')
    ↓
Pobierz WSZYSTKIE posty:
    get_posts(['post_type' => 'post'])
    [Post1, Post2, Post3, Post4, ...]
    ↓
Filtruj po translations['en'].id:
    
    Post1:
      id: 10
      translations: { pl: {id: 20}, en: {id: 30} }
      → czy id == translations['en'].id? (10 == 30? NO) → ODRZUĆ
    
    Post2:
      id: 30
      translations: { pl: {id: 20}, en: {id: 30} }
      → czy id == translations['en'].id? (30 == 30? YES) → ZACHOWAJ
    
    Post3:
      id: 40
      translations: null
      → brak translations → ZACHOWAJ (language-agnostic)
    ↓
Teraz pobierz pełne dane dla wybranych postów:
    get_posts(['include' => '30,40', 'posts_per_page' => 100])
    ↓
Mapuj na get_post_data()
    ↓
Zwróć JSON
```

---

## 6. Obsługa featured images

```
Dla każdego posta:
    ↓
    featured_media_id = get_post_thumbnail_id($post->ID)
    ↓
    IF featured_media_id EXISTS:
        featured_image = {
            source_url: wp_get_attachment_url(featured_media_id),
            alt_text: get_post_meta(featured_media_id, '_wp_attachment_image_alt', true)
        }
    ↓
    featured_image → dodaj do JSON
    
    OSOBNO: zbierz wszystkie attachment IDs:
        $attachments = get_posts(['post_type' => 'attachment'])
        ↓
        Dla każdego:
            file = get_attached_file($id)
            copy(file, /tmp/portfolio-export-xxx/images/)
```

---

## 7. Tworzenie ZIP i pobieranie

```
generate_and_download_zip()
    ↓
1. Stwórz temp katalog:
   /tmp/portfolio-export-1234567890/
    ↓
2. Wygeneruj JSONy:
   Zapisz languages.json
   Dla każdego języka:
       wp_mkdir_p(/tmp/.../pl/)
       file_put_contents(.../pl/pages.json, json_encode(...))
       file_put_contents(.../pl/posts.json, json_encode(...))
       ...
    ↓
3. Skopiuj obrazy:
   copy_featured_images(/tmp/portfolio-export-xxx/)
    ↓
4. Stwórz ZIP:
   $zip = new ZipArchive()
   $zip->open('/tmp/portfolio-export.zip')
   add_files_to_zip($zip, $temp_dir, 'portfolio-export')
   $zip->close()
    ↓
5. Wyślij do pobrania:
   header('Content-Type: application/zip')
   header('Content-Disposition: attachment; filename="portfolio-export.zip"')
   readfile('/tmp/portfolio-export.zip')
   exit
    ↓
6. Usuń temp pliki:
   remove_directory(/tmp/portfolio-export-xxx/)
   unlink(/tmp/portfolio-export.zip)
```

---

## 8. Co się dzieje w React po wgraniu plików

```
User rozpakuje ZIP
    ↓
portfolio-export/
├── languages.json
├── pl/
├── en/
└── images/
    ↓
Wgra do:
    public/api/
    ├── languages.json
    ├── pl/
    ├── en/
    └── images/
    ↓
npm run build
    ↓
React aplikacja uruchamia src/lib/local-api.ts:
    
    getLanguages() → fetch('/api/languages.json')
    getPages('pl') → fetch('/api/pl/pages.json')
    getMenuItems('pl') → fetch('/api/pl/menu.json')
    ...
    ↓
Komponenty renderują dane z JSON-ów
    ↓
Deploy aplikacji na hosting
    ↓
Strona działa bez konieczności dostępu do WordPressa!
```

---

## 9. Error handling

```
try {
    generate_all_json_files()
    
    // Jeśli coś pójdzie źle:
    ↓
} catch (Exception $e) {
    wp_die('Błąd: ' . $e->getMessage())
}

Możliwe błędy:
├─ Brak permisji do zapisu w upload_dir
├─ WordPress baza niedostępna
├─ Polylang plugin nie zainstalowany (fallback do 'pl')
├─ Brak kategorii 'recommended' (zwróć [])
├─ Brak menu location 'main' (zwróć [])
└─ ZipArchive nie dostępny (sprawdź php.ini)
```

---

## 10. Sprawdzenie czy JSON jest poprawny

```
Po wygenerowaniu ZIP:

TERMINAL:
$ unzip portfolio-export.zip
$ jq '.' languages.json

Powinno wyświetlić JSON bez błędów.

Jeśli błąd:
$ jq '.' languages.json 2>&1 | head -20

Pokaże gdzie jest błąd (np. brakujący cudzysłów).

PRZEGLĄDARKA (dev tools):
Network → fetch('/api/languages.json')
Status: 200
Response: [{ "slug": "pl", ... }]

Jeśli 404:
- Sprawdzić czy pliki są w public/api/
- Sprawdzić czy webpack/vite je bunduje
```

---

## 11. Debugowanie

Jeśli coś nie działa:

```php
// 1. Sprawdzić czy Polylang działa
if (function_exists('pll_languages_list')) {
    error_log(print_r(pll_languages_list(['fields' => 'all']), true));
}

// 2. Sprawdzić featured images
$att_id = get_post_thumbnail_id(123);
error_log('Featured ID: ' . $att_id);
error_log('URL: ' . wp_get_attachment_url($att_id));

// 3. Sprawdzić ACF
if (function_exists('get_field')) {
    error_log('ACF fields: ' . json_encode(get_fields(123)));
}

// 4. Sprawdzić menu
$locs = get_nav_menu_locations();
error_log('Menu locations: ' . print_r($locs, true));

// Logi będą w:
// wp-content/debug.log
```

---

## Podsumowanie: Rzeczy do zapamiętania

| Krok | Co robi | Output |
|------|---------|--------|
| 1 | Pobiera języki z WordPressa | `languages.json` |
| 2 | Dla każdego języka pobiera strony/wpisy/sekcje | `{lang}/*.json` |
| 3 | Filtruje po tłumaczeniach (jeśli Polylang) | Dane tylko dla wybranego języka |
| 4 | Pobiera featured images | `images/` folder |
| 5 | Tworzy ZIP ze wszystkim | `portfolio-export.zip` |
| 6 | Wysyła do pobrania | Browser pobiera ZIP |
| 7 | Czyści pliki tymczasowe | Serwer clean |

React czyta JSON-y z `public/api/` i renderuje stronę bez dostępu do WordPressa!
