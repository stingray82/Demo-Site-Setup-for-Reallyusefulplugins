<?php
/**
 * Plugin Name: Secure Multisite Uploads & WP Media Folder Fix
 * Description: Forces uploads into `/uploads/sites/{blog_id}/`, restricts users to their own uploads folder, disables date-based folders, and ensures WP Media Folder uses real directories.
 * Version: 1.3
 */

if (!defined('ABSPATH')) exit;

/**
 * 🛑 Disable Yearly/Monthly Upload Folders in Multisite
 * Ensures uploads go directly into `/uploads/sites/{blog_id}/` without date-based folders.
 */
add_filter('upload_dir', function($dirs) {
    if (is_multisite()) {
        $blog_id = get_current_blog_id();
        $dirs['path'] = WP_CONTENT_DIR . "/uploads/sites/$blog_id";
        $dirs['url'] = WP_CONTENT_URL . "/uploads/sites/$blog_id";
        $dirs['subdir'] = ''; // No date-based folders
    }
    return $dirs;
}, 999);

