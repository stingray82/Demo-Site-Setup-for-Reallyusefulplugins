<?php
/**
 * Plugin Name:       WP Video Bank
 * Description:       embed support tutorials straight into client dashboards, choose from multiple video players, and enjoy full white-label customization
 * Version:           0.9.5
 * Requires at least: 6.5
 * Tested up to:      6.8.2
 * Requires PHP:      8.0
 * Author:            ReallyUsefulPlugins.com
 * Author URI:        https://Reallyusefulplugins.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       video-bank-pro
 * Website:           https://reallyusefulplugins.com
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Whitelabel Settings 
define('RUP_VB_PRO_MAIN_PLUGIN_FILE', __FILE__);


// Include platform-specific handlers.
require_once plugin_dir_path(__FILE__) . 'inc/players/video-bank-loom.php';


// Register settings, add menus, and enqueue scripts/styles.
require_once plugin_dir_path(__FILE__) . 'admin/video-bank-settings.php';



// Register settings, add menus, and enqueue scripts/styles.
function RUP_VB_video_bank_register_settings() {
    add_option('RUP_VB_video_bank_videos', array());
    register_setting('RUP_VB_video_bank_options', 'RUP_VB_video_bank_videos', 'RUP_VB_video_bank_sanitize_videos');

    // Register the show/hide pages option
    add_option('RUP_VB_video_bank_show_pages', 'true'); // Default to showing pages
    register_setting('RUP_VB_video_bank_options', 'RUP_VB_video_bank_show_pages');
}
add_action('admin_init', 'RUP_VB_video_bank_register_settings');

// Sanitize the videos array before saving.
function RUP_VB_video_bank_sanitize_videos($input) {
    $sanitized_videos = array();
    foreach ($input as $video) {
        $sanitized_video = array(
            'url' => esc_url_raw($video['url'], array('http', 'https')),
            'description' => wp_kses($video['description'], [
                'span' => ['style' => true, 'class' => true],
                'b' => [],
                'strong' => [],
                'i' => [],
                'em' => [],
                'u' => [],
                'br' => [],
                'p' => ['style' => true, 'class' => true],
                'ul' => ['class' => true],
                'ol' => ['class' => true],
                'li' => ['class' => true],
                'a' => ['href' => true, 'target' => true, 'rel' => true, 'class' => true],
                'h1' => ['style' => true], 'h2' => ['style' => true], 'h3' => ['style' => true],
                'h4' => ['style' => true], 'h5' => ['style' => true], 'h6' => ['style' => true],
            ]),

            'topic' => sanitize_text_field($video['topic']),
            'uniqvidid' => isset($video['uniqvidid']) ? sanitize_text_field($video['uniqvidid']) : 'VID' . str_pad(count($sanitized_videos) + 1, 9, '0', STR_PAD_LEFT),
        );
        $sanitized_videos[] = $sanitized_video;
    }
    return $sanitized_videos;
}



/**
 * Enqueue the plugin styles and scripts.
 */
function RUP_VB_video_bank_enqueue_assets() {
    // Enqueue the CSS file
    wp_enqueue_style(
        'video_bank-styles',
        plugin_dir_url(__FILE__) . 'css/video.css', // Updated to match your directory structure
        array(),
        '1.0',
        'all'
    );

    // Enqueue jQuery UI (for sortable functionality) if not already enqueued
    if (!wp_script_is('jquery-ui-sortable', 'enqueued')) {
        wp_enqueue_script('jquery-ui-sortable');
    }

    // Enqueue the JavaScript file
    wp_enqueue_script(
        'video_bank-scripts',
        plugin_dir_url(__FILE__) . 'js/video-bank.js',
        array('jquery', 'jquery-ui-sortable'), // Ensure dependencies are loaded
        '1.0',
        true
    );
}
add_action('admin_enqueue_scripts', 'RUP_VB_video_bank_enqueue_assets');


/**
 * Determine and return the embed HTML for a given URL or ID.
 */
function RUP_VB_video_bank_get_embed_url( $url_or_id ) {
    $embed = '';

    // Only run when Pro licensing is active
    if ( is_rupvb_ready() ) {
        if ( strpos( $url_or_id, 'loom.com' ) !== false ) {
            $embed = RUP_VB_video_bank_get_loom_embed( $url_or_id );
        } elseif ( strpos( $url_or_id, 'youtube.com' ) !== false || strpos( $url_or_id, 'youtu.be' ) !== false ) {
            $embed = RUP_VB_video_bank_videos_get_youtube_embed( $url_or_id );
        } elseif ( strpos( $url_or_id, 'vimeo.com' ) !== false ) {
            $embed = RUP_VB_video_bank_videos_get_vimeo_embed( $url_or_id );
        } elseif ( strpos( $url_or_id, 'mediadelivery.net' ) !== false ) {
            $embed = RUP_VB_video_bank_videos_get_bunny_embed( $url_or_id );
        } elseif ( strpos( $url_or_id, 'usebubbles.com' ) !== false ) {
            $embed = RUP_VB_video_bank_videos_get_bubble_embed( $url_or_id );
        } elseif ( strpos( $url_or_id, 'komododecks.com' ) !== false ) {
            $embed = RUP_VB_video_bank_videos_get_komodo_embed( $url_or_id );
        } elseif ( strpos( $url_or_id, 'sendspark.com' ) !== false ) {
            $embed = RUP_VB_video_bank_videos_get_sendspark_embed( $url_or_id );
        } elseif ( strpos( $url_or_id, 'berrycast.com' ) !== false ) {
            $embed = RUP_VB_video_bank_videos_get_berrycast_embed( $url_or_id );
        } elseif ( strpos( $url_or_id, 'zight.com' ) !== false ) {
            $embed = RUP_VB_video_bank_videos_get_zight_embed( $url_or_id );
        } elseif ( strpos( $url_or_id, 'calip.io' ) !== false || strpos( $url_or_id, 'calipio.com' ) !== false ) {
            $embed = RUP_VB_video_bank_videos_get_calipio_embed( $url_or_id );
        } elseif ( strpos( $url_or_id, 'storyxpress.co' ) !== false ) {
            $embed = RUP_VB_video_bank_videos_get_storyxpress_embed( $url_or_id );
        } elseif ( strpos( $url_or_id, 'wave.video' ) !== false ) {
            $embed = RUP_VB_video_bank_videos_get_wavevideo_embed( $url_or_id );
        } elseif ( strpos( $url_or_id, 'google.com' ) !== false ) {
            $embed = RUP_VB_video_bank_videos_get_google_embed( $url_or_id );
        } elseif ( strpos( $url_or_id, 'dadan.io' ) !== false ) {
            $embed = RUP_VB_video_bank_videos_get_dadan_embed( $url_or_id );
        }
    }

    // Fallback if nothing returned
    if ( empty( $embed ) ) {
        $embed = '<p>Platform not supported. Please check the URL or upgrade to Pro for more platforms.</p>';
    }

    return $embed;
}



if ( ! defined('RUP_VB_PRO_LOADED') ) {
    define('RUP_VB_PRO_LOADED', true);
}



/// Load Our Additional Players
require_once plugin_dir_path(__FILE__) . 'inc/players/video-bank-youtube.php';
require_once plugin_dir_path(__FILE__) . 'inc/players/video-bank-vimeo.php';
require_once plugin_dir_path(__FILE__) . 'inc/players/video-bank-bunny.php';
require_once plugin_dir_path(__FILE__) . 'inc/players/video-bank-bubble.php';
require_once plugin_dir_path(__FILE__) . 'inc/players/video-bank-komodo.php';
require_once plugin_dir_path(__FILE__) . 'inc/players/video-bank-sendspark.php';
require_once plugin_dir_path(__FILE__) . 'inc/players/video-bank-berrycast.php';
require_once plugin_dir_path(__FILE__) . 'inc/players/video-bank-zight.php';
require_once plugin_dir_path(__FILE__) . 'inc/players/video-bank-calipio.php';
require_once plugin_dir_path(__FILE__) . 'inc/players/video-bank-storyxpress.php';
require_once plugin_dir_path(__FILE__) . 'inc/players/video-bank-wavevideo.php';
require_once plugin_dir_path(__FILE__) . 'inc/players/video-bank-google.php';
require_once plugin_dir_path(__FILE__) . 'inc/players/video-bank-dadan.php';



// etc.

function RUP_VB_PRO_initialize_sureforms_licensing() {
    $white_label = get_option('rup_vb_whitelabel', array());
    $fields_populated = !empty($white_label['plugin_name']) && !empty($white_label['plugin_description']) && !empty($white_label['menu_name']);
    $show_whitelabel = (!$fields_populated || (defined('RUP_VB_SHOW_WHITELABEL_EDIT') && RUP_VB_SHOW_WHITELABEL_EDIT));
    $client_path = plugin_dir_path( __FILE__ ) . 'inc/src/Client.php';

    if ( ! file_exists( $client_path ) ) {
        error_log( 'Client.php not found at: ' . $client_path );
        return;
    }

    if ( ! class_exists( 'rupvbpro\Licensing\Client' ) ) {
        require_once $client_path;
        require_once plugin_dir_path(__FILE__) . 'inc/src/toolbox.php';
    }

    // Initialize the SureCart Licensing Client
    $client = new \rupvbpro\Licensing\Client( 'wpvideobank', 'pt_usTzCchJQpYtKWCoRCusHS5P', __FILE__ );

    // Optionally set your plugin's textdomain for internationalization
    //$client->set_textdomain( 'response-for-sureforms-textdomain' );

    // Add the license management settings page
    if ($show_whitelabel) {
    $client->settings()->add_page(
        [
            'type'        => 'submenu',
            'parent_slug' => 'video_bank-videos', // Submenu under your plugin menu
            'page_title'  => 'Manage License',
            'menu_title'  => 'Manage License',
            'capability'  => 'manage_options',
            'menu_slug'   => 'rupvbpro_license_settings',
        ]
    );
}
    
}
add_action( 'init', 'RUP_VB_PRO_initialize_sureforms_licensing' );




add_filter('all_plugins', 'RUP_VB_white_label_plugin_data_pro');

add_filter( 'rup_vb_white_label_plugin_data', 'RUP_VB_white_label_plugin_data_pro', 10, 1 );
function RUP_VB_white_label_plugin_data_pro( $plugins ) {
    if ( ! function_exists( 'is_rupvb_ready' ) || ! is_rupvb_ready() ) {
        return $plugins;
    }

    // Retrieve white label settings
    $white_label = get_option( 'rup_vb_whitelabel', array() );

    // Determine the plugin file key for Pro
    if ( defined( 'RUP_VB_PRO_MAIN_PLUGIN_FILE' ) ) {
        $my_plugin_file = plugin_basename( RUP_VB_PRO_MAIN_PLUGIN_FILE );
    } else {
        $my_plugin_file = plugin_basename( __FILE__ );
    }

    // Make sure our plugin exists in the list
    if ( isset( $plugins[ $my_plugin_file ] ) ) {
        // Hide plugin if requested
        if ( ! empty( $white_label['hide_plugin'] ) ) {
            unset( $plugins[ $my_plugin_file ] );
            return $plugins;
        }

        // Override header data
        if ( ! empty( $white_label['plugin_name'] ) ) {
            $plugins[ $my_plugin_file ]['Name'] = $white_label['plugin_name'];
        }
        if ( ! empty( $white_label['plugin_description'] ) ) {
            $plugins[ $my_plugin_file ]['Description'] = $white_label['plugin_description'];
        }
        if ( ! empty( $white_label['plugin_uri'] ) ) {
            $plugins[ $my_plugin_file ]['PluginURI'] = $white_label['plugin_uri'];
        }
        if ( ! empty( $white_label['author'] ) ) {
            $plugins[ $my_plugin_file ]['Author'] = $white_label['author'];
        }
        if ( ! empty( $white_label['author_uri'] ) ) {
            $plugins[ $my_plugin_file ]['AuthorURI'] = $white_label['author_uri'];
        }
    }

    return $plugins;
}