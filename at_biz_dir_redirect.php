<?php
function abd_redirect()
{
    ob_start();

    global $wpdb;
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $prefix = $wpdb->prefix;


    $results = $wpdb->get_results("SELECT * FROM `qzorn_posts` where post_type like '%at_biz_dir%' and post_author = {$user_id}")[0];

    if ($results) {
        $url = "https://aptmd.org/enviar-listagem/edit/" . $results->ID;
    } else {
        $url = "https://aptmd.org/enviar-listagem/";
    }
    wp_redirect($url);

    return ob_get_clean();
}
add_shortcode('abd_redirect', 'abd_redirect');

function create_event()
{
    ob_start();

    $post_data = array(
        'post_title' => 'My New Event',
        'post_content' => 'This is the content of my new event.',
        'post_type' => 'tribe_events',
        'post_status' => 'publish'
    );

    // Insert the event into the database
    $event_id = wp_insert_post($post_data);

    // Set the event data
    $event_data = array(
        '_EventStartDate' => '2023-03-15 09:00:00',
        '_EventEndDate' => '2023-03-15 17:00:00',
    );

    // Save the event data
    foreach ($event_data as $key => $value) {
        update_post_meta($event_id, $key, $value);
    }

    return ob_get_clean();
}
add_shortcode('create_event', 'create_event');
?>
