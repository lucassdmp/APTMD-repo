<?php

function comprar_wc(){
    $order_data = array(
        'customer_id' => 123,
        'line_items' => array(
            array(
                'product_id' => 123,
                'quantity' => 1
            )
        )
    );
    
    $response = wp_remote_post( 'https://aptmd.org/wp-json/wc/v3/orders', array(
        'method' => 'POST',
        'timeout' => 45,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode(WC_CONSUMER_KEY . ':' . WC_CONSUMER_SECRET),
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($order_data),
        'cookies' => array()
    ));
}

?>