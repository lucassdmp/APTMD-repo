<?php
function validar_socio()
{
    ob_start();
    $user_id = get_current_user_id();
    $user = get_user_by('id', $user_id);
    $meta = get_user_meta($user_id);
    $type = $meta['Socio Type'][0];
    $socio = $meta['Socio'][0];
    $link = '';
    $valid = get_user_meta($user_id, 'valid', true);
    
    if($socio == ''){
        ?><h1>Você ainda não foi validado!</h1><?php
        return;
    }

?>
    <div class="Socio_container">
        <h1>Suas Informações</h1>
        <h2 class="TAG">Nome: <h3 class="info"> <?php echo $meta['first_name'][0] . ' ' . $meta['last_name'][0] ?></h3>
        </h2>
        <h2 class="TAG">Socio: <h3 class="info"> <?php echo $meta['Socio'][0] ?></h3>
        </h2>
        <h2 class="TAG">Tipo de Socio: <h3 class="info"> <?php echo $meta['Socio Type'][0] ?></h3>
        </h2>
        <form action="<?php
        if($type == 'formador-1')
            $link = 'product/socio-formador-100-off-1semestre/';
        else if ($type == 'formador-2')
            $link = 'product/socio-formador-2-semestres-100-off/';
        else if ($type == 'amigo-1')
            $link = 'product/socio-amigo-1-semestre/';
        else if ($type == 'amigo-2')
            $link = 'product/socio-amigo-2-semestres/';
        else if ($type == 'terapeuta-1')
            $link = 'product/socio-terapeuta-1-semestre/';
        else if ($type == 'terapeuta-2')
            $link = 'product/socio-terapeuta-2-semestres/';
        else
            $link = '';
            
        echo home_url($link) ?>" method="post">
        <input class="button" type="submit" name="Validar" value="Próximo Passo">
        </form>
    </div>
    <style>
        .Socio_container {
            width: 50%;
            /* adjust this value as needed */
            margin: 0 auto;
            /* center the container */
            background-color: #f2f2f2;
            /* add a background color */
            border: 1px solid #ccc;
            /* add a border */
            border-radius: 5px;
            /* round the corners */
            padding: 20px;
            /* add some padding */
        }

        .Socio_container h1 {
            text-align: center;
            /* center the heading */
            margin: 0 0 20px 0;
            /* add some space below the heading */
            color: #333;
            /* change the text color */
        }

        .Socio_container .TAG {
            font-weight: bold;
            /* make the text bold */
            margin: 0 0 10px 0;
            /* add some space below the text */
            color: #666;
            /* change the text color */
        }

        .Socio_container .info {
            color: #999;
            /* change the text color */
        }

        .Socio_container .button {
            display: block;
            /* make the button span the full width of the container */
            width: 100%;
            padding: 10px;
            /* add some padding to the button */
            background-color: #333;
            /* change the button's background color */
            border: 0;
            /* remove the border */
            border-radius: 5px;
            /* round the corners */
            color: #fff;
            /* change the text color */
            font-size: 18px;
            /* increase the font size */
            cursor: pointer;
            /* add a cursor */
        }

        .Socio_container .button:hover {
            background-color: #666;
            /* change the button's background color on hover */
        }
    </style>
<?php
    // var_dump(get_product(8584));

    return ob_get_clean();
}
add_shortcode('validar_socio', 'validar_socio');
?>