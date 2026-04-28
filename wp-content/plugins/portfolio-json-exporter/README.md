# Portfolio JSON Exporter

Wtyczka WordPress, która generuje wszystkie potrzebne pliki JSON dla aplikacji React/Vite.

## Instalacja

### Metoda 1: Upload przez panel WordPress

1. Przejdź do **Wtyczki → Dodaj nową**
2. Kliknij **Prześlij wtyczkę**
3. Prześlij plik `portfolio-json-exporter.zip`
4. Aktywuj wtyczkę

### Metoda 2: FTP/SSH

1. Rozpakuj folder `portfolio-json-exporter/` do `wp-content/plugins/`
2. Przejdź do **Wtyczki** w panelu WordPress
3. Aktywuj wtyczkę

## Użycie

1. Po aktywacji wtyczki przejdź do **Narzędzia → Portfolio JSON Exporter**
2. Kliknij przycisk **Pobierz JSON + Zdjęcia (ZIP)**
3. Czekaj na wygenerowanie i automatyczne pobranie pliku `portfolio-export.zip`

## Zawartość ZIP

```
portfolio-export/
├── languages.json          # Lista języków
├── siteSettings.json       # Globalne ustawienia strony
├── pl/                     # Folder dla polskiego
│   ├── pages.json
│   ├── posts.json
│   ├── projects.json
│   ├── sections.json
│   ├── menu.json
│   └── siteSettings.json
├── en/                     # Folder dla angielskiego (jeśli istnieje)
│   ├── pages.json
│   ├── posts.json
│   ├── projects.json
│   ├── sections.json
│   ├── menu.json
│   └── siteSettings.json
└── images/                 # Wszystkie zdjęcia featured images
    ├── image1.jpg
    ├── image2.png
    └── ...
```

## Wgranie plików do aplikacji React

1. Rozpakuj pobrany plik `portfolio-export.zip`
2. Skopiuj strukturę w całości do `/public/api/` w projekcie React:

```
portfolio/
└── public/
    └── api/
        ├── languages.json
        ├── siteSettings.json
        ├── pl/
        │   ├── pages.json
        │   ├── posts.json
        │   ├── projects.json
        │   ├── sections.json
        │   ├── menu.json
        │   └── siteSettings.json
        ├── en/
        └── images/
```

## Wymagania

- WordPress 5.0+
- PHP 7.4+
- ZipArchive (zwykle domyślnie włączony)
- Jeśli używasz Polylang: obsługa wielojęzyczności

## Co jest eksportowane

- ✓ Strony (`pages.json`)
- ✓ Wpisy (`posts.json`)
- ✓ Projekty z kategorii "recommended" (`projects.json`)
- ✓ Sekcje - custom post type (`sections.json`)
- ✓ Menu (`menu.json`)
- ✓ Ustawienia strony (`siteSettings.json`)
- ✓ Zdjęcia featured images
- ✓ Pola ACF (jeśli zainstalowany)
- ✓ Obsługa wielojęzyczności (Polylang)

## Troubleshooting

### "Bezpieczeństwo: nieprawidłowy nonce"
- Upewnij się, że jesteś zalogowany jako administrator
- Przejdź bezpośrednio na stronę wtyczki

### Plik ZIP się nie pobiera
- Sprawdź czy folder uploads ma uprawnienia do zapisu
- Sprawdź logi WordPress: `/wp-content/debug.log`

### Brak obrazów
- Upewnij się że zdjęcia featured image są wgrany do MediaLibrary
- Sprawdź czy WP ma dostęp do plików

## Wsparcie

Jeśli znajdziesz błędy, sprawdź:
1. Czy WordPress jest aktualny
2. Czy Polylang (jeśli używasz) jest aktualny
3. Czy masz uprawnienia administratora
