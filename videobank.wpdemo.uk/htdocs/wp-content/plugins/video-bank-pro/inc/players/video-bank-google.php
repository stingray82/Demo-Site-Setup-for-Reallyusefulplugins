<?php
function RUP_VB_video_bank_videos_get_google_embed($url) {
    $query = parse_url($url, PHP_URL_QUERY);
    if ( $query === null ) {
        $query = '';
    }
    parse_str($query, $url_params);
    $embedID = isset($url_params['v']) ? $url_params['v'] : substr(parse_url($url, PHP_URL_PATH), 1);
    return "{$url}/preview";
}
