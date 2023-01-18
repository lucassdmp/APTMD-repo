<?php

/**
 * Plugin Name: Aptmd Plugin
 * 
 */
function update_user()
{
    global $wpdb;

    $all_users = get_users();
    foreach ($all_users as $user) {
        $id = $user->ID;
        wp_set_password('1234socio', $id);
    }
}

include('load_csv.php');
include('add_socio_touser.php');
include('socio_card.php');
include('confirmar_socio.php');
include('validar_socio.php');
include('certificados.php');
include('validar_cert.php');
include('canalizacao.php');
include('listar_meus_certificados.php');
?>