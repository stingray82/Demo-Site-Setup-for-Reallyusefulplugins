<?php
/*
Plugin Name: Whitelabel Settings MU Plugin
Description: Adds a top-level admin menu to toggle whitelabel mode on and off.
Version: 1.4
Author: Your Name
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Read the setting at the earliest point to define the constant
$whitelabel_enabled = get_option('rup_vb_show_whitelabel_edit', false);
define('RUP_VB_SHOW_WHITELABEL_EDIT', $whitelabel_enabled);

// Enqueue script for toggle functionality
add_action('admin_enqueue_scripts', function() {
    wp_enqueue_script('whitelabel-toggle-script', plugin_dir_url(__FILE__) . 'whitelabel-toggle.js', ['jquery'], '1.0', true);
    wp_enqueue_style('whitelabel-settings-style', plugin_dir_url(__FILE__) . 'whitelabel-style.css');
});

// Add top-level admin menu
add_action('admin_menu', function() {
    add_menu_page(
        'Whitelabel Settings',
        'Whitelabel Settings',
        'manage_options',
        'whitelabel-settings',
        'whitelabel_settings_page',
        'dashicons-admin-generic',
        25
    );
});

// Global admin notice to guide users to settings page
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $is_enabled = defined('RUP_VB_SHOW_WHITELABEL_EDIT') && RUP_VB_SHOW_WHITELABEL_EDIT;
    $message = $is_enabled ? 'Whitelabel override is currently enabled.' : 'Whitelabel override is currently disabled.';
    $link = admin_url('admin.php?page=whitelabel-settings');
    echo '<div class="notice notice-info is-dismissible">
        <p><strong>' . esc_html($message) . '</strong> You can toggle this setting in the <a href="' . esc_url($link) . '">Whitelabel Settings</a> page.</p>
    </div>';
});

// Admin page content
function whitelabel_settings_page() {
    $is_enabled = defined('RUP_VB_SHOW_WHITELABEL_EDIT') && RUP_VB_SHOW_WHITELABEL_EDIT;
    ?>
    <div class="wrap">
        <h1>Whitelabel Settings</h1>
        <p>This page allows you to enable or disable the Whitelabel mode for your site. Enabling Whitelabel mode will show customization options and branding settings, while disabling it will hide those options.</p>
        <p>Use the toggle below to switch between enabled and disabled modes. The change takes effect immediately and simulates the constant <code>RUP_VB_SHOW_WHITELABEL_EDIT</code>.</p>
        <div class="whitelabel-toggle-wrapper">
            <label class="switch">
                <input type="checkbox" id="whitelabel-toggle" <?php echo $is_enabled ? 'checked' : ''; ?>>
                <span class="slider"></span>
            </label>
            <span class="toggle-label">Enable Whitelabel overide</span>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#whitelabel-toggle').on('change', function() {
                var enabled = $(this).is(':checked') ? 1 : 0;
                $.post(ajaxurl, {
                    action: 'toggle_whitelabel',
                    enabled: enabled
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    }
                });
            });
        });
        </script>
    </div>
    <?php
}

// Handle AJAX request
add_action('wp_ajax_toggle_whitelabel', function() {
    $enabled = isset($_POST['enabled']) && $_POST['enabled'] == '1';
    update_option('rup_vb_show_whitelabel_edit', $enabled);
    wp_send_json_success(['enabled' => $enabled]);
});
