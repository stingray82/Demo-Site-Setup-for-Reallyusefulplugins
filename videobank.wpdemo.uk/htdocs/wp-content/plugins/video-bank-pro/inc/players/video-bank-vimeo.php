<?php
function RUP_VB_video_bank_videos_get_vimeo_embed($url) {
    $embedID = (int) substr(parse_url($url, PHP_URL_PATH), 1);
    return "https://player.vimeo.com/video/{$embedID}";
}
