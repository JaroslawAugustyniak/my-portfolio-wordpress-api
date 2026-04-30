<?php

class Portfolio_JSON_Exporter {

    private static function clean_description($text, $limit = 160) {
        // Usuń HTML tagi
        $text = wp_strip_all_tags($text);
        // Usuń extra whitespace i newlines
        $text = preg_replace('/\s+/', ' ', $text);
        // Trim
        $text = trim($text);
        // Ogranicz do limitu
        if (mb_strlen($text) > $limit) {
            $text = mb_substr($text, 0, $limit) . '...';
        }
        return $text;
    }

    private static function get_attachment_url_with_webp($attachment_id) {
        if (!$attachment_id) return '';

        $file = get_attached_file($attachment_id);
        if (!$file) return wp_get_attachment_url($attachment_id);

        // Sprawdź czy istnieje wersja webp
        $webp_file = self::get_webp_file_path($file);

        if ($webp_file && file_exists($webp_file)) {
            // Zwróć URL do webp
            $uploads_dir = wp_upload_dir();
            return str_replace($uploads_dir['basedir'], $uploads_dir['baseurl'], $webp_file);
        }

        return wp_get_attachment_url($attachment_id);
    }

    private static function get_webp_file_path($original_file) {
        // Konstruuj ścieżkę webp zachowując strukturę YYYY/m/
        // WebP konwerter dodaje .webp na koniec zamiast zamieniać rozszerzenie
        // Np: 1-2.png → 1-2.png.webp

        $uploads_dir = wp_upload_dir();
        $base_path = $uploads_dir['basedir']; // /wp-content/uploads
        $webp_base = dirname($base_path) . '/uploads-webpc/uploads'; // /wp-content/uploads-webpc/uploads

        // Pobierz relative path od upload_dir
        $relative_path = str_replace($base_path . '/', '', $original_file);

        // Konstruuj pełną ścieżkę webp (dodaj .webp na koniec)
        $webp_file = $webp_base . '/' . $relative_path . '.webp';

        return $webp_file;
    }

    private static function convert_image_url_to_api_path($url) {
        if (!$url) return '';

        $uploads_dir = wp_upload_dir();
        $base_url = $uploads_dir['baseurl'];
        $webp_url = dirname($base_url) . '/uploads-webpc/uploads';

        // Jeśli to WebP URL, usunięj /uploads-webpc/uploads/
        if (strpos($url, $webp_url) !== false) {
            $relative_path = str_replace($webp_url, '', $url);
            return '/api/images' . $relative_path;
        }

        // Jeśli to zwykły URL, usunięj /uploads/
        if (strpos($url, $base_url) === 0) {
            $relative_path = str_replace($base_url, '', $url);
            return '/api/images' . $relative_path;
        }

        return $url;
    }

    private static function convert_urls_in_data($data) {
        $uploads_dir = wp_upload_dir();
        $base_url = $uploads_dir['baseurl'];
        $base_path = $uploads_dir['basedir'];
        $webp_base = dirname($base_path) . '/uploads-webpc/uploads';

        if (is_string($data)) {
            // Sprawdź czy to URL z upload_dir
            if (strpos($data, $base_url) !== false) {
                // Spróbuj znaleźć webp version
                $relative_path = str_replace($base_url, '', $data);
                $file_path = $base_path . $relative_path;

                // Konstruuj ścieżkę webp zachowując strukturę YYYY/m/
                // WebP konwerter dodaje .webp na koniec: 1-2.png → 1-2.png.webp
                $relative_from_base = str_replace($base_path . '/', '', $file_path);
                $webp_file = $webp_base . '/' . $relative_from_base . '.webp';

                if (file_exists($webp_file)) {
                    // Zwróć URL do webp zamiast oryginału
                    $webp_url_correct = str_replace(dirname($base_path) . '/uploads-webpc/uploads', $base_url, $webp_file);
                    return self::convert_image_url_to_api_path($webp_url_correct);
                }

                return self::convert_image_url_to_api_path($data);
            }
            return $data;
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::convert_urls_in_data($value);
            }
            return $data;
        }

        if (is_object($data)) {
            foreach ($data as $key => $value) {
                $data->$key = self::convert_urls_in_data($value);
            }
            return $data;
        }

        return $data;
    }

    public static function export_languages() {
        $languages = [];

        // Jeśli używasz Polylang
        if (function_exists('pll_languages_list')) {
            $lang_list = pll_the_languages( array( 'raw' => 1 ) );
             
            foreach ($lang_list as $lang) {
                
                $languages[] = [
                    'slug' => $lang['slug'],
                    'name' => $lang['name'],
                    'locale' => $lang['locale'],
                    'is_default' => $lang['current_lang'] ? true : false,
                    'flag_url' => $lang['flag'] ?? '',
                ];
            }
        } else {
            // Fallback dla brak Polylang
            $languages[] = [
                'slug' => 'pl',
                'name' => 'Polski',
                'locale' => 'pl_PL',
                'is_default' => true,
                'flag_url' => '',
            ];
        }

        return $languages;
    }

    public static function export_pages($lang = null) {
        $args = [
            'post_type'      => 'page',
            'posts_per_page' => 100,
            'post_status'    => 'publish',
        ];

        if ($lang) {
            $args['lang'] = $lang;
        }

        $main_language = pll_default_language(); // Pobierz domyślny język, np. 'pl'
        
        $pages = get_posts($args);
        $result = [];

        foreach ($pages as $page) {
            // Podstawowe dane strony pobrane Twoją metodą
            $page_data = self::get_page_data($page, $lang);

            
            if($main_language != $lang) {
                $translations = pll_get_post_translations($page->ID);
                $idTranslation = $translations[$main_language] ?? null; // Pobierz ID tłumaczenia dla głównego języka
                if ($idTranslation) {
                    $translated_post = get_post($idTranslation);
                    if ($translated_post) {
                        $page_data['slug'] = $translated_post->post_name;
                    }
                }
            }


            $result[] = $page_data;
        }

       return $result;
    }

    public static function export_posts($post_type = 'post', $lang = null) {
        $main_language = pll_default_language(); // np. 'pl'
        
        $args = [
            'post_type'      => $post_type,
            'posts_per_page' => 100,
            'post_status'    => 'publish',
            'lang'           => $main_language, // Zawsze pobieramy bazę z głównego języka
        ];

        $posts = get_posts($args);
        $result = [];

        foreach ($posts as $post) {
            $final_post = $post; // Domyślnie bierzemy post główny
            $is_translated = false;

            // Jeśli szukany język jest inny niż domyślny, szukamy tłumaczenia
            if ($lang && $lang !== $main_language) {
                $translations = pll_get_post_translations($post->ID);
                
                if (isset($translations[$lang])) {
                    $translated_id = $translations[$lang];
                    $translated_post = get_post($translated_id);
                    
                    if ($translated_post && $translated_post->post_status === 'publish') {
                        $final_post = $translated_post;
                        $is_translated = true;
                    }
                }
            }

            // Pobieramy dane (przekazujemy $final_post, który jest albo oryginałem, albo tłumaczeniem)
            $page_data = self::get_post_data($final_post, $lang);

            // Dodajemy informację o głównym slugu (zawsze z oryginału dla spójności)
            // $page_data['main_slug'] = $post->post_name;
            $page_data['is_translation_present'] = $is_translated;

            $result[] = $page_data;
        }

        return $result;
    }

    public static function export_projects_recommended($lang = null) {
        $main_language = pll_default_language(); // np. 'pl'
        
        $args = [
            'post_type'      => 'post',
            'posts_per_page' => 100,
            'post_status'    => 'publish',
            'lang'           => $main_language, // Pobieramy bazę projektów z języka domyślnego
            'tax_query'      => [
                [
                    'taxonomy' => 'category',
                    'field'    => 'slug',
                    'terms'    => 'recommended', // Upewnij się, że ten slug istnieje w j. domyślnym
                ]
            ],
        ];

        $posts = get_posts($args);
        $result = [];

        foreach ($posts as $post) {
            $final_post = $post; // Domyślnie oryginał

            // Jeśli szukamy tłumaczenia i język jest inny niż domyślny
            if ($lang && $lang !== $main_language) {
                $translations = pll_get_post_translations($post->ID);
                
                if (isset($translations[$lang])) {
                    $translated_id = $translations[$lang];
                    $translated_post = get_post($translated_id);
                    
                    // Sprawdzamy czy tłumaczenie jest opublikowane
                    if ($translated_post && $translated_post->post_status === 'publish') {
                        $final_post = $translated_post;
                    }
                }
            }

            // Pobieramy dane z finalnego obiektu (oryginał lub tłumaczenie)
            $post_data = self::get_post_data($final_post, $lang);

            // Zawsze zachowujemy slug z języka domyślnego jako punkt odniesienia
            $post_data['main_slug'] = $post->post_name;

            $result[] = $post_data;
        }

        return $result;
    }

    public static function export_sections($lang = null) {

        $args = [
            'post_type'      => 'section',
            'posts_per_page' => 100,
            'post_status'    => 'publish',
        ];

        if ($lang) {
            $args['lang'] = $lang;
        }

        $main_language = pll_default_language(); // Pobierz domyślny język, np. 'pl'
        
        $pages = get_posts($args);
        $result = [];

        foreach ($pages as $page) {
            // Podstawowe dane strony pobrane Twoją metodą
            $page_data = self::get_page_data($page, $lang);

            
            if($main_language != $lang) {
                $translations = pll_get_post_translations($page->ID);
                $idTranslation = $translations[$main_language] ?? null; // Pobierz ID tłumaczenia dla głównego języka
                if ($idTranslation) {
                    $translated_post = get_post($idTranslation);
                    if ($translated_post) {
                        $page_data['slug'] = $translated_post->post_name;
                    }
                }
            }


            $result[] = $page_data;
        }

       return $result;
    }

    public static function export_menu($menu_name = 'main', $lang = null) {
        $locations = get_nav_menu_locations();
        $main_language = pll_default_language();

        if($lang != $main_language) {
            $menu_name = $menu_name . '___' . $lang; // Zakładamy, że menu dla tłumaczenia ma nazwę np. "main-en"
        }

        if (!isset($locations[$menu_name])) {
            return [];
        }

        $menu_id = $locations[$menu_name];
        $menu_items = wp_get_nav_menu_items($menu_id);


        if (!$menu_items) {
            return [];
        }

        $result = [];
        foreach ($menu_items as $item) {
            $result[] = [
                'id' => $item->ID,
                'title' => $item->title,
                'url' => $item->url,
                'target' => $item->target ?? '',
                'classes' => $item->classes ?? [],
                'description' => $item->description ?? '',
                'parent' => $item->menu_item_parent ? (int) $item->menu_item_parent : 0,
                'type' => $item->type,
                'type_label' => $item->type_label,
                'attr_title' => $item->attr_title ?? '',
            ];
        }

        return $result;
    }

    public static function export_site_settings() {
        $settings = [
            'title' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
        ];

        // Dodaj logo z theme customizer
        $logo_id = get_theme_mod('custom_logo');
        if ($logo_id) {
            $logo_url = wp_get_attachment_image_url($logo_id, 'full');
            if ($logo_url) {
                $settings['logo'] = self::convert_image_url_to_api_path($logo_url);
            }
        }

        // Favicon z WordPress site icon
        $favicon_url = get_site_icon_url();
        if ($favicon_url) {
            $settings['favicon'] = self::convert_image_url_to_api_path($favicon_url);
        }

        // OG Image z RankMath - główny cover/featured image
        $rank_math_titles = get_option('rank-math-options-titles');
        if ($rank_math_titles && isset($rank_math_titles['open_graph_image'])) {
            $og_image_url = $rank_math_titles['open_graph_image'];
            if ($og_image_url) {
                $settings['image'] = self::convert_image_url_to_api_path($og_image_url);
            }
        }

        return $settings;
    }

    public static function export_pages_metadata($lang = null) {
        $pages = self::export_pages($lang);
        $result = [];

        foreach ($pages as $page) {
            $post_id = $page['id'];

            // RankMath fields
            $rm_title = get_post_meta($post_id, 'rank_math_title', true);
            $rm_description = get_post_meta($post_id, 'rank_math_description', true);
            $rm_image = get_post_meta($post_id, 'rank_math_facebook_image', true);

            // Fallback to post data
            $title = $rm_title ?: ($page['title']['rendered'] ?? '');
            $description = $rm_description ?: self::clean_description($page['excerpt']['rendered'] ?? '');
            $image = $rm_image ? self::convert_image_url_to_api_path($rm_image) : ($page['featured_image']['source_url'] ?? '');

            $result[] = [
                'slug' => $page['slug'],
                'title' => $title,
                'description' => $description,
                'image' => $image,
            ];
        }

        return $result;
    }

    public static function export_posts_metadata($lang = null) {
        $posts = self::export_posts('post', $lang);
        $result = [];

        foreach ($posts as $post) {
            $post_id = $post['id'];

            // RankMath fields
            $rm_title = get_post_meta($post_id, 'rank_math_title', true);
            $rm_description = get_post_meta($post_id, 'rank_math_description', true);
            $rm_image = get_post_meta($post_id, 'rank_math_facebook_image', true);

            // Fallback to post data
            $title = $rm_title ?: ($post['title']['rendered'] ?? '');
            $description = $rm_description ?: self::clean_description($post['excerpt']['rendered'] ?? '');
            $image = $rm_image ? self::convert_image_url_to_api_path($rm_image) : ($post['featured_image']['source_url'] ?? '');

            $result[] = [
                'slug' => $post['slug'],
                'title' => $title,
                'description' => $description,
                'image' => $image,
            ];
        }

        return $result;
    }

    private static function get_page_data($post, $lang = null) {
        $featured_media_id = get_post_thumbnail_id($post->ID);
        $featured_image = null;

        if ($featured_media_id) {
            $image_url = self::get_attachment_url_with_webp($featured_media_id);
            $featured_image = [
                'source_url' => self::convert_image_url_to_api_path($image_url),
                'alt_text' => get_post_meta($featured_media_id, '_wp_attachment_image_alt', true),
            ];
        }

        $page_data = [
            'id' => $post->ID,
            'title' => [
                'rendered' => $post->post_title,
            ],
            'content' => [
                'rendered' => apply_filters('the_content', $post->post_content),
            ],
            'excerpt' => [
                'rendered' => $post->post_excerpt ?: wp_strip_all_tags($post->post_content),
            ],
            'slug' => $post->post_name,
            'date' => $post->post_date_gmt,
        ];

        // Dodaj featured image jeśli istnieje
        if ($featured_image) {
            $page_data['featured_image'] = $featured_image;
        }

        // Dodaj ACF jeśli jest dostępny
        if (function_exists('get_field')) {
            $acf_data = get_fields($post->ID);
            if ($acf_data) {
                $page_data['acf'] = self::convert_urls_in_data($acf_data);
            }
        }

        return $page_data;
    }

    private static function get_post_data($post, $lang = null) {
        $featured_media_id = get_post_thumbnail_id($post->ID);
        $featured_image = null;

        if ($featured_media_id) {
            $image_url = self::get_attachment_url_with_webp($featured_media_id);
            $featured_image = [
                'source_url' => self::convert_image_url_to_api_path($image_url),
                'alt_text' => get_post_meta($featured_media_id, '_wp_attachment_image_alt', true),
            ];
        }

        $post_data = [
            'id' => $post->ID,
            'title' => [
                'rendered' => $post->post_title,
            ],
            'content' => [
                'rendered' => apply_filters('the_content', $post->post_content),
            ],
            'excerpt' => [
                'rendered' => $post->post_excerpt ?: wp_strip_all_tags($post->post_content),
            ],
            'slug' => $post->post_name,
            'date' => $post->post_date_gmt,
            'featured_media' => $featured_media_id,
        ];

        // Dodaj featured image
        if ($featured_image) {
            $post_data['featured_image'] = $featured_image;
        }

        // Dodaj kategorie
        $categories = wp_get_post_categories($post->ID, ['fields' => 'ids']);
        if ($categories) {
            $post_data['categories'] = $categories;
        }

        // Dodaj tagi (nazwy zamiast ID)
        $tags = wp_get_post_tags($post->ID, ['fields' => 'names']);
        if ($tags) {
            $post_data['tags'] = $tags;
        }

        // Dodaj ACF jeśli jest dostępny
        if (function_exists('get_field')) {
            $acf_data = get_fields($post->ID);
            if ($acf_data) {
                $post_data['acf'] = self::convert_urls_in_data($acf_data);
            }
        }

        return $post_data;
    }

    public static function generate_all_json_files() {
        $result = [];

        try {
            // Eksportuj języki
            $languages = self::export_languages();
            $result['languages'] = [
                'success' => true,
                'count' => count($languages),
                'data' => $languages,
            ];

            // Dla każdego języka eksportuj dane
            foreach ($languages as $lang) {
                $lang_slug = $lang['slug'];

                // siteSettings z metadanymi stron i wpisów
                $site_settings = self::export_site_settings();
                $site_settings['pages'] = self::export_pages_metadata($lang_slug);
                $site_settings['posts'] = self::export_posts_metadata($lang_slug);

                $lang_dir = [
                    'pages' => self::export_pages($lang_slug),
                    'posts' => self::export_posts('post', $lang_slug),
                    'projects' => self::export_projects_recommended($lang_slug),
                    'menu' => self::export_menu('menu', $lang_slug),
                    'siteSettings' => $site_settings,
                ];

                $result[$lang_slug] = [
                    'success' => true,
                    'data' => $lang_dir,
                ];
            }
            
            return $result;

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
