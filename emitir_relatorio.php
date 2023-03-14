<?php
function relatorio()
{
    ob_start();
    $output_file = 'Rel - ' . date("Y-m-d") . '.csv';
    $gerado = false;

    if (isset($_POST['relEmail'])) {
        global $wpdb;
        $query = "SELECT u.ID AS user_id, u.user_email, u.display_name, m.meta_value AS socio FROM qzorn_users u INNER JOIN qzorn_usermeta m ON u.ID = m.user_id AND m.meta_key = 'socio' AND m.meta_value != 99999999 ORDER BY CAST(m.meta_value AS UNSIGNED) ASC, u.ID ASC";

        $usuarios = $wpdb->get_results($query);

        // Set the output file name and path
        $output_file = 'Rel - ' . date("Y-m-d") . '.csv';

        // Open the output file in write mode
        $file = fopen($output_file, 'w');

        // Write the column headers to the output file
        fputcsv($file, array('Num. De Socio', 'ID', 'Nome', 'Email', 'Plano', 'Data Inicial', 'Data Final', 'Status', 'Origem', 'ID do Criador'));

        // Write the query results to the output file
        foreach ($usuarios as $usuario) {
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

                if (strpos($metas["_title"][0], "Novo") === 0) {
                    continue;
                } else {
                    fputcsv($file, array($socio, $id, $nome, $email, $metas["_title"][0], date('Y-m-d H:i:s', intval($metas["_start_date"][0])), date('Y-m-d H:i:s', intval($metas["_end_date"][0])), $metas["_status"][0], $origem, $plan[0]->post_author));
                    // echo "<br><br><br>".strpos($metas["_title"][0],"Novo");
                    // var_dump(array($socio,$id, $nome, $email, $metas["_title"][0], date('Y-m-d H:i:s', intval($metas["_start_date"][0])), date('Y-m-d H:i:s', intval($metas["_end_date"][0])), $metas["_status"][0], $origem, $plan[0]->post_author));
                }
            }
        }
        // Close the output file
        fclose($file);
        echo "<h1>Relátorio gerador</h1>";
        $gerado = true;
    }
?>
    <h1 class="title">Gerar relatorio</h1>
    <form action="" method="post">
        <input type="submit" class="Download" value="Gerar relatorio" name="relEmail">
    </form>
    <?php if ($gerado) : ?>
        <a href="<?php echo get_home_url() . '/' . $output_file ?>" download>Download CSV</a>
    <?php endif; ?>

    <style>
        .Download {
            display: inline-block;
            padding: 10px 20px;
            background: #4992ce;
            color: #fff;
            text-decoration: none;
            font-size: 20px;
            font-weight: bold;
            border-radius: 5px;
        }

        .title {
            text-align: center;
            color: #4992ce;
        }
    </style>
<?php

    return ob_get_clean();
}
add_shortcode('relatorio', 'relatorio');

