jQuery(document).ready(function($) {
    console.log("Video Bank admin script loaded successfully.");

    // Accordion functionality for topics (collapsible)
    $('.video_bank-topic-title').on('click', function() {
        $(this).next('.video_bank-videos-list').slideToggle();
        $(this).toggleClass('active');
    });

    // Handle click on video description to load embed
    $('.video_bank-description-item').on('click', function(e) {
        e.preventDefault();
        var embedUrl = $(this).data('embed-url');
        var description = $(this).data('description');
        $('#video_bank-embed-frame').attr('src', embedUrl);
        $('#video_bank-video-description-display').html(description); // This div displays formatted HTML
    });

    // Handle edit link click to load video data into the form
    $('.video_bank-edit').on('click', function(e) {
        e.preventDefault();
        $('#video_bank-video-url').val($(this).data('url'));
        $('#video_bank-video-topic').val($(this).data('topic'));
        $('#uniqvidid').val($(this).data('id'));

        // Set content in TinyMCE editor (preserves formatting)
        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('video_bank-video-description')) {
            tinyMCE.get('video_bank-video-description').setContent($(this).data('description'));
        } else {
            $('#video_bank-video-description').val($(this).data('description'));
        }
    });

    // Make topics sortable
    $('#video_bank_topic_sortable').sortable({
        update: function(event, ui) {
            var sortedTopics = $(this).sortable('toArray', { attribute: 'data-topic' });
            console.log('Sorted topics:', sortedTopics);

            // Send the new order to the server via AJAX
            $.post(ajaxurl, {
                action: 'video_bank_save_topic_order',
                order: sortedTopics
            }, function(response) {
                console.log(response);
                if (response.success) {
                    alert('Topic order saved successfully.');
                } else {
                    alert('Failed to save topic order.');
                }
            });
        }
    });
});
