<?php
function RUP_VB_video_bank_videos_get_bunny_embed($url) {
    // Parse the URL to get the path component
    $url_path = parse_url($url, PHP_URL_PATH);
    //error_log('The value of $url_path is: ' . $url_path);

    // Split the path into parts
    $url_parts = explode('/', trim($url_path, '/'));
    //error_log('The value of $url_parts is: ' . print_r($url_parts, true)); // Log the array content

    // The video library ID is the second part, and the video ID is the third part
    if (count($url_parts) >= 3 && $url_parts[0] === 'play') {
        $video_library_id = $url_parts[1]; // 12468
        $video_id = $url_parts[2]; // 49497333-f288-4821-aa3a-4cf3db9e654a
        
        // Construct the new embed URL
        $embed_url = "https://iframe.mediadelivery.net/embed/{$video_library_id}/{$video_id}";
        //error_log('The generated embed URL is: ' . $embed_url);
        
        return $embed_url;
    } else {
        //error_log('Invalid URL structure.');
        return null; // Or handle the error as needed
    }
}