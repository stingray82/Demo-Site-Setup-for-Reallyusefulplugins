<?php
/**
 * Plugin Name: Admin Media Upload Notice
 * Description: Displays a banner in the admin area reminding users that they can upload files via the Media panel. Files are stored in /var/www/ticketmanagerpro.wpdemo.uk/htdocs/wp-content/uploads/sites/{site_id}. Upload PDFs for delivery and ensure file names are unique for multiple events.
 * Version: 1.2
 * Author: Your Name
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Display admin notices for Surecart and SureMail.
 */
add_action( 'admin_notices', 'ssan_display_notices' );
function ssan_display_notices() {
    // Only show the notices to users with the manage_options capability (admins).
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

      // Surecart Notice
    $surecart_message = sprintf(
        '<p><strong>Surecart Notice:</strong> Surecart is installed and activated. You will either need to create a new account or connect to your store using an API key.</code></p>',
        esc_html( $upload_path )
    );

    // SureMail SMTP Notice
    $suremail_message = '<p><strong>SMTP Notice:</strong> To prevent SPAM emails, email functionality has been disabled. If you wish to send emails, you will need to connect your SMTP account using SureMail.</p>';

    echo '<div class="notice notice-info is-dismissible">' . $surecart_message . '</div>';
    echo '<div class="notice notice-warning is-dismissible">' . $suremail_message . '</div>';

    //Surecart Checkout Form Message
    $surecart_checkout = '<p><strong>Warning:</strong> You need to add the short code [surecart_links] to your dashboard to view the links if testing and to be able to see them</p>';
    echo '<div class="notice notice-warning is-dismissible">' . $surecart_checkout . '</div>';


}