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
function remove_admin_bar() {
  if (!current_user_can('administrator') && !is_admin()) {
    show_admin_bar(false);
  }
}
add_action('after_setup_theme', 'remove_admin_bar');

wp_lostpassword_url(home_url('reset-password/'));

include('load_csv.php');
include('add_socio_touser.php');
include('socio_card.php');
include('confirmar_socio.php');
include('validar_socio.php');
include('certificados.php');
include('validar_cert.php');
include('canalizacao.php');
include('listar_meus_certificados.php');
include('meus_canalizacao.php');
include('corrigir_datas.php');
include('resetpass.php');
?>