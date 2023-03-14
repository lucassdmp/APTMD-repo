<?php
function corrigir_novo()
{
    ob_start();

    global $wpdb;
    var_dump($wpdb);

    $encomendas = $wpdb->get_results("SELECT * FROM {$wpdb->posts} 
    WHERE `post_type` = 'ywcmbs-membership' 
    AND `post_status` = 'publish' 
    AND `guid` LIKE '%aptmd.org%'
    AND `post_title` LIKE '%novo%'");

    foreach($encomendas as $plan){
        $id = $plan->ID;
        update_post_meta($id, '_start_date', '1677466800');
        update_post_meta($id, '_end_date', '1677898800');      
    }
    return ob_get_clean();
}
add_shortcode('corrigir_novo', 'corrigir_novo');
