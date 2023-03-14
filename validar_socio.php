<?php
function validar_socio()
{
    ob_start();


    $user_id = get_current_user_id();
    $user = get_user_by('id', $user_id);
    $meta = get_user_meta($user_id);
    $type = $meta['Socio Type'][0];
    $socion = $meta['Socio'][0];
    $link = '';
    $valid = $meta['Valid'][0];
    $ftype = explode('-', $type)[0];
    $display_name = $user->display_name;
    global $wpdb;
    echo $valid . " " . $type . " " . $socion;

    if ($socion == '' && $valid == '1' && $type != '') {
        echo "debug socio not empty";
        $max_socio = intval($wpdb->get_var("SELECT MAX(CAST(meta_value as UNSIGNED)) FROM $wpdb->usermeta WHERE meta_key = 'Socio'"));
        $max_socio++;
        update_user_meta($user_id, 'Socio', $max_socio);
    }


    $url = "https://aptmd.org/wp-json/wc/v3/orders?customer=" . $user_id;

    $response = wp_remote_get($url, array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode(WC_CONSUMER_KEY . ':' . WC_CONSUMER_SECRET)
        )
    ));
    $op = array(
        'terapeuta-1' => 'Sócio Terapeuta 1 Semestre',
        'terapeuta-2' => 'Sócio Terapeuta 2 Semestres',
        'amigo-1' => 'Sócio Amigo 1 Semestre',
        'amigo-2' => 'Sócio Amigo 2 Semestres',
        'formador-1' => 'Renovação Sócio Formador 1 Semestres',
        'formador-2' => 'Renovação Sócio Formador 2 Semestres'
    );
    $plano = $op[$ftype . '-1'];
    $plano2 = $op[$ftype . '-2'];
    $datas = json_decode(wp_remote_retrieve_body($response), true);
    if (!empty($datas)) {
        foreach ($datas as $data) :
            $status = $data['status'];

            foreach ($data['line_items'] as $lineitem) {
                if ($lineitem['name'] != $plano && $lineitem['name'] != $plano2) {
                    continue;
                }
                if ($status === 'completed') {
                    // echo $plano." ".$plano2;
                    update_user_meta($user_id, 'Valid', '1');
                    if ($lineitem['name'] == $plano)
                        update_user_meta($user_id, 'Socio Type', $ftype . '-1');
                    else
                        update_user_meta($user_id, 'Socio Type', $ftype . '-2');
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

    <form class="form_valid" action="" method="post">
        <div class="Socio_container">
            <h1>As Tuas Informações</h1>
            <h2 class="TAG">Nome: <h3 class="info"> <?php echo $display_name ?></h3>
            </h2>
            <?php if ($socion != '' && $valid == '1') : ?>
                <h2 class="TAG">Tipo de Socio: <h3 class="info"> <?php echo $meta['Socio Type'][0] ?></h3>
                </h2>
            <?php endif; if($socion == '' && $valid == '') : ?>
                <h2 class="TAG">Tipo de Socio:
                    <select name='tiposelect' class="tipoSelect">
                        <option value=''>Escolhe uma opção</option>
                        <option value="<?php echo $ftype ?>-1"><?php echo ucfirst($ftype) ?> - 1 Semestre</option>
                        <option value="<?php echo $ftype ?>-2"><?php echo ucfirst($ftype) ?> - 2 Semestre</option>
                    </select>
                </h2>
            <?php endif; ?>
            <?php if ($valid == '1' && $socion != '') :?>
                <h2 class="TAG">Numero De Sócio: <h3 class="info"><?php echo $meta['Socio'][0] ?></h3>
                </h2>
            <?php endif; ?>
            <?php if ($valid == '1') : ?>
                <h1 class="parabens">Parabéns, você já é um sócio!</h1>
            <?php else : ?>
                <input class="button" type="submit" name="Validar" value="Próximo Passo">
            <?php endif; ?>
        </div>
    </form>
    <script>
        const select = document.querySelector('.tipoSelect');
        const form = document.querySelector('.form_valid');
        select.addEventListener('change', (event) => {
            var value = select.value;
            console.log(value);
            console.log(form.action);
            if (value == 'amigo-1')
                form.action = 'https://aptmd.org/produto/socio-amigo-1-semestre/';
            else if (value == 'amigo-2')
                form.action = 'https://aptmd.org/produto/socio-amigo-2-semestres/';
            else if (value == 'terapeuta-1')
                form.action = 'https://aptmd.org/produto/socio-terapeuta-1-semestre/';
            else if (value == 'terapeuta-2')
                form.action = 'https://aptmd.org/produto/socio-terapeuta-2-semestres/';
            else if (value == 'formador-1')
                form.action = 'https://aptmd.org/produto/renovacao-socio-formador-1-semestre';
            else if (value == 'formador-2')
                form.action = 'https://aptmd.org/produto/renovacao-socio-formador-2-semestres';
            else
                form.action = 'https://aptmd.org/produto/socio-amigo-1-semestre/';
            console.log(form.action);
        });
    </script>
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