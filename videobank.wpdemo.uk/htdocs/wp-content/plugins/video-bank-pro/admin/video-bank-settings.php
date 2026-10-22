<?php
/**
 * ADMIN PAGE CODE WITH WHITE LABEL SETTINGS AND FUNCTION PREFIX RUP_VB_
 *
 * This file now allows white labeling of:
 * - Admin Menu Name & Page Titles
 * - Plugin Header data (Name, Description, Plugin URI, Author, Author URI)
 * - Option to hide the plugin from the installed plugins list.
 */

add_filter('tiny_mce_before_init', function ($init) {
    // Allow common inline styles and classes
    $init['extended_valid_elements'] = 'span[*],b,strong,i,em,u,p[*],h1[*],h2[*],h3[*],h4[*],h5[*],h6[*],a[href|target|rel|class|style],ul[*],ol[*],li[*]';
    $init['valid_children'] = '+body[style]';

    // Add toolbar buttons
    $init['toolbar1'] = 'formatselect,fontselect,fontsizeselect,bold,italic,underline,forecolor,backcolor,removeformat,styleselect';
    $init['toolbar2'] = 'bullist,numlist,blockquote,link,unlink,undo,redo';
    $init['toolbar1'] .= ',fontselect';


    // Headings and paragraph options for the dropdown
    $init['block_formats'] = 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4; Heading 5=h5; Heading 6=h6';

    // Font sizes to allow
    $init['font_formats'] = 'Arial=arial,helvetica,sans-serif; Times=times new roman,times; Courier=courier new,courier;';
    $init['fontsize_formats'] = '10px 12px 14px 16px 18px 24px 32px';

    // Text color options
    $init['textcolor_map'] = json_encode([
        "000000", "Black",
        "FF0000", "Red",
        "00FF00", "Green",
        "0000FF", "Blue",
        "FFFF00", "Yellow",
        "FFA500", "Orange",
        "800080", "Purple",
        "808080", "Gray",
        "FFFFFF", "White"
    ]);
    $init['textcolor_rows'] = 6;

    return $init;
});





/**
 * Add the plugin menu pages.
 */
function RUP_VB_add_menu_pages() {
    // Core defaults
    $menu_name  = 'Video Bank';
    $page_title = 'Video Bank Videos';

    if ( function_exists( 'is_rupvb_ready' ) && is_rupvb_ready() ) {
        $white_label = get_option( 'rup_vb_whitelabel', array() );

        // Ensure correct display
        $menu_name  = ! empty( $white_label['menu_name'] ) 
                        ? stripslashes( $white_label['menu_name'] ) 
                        : $menu_name;

        $page_title = ! empty( $white_label['plugin_name'] ) 
                        ? stripslashes( $white_label['plugin_name'] ) . ' Videos' 
                        : $page_title;

        $fields_populated = ! empty( $white_label['plugin_name'] )
                            && ! empty( $white_label['plugin_description'] )
                            && ! empty( $white_label['menu_name'] );

        $show_whitelabel = empty( $white_label['whitelabel_lock'] ) 
                            || ( defined( 'RUP_VB_SHOW_WHITELABEL_EDIT' ) && RUP_VB_SHOW_WHITELABEL_EDIT );
    } else {
        $show_whitelabel = false;
    }

    // Top-level
    add_menu_page(
        $page_title,
        $menu_name,
        'edit_posts',
        'video_bank-videos',
        'RUP_VB_videos_page',
        'dashicons-video-alt3'
    );

    // Submenus (if editable)
    if ( $show_whitelabel ) {
        add_submenu_page(
            'video_bank-videos',
            'Add/Organize Videos',
            'Add/Organize',
            'manage_options',
            'video_bank-settings',
            'RUP_VB_settings_page'
        );
        add_submenu_page(
            'video_bank-videos',
            'Video Bank Options',
            'Options',
            'manage_options',
            'video_bank-options',
            'RUP_VB_options_page'
        );
    }
}

add_action( 'admin_menu', 'RUP_VB_add_menu_pages' );



/**
 * Display the Videos page.
 */
function RUP_VB_videos_page() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_die( 'You do not have sufficient permissions to access this page.' );
    }

    $videos = get_option( 'RUP_VB_video_bank_videos', [] );

    echo "<div class='wrap'>";

    if ( empty( $videos ) ) {
        echo '<h2>' . esc_html__( 'No videos added yet.', 'rup-vb' ) . '</h2>';
        echo '</div>'; // close wrap
        return;
    }

    // Group by topic
    $topics = [];
    foreach ( $videos as $video ) {
        $topics[ $video['topic'] ][] = $video;
    }

    // Determine first embed URL
    $first_topic = reset( $topics );
    $first_video = reset( $first_topic );
    $first_embed = $first_video ? RUP_VB_video_bank_get_embed_url( $first_video['url'] ) : '';

    ?>
    <h1><?php esc_html_e( 'Video Library', 'rup-vb' ); ?></h1>
    <div class="video_bank-container">
        <div class="video_bank-sidebar">
            <?php foreach ( $topics as $topic => $list ) : ?>
                <div class="video_bank-topic">
                    <h3 class="video_bank-topic-title"><?php echo esc_html( stripslashes($topic) ); ?></h3>
                    <ul class="video_bank-videos-list">
                        <?php foreach ( $list as $video ) : ?>
                            <?php $embed = RUP_VB_video_bank_get_embed_url( $video['url'] ); ?>
                            <li class="video-bank-item" style="cursor:pointer;" data-embed-url="<?php echo esc_attr( $embed ); ?>">
                                <div class="video-description"><?php echo wp_kses_post( stripslashes($video['description']) ); ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="video_bank-content">
            <div class="video_bank-video-display">
                <iframe id="video_bank-embed-frame" src="<?php echo esc_attr( $first_embed ); ?>" allow="autoplay; encrypted-media" allowfullscreen></iframe>
            </div>
        </div>
    </div>

    <script>
    jQuery(function($){
        // Initially collapse all video lists and show the first one
        $('.video_bank-videos-list').hide();
        $('.video_bank-videos-list').first().show();

        // Enable clicking a video to load it in the player
        $(document).on('click', '.video-bank-item', function(e){
            e.preventDefault();
            var embedUrl = $(this).data('embed-url');
            $('#video_bank-embed-frame').attr('src', embedUrl);
        });
    });
    </script>
    </div> <!-- closing wrap -->
    <?php
}



/**
 * Settings page for adding and organizing videos.
 */
function RUP_VB_settings_page() {
    ob_start();

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'You do not have sufficient permissions to access this page.' );
    }

    $videos = get_option( 'RUP_VB_video_bank_videos', [] );

    // Handle form submission for add/edit
    if ( isset( $_POST['video_bank_video_url'] ) ) {
        $url   = esc_url_raw( $_POST['video_bank_video_url'] );
        $desc  = wp_kses_post( $_POST['video_bank_video_description'] );
        $topic = sanitize_text_field( $_POST['video_bank_video_topic'] );
        $uniq  = sanitize_text_field( $_POST['uniqvidid'] ?? '' );

        if ( $uniq ) {
            $videos = array_values(array_filter( $videos, fn( $v ) => $v['uniqvidid'] !== $uniq ));
        }

        if ( ! $uniq ) {
            $max = 0;
            foreach ( $videos as $v ) {
                $n = (int) substr( $v['uniqvidid'], 3 );
                $max = max( $max, $n );
            }
            $uniq = 'VID' . str_pad( $max + 1, 9, '0', STR_PAD_LEFT );
        }

        $videos[] = [
            'uniqvidid'   => $uniq,
            'url'         => $url,
            'description' => $desc,
            'topic'       => $topic,
        ];

        update_option( 'RUP_VB_video_bank_videos', array_values( $videos ) );
        ob_end_clean();
        ?>
        <script>window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=video_bank-settings&video_updated=1' ) ); ?>';</script>
        <?php
        exit;
    }

    // Handle delete
    if ( isset( $_POST['video_bank_delete_id'] ) ) {
        $del = sanitize_text_field( $_POST['video_bank_delete_id'] );
        $videos = array_filter( $videos, fn( $v ) => $v['uniqvidid'] !== $del );
        update_option( 'RUP_VB_video_bank_videos', array_values( $videos ) );
        echo '<div class="updated"><p>Video deleted successfully.</p></div>';
    }

    if ( isset($_GET['video_updated']) ) {
        echo '<div class="updated"><p>Video saved successfully.</p></div>';
    }

    $topics = [];
    foreach ( $videos as $video ) {
        $topics[ $video['topic'] ][] = $video;
    }
    ?>
    <div class="wrap" style="padding-left:20px;">
        <h1><?php esc_html_e( 'Add / Edit Video', 'rup-vb' ); ?></h1>
        <form id="video_bank-settings-form" method="post">
            <input type="hidden" name="uniqvidid" id="uniqvidid" value="">
            <table class="form-table">
                <tr>
                    <th><label for="video_bank-video-url"><?php _e( 'Video URL or ID', 'rup-vb' ); ?></label></th>
                    <td>
                        <input name="video_bank_video_url" id="video_bank-video-url" type="url" class="regular-text" required>
                        <p class="description">Paste an an embed-compatible URL i.e Youtube or Vimeo</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="video_bank-video-description"><?php _e( 'Video Title / Short Description', 'rup-vb' ); ?></label></th>
                    <td>
                        <?php
                        wp_editor(
                            '',
                            'video_bank_video_description',
                            [
                                'textarea_name' => 'video_bank_video_description',
                                'media_buttons' => false,
                                'textarea_rows' => 5,
                                'teeny'         => false,
                                'quicktags'     => true,
                            ]
                        );
                        ?>
                        <p class="description">Shown in the sidebar. Keep it short and helpful, like “Intro to CSS Grid”.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="video_bank-video-topic"><?php _e( 'Topic (Group or Category)', 'rup-vb' ); ?></label></th>
                    <td>
                        <input name="video_bank_video_topic" id="video_bank-video-topic" type="text" class="regular-text" required>
                        <p class="description">Used to group videos together (e.g., “Beginner Tutorials”, “Design Tips”).</p>
                    </td>
                </tr>
            </table>
            <?php submit_button( __( 'Save Video', 'rup-vb' ) ); ?>
        </form>

        <?php if ( ! empty( $topics ) ) : ?>
            <h2>
                <?php esc_html_e( 'Manage Videos', 'rup-vb' ); ?>
                <span class="dashicons dashicons-info" 
                      title="Click a topic name to expand. Drag topics to reorder them." 
                      style="cursor: help; vertical-align: text-top; margin-left: 4px;"></span>
            </h2>
            <p class="description" style="margin-top: -10px;">
                You can drag topics to reorder them. Click a topic to expand its videos.
            </p>

            <div id="video_bank_topic_sortable" class="video_bank-saved-videos" style="max-height:600px; overflow-y:auto; padding-right:20px;">
                <?php foreach ( $topics as $topic => $list ) : ?>
                    <div class="postbox video-topic-card" data-topic="<?php echo esc_attr( $topic ); ?>">
                        <h2 class="hndle video-bank-topic-handle" style="padding:10px; cursor:move;"><?php echo esc_html( $topic ); ?></h2>
                        <div class="inside video-bank-topic-body">
                            <ul class="video-list">
                                <?php foreach ( $list as $video ) : ?>
                                    <li style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;">
                                        <span><?php echo wp_kses_post( stripslashes($video['description']) ); ?></span>
                                        <span>
                                            <button type="button" class="button button-primary video_bank-edit" 
                                                data-id="<?php echo esc_attr( $video['uniqvidid'] ); ?>" 
                                                data-url="<?php echo esc_attr( $video['url'] ); ?>" 
                                                data-description="<?php echo esc_attr( wp_kses_post( $video['description'] ) ); ?>"
                                                data-topic="<?php echo esc_attr( stripslashes($video['topic']) ); ?>"
                                                style="margin-right:8px;"><?php esc_html_e( 'Edit', 'rup-vb' ); ?>
                                            </button>
                                            <form method="post" style="display:inline;" 
                                                onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this video?', 'rup-vb' ) ); ?>');">
                                                <input type="hidden" name="video_bank_delete_id" value="<?php echo esc_attr( $video['uniqvidid'] ); ?>">
                                                <button type="submit" class="button button-secondary"><?php esc_html_e( 'Delete', 'rup-vb' ); ?></button>
                                            </form>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
    jQuery(function($){
        $('.video-bank-topic-body').hide();
        $(document).on('click', '.video-bank-topic-handle', function(){
            $(this).toggleClass('closed').next('.video-bank-topic-body').slideToggle();
        });
        $('#video_bank_topic_sortable').sortable({
            handle: '.video-bank-topic-handle',
            items: '.video-topic-card',
            update: function(){
                var order = $(this).children().map(function(){ return $(this).data('topic'); }).get();
                $.post(ajaxurl, { action:'video_bank_save_topic_order', order:order });
            }
        });

        $('.video_bank-edit').on('click', function(){
            $('#uniqvidid').val($(this).data('id'));
            $('#video_bank-video-url').val($(this).data('url'));
            $('#video_bank-video-topic').val($(this).data('topic'));

            const content = $(this).data('description');
            if (typeof tinyMCE !== 'undefined' && tinyMCE.get('video_bank_video_description')) {
                tinyMCE.get('video_bank_video_description').setContent(content);
            } else {
                $('#video_bank_video_description').val(content);
            }
        });
    });
    </script>
    <?php
}




/**
 * Options page including Show/Hide pages and White Label settings.
 */
function RUP_VB_options_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    if (isset($_POST['rup_vb_white_label'])) {
        $white_label = array(
            'plugin_name'        => wp_unslash($_POST['rup_vb_white_label']['plugin_name']),
            'plugin_description' => wp_unslash($_POST['rup_vb_white_label']['plugin_description']),
            'menu_name'          => wp_unslash($_POST['rup_vb_white_label']['menu_name']),
            'plugin_uri'         => esc_url_raw($_POST['rup_vb_white_label']['plugin_uri']),
            'author'             => wp_unslash($_POST['rup_vb_white_label']['author']),
            'author_uri'         => esc_url_raw($_POST['rup_vb_white_label']['author_uri']),
            'hide_plugin'        => isset($_POST['rup_vb_white_label']['hide_plugin']) ? 1 : 0,
            'whitelabel_lock'    => isset($_POST['rup_vb_white_label']['whitelabel_lock']) ? 1 : 0,
        );
        update_option('rup_vb_whitelabel', $white_label);
        echo '<div class="updated"><p>White label settings saved successfully.</p></div>';
    }

    $white_label = get_option('rup_vb_whitelabel', array());
    $fields_populated = !empty($white_label['plugin_name']) && !empty($white_label['plugin_description']) && !empty($white_label['menu_name']);
    $show_whitelabel = (!$fields_populated || (defined('RUP_VB_SHOW_WHITELABEL_EDIT') && RUP_VB_SHOW_WHITELABEL_EDIT));

    echo '<div class="wrap">';
    echo '<h1>Video Bank Options</h1>';
    echo '<form method="post" enctype="multipart/form-data" action="">';

    if ( defined('RUP_VB_PRO_LOADED') ) {
        echo '<h2>White Label Settings</h2>';
        echo '<table class="form-table">';

        echo '<tr>';
        echo '<th><label for="rup_vb_white_label_plugin_name">Plugin Name</label></th>';
        echo '<td><input type="text" id="rup_vb_white_label_plugin_name" name="rup_vb_white_label[plugin_name]" value="' . (!empty($white_label['plugin_name']) ? esc_attr($white_label['plugin_name']) : '') . '" class="regular-text"></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th><label for="rup_vb_white_label_plugin_description">Plugin Description</label></th>';
        echo '<td><input type="text" id="rup_vb_white_label_plugin_description" name="rup_vb_white_label[plugin_description]" value="' . (!empty($white_label['plugin_description']) ? esc_attr($white_label['plugin_description']) : '') . '" class="regular-text"></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th><label for="rup_vb_white_label_menu_name">Admin Menu Name</label></th>';
        echo '<td><input type="text" id="rup_vb_white_label_menu_name" name="rup_vb_white_label[menu_name]" value="' . (!empty($white_label['menu_name']) ? esc_attr($white_label['menu_name']) : '') . '" class="regular-text"></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th><label for="rup_vb_white_label_plugin_uri">Plugin URI</label></th>';
        echo '<td><input type="text" id="rup_vb_white_label_plugin_uri" name="rup_vb_white_label[plugin_uri]" value="' . (!empty($white_label['plugin_uri']) ? esc_url($white_label['plugin_uri']) : '') . '" class="regular-text"></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th><label for="rup_vb_white_label_author">Author</label></th>';
        echo '<td><input type="text" id="rup_vb_white_label_author" name="rup_vb_white_label[author]" value="' . (!empty($white_label['author']) ? esc_attr($white_label['author']) : '') . '" class="regular-text"></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th><label for="rup_vb_white_label_author_uri">Author URI</label></th>';
        echo '<td><input type="text" id="rup_vb_white_label_author_uri" name="rup_vb_white_label[author_uri]" value="' . (!empty($white_label['author_uri']) ? esc_url($white_label['author_uri']) : '') . '" class="regular-text"></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th><label for="rup_vb_white_label_hide_plugin">Hide from Plugins List</label></th>';
        echo '<td><input type="checkbox" id="rup_vb_white_label_hide_plugin" name="rup_vb_white_label[hide_plugin]" value="1" ' . (!empty($white_label['hide_plugin']) ? 'checked' : '') . '></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th><label for="rup_vb_white_label_whitelabel_lock">Lock White Label Settings</label></th>';
        echo '<td><input type="checkbox" id="rup_vb_white_label_whitelabel_lock" name="rup_vb_white_label[whitelabel_lock]" value="1" ' . (!empty($white_label['whitelabel_lock']) ? 'checked' : '') . '>';
        echo '<p class="description">Prevents further editing of white label settings and hides related admin pages unless unlocked.</p>';
        echo '</td>';
        echo '</tr>';

        echo '</table>';
    }

    echo '<h2>Export/Import Settings & Videos</h2>';
    echo '<p><a href="' . admin_url('admin.php?page=video_bank-options&video_bank_export=true') . '" class="button">Export</a></p>';
    echo '<h3>Import</h3>';
    echo '<input type="file" name="video_bank_import_file" />';
    echo '<p><button id="video_bank_import_btn" name="video_bank_import_btn" class="button">Import</button></p>';

    submit_button('Save Options');
    echo '</form>';
    echo '</div>';
}



if (isset($_POST['video_bank_import_btn'])) {
    RUP_VB_import_videos();
}

/**
 * Export Videos and White Label Settings as JSON.
 */
function RUP_VB_export_videos() {
    $videos = get_option('RUP_VB_video_bank_videos', array());
    $white_label = get_option('rup_vb_whitelabel', array());
    $data = array(
        'videos'      => $videos,
        'white_label' => $white_label,
    );

    if (empty($videos) && empty($white_label)) {
        wp_die('No videos or settings available to export.');
    }

    $filename = 'video-bank-export.json';
    $json_data = json_encode($data);

    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($json_data));
    header('Connection: close');

    echo $json_data;
    exit;
}
if (isset($_GET['video_bank_export']) && $_GET['video_bank_export'] === 'true') {
    RUP_VB_export_videos();
}


/**
 * Import Videos and White Label Settings from JSON.
 */
function RUP_VB_import_videos() {
    if (isset($_FILES['video_bank_import_file']) && $_FILES['video_bank_import_file']['error'] == UPLOAD_ERR_OK) {
        $import_file = $_FILES['video_bank_import_file']['tmp_name'];
        $raw = file_get_contents($import_file);
        error_log('Imported raw data: ' . $raw);

        $import_data = json_decode($raw, true);
        error_log('Decoded import data: ' . print_r($import_data, true));

        if (json_last_error() === JSON_ERROR_NONE && is_array($import_data)) {
            if (isset($import_data['videos'])) {
                $videos_data = $import_data['videos'];
                if (isset($import_data['white_label']) && is_array($import_data['white_label'])) {
                    update_option('rup_vb_whitelabel', $import_data['white_label']);
                }
            } else {
                $videos_data = $import_data;
            }
            
            $existing_videos = get_option('RUP_VB_video_bank_videos', array());
            $existing_ids = array_column($existing_videos, 'uniqvidid');
            $max_id = 0;
            foreach ($existing_ids as $id) {
                $num_part = (int) substr($id, 3);
                if ($num_part > $max_id) {
                    $max_id = $num_part;
                }
            }
            
            foreach ($videos_data as &$video) {
                if (!isset($video['uniqvidid']) || empty($video['uniqvidid'])) {
                    $max_id++;
                    $video['uniqvidid'] = 'VID' . str_pad($max_id, 9, '0', STR_PAD_LEFT);
                } elseif (in_array($video['uniqvidid'], $existing_ids)) {
                    $max_id++;
                    $video['uniqvidid'] = 'VID' . str_pad($max_id, 9, '0', STR_PAD_LEFT);
                }
            }
            
            $final_videos = array_merge($existing_videos, $videos_data);
            error_log('Final Videos Array: ' . print_r($final_videos, true));
            update_option('RUP_VB_video_bank_videos', $final_videos);
            echo '<div class="updated"><p>Import completed successfully.</p></div>';
        } else {
            echo '<div class="error"><p>Invalid JSON format. Please check the file and try again.</p></div>';
        }
    } else {
        echo '<div class="error"><p>No file uploaded or there was an upload error. Please try again.</p></div>';
    }
}



/**
 * Save the new topic order (AJAX).
 */
function RUP_VB_save_topic_order() {
    if (!isset($_POST['order']) || !is_array($_POST['order'])) {
        wp_send_json_error('Invalid order data.');
    }

    $new_order = array_map('sanitize_text_field', $_POST['order']);
    $videos = get_option('RUP_VB_video_bank_videos', array());
    if (!$videos) {
        wp_send_json_error('No videos found.');
    }

    $topics = array();
    foreach ($videos as $video) {
        $topics[$video['topic']][] = $video;
    }

    $ordered_videos = array();
    foreach ($new_order as $topic) {
        if (isset($topics[$topic])) {
            foreach ($topics[$topic] as $video) {
                $ordered_videos[] = $video;
            }
        }
    }

    update_option('RUP_VB_video_bank_videos', $ordered_videos);
    wp_send_json_success('Topic order saved.');
}
add_action('wp_ajax_video_bank_save_topic_order', 'RUP_VB_save_topic_order');


/**
 * Filter the plugin header data to enable white labeling.
 */
function RUP_VB_white_label_plugin_data($plugins) {
    $white_label = get_option('rup_vb_whitelabel', array());

    if ( defined('RUP_VB_MAIN_PLUGIN_FILE') ) {
        $my_plugin_file = plugin_basename(RUP_VB_MAIN_PLUGIN_FILE);
    } else {
        $my_plugin_file = plugin_basename(__FILE__);
    }

    if ( isset($plugins[$my_plugin_file]) ) {
        if ( !empty($white_label['hide_plugin']) ) {
            unset($plugins[$my_plugin_file]);
            return $plugins;
        }

        // strip just to be safe
        $safe_strip = function( $val ) {
            return stripslashes( $val );
        };


        if (!empty($white_label['plugin_name'])) {
            $plugins[$my_plugin_file]['Name'] = $safe_strip($white_label['plugin_name']);
        }
        if (!empty($white_label['plugin_description'])) {
            $plugins[$my_plugin_file]['Description'] = $safe_strip($white_label['plugin_description']);
        }
        if (!empty($white_label['plugin_uri'])) {
            $plugins[$my_plugin_file]['PluginURI'] = esc_url($white_label['plugin_uri']);
        }
        if (!empty($white_label['author'])) {
            $plugins[$my_plugin_file]['Author'] = $safe_strip($white_label['author']);
        }
        if (!empty($white_label['author_uri'])) {
            $plugins[$my_plugin_file]['AuthorURI'] = esc_url($white_label['author_uri']);
        }
    }

    return $plugins;
}


add_filter('all_plugins', 'RUP_VB_white_label_plugin_data');
?>
