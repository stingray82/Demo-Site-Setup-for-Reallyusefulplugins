<?php
function RUP_VB_video_bank_videos_get_calipio_embed($url) {
    // Parse the URL to get its components
    $url_parts = parse_url($url);

    // Initialize the embedID variable
    $embedID = '';

    // Check if the path is present in the URL
    if (isset($url_parts['path'])) {
        // Extract the path part of the URL (e.g., "/ESCk5hMW")
        $path = ltrim($url_parts['path'], '/'); // Remove leading slash

        // Check if a fragment exists (e.g., "#AwS27QUh")
        if (isset($url_parts['fragment'])) {
            // Append the fragment to the path (e.g., "ESCk5hMW#AwS27QUh")
            $embedID = $path . '#' . $url_parts['fragment'];
        } else {
            // Use only the path if no fragment is present
            $embedID = $path;
        }
    }

    // Return the complete embed URL using the extracted embedID
    return "https://calipio.com/app/play/{$embedID}";
}
