# Specyfikacja: Co i Jak zapisać - Portfolio JSON Exporter

## 1. Struktura katalogów

```
portfolio-export/
├── languages.json              # ← Główny plik z listą języków
├── siteSettings.json           # ← Globalne ustawienia
├── pl/                         # ← Folder dla każdego języka (slug)
│   ├── pages.json              # ← Strony (post_type='page')
│   ├── posts.json              # ← Wpisy (post_type='post')
│   ├── projects.json           # ← Wpisy z kategorii 'recommended'
│   ├── sections.json           # ← Custom post type 'section'
│   ├── menu.json               # ← Menu główne (location='main')
│   └── siteSettings.json       # ← Ustawienia strony dla tego języka
├── en/
│   ├── pages.json
│   ├── posts.json
│   ├── projects.json
│   ├── sections.json
│   ├── menu.json
│   └── siteSettings.json
└── images/                     # ← Wszystkie featured images
    ├── image-001.jpg
    ├── image-002.png
    └── ...
```

## 2. Zawartość plików

### 2.1 `languages.json`

```json
[
  {
    "slug": "pl",
    "name": "Polski",
    "locale": "pl_PL",
    "is_default": true,
    "flag_url": ""
  },
  {
    "slug": "en",
    "name": "English",
    "locale": "en_US",
    "is_default": false,
    "flag_url": ""
  }
]
```

**Wymagane pola:**
- `slug` (string) — kod języka (pl, en, de, etc.)
- `name` (string) — nazwa języka
- `locale` (string) — locale WordPress (pl_PL, en_US, etc.)
- `is_default` (boolean) — czy to język domyślny
- `flag_url` (string) — URL do flagi (może być pusty)

**Źródło:** `pll_languages_list(['fields' => 'all'])` jeśli Polylang, inaczej fallback do 'pl'

---

### 2.2 `siteSettings.json` (globalne)

```json
{
  "site_name": "My Portfolio",
  "site_description": "Portfolio description",
  "site_url": "https://portfolio.local",
  "admin_email": "admin@portfolio.local"
}
```

**Wymagane pola:**
- `site_name` (string) — `get_bloginfo('name')`
- `site_description` (string) — `get_bloginfo('description')`
- `site_url` (string) — `get_bloginfo('url')`
- `admin_email` (string) — `get_option('admin_email')`

**Źródło:** `get_bloginfo()` i `get_option()`

---

### 2.3 `{lang}/pages.json`

```json
[
  {
    "id": 2,
    "title": {
      "rendered": "O nas"
    },
    "content": {
      "rendered": "<p>HTML content here...</p>"
    },
    "excerpt": {
      "rendered": "Short description"
    },
    "slug": "about",
    "date": "2024-01-15T10:30:00",
    "featured_image": {
      "source_url": "/path/to/image.jpg",
      "alt_text": "Alt text"
    },
    "acf": {
      "custom_field_1": "value",
      "custom_field_2": ["array", "values"]
    }
  }
]
```

**Wymagane pola:**
- `id` (int) — ID posta
- `title.rendered` (string) — tytuł
- `content.rendered` (string) — HTML treści (z `apply_filters('the_content', ...)`)
- `excerpt.rendered` (string) — streszczenie (lub wycięte z treści)
- `slug` (string) — URL slug
- `date` (string, ISO 8601) — data (GMT)

**Opcjonalne pola:**
- `featured_image` (object) — jeśli istnieje featured image
  - `source_url` (string) — URL do zdjęcia (ścieżka względna od public/)
  - `alt_text` (string) — tekst alternatywny
- `acf` (object) — pola ACF jeśli istnieją

**Filtry:**
- Pobierz strony: `get_posts(['post_type' => 'page', 'posts_per_page' => 100])`
- Jeśli Polylang: filtruj po `translations[lang].id`
- Treść: `apply_filters('the_content', $post->post_content)`

---

### 2.4 `{lang}/posts.json`

```json
[
  {
    "id": 42,
    "title": {
      "rendered": "Mój pierwszy wpis"
    },
    "content": {
      "rendered": "<p>HTML content...</p>"
    },
    "excerpt": {
      "rendered": "Streszczenie"
    },
    "slug": "my-first-post",
    "date": "2024-03-10T14:20:00",
    "featured_media": 123,
    "featured_image": {
      "source_url": "/path/to/featured.jpg",
      "alt_text": "Featured image"
    },
    "categories": [5, 8],
    "tags": [10, 15],
    "acf": {
      "gallery": ["image1.jpg", "image2.jpg"],
      "live_url": "https://example.com"
    }
  }
]
```

**Wymagane pola:**
- Wszystkie z `pages.json` +
- `featured_media` (int) — ID featured image (lub null)
- `featured_image` (object) — featured image data (jeśli istnieje)

**Opcjonalne pola:**
- `categories` (array<int>) — ID kategorii
- `tags` (array<int>) — ID tagów
- `acf` (object) — custom fields (m.in. galeria, linki)

**Filtry:**
- Pobierz wpisy: `get_posts(['post_type' => 'post', 'posts_per_page' => 100])`
- Jeśli Polylang: filtruj po `translations[lang].id`

---

### 2.5 `{lang}/projects.json`

**To jest filtr posts.json** — tylko wpisy z kategorią `slug = 'recommended'`

```json
[
  {
    "id": 50,
    "title": { "rendered": "Portfolio project" },
    "content": { "rendered": "<p>...</p>" },
    "excerpt": { "rendered": "..." },
    "slug": "project-slug",
    "date": "2024-02-20T09:00:00",
    "featured_media": 200,
    "featured_image": {
      "source_url": "/path/to/project-image.jpg",
      "alt_text": "Project image"
    },
    "categories": [5],
    "tags": [20, 21],
    "acf": {
      "live_url": "https://project.com",
      "github_url": "https://github.com/...",
      "gallery": ["img1.jpg", "img2.jpg"]
    }
  }
]
```

**To samo co `posts.json`, ale:**
- Filtruj po `tax_query`: `['taxonomy' => 'category', 'field' => 'slug', 'terms' => 'recommended']`
- Jeśli brak kategorii "recommended" → zwróć pustą tablicę `[]`

---

### 2.6 `{lang}/sections.json`

```json
[
  {
    "id": 100,
    "title": { "rendered": "Sekcja Hero" },
    "content": { "rendered": "<p>...</p>" },
    "excerpt": { "rendered": "..." },
    "slug": "hero-section",
    "date": "2024-01-05T12:00:00",
    "featured_image": {
      "source_url": "/path/to/section-bg.jpg",
      "alt_text": "Section background"
    },
    "acf": {
      "section_type": "hero",
      "button_text": "Start",
      "button_url": "/projects"
    }
  }
]
```

**Format:** jak `pages.json`, ale dla custom post type `'section'`

**Filtry:**
- Pobierz sekcje: `get_posts(['post_type' => 'section', 'posts_per_page' => 100])`
- Jeśli Polylang: filtruj po `translations[lang].id`

---

### 2.7 `{lang}/menu.json`

```json
[
  {
    "id": 10,
    "title": "Strona główna",
    "url": "/",
    "target": "",
    "classes": ["menu-item"],
    "description": "",
    "parent": 0,
    "type": "custom",
    "type_label": "Custom Link",
    "attr_title": ""
  },
  {
    "id": 11,
    "title": "Projekty",
    "url": "/projects",
    "target": "",
    "classes": [],
    "description": "",
    "parent": 0,
    "type": "custom",
    "type_label": "Custom Link",
    "attr_title": ""
  },
  {
    "id": 12,
    "title": "O nas",
    "url": "/about",
    "target": "_blank",
    "classes": [],
    "description": "More about us",
    "parent": 0,
    "type": "post_type",
    "type_label": "Page",
    "attr_title": "Our story"
  }
]
```

**Wymagane pola:**
- `id` (int) — ID menu item
- `title` (string) — tekst menu
- `url` (string) — URL (może być względny: `/projects`)
- `target` (string) — `''` lub `'_blank'`
- `classes` (array<string>) — CSS classes
- `parent` (int) — ID parent menu item (0 = brak rodzica)
- `type` (string) — typ (custom, post_type, taxonomy, etc.)
- `type_label` (string) — etykieta typu (Custom Link, Page, etc.)

**Opcjonalne pola:**
- `description` (string)
- `attr_title` (string) — title attribute

**Filtry:**
- Pobierz menu location: `get_nav_menu_locations()` → szukaj `'main'`
- Pobierz items: `wp_get_nav_menu_items($menu_id)`
- Jeśli Polylang: pobierz menu dla każdego języka (`menu_lang` parameter)

---

### 2.8 `{lang}/siteSettings.json`

```json
{
  "site_name": "My Portfolio",
  "site_description": "Portfolio description",
  "site_url": "https://portfolio.local",
  "admin_email": "admin@portfolio.local"
}
```

**Dokładnie to samo co globalny `siteSettings.json`**

---

## 3. Obsługa Featured Images / Zdjęć

### Wymaga obsługi:

1. **Ścieżki względne:**
   - `"source_url": "/path/to/image.jpg"` (relatywna od `public/`)
   - NIE: pełny URL typu `"https://wp.local/wp-content/uploads/..."`

2. **Kopiowanie plików:**
   - Pobierz wszystkie attachment IDs: `get_posts(['post_type' => 'attachment'])`
   - Dla każdego: `get_attached_file($id)` → skopiuj do `portfolio-export/images/`
   - Zachowaj oryginalne nazwy plików

3. **W JSON-ach:**
   - `"source_url": "/images/photo-2024-01.jpg"` (relatywny path do images/)

---

## 4. Obsługa wielojęzyczności (Polylang)

### Jeśli jest zainstalowany Polylang:

1. **Pobierz języki:**
   ```php
   $langs = pll_languages_list(['fields' => 'all']);
   ```

2. **Dla każdego posta/strony filtruj:**
   ```php
   if ($post->translations && $post->translations[$lang_slug]) {
       $correct_id = $post->translations[$lang_slug]['id'];
       // Użyj tego ID do pobrania pełnych danych
   }
   ```

3. **Dla menu:**
   - Pobranie menu nie zmienia się, ale sprawdź czy menu ma parametr `lang`
   - Fallback: pobierz jedno menu dla wszystkich języków

### Fallback (bez Polylang):
- Domyślny język: 'pl'
- Jeden folder `/pl/` z wszystkimi danymi

---

## 5. ACF Fields — co trzeba wiedzieć

Jeśli jest zainstalowany Advanced Custom Fields (ACF):

```php
if (function_exists('get_field')) {
    $acf_data = get_fields($post_id);
    // Zwraca: ['field_name' => value, ...]
}
```

**Zapisz w JSON:**
```json
"acf": {
  "gallery": ["image1.jpg", "image2.jpg"],
  "live_url": "https://example.com",
  "description": "Custom field value"
}
```

---

## 6. Checklist: Co ma być w każdym JSON-ie

### languages.json
- [ ] Tablica z min. 1 językiem
- [ ] Każdy język ma: slug, name, locale, is_default, flag_url
- [ ] Jeden język ma `is_default: true`

### siteSettings.json (globalny i per-lang)
- [ ] site_name
- [ ] site_description
- [ ] site_url
- [ ] admin_email

### pages.json
- [ ] Array postów typu 'page'
- [ ] Każdy ma: id, title.rendered, content.rendered, excerpt.rendered, slug, date
- [ ] Opcjonalnie: featured_image, acf
- [ ] Filtrowani po języku (jeśli Polylang)

### posts.json
- [ ] Array postów typu 'post'
- [ ] Rozszerzenie pages.json o: featured_media, featured_image, categories, tags
- [ ] Opcjonalnie: acf
- [ ] Filtrowani po języku

### projects.json
- [ ] Array postów z kategorią `slug='recommended'`
- [ ] Formatowanie jak posts.json
- [ ] Jeśli brak kategorii → []

### sections.json
- [ ] Array postów typu 'section'
- [ ] Formatowanie jak pages.json
- [ ] Opcjonalnie: acf (dla sekcji spraw, aby miały dane konfiguracyjne)

### menu.json
- [ ] Array menu items z location='main'
- [ ] Każdy item ma: id, title, url, parent, type, type_label
- [ ] URLs mogą być względne (`/projects`) lub bezwzględne
- [ ] Respektuj hierarchię: parent ID

---

## 7. Błędy do unikania

❌ **Pełne URLe zamiast ścieżek względnych**
```json
// ZŁE:
"source_url": "https://wp.local/wp-content/uploads/2024/01/image.jpg"

// DOBRZE:
"source_url": "/images/image.jpg"
```

❌ **Brakujące filtrowanie per-język**
- Jeśli Polylang jest zainstalowany, MUSISZ filtrować po `translations[lang].id`
- Inaczej każdy język będzie mieć te same dane

❌ **Obrazki nie skopiowane**
- Pobierz wszystkie attachment IDs
- Skopiuj pliki do `images/`
- Zaktualizuj ścieżkę w JSON-ach

❌ **Nieprzetworzona treść**
- Użyj `apply_filters('the_content', $post->post_content)` do treści
- Bez filtra będzie raw shortcode i inne WordPress stuff

❌ **Puste tablice zamiast pustego arraya**
- `categories: []` (nie: `[]` bez klucza)
- Zawsze zwróć array, nawet jeśli pusty

---

## 8. Testowanie

Po wygenerowaniu ZIP:

```bash
# Rozpakuj
unzip portfolio-export.zip

# Sprawdź strukturę
ls -la portfolio-export/
ls -la portfolio-export/pl/
ls -la portfolio-export/images/

# Sprawdź JSON-y (powinny być valid)
jq . portfolio-export/languages.json
jq . portfolio-export/pl/pages.json
```

Jeśli JSON jest nieprawidłowy → wrzuci błąd i nie załaduje się w React.

---

## 9. Integracja z React

Aplikacja React już ma `src/lib/local-api.ts` z funkcjami:
- `getLanguages()` — czyta `languages.json`
- `getPages(lang)` — czyta `{lang}/pages.json`
- `getPosts(lang)` — czyta `{lang}/posts.json`
- `getPortfolioPosts(lang)` — czyta `{lang}/projects.json`
- `getMenuItems(lang)` — czyta `{lang}/menu.json`
- itd.

**Nie zmienia się!** Wtyczka tylko musi generować poprawne struktury JSON-ów.

---

## 10. Zmiana w HTML-u vs React

W JS/TS zawsze używaj:
```javascript
import { usePortfolioData } from './hooks/usePortfolioProjects';

const { data, isLoading } = usePortfolioData();
// data[0].featured_image.source_url
```

Nie montuj ścieżek ręcznie — struktura JSON musi być dokładnie taka, jak oczekuje kod React.
