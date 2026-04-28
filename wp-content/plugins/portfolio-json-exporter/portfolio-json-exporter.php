<?php
/**
 * Plugin Name: Portfolio JSON Exporter
 * Plugin URI: https://portfolio.local
 * Description: Generuje pliki JSON z danymi portfolio do pobrania w ZIP-ie
 * Version: 1.0.0
 * Author: Portfolio
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

// Definiuj ścieżkę do pluginu
define('PORTFOLIO_JSON_EXPORTER_DIR', plugin_dir_path(__FILE__));
define('PORTFOLIO_JSON_EXPORTER_URL', plugin_dir_url(__FILE__));

// Ładuj klasy
require_once PORTFOLIO_JSON_EXPORTER_DIR . 'includes/class-exporter.php';
require_once PORTFOLIO_JSON_EXPORTER_DIR . 'includes/class-admin.php';

// Inicjalizuj plugin
function portfolio_json_exporter_init() {
    new Portfolio_JSON_Exporter_Admin();
}

add_action('plugins_loaded', 'portfolio_json_exporter_init');

// Zarejestruj activation hook
register_activation_hook(__FILE__, 'portfolio_json_exporter_activate');

function portfolio_json_exporter_activate() {
    // Możesz dodać coś do zrobienia podczas aktivacji
}



add_action('rest_api_init', function () {

	// remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    
    // add_filter('rest_pre_serve_request', function($value) {
    //     // Możesz wpisać '*' (wszystkie domeny), ale lepiej podać konkretną
    //     header('Access-Control-Allow-Origin: https://portfolio.augustyniak.xyz');
    //     header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    //     header('Access-Control-Allow-Credentials: true');
    //     header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');
        
    //     return $value;
    // });
	
	$post_types = ['page', 'post', 'dictionary', 'section'];

    foreach ($post_types as $type) {
		register_rest_field($type, 'translations', [
            'get_callback' => function ($post_array) {
				$post_id = $post_array['id'];
				$translations = pll_get_post_translations($post_id);
				
				// Opcjonalnie: zamień ID na slugi, aby frontend miał łatwiej
				$formatted_translations = [];
				foreach ($translations as $lang => $id) {
					$formatted_translations[$lang] = [
						'id' => $id,
						'slug' => get_post_field('post_name', $id)
					];
				}
				return $formatted_translations;
			},
			'schema' => null,
        ]);

        register_rest_field($type, 'acf', [
            'get_callback' => function($post_array) {
                // Pobieramy ID posta z tablicy, którą dostarcza API
                $post_id = $post_array['id'];
                // Wymuszamy pobranie wszystkich pól ACF dla tego konkretnego ID
                $fields = get_fields($post_id);
                
                return $fields ? $fields : new stdClass(); // Zwraca obiekt nawet jeśli pusty
            },
            'update_callback' => null,
            'schema'          => null,
        ]);

		register_rest_route('moje-api/v1', '/menu/(?P<location>[a-zA-Z0-9_-]+)', [
			'methods' => 'GET',
			'callback' => 'get_menu_by_location_and_lang',
			'permission_callback' => '__return_true',
		]);

		register_rest_route('moje-api/v1', '/info', [
			'methods' => 'GET',
			'callback' => function($request) {
				// Pobieramy ID z parametrów URL (np. /info?id=123)
				$post_id = $request->get_param('id');
				
				// Jeśli nie ma ID, używamy ID strony głównej ustawionej w WordPressie
				if (!$post_id) {
					$post_id = get_option('page_on_front');
				}

				$data = [
					'name'        => get_bloginfo('name'),
					'logo'        => wp_get_attachment_url(get_theme_mod('custom_logo')),
					'favicon'     => get_site_icon_url(),
					'seo'         => []
				];

				if (defined('WPSEO_VERSION')) {
					// Kluczowa zmiana: pobieramy meta dla konkretnego ID posta
					$surfaces = YoastSEO()->meta->for_post($post_id);

					if ($surfaces) {
						$data['seo'] = [
							'title'          => $surfaces->title,
							'meta_desc'      => $surfaces->metadesc,
							'canonical'      => $surfaces->canonical,
							// Yoast automatycznie wygeneruje te dane, 
							// biorąc pod uwagę nadpisania wprowadzone przez użytkownika w edycji wpisu
							'og_title'       => $surfaces->open_graph_title ?: $surfaces->title,
							'og_description' => $surfaces->open_graph_description ?: $surfaces->metadesc,
							'og_image'       => $surfaces->open_graph_image,
							'twitter_site'   => $surfaces->twitter_site,
							// Opcjonalnie: cały wyrenderowany blok meta tagów
							'head_html'      => $surfaces->get_head()
						];
					}
				}

				return $data;
			},
			'permission_callback' => '__return_true'
		]);
    }
});


function get_menu_by_location_and_lang($data) {
    $location = $data['location']; // np. 'header-menu'
    $lang = isset($_GET['lang']) ? $_GET['lang'] : pll_default_language();

    // 1. Pobierz wszystkie lokalizacje menu
    $locations = get_nav_menu_locations();
    
    if (!isset($locations[$location])) {
        return new WP_Error('no_menu', 'Lokalizacja nie istnieje', ['status' => 404]);
    }

    // 2. Pobierz ID menu dla tej lokalizacji
    $menu_id = $locations[$location];


    // 4. Pobierz elementy menu
    $items = wp_get_nav_menu_items($menu_id);

    return rest_ensure_response($items);
}

function rejestruj_menu_nawigacyjne() {
    register_nav_menus(
      array(
        'menu' => __('Menu Główne'),
      )
    );
  }
  add_action('after_setup_theme', 'rejestruj_menu_nawigacyjne');


add_filter('acf/settings/rest_api_enabled', '__return_true');

add_filter('rest_prepare_slownik', 'add_acf_to_all_languages', 10, 3); // Zmień 'slownik' na swój post_type

function add_acf_to_all_languages($response, $post, $request) {
    // Pobieramy wszystkie pola ACF dla tego konkretnego ID (nawet jeśli to tłumaczenie)
    $fields = get_fields($post->ID);
    
    // Nadpisujemy lub dodajemy obiekt 'acf' do odpowiedzi JSON
    $response->data['acf'] = $fields;

    return $response;
}
