<?php
function RUP_VB_video_bank_videos_get_youtube_embed($url) {
    parse_str(parse_url($url, PHP_URL_QUERY), $url_params);
    $embedID = isset($url_params['v']) ? $url_params['v'] : substr(parse_url($url, PHP_URL_PATH), 1);
    return "https://www.youtube.com/embed/{$embedID}";
}
