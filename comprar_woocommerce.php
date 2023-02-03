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
            'Authorization' => 'Basic ' . base64_encode('ck_1de2660a530ed390b4f1eb7e7ad0eab4a73aaa2c' . ':' . 'cs_b8dd7c690aa6f659946d390f56605168026bd9c9'),
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($order_data),
        'cookies' => array()
    ));
}

?>