<?php
function validar_socio()
{
    ob_start();
    $user_id = get_current_user_id();
    $user = get_user_by('id', $user_id);
    $meta = get_user_meta($user_id);
    $type = $meta['Socio Type'][0];
    $link = '';
    $valid = $meta['Valid'][0];
    global $wpdb;


    $url = "https://aptmd.org/wp-json/wc/v3/orders?customer=" . $user_id;

    $response = wp_remote_get($url, array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode('ck_1de2660a530ed390b4f1eb7e7ad0eab4a73aaa2c' . ':' . 'cs_b8dd7c690aa6f659946d390f56605168026bd9c9')
        )
    ));
    $op = array(
        'terapeuta-1' => 'Sócio Terapeuta 1 Semestre',
        'terapeuta-2' => 'Sócio Terapeuta 2 Semestres',
        'amigo-1' => 'Sócio Amigo 1 Semestre',
        'amigo-2' => 'Sócio Amigo 2 Semestres',
        'formador-1' => 'Renovação Sócio Formador Canalização | 1 Semestre',
        'formador-2' => 'Renovação Sócio Formador Canalização | 2 Semestres'    
    );
    $plano = $op[$type];

    $datas = json_decode(wp_remote_retrieve_body($response), true);
    if (!empty($datas)) {
        foreach ($datas as $data) :
            $status = $data['status'];

            foreach ($data['line_items'] as $lineitem) {
                if ($lineitem['name'] != $plano) {
                    continue;
                }
                if ($status === 'completed') {
                    update_user_meta($user_id, 'Valid', '1');
                    break;
                }
            }
        endforeach;
    }


    if ($type == '') {
?><h1>Você ainda não foi validado!</h1><?php
                                                return;
                                            }

                                                ?>
    <div class="Socio_container">
        <h1>As Tuas Informações</h1>
        <h2 class="TAG">Nome: <h3 class="info"> <?php echo $meta['first_name'][0] . ' ' . $meta['last_name'][0] ?></h3>
        </h2>
        <h2 class="TAG">Tipo de Socio: <h3 class="info"> <?php echo $meta['Socio Type'][0] ?></h3>

            <?php if ($valid == '1') :
                if ($meta['Socio'][0] == '') {
                    $max_socio = intval($wpdb->get_var("SELECT MAX(CAST(meta_value as UNSIGNED)) FROM $wpdb->usermeta WHERE meta_key = 'Socio'"));
                    $max_socio++;
                    update_user_meta($user_id, 'Socio', $max_socio);
                }
                $meta = get_user_meta($user_id);
            ?>
                <h2 class="TAG">Numero De Sócio: <h3 class="info"><?php echo $meta['Socio'][0] ?></h3>
                </h2>
            <?php endif; ?>
            <form action="<?php
                            if ($type == 'amigo-1')
                                $link = 'produto/socio-amigo-1-semestre/';
                            else if ($type == 'amigo-2')
                                $link = 'produto/socio-amigo-2-semestres/';
                            else if ($type == 'terapeuta-1')
                                $link = 'produto/socio-terapeuta-1-semestre/';
                            else if ($type == 'terapeuta-2')
                                $link = 'produto/socio-terapeuta-2-semestres/';
                            else if ($type == 'formador-1')
                                $link = 'produto/renovacao-socio-formador-1-semestre';
                            else if ($type == 'formador-2')
                                $link = 'produto/renovacao-socio-formador-2-semestres';
                            else
                                $link = '';
                            echo home_url($link) ?>" method="post">
                <?php if ($valid != '1') : ?>
                    <input class="button" type="submit" name="Validar" value="Próximo Passo">
                <?php else : ?>
                    <h1 class="parabens">Parabéns, você já é um sócio!</h1>
                <?php endif; ?>
            </form>
    </div>
    <style>
        .Socio_container {
            width: 50%;
            margin: 0 auto;
            background-color: #ffffff;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 20px;
        }

        .Socio_container h1 {
            text-align: center;
            color: #333;
            margin: 0 0 20px 0;
        }

        .Socio_container h2 {
            color: #4992ce;
            font-weight: bold;
            margin: 0 0 10px 0;
        }

        .Socio_container h3 {
            color: #999;
        }

        .Socio_container .button {
            width: 100%;
            padding: 10px;
            background-color: #4992ce;
            border: 0;
            border-radius: 5px;
            color: #fff;
            font-size: 18px;
            cursor: pointer;
        }

        .Socio_container .button:hover {
            background-color: #666;
        }

        .Socio_container h1.parabens {
            text-align: center;
            color: #4992ce;
            margin: 20px 0;
        }
    </style>
<?php
    // var_dump(get_product(8584));

    return ob_get_clean();
}
add_shortcode('validar_socio', 'validar_socio');
?>