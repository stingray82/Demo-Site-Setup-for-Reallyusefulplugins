<?php
function RUP_VB_video_bank_videos_get_bubble_embed($url) {
    $url_path = parse_url($url, PHP_URL_PATH);
    //error_log('The value of $url_path is: ' . $url_path);
    
    $url_parts = explode('/', trim($url_path, '/')); // Trim the leading/trailing slashes and explode the path into parts
    //error_log('The value of $url_parts is: ' . print_r($url_parts, true)); // Log the array content

    // The embedID should be the first part of the path in your case
    $embedID = $url_parts[0];
    //error_log('The value of $embedID is: ' . $embedID);

    return "https://app.usebubbles.com/embed/{$embedID}";
}
