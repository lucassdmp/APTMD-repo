<?php
function confirmar_socio()
{
    ob_start();
    $error = 0;
    $sucess = 0;
    $users = null;
    if (isset($_POST['socio'])) {
        $socio = $_POST['socio'];
    } else {
        $socio = 0;
    }
    if (isset($_POST['submitsocionum'])) {
        if ($socio == 0) {
            $error = 1;
        } else {
            // Search for users with the 'Socio' meta key and the value from the form input
            $args = array(
                'meta_query' => array(
                    array(
                        'key' => 'Socio',
                        'value' => intval($socio),
                        'compare' => '=',
                        'type' => 'string'
                    )
                )
            );

            $users = get_users($args);

            // If a user is found, get the user's display name
            if (!empty($users)) {
                $users = $users[0];
                $sucess = 1;
            } else {
                $error = 1;
            }
        }
    }

?>
    <div class="socio_div">
        <?php if ($sucess === 1) : ?>
            <div class="socio_info">
                <div class="text">
                    <h3 class="nome"><?php echo $users->display_name ?></h3>
                    <h4 class="socio">Sócio Nº: <?php echo intval($socio) ?></h4>
                </div>
                <p style="color: green;">Socio Verificado Com Sucesso!</p>
                <div class="div_links">
                    <a href="<?php echo home_url('checagem-de-socio/'); ?>" class="bta">Nova Verificação</a>
                    <a href="<?php echo home_url(); ?>" class="bta">Sair</a>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($sucess === 0) : ?>
            <form action="" method="post">
                <label for="socio">Número do Sócio</label>
                <input type="text" name="socio" id="socio" required><br>
                <?php if ($error === 1) : ?>
                    <div class="error">Sócio Não Encontrado!</div>
                <?php endif; ?>
                <input type="submit" value="Verificar" name="submitsocionum">
            </form>
        <?php endif; ?>
    </div>
    <style>
        .links {
            display: flex;
            justify-content: space-between;
            font-family: 'Lato', sans-serif;
        }

        a.bta {
            font-family: 'Lato', sans-serif;
            display: inline-block;
            padding: 0 20px;
            height: 40px;
            line-height: 40px;
            border: black 1px solid;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 2px 2px rgba(0, 0, 0, 0.2);
            /* Add this line */
        }

        .socio_div {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .socio_info {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
            text-align: center;
            /* Add this line */
        }

        .name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            font-family: 'Lato', sans-serif;
        }

        .member-number {
            font-size: 18px;
            color: gray;
            font-family: 'Lato', sans-serif;
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
            font-family: 'Lato', sans-serif;
        }

        label {
            font-size: 18px;
            margin-bottom: 10px;
            font-family: 'Lato', sans-serif;
        }

        input[type="text"] {
            width: 200px;
            height: 40px;
            border: 1px solid lightgray;
            border-radius: 5px;
            padding: 0 10px;
            font-size: 16px;
            font-family: 'Lato', sans-serif;
        }

        input[type="submit"] {
            width: 200px;
            height: 40px;
            border: none;
            border-radius: 5px;
            background-color: #0077C9;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            font-family: 'Lato', sans-serif;
        }

        .error {
            color: red;
            font-size: 16px;
            margin-top: 10px;
        }
    </style>
<?php
    return ob_get_clean();
}
add_shortcode('confirmar_socio', 'confirmar_socio');

?>