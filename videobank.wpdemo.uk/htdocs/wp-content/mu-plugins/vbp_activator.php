<?php
/*
Plugin Name: SureCart Plugins License Activator
Description: Automatically activates a plugin license via SureCart API.
Author: Nathan Foley
Version: 1.31
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Configuration constants.
 *
 * Replace 'your-license-key-here' with your license UUID.
 * Replace 'xxx' with your desired prefix (e.g., 'sc') for the option name.
 */
define( 'SC_LICENSE_KEY', 'c995b33d-e197-303e-7b06-a906f51071b7' );
define( 'SC_OPTION_NAME', 'wpvideobank_license_options' ); // This will result in an option named "xxx_license_options".
define( 'SC_PUBLIC_TOKEN', 'pt_usTzCchJQpYtKWCoRCusHS5P' ); // This should be the public token".

/**
 * SureCart Licensing Client
 */
class SC_Client {
    public $version = '1.0.1';
    public $public_token;
    
    public function __construct( $public_token ) {
        $this->public_token = $public_token;
    }

    public function endpoint() {
        return 'https://api.surecart.com/';
    }

    public function send_request( $method, $route, $body = null ) {
        $headers = [
            'X-SURECART-WP-LICENSING-SDK-VERSION' => $this->version,
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$this->public_token}",
            'Content-Type' => 'application/json'
        ];

        $response = wp_remote_request(
            $this->endpoint() . $route,
            [
                'headers'  => $headers,
                'method'   => $method,
                'timeout'  => 30,
                'body'     => $body ? wp_json_encode( $body ) : null,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return json_decode( wp_remote_retrieve_body( $response ) );
    }
}

/**
 * SureCart License Management
 */
class SC_License {
    protected $client;

    public function __construct( $client ) {
        $this->client = $client;
    }

    public function validate( $license_key ) {
        $route = "v1/public/licenses/{$license_key}";
        return $this->client->send_request( 'GET', $route );
    }
}

/**
 * SureCart Activation Management
 */
class SC_Activation {
    protected $client;

    public function __construct( $client ) {
        $this->client = $client;
    }

    public function create( $license_id ) {
        $body = [
            'activation' => [
                'fingerprint' => esc_url_raw( get_site_url() ),
                'name'        => get_bloginfo(),
                'license'     => $license_id,
            ]
        ];

        return $this->client->send_request( 'POST', 'v1/public/activations', $body );
    }
}

/**
 * Activate SureCart License and store the activation data in the options table.
 */
function sc_activate_license() {
    if ( empty( SC_LICENSE_KEY ) || empty( SC_PUBLIC_TOKEN ) ) {
        error_log( 'SC License activation aborted: Missing license key or public token.' );
        return;
    }

    // Check if activation is already successful
    $stored_license_data = get_option( SC_OPTION_NAME, [] );

    // If activation data exists and matches, do nothing
    if (
        isset($stored_license_data['sc_license_key']) &&
        $stored_license_data['sc_license_key'] === SC_LICENSE_KEY &&
        ! empty($stored_license_data['sc_activation_id'])
    ) {
        return; // No need to re-activate
    }

    // Initialize Client, License, and Activation handlers
    $client = new SC_Client( SC_PUBLIC_TOKEN );
    $license = new SC_License( $client );
    $activation = new SC_Activation( $client );

    // Validate the license
    $validated_license = $license->validate( SC_LICENSE_KEY );

    if ( is_wp_error( $validated_license ) ) {
        error_log( 'SC License validation failed: ' . $validated_license->get_error_message() );
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>SureCart License validation failed. Please check your license key.</p></div>';
        });
        return;
    }

    if ( empty( $validated_license->id ) ) {
        error_log( 'SC License validation failed: No license ID returned.' );
        return;
    }

    // Get License ID
    $license_id = $validated_license->id;

    // Activate the license
    $activation_data = $activation->create( $license_id );

    if ( is_wp_error( $activation_data ) ) {
        error_log( 'SC License activation failed: ' . $activation_data->get_error_message() );
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>SureCart License activation failed. Please try again.</p></div>';
        });
        return;
    }

    if ( empty( $activation_data->id ) ) {
        error_log( 'SC License activation failed: No activation ID returned.' );
        return;
    }

    // Get Activation ID
    $activation_id = $activation_data->id;

    // Prepare the serialized data format
    $license_options = [
        0                  => false, // Represents "i:0;b:0;" in the serialized format.
        'sc_license_key'    => SC_LICENSE_KEY,
        'sc_license_id'     => $license_id,
        'sc_activation_id'  => $activation_id,
    ];

    // Store activation data in the options table
    update_option( SC_OPTION_NAME, $license_options );

    error_log( 'SC License activated successfully and saved in the options table.' );
    add_action('admin_notices', function() {
        echo '<div class="notice notice-success"><p>SureCart License successfully activated.</p></div>';
    });
}

// Run the activation function on admin init
add_action( 'admin_init', 'sc_activate_license' );


/* ==============================================================
   Updater Code (Encapsulated in a Unique Class)

   # FIND AND REPLACE video_bank_pro_activator WITH unique identifier i.e emailit
   # FIND AND REPLACE ehy6Ed8CgUmGXiXiu83YxW9uwPFzMd9Qk  WITH unique identifier  i.e apikey
   # FIND AND REPLACE /mu-plugins/vbp_activator.php  WITH unique identifier i.e /mu-plugins/email-api.php
   # FIND AND REPLACE videobankproactivator  WITH unique identifier i.e emailit-api-interface
   ============================================================== */


/**
 * Class rup_mu_updater_video_bank_pro_activator
 *
 * Encapsulates the update-checking functionality.
 */
if ( ! class_exists('rup_mu_updater_video_bank_pro_activator') ) {
    class rup_mu_updater_video_bank_pro_activator {

        private $api_url;
        private $api_key;
        private $local_file;
        private $plugin_id;

        /**
         * Constructor.
         *
         * @param string $api_url    The API endpoint URL.
         * @param string $api_key    The API key for authentication.
         * @param string $local_file Full filesystem path to the local MU plugin file.
         * @param string $plugin_id  The plugin identifier.
         */
        public function __construct( $api_url, $api_key, $local_file, $plugin_id ) {
            $this->api_url    = $api_url;
            $this->api_key    = $api_key;
            $this->local_file = $local_file;
            $this->plugin_id  = $plugin_id;
        }

        /**
         * Checks for an update via the API and updates the plugin file if an update is available.
         */
        public function check_for_update() {
            // Get the current version from the local plugin file.
            $local_data = $this->get_plugin_header_data_from_file( $this->local_file );
            $local_version = ! empty( $local_data['Version'] ) ? trim( $local_data['Version'] ) : '';

            // Build request arguments.
            $args = array(
                'timeout' => 10,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                ),
                'body'    => array(
                    'plugin'   => $this->plugin_id,
                    'version'  => $local_version,
                    'site_url' => home_url(),
                    'ip'       => $_SERVER['REMOTE_ADDR'],
                ),
            );

            // Query the API.
            $response = wp_remote_post( $this->api_url, $args );
            if ( is_wp_error( $response ) ) {
                error_log( '[RUP API] Error fetching update info: ' . $response->get_error_message() );
                return;
            }

            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body, true );
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                error_log( '[RUP API] Error parsing update info JSON: ' . json_last_error_msg() );
                return;
            }

            // Expected API JSON response:
            // {
            //   "update_available": true,
            //   "download_url": "https://yourserver.com/secure-path/new-version.txt",
            //   "version": "1.32"
            // }
            if ( empty( $data['update_available'] ) || ! $data['update_available'] ) {
                error_log( '[RUP API] No update available. Local version (' . $local_version . ') is current.' );
                return;
            }

            if ( empty( $data['download_url'] ) || empty( $data['version'] ) ) {
                error_log( '[RUP API] Update available, but download_url or version not provided.' );
                return;
            }

            $download_url   = $data['download_url'];
            $remote_version = trim( $data['version'] );

            // Fetch the update file from the provided download URL.
            $remote_response = wp_remote_get( $download_url, array( 'timeout' => 10 ) );
            if ( is_wp_error( $remote_response ) ) {
                error_log( '[RUP API] Error fetching update file: ' . $remote_response->get_error_message() );
                return;
            }

            $remote_body = wp_remote_retrieve_body( $remote_response );
            if ( empty( $remote_body ) ) {
                error_log( '[RUP API] Update file is empty.' );
                return;
            }

            // Optional: Validate remote content (e.g., using a checksum or signature check) here.

            // Backup the local file before updating.
             /* $backup_file = $this->local_file . '.bak-' . time();
            if ( ! copy( $this->local_file, $backup_file ) ) {
                error_log( '[RUP API] Failed to backup local file to ' . $backup_file );
                return;
            } */

            // Write the new update to the local file.
            if ( file_put_contents( $this->local_file, $remote_body ) !== false ) {
                error_log( '[RUP API] Updated plugin from version ' . $local_version . ' to ' . $remote_version );
            } else {
                error_log( '[RUP API] Failed to update local file.' );
            }
        }

        /**
         * Extracts plugin header data from a file.
         *
         * @param string $file Path to the file.
         * @return array Associative array of header fields.
         */
        private function get_plugin_header_data_from_file( $file ) {
            if ( ! file_exists( $file ) ) {
                return array();
            }
            $plugin_data = file_get_contents( $file );
            return $this->get_plugin_header_data_from_string( $plugin_data );
        }

        /**
         * Extracts plugin header data from a string.
         *
         * @param string $plugin_data The contents of the plugin file.
         * @return array Associative array of header fields.
         */
        private function get_plugin_header_data_from_string( $plugin_data ) {
            $default_headers = array(
                'Name'        => 'Plugin Name',
                'Version'     => 'Version',
                'PluginURI'   => 'Plugin URI',
                'Description' => 'Description',
                'Author'      => 'Author',
                'AuthorURI'   => 'Author URI',
                'TextDomain'  => 'Text Domain',
            );

            $data = array();
            foreach ( $default_headers as $field => $header ) {
                if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( $header, '/' ) . '\s*:\s*(.*)$/mi', $plugin_data, $match ) && $match[1] ) {
    $data[ $field ] = trim( $match[1] );
} else {
    $data[ $field ] = '';
}

            }
            return $data;
        }
    }
}

/* ===== End Updater Class ======= */


/* ===== Scheduling Setup ======= */

/**
 * Scheduling Setup
 *
 * Add our custom schedule interval (set via $rup_schedule_interval) to WP-Cron.
 */
$rup_update_api_url      = 'https://tdrepo.uk/update/mu-updater.php';
$rup_api_key             = 'ehy6Ed8CgUmGXiXiu83YxW9uwPFzMd9Qk';
$rup_local_file          = WP_CONTENT_DIR . '/mu-plugins/vbp_activator.php';
$rup_plugin_identifier   = 'videobankproactivator';
$rup_schedule_interval   = 21600; // For testing; change to 21600 (6 hours) for production.

// Generate a unique cron hook name based on the plugin identifier.
$unique_hook = 'rup_mu_remote_updates_api_cron_' . sanitize_title( $rup_plugin_identifier );

// Add our custom schedule interval to WP-Cron.
add_filter( 'cron_schedules', function ( $schedules ) use ( $rup_schedule_interval ) {
    $schedules['rup_custom_interval'] = array(
        'interval' => $rup_schedule_interval,
        'display'  => 'Every ' . ( $rup_schedule_interval / 3600 ) . ' Hours',
    );
    return $schedules;
} );

// Schedule the API update check event for this plugin if it's not already scheduled.
if ( ! wp_next_scheduled( $unique_hook ) ) {
    wp_schedule_event( time(), 'rup_custom_interval', $unique_hook );
}

// Hook the API update check into our scheduled event using the unique hook.
add_action( $unique_hook, function() use ( $rup_update_api_url, $rup_api_key, $rup_plugin_identifier, $rup_local_file ) {
    $updater = new rup_mu_updater_video_bank_pro_activator( $rup_update_api_url, $rup_api_key, $rup_local_file, $rup_plugin_identifier );
    $updater->check_for_update();
} );

// Optionally, trigger an immediate check for testing purposes.
// $updater = new rup_mu_updater_video_bank_pro_activator( $rup_update_api_url, $rup_api_key, $rup_local_file, $rup_plugin_identifier );
// $updater->check_for_update();