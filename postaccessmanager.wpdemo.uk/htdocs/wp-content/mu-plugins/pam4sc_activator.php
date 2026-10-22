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
define( 'SC_LICENSE_KEY', 'bf6dbef0-86c5-4b38-af41-a35d9e5d70eb' );
define( 'SC_OPTION_NAME', 'postaccessmanagerforsurecart_license_options' ); // This will result in an option named "xxx_license_options".
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