<?php
// Exit if accessed directly.
// toolbox and helper funcitons
defined('ABSPATH') || exit;

/**
 * Check if the plugin is activated and licensed.
 *
 * @return bool True if licensed and activated, false otherwise.
 */
function RUP_VB_video_bank_videos_get_client() {
    static $client = null;

    if ( $client === null ) {
        $client = new \rupvbpro\Licensing\Client( 'wpvideobank', 'pt_usTzCchJQpYtKWCoRCusHS5P', RUP_VB_PRO_MAIN_PLUGIN_FILE );
    }

    return $client;
}

function RUP_VB_video_bank_videos_is_activated_and_licensed() {
    $client = RUP_VB_video_bank_videos_get_client();

    if ( ! $client ) {
        return false;
    }

    // Retrieve license data from the database
    $license_options = get_option( 'wpvideobank_license_options', [] );

    $license_key = $license_options['sc_license_key'] ?? null;
    $activation_id = $license_options['sc_activation_id'] ?? null;

    if ( empty( $license_key ) || empty( $activation_id ) ) {
        return false;
    }

    $activation = new \rupvbpro\Licensing\Activation( $client );
    $license = new \rupvbpro\Licensing\License( $client );

    $is_license_valid = $license->is_valid( $license_key );

    if ( ! $is_license_valid ) {
            return false;
    }


    $activation_data = $activation->get( $activation_id );

    if ( is_wp_error( $activation_data ) ) {
        return false;
    }

    if ( empty( $activation_data ) || ! isset( $activation_data->id ) ) {
        //error_log( '[RUP_VB_video_bank_videos] Activation ID not found in activation data.' );
        return false;
    }

    return true;
}

global $rup_vb_load_players;
$rup_vb_load_players = RUP_VB_video_bank_videos_is_activated_and_licensed();
//error_log( 'License check: ' . var_export( RUP_VB_video_bank_videos_is_activated_and_licensed(), true ) );


function is_rupvb_ready() {
    // Get the saved options from the database.
    $options = get_option('wpvideobank_license_options', []);

    // Check that the expected keys are present and not empty.
    if (
        !empty($options['sc_license_key']) &&
        !empty($options['sc_license_id']) &&
        !empty($options['sc_activation_id'])
    ) {
        return true;
    }
    return false;
}


?>
