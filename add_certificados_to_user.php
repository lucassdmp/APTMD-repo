<?php

function add_certs(){
    ob_start();

    if(isset($_GET['certtype']) && isset($_GET['email']) && isset($_GET['quantidade'])){
        $certtype = $_GET['certtype'];
        $email = $_GET['email'];
        $quantidade = intval($_GET['quantidade']);
        $quantidade = $quantidade < 0 ? -1*$quantidade : $quantidade;

        $user = get_user_by('email', $email);
        $user_id = $user->ID;

        if($certtype == 'certificados'){
            $certtype = 'certificados';
        } else if($certtype == 'canalizacao'){
            $certtype = 'canalizacao';
        }

        $certs = intval(get_user_meta($user_id, $certtype, true));
        $certs = $certs + $quantidade;
        update_user_meta($user_id, $certtype, $certs);

        echo "<h1>".$quantidade ."Adicionado com sucesso!</h1>";
    }

    ?>
    <form action="" method="get">
        <select name="certtype" id="certtype">
            <option value="certificados">TMD</option>
            <option value="canalizacao">Canalização</option>
        </select>
        <input type="email" name="email" id="certemail" placeholder="Email do Sócio">
        <input type="number" name="quantidade" id="certquant" placeholder="Quantidade de Certificados">
        <input type="submit" value="Adicionar">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('add_certs', 'add_certs');

?>