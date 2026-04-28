# Cheat Sheet - Portfolio JSON Exporter

Szybka referencyja do poprawy kodu wtyczki.

---

## Co generować (struktura)

```
portfolio-export/
├── languages.json                    # Języki
├── siteSettings.json                 # Globalne ustawienia
├── pl/ en/ de/ /                     # Po jednym folderze per język
│   ├── pages.json                    # `get_posts(['post_type' => 'page'])`
│   ├── posts.json                    # `get_posts(['post_type' => 'post'])`
│   ├── projects.json                 # posts z kategorią 'recommended'
│   ├── sections.json                 # `get_posts(['post_type' => 'section'])`
│   ├── menu.json                     # `wp_get_nav_menu_items(menu_id)`
│   └── siteSettings.json             # Kopja globalnego
└── images/                           # `get_attached_file($id)` dla wszystkich
```

---

## Format każdego JSON-a

### languages.json
```json
[
  { "slug": "pl", "name": "Polski", "locale": "pl_PL", "is_default": true, "flag_url": "" },
  { "slug": "en", "name": "English", "locale": "en_US", "is_default": false, "flag_url": "" }
]
```

### siteSettings.json
```json
{
  "site_name": "get_bloginfo('name')",
  "site_description": "get_bloginfo('description')",
  "site_url": "get_bloginfo('url')",
  "admin_email": "get_option('admin_email')"
}
```

### pages.json, posts.json, sections.json
```json
[
  {
    "id": 1,
    "title": { "rendered": "Title" },
    "content": { "rendered": "<p>apply_filters('the_content', ...)</p>" },
    "excerpt": { "rendered": "Short desc or first 55 words" },
    "slug": "slug-from-post",
    "date": "2024-01-15T10:30:00",
    "featured_media": 123,                          // only posts
    "featured_image": {                             // only if exists
      "source_url": "/images/file.jpg",            // RELATIVE PATH!
      "alt_text": "get_post_meta(..._alt, true)"
    },
    "categories": [5, 8],                          // only posts
    "tags": [10],                                  // only posts
    "acf": { "field": "value" }                    // if ACF exists
  }
]
```

### projects.json
- **To samo co posts.json, ale:**
  - `tax_query: { taxonomy: 'category', field: 'slug', terms: 'recommended' }`
  - Jeśli kategorii brak → `[]`

### sections.json
- **To samo co pages.json, ale:**
  - `post_type: 'section'` zamiast 'page'

### menu.json
```json
[
  {
    "id": 10,
    "title": "Menu text",
    "url": "/relative-or-absolute-url",
    "target": "" or "_blank",
    "classes": ["css-class"],
    "description": "optional",
    "parent": 0,                      // id parent item or 0
    "type": "custom" or "post_type",
    "type_label": "Custom Link" or "Page",
    "attr_title": "optional"
  }
]
```

---

## Kluczowe funkcje WordPress

```php
// Pobieranie
$pages = get_posts(['post_type' => 'page', 'posts_per_page' => 100]);
$posts = get_posts(['post_type' => 'post', 'posts_per_page' => 100]);
$sections = get_posts(['post_type' => 'section', 'posts_per_page' => 100]);

// Polylang
pll_languages_list(['fields' => 'all']);           // Lista języków
$post->translations[$lang_slug];                   // ID tłumaczenia

// Obrazy
get_post_thumbnail_id($post_id);                   // Featured image ID
wp_get_attachment_url($attachment_id);             // URL
get_post_meta($attachment_id, '_wp_attachment_image_alt', true);  // Alt text
get_attached_file($attachment_id);                 // Ścieżka pliku

// Treść
apply_filters('the_content', $post->post_content); // HTML bez shortcode
wp_strip_all_tags($post->post_content);            // Tekst do excerpta

// Menu
get_nav_menu_locations();                          // Dostępne lokalizacje
wp_get_nav_menu_items($menu_id);                   // Items menu

// ACF
get_fields($post_id);                              // { field: value, ... }

// Kategorie, tagi
wp_get_post_categories($post_id, ['fields' => 'ids']);
wp_get_post_tags($post_id, ['fields' => 'ids']);
```

---

## Ścieżki i adresy

❌ **ZŁE - pełne URLe**
```json
"source_url": "https://wp.local/wp-content/uploads/2024/01/image.jpg"
```

✅ **DOBRZE - ścieżki względne**
```json
"source_url": "/images/image.jpg"
```

Powodem: obrazki będą w `public/api/images/` i dostępne z `/api/images/`

---

## Filtrowanie per-język (Polylang)

```php
// Pobierz wszystkie
$all_posts = get_posts(['post_type' => 'post', 'posts_per_page' => 100]);

// Filtruj po języku
$posts_for_lang = array_filter($all_posts, function($post) use ($lang) {
    // Jeśli post ma tłumaczenie na ten język
    if ($post->translations && isset($post->translations[$lang])) {
        return $post->id === $post->translations[$lang]['id'];
    }
    // Fallback: post bez tłumaczeń
    return !$post->translations;
});

// Teraz pobierz pełne dane
$post_ids = array_map(fn($p) => $p->id, $posts_for_lang);
$full_posts = get_posts([
    'include' => implode(',', $post_ids),
    'posts_per_page' => 100
]);
```

---

## Kopiowanie obrazów

```php
// 1. Pobierz wszystkie attachmenty
$attachments = get_posts(['post_type' => 'attachment', 'posts_per_page' => -1]);

// 2. Dla każdego skopiuj
foreach ($attachments as $att) {
    $file = get_attached_file($att->ID);
    if ($file && file_exists($file)) {
        $filename = basename($file);
        copy($file, $temp_dir . '/images/' . $filename);
    }
}

// 3. W JSON-ach użyj nazwy pliku
"source_url": "/images/" . $filename
```

---

## Tworzenie ZIP-a

```php
$zip = new ZipArchive();
$zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE);

// Dodaj plik
$zip->addFile($file_path, 'portfolio-export/file.json');

// Dodaj folder (rekurencyjnie)
// Funkcja add_files_to_zip() robi to

$zip->close();
```

---

## Pobieranie pliku do użytkownika

```php
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="portfolio-export.zip"');
header('Content-Length: ' . filesize($file_path));
readfile($file_path);
exit;
```

---

## Bezpieczeństwo

```php
// Sprawdzenie uprawnień
if (!current_user_can('manage_options')) {
    wp_die('Brak uprawnień');
}

// Sprawdzenie nonce
if (!wp_verify_nonce($_POST['_wpnonce'], 'nonce_action')) {
    wp_die('Błąd bezpieczeństwa');
}

// Tworzenie nonce
wp_nonce_field('nonce_action');
```

---

## Czyszczenie plików tymczasowych

```php
// Usuń katalog rekurencyjnie
function remove_directory($dir) {
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $path = $dir . '/' . $file;
                is_dir($path) ? remove_directory($path) : unlink($path);
            }
        }
        rmdir($dir);
    }
}
```

---

## Test JSON-a

```bash
# Czy poprawny JSON?
jq . portfolio-export/languages.json
jq . portfolio-export/pl/pages.json

# Ilość elementów
jq '. | length' portfolio-export/pl/posts.json

# Konkretny field
jq '.[0].featured_image.source_url' portfolio-export/pl/posts.json
```

---

## To co może być źródłem błędów

| Problem | Przyczyna | Rozwiązanie |
|---------|-----------|------------|
| Obraz nie ładuje się | Pełny URL zamiast /images/file.jpg | Używaj ścieżek względnych |
| Treść braku, widać shortcode | Nie użyto `apply_filters()` | Dodaj: `apply_filters('the_content', ...)` |
| Jeden język pokazuje dane drugiego | Brak filtrowania po tłumaczeniach | Filtruj per język z `translations[lang].id` |
| Brakuje obrazów w ZIP | Nie skopiowano attachmentów | `get_attached_file()` + `copy()` |
| Menu puste | Szukasz złej lokalizacji menu | Sprawdź `get_nav_menu_locations()` |
| ACF pola brakuje | Nie sprawdzasz czy ACF istnieje | `if (function_exists('get_field'))` |
| ZIP się nie pobiera | Błąd przy tworzeniu ZIP | Dodaj try-catch, sprawdź uprawnienia folderu |

---

## Szybka checklist przed commitem

- [ ] `languages.json` ma min. 1 język z `is_default: true`
- [ ] Każdy `{lang}/` folder ma 6 plików: pages, posts, projects, sections, menu, siteSettings
- [ ] Wszystkie `featured_image.source_url` zaczynają się od `/images/`
- [ ] Brak pełnych URLi w JSON-ach
- [ ] Treści są przetworzone przez `apply_filters('the_content', ...)`
- [ ] Gdy Polylang → każdy język ma filtrowane dane
- [ ] Folder `images/` ma wszystkie featured images (nie więcej, nie mniej)
- [ ] ZIP się generuje i pobiera bez błędów

---

## Logi do sprawdzenia

```bash
# WordPress debug log
tail -f /var/www/wp/wp-content/debug.log

# PHP errors
docker logs wp_php | tail -50

# Nginx logs
docker logs wp_nginx | tail -50
```

---

## Pełne flow co robi funkcja `generate_and_download_zip()`

1. Stwórz temp katalog: `/tmp/portfolio-export-{timestamp}/`
2. Wygeneruj JSON-y za pomocą klasy `Portfolio_JSON_Exporter`
3. Zapisz każdy JSON do odpowiedniego miejsca w temp katalogu
4. Skopiuj obrazy z MediaLibrary do `/tmp/.../images/`
5. Stwórz ZIP z całego katalogu `/tmp/portfolio-export-{timestamp}/`
6. Wyślij ZIP do pobrania z nagłówkami HTTP
7. Usuń pliki tymczasowe
8. Zakończ (`exit`)
