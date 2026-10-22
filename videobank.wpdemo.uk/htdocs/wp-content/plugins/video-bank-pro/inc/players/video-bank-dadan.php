<?php
function RUP_VB_video_bank_videos_get_dadan_embed($url) {
    if ( preg_match('#/(?:embed|share)/([a-zA-Z0-9]+)#', $url, $matches) ) {
        $video_id = $matches[1];
        return 'https://app.dadan.io/video/embed/' . $video_id .
            '?hide_emojies=false&hide_interactions=false&hide_comments=false&hide_owner=false&hide_analytics=false&hide_watch_on_dadan=false&hide_chapters=false';
    }

    return esc_url($url);
}
