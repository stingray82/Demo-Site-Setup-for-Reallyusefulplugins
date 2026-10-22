<?php
/**
 * Video Bank Loom Embed Functions
 *
 * This file contains functions to handle Loom video embedding and optional
 * API integration for retrieving video details.
 */

/**
 * Extract the Loom video ID and return the embed URL.
 *
 * @param string $url The Loom video URL.
 * @return string The Loom embed URL.
 */
function RUP_VB_video_bank_get_loom_embed($url) {
    // Parse the URL to get the path
    $url_path = parse_url($url, PHP_URL_PATH);
    
    // Extract the video ID from the URL path
    $url_parts = explode('/', rtrim($url_path, '/'));
    $embedID = end($url_parts);
    
    // Return the Loom embed URL
    return "https://www.loom.com/embed/{$embedID}";
}

/**
 * Optionally, you can integrate Loom API to retrieve additional data.
 * This requires an API key and more advanced handling.
 *
 * @param string $api_key Your Loom API key.
 * @param string $video_id The Loom video ID.
 * @return array Video details from the Loom API.
 */
function RUP_VB_video_bank_get_loom_video_details($api_key, $video_id) {
    $api_url = "https://api.loom.com/v1/videos/{$video_id}";
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Bearer {$api_key}"
    ));
    
    // Execute and fetch the response
    $response = curl_exec($ch);
    curl_close($ch);
    
    // Decode the JSON response
    $video_details = json_decode($response, true);
    
    return $video_details;
}

/**
 * Example usage:
 * $embed_url = video_bank_get_loom_embed('https://www.loom.com/share/abcd1234');
 * echo $embed_url; // Outputs: https://www.loom.com/embed/abcd1234
 */
?>
