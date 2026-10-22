<?php
function RUP_VB_video_bank_videos_get_storyxpress_embed($url) {
    // Parse the URL to get its path
    $path = parse_url($url, PHP_URL_PATH);

    // Extract the video ID from the path (removing the leading "/video/")
    $embedID = ltrim($path, '/video/');

    // Log the extracted embed ID
    //error_log('StoryXpress Embed ID: ' . $embedID);

    // Return the full embed URL using the extracted embed ID
    return "https://embed.storyxpress.co/embed/{$embedID}";
}