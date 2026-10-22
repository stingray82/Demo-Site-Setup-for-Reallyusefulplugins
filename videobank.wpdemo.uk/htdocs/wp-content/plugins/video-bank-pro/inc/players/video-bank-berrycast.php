<?php
function RUP_VB_video_bank_videos_get_berrycast_embed($url) {
    $url_path = parse_url($url, PHP_URL_PATH);
    $url_parts = explode('/', rtrim($url_path, '/'));
    $embedID = end($url_parts);
    return "https://www.berrycast.com/conversations/{$embedID}/video-player";
}