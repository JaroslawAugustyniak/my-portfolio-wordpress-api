<?php

class Portfolio_JSON_Exporter_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_post_export_portfolio_json', [$this, 'handle_export']);
    }

    public function add_admin_menu() {
        add_management_page(
            'Portfolio JSON Exporter',
            'Portfolio JSON Exporter',
            'manage_options',
            'portfolio-json-exporter',
            [$this, 'render_admin_page']
        );
    }

    public function render_admin_page() {
        // Testuj metody
        //  $data = Portfolio_JSON_Exporter::generate_all_json_files();
        //  pr($data); die;
        ?>
        <div class="wrap">
            <h1>Portfolio JSON Exporter</h1>
            <p>Ta wtyczka generuje wszystkie potrzebne pliki JSON dla Twojej aplikacji React.</p>


            <div style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px; max-width: 600px;">
                <h2>Eksportuj dane</h2>
                <p>Kliknij poniższy przycisk aby wygenerować i pobrać wszystkie pliki JSON:</p>

                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('portfolio_json_exporter_nonce'); ?>
                    <input type="hidden" name="action" value="export_portfolio_json">
                    <button type="submit" class="button button-primary button-large">
                        📥 Pobierz JSON + Zdjęcia (ZIP)
                    </button>
                </form>

                <hr>

                <h3>Informacje:</h3>
                <ul>
                    <li>✓ Wszystkie strony i wpisy</li>
                    <li>✓ Sekcje (custom post type)</li>
                    <li>✓ Menu</li>
                    <li>✓ Obsługa wielojęzyczności (Polylang)</li>
                    <li>✓ Zdjęcia featured</li>
                    <li>✓ Pola ACF</li>
                </ul>

                <h3>Struktura pliku ZIP:</h3>
                <pre>portfolio-export/
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
│   ├── pages.json
│   ├── posts.json
│   ├── projects.json
│   ├── sections.json
│   ├── menu.json
│   └── siteSettings.json
└── images/
    ├── [featured images]
    └── [other images]</pre>
            </div>
        </div>
        <?php
    }

    public function handle_export() {
        // Sprawdź nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'portfolio_json_exporter_nonce')) {
            wp_die('Bezpieczeństwo: nieprawidłowy nonce');
        }

        // Sprawdź uprawnienia
        if (!current_user_can('manage_options')) {
            wp_die('Nie masz uprawnień do tej operacji');
        }

        // Wygeneruj plik ZIP
        $this->generate_and_download_zip();
    }

    private function generate_and_download_zip() {
        // Stwórz tymczasowy katalog
        $temp_dir = wp_upload_dir()['basedir'] . '/portfolio-export-temp-' . time();
        wp_mkdir_p($temp_dir);

        try {
            // Wygeneruj wszystkie dane
            $data = Portfolio_JSON_Exporter::generate_all_json_files();

            // Zapisz languages.json
            file_put_contents(
                $temp_dir . '/languages.json',
                json_encode($data['languages']['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            // Dla każdego języka stwórz folder i zapisz pliki
            foreach ($data['languages']['data'] as $lang) {
                $lang_slug = $lang['slug'];
                $lang_dir = $temp_dir . '/' . $lang_slug;
                wp_mkdir_p($lang_dir);

                if (isset($data[$lang_slug])) {
                    $lang_data = $data[$lang_slug]['data'];

                    file_put_contents(
                        $lang_dir . '/pages.json',
                        json_encode($lang_data['pages'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    );

                    file_put_contents(
                        $lang_dir . '/posts.json',
                        json_encode($lang_data['posts'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    );

                    file_put_contents(
                        $lang_dir . '/projects.json',
                        json_encode($lang_data['projects'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    );

                    file_put_contents(
                        $lang_dir . '/sections.json',
                        json_encode($lang_data['sections'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    );

                    file_put_contents(
                        $lang_dir . '/menu.json',
                        json_encode($lang_data['menu'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    );

                    file_put_contents(
                        $lang_dir . '/siteSettings.json',
                        json_encode($lang_data['siteSettings'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    );
                }
            }

            // Pobierz wszystkie obrazy featured
            $this->copy_featured_images($temp_dir);

            // Stwórz ZIP
            $zip_path = wp_upload_dir()['basedir'] . '/portfolio-export.zip';
            $this->create_zip($temp_dir, $zip_path);

            // Wyślij plik do pobrania
            $this->download_file($zip_path, 'portfolio-export.zip');

            // Usuń pliki tymczasowe
            $this->remove_directory($temp_dir);
            if (file_exists($zip_path)) {
                unlink($zip_path);
            }

            exit;

        } catch (Exception $e) {
            wp_die('Błąd podczas eksportu: ' . $e->getMessage());
        }
    }

    private function copy_featured_images($temp_dir) {
        wp_mkdir_p($temp_dir . '/images');
        
        // Pobierz wszystkie attachment IDs
        $args = [
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'post_status' => 'inherit',
            ];
            
            $attachments = get_posts($args);
            $uploads_dir = wp_upload_dir();
            $base_path = $uploads_dir['basedir'];
            $webp_base = dirname($base_path) . '/uploads-webpc/uploads';
            
        foreach ($attachments as $attachment) {
            $file = get_attached_file($attachment->ID);
            if (!$file) continue;

            // Sprawdź czy istnieje wersja webp (preferuj webp jeśli dostępne)
            $file_to_copy = $this->get_webp_file_if_exists($file);

            // Sprawdzaj czy plik do kopiowania istnieje (oryginał lub webp)
            if (!file_exists($file_to_copy)) {
                continue;
            }

            // Zachowaj strukturę YYYY/m/ z upload_dir
            // Jeśli to webp, usunięj /uploads-webpc/uploads/, inaczej usunięj /uploads/
            if (strpos($file_to_copy, $webp_base) === 0) {
                $relative_path = str_replace($webp_base . '/', '', $file_to_copy);
            } else {
                $relative_path = str_replace($base_path . '/', '', $file_to_copy);
            }

            $dest_file = $temp_dir . '/images/' . $relative_path;
            $dest_dir = dirname($dest_file);

            // Stwórz folder jeśli nie istnieje
            wp_mkdir_p($dest_dir);

            copy($file_to_copy, $dest_file);
        }
        
    }

    private function get_webp_file_if_exists($original_file) {
        // Konstruuj ścieżkę webp zachowując strukturę YYYY/m/
        // WebP konwerter dodaje .webp na koniec: 1-2.png → 1-2.png.webp
        $uploads_dir = wp_upload_dir();
        $base_path = $uploads_dir['basedir']; // /wp-content/uploads
        $webp_base = dirname($base_path) . '/uploads-webpc/uploads'; // /wp-content/uploads-webpc/uploads

        // Pobierz relative path od upload_dir
        $relative_path = str_replace($base_path . '/', '', $original_file);

        // Konstruuj pełną ścieżkę webp (dodaj .webp na koniec)
        $webp_file = $webp_base . '/' . $relative_path . '.webp';

        if (file_exists($webp_file)) {
            return $webp_file;
        }

        return $original_file;
    }

    private function create_zip($source, $destination) {
        $zip = new ZipArchive();

        if (!$zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
            throw new Exception('Nie udało się otworzyć ZIP-a');
        }

        // Dodaj wszystkie pliki
        $this->add_files_to_zip($zip, $source, 'portfolio-export');
        $zip->close();
    }

    private function add_files_to_zip($zip, $source, $base_path = '') {
        $source = rtrim($source, '/');

        if (is_dir($source)) {
            $files = scandir($source);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $file_path = $source . '/' . $file;
                    $zip_path = $base_path . '/' . $file;

                    if (is_dir($file_path)) {
                        $zip->addEmptyDir($zip_path);
                        $this->add_files_to_zip($zip, $file_path, $zip_path);
                    } else {
                        $zip->addFile($file_path, $zip_path);
                    }
                }
            }
        } elseif (is_file($source)) {
            $zip->addFile($source, $base_path);
        }
    }

    private function download_file($file_path, $file_name) {
        if (file_exists($file_path)) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . $file_name . '"');
            header('Content-Length: ' . filesize($file_path));
            header('Pragma: no-cache');
            header('Expires: 0');
            readfile($file_path);
        } else {
            wp_die('Plik nie znaleziony');
        }
    }

    private function remove_directory($dir) {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $file_path = $dir . '/' . $file;
                    if (is_dir($file_path)) {
                        $this->remove_directory($file_path);
                    } else {
                        unlink($file_path);
                    }
                }
            }
            rmdir($dir);
        }
    }
}
