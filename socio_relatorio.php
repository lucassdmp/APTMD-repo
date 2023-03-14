<?php
function socios_table(){
    ob_start();
    global $wpdb;
    $query = "SELECT u.ID AS user_id, u.user_email, u.display_name, m.meta_value AS socio FROM qzorn_users u INNER JOIN qzorn_usermeta m ON u.ID = m.user_id AND m.meta_key = 'socio' AND m.meta_value != 99999999 ORDER BY CAST(m.meta_value AS UNSIGNED) ASC, u.ID ASC";

    $usuarios = $wpdb->get_results($query);
    ?>
    <table>
        <thead>
            <tr>
                <th>Num. De Socio</th>
                <th>ID</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Plano</th>
                <th>Data Inicial</th>
                <th>Data Final</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($usuarios as $usuario): 
                $id = $usuario->user_id;
                $nome = $usuario->display_name;
                $email = $usuario->user_email;
                $socio = $usuario->socio;
                

                $origem = "";

                $planos_id = $wpdb->get_results("SELECT * FROM `qzorn_postmeta` where meta_value = {$id} and meta_key = '_user_id'");

                foreach ($planos_id as $plano) {
                    $post_id = $plano->post_id;
                    $plan = $wpdb->get_results("SELECT * FROM `qzorn_posts` 
                        where ID = {$post_id} 
                        and `post_type` = 'ywcmbs-membership' 
                        AND `post_status` = 'publish' 
                        AND `guid` LIKE '%aptmd.org%'
                        and `post_title` NOT LIKE '%novo%'");

                    if (intval($plan[0]->post_author) === intval($id)) {
                        $origem = "Pagamento";
                    } else {
                        $origem = "Painel Administrativo";
                    }
                    // echo $plan[0]->post_author." ". $socio . " " . $id . " " . $nome . " " . $email . " " . $plan[0]->post_title . " " . date('Y-m-d H:i:s', intval($plan[0]->post_date)) . " " . date('Y-m-d H:i:s', intval($plan[0]->post_modified)) . " " . $plan[0]->post_status . " " . $origem . "<br>";
                    $metas = get_post_meta($post_id);
                    if (strpos($metas["_title"][0], "Novo") === 0 || $metas['_status'][0] === 'expired') {
                        continue;
                    } else {
                        echo "<tr>
                                <td>".$socio."</td>
                                <td>".$id."</td>
                                <td>".$nome."</td>
                                <td>".$email."</td>
                                <td>".$metas['_title'][0]."</td>
                                <td>".date('Y-m-d H:i:s', intval($metas["_start_date"][0]))."</td>
                                <td>".date('Y-m-d H:i:s', intval($metas["_end_date"][0]))."</td>
                            </tr>";
                    }
                }
            endforeach;?>
        </tbody>
    </table>
    <?php
    return ob_get_clean();
}

add_shortcode('socios_table', 'socios_table');