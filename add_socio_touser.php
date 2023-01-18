<?php
function field_user()
{
    ob_start();
    $sucess = 0;
    $error = 0;

    global $wpdb;
    if (isset($_POST['submitsocioemail'])) {
        $max_socio = intval($wpdb->get_var("SELECT MAX(CAST(meta_value as UNSIGNED)) FROM $wpdb->usermeta WHERE meta_key = 'Socio'"));
        $socio = $_POST['socio'];
        $socio_type = $_POST['socio-type'];
        $user = get_user_by('email', $socio);

        $user_meta = get_user_meta($user->ID, 'Socio', true);
        
        if ($user_meta) {
            $error = 2;
        } else {
            if ($user !== false) {
                $id = $user->ID;
                $socio_num = $max_socio + 1;
                update_user_meta($id, "Socio", $socio_num);
                update_user_meta($id, "Socio Type", $socio_type);
                $sucess = 1;

                $message = '
                <!DOCTYPE html>
                <html lang="en">

                <head>
                    <meta charset="UTF-8">
                    <meta http-equiv="X-UA-Compatible" content="IE=edge">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <style>
                        .Email {
                            font-family: Arial, sans-serif;
                            max-width: 600px;
                            margin: 0 auto;
                        }

                        .Email img {
                            display: block;
                            margin: 0 auto;
                            max-width: 300px;
                        }

                        .Email h1 {
                            text-align: center;
                            font-size: 24px;
                            font-weight: normal;
                            margin: 20px 0;
                        }

                        .Email .content {
                            font-size: 16px;
                            line-height: 1.5;
                            margin: 20px 0;
                        }

                        .Email .signature {
                            text-align: right;
                            font-size: 14px;
                            margin-top: 20px;
                        }
                    </style>
                </head>

                <body>
                <div class="Email">
                    <img src="https://aptmd.org/wp-content/uploads/2023/01/phpchDK5rAM-removebg-preview.png" alt=""><br>
                    <h1>Olá! Seja bem-vinda Querida Alma, ' . get_user_meta($id, 'first_name', true) . ' ' . get_user_meta($id, 'last_name', true) . '</h1>
                    <p class="content">Temos a alegria de informar que a tua ficha de sócio foi validada com sucesso. <br><br>
                        Segue o teu número de sócio: ' . $socio_num . '.<br><br><br>
                        Para avançar deverás aceder à tua área de sócio através do link: https://aptmd.org/login/ , clicar no menu à
                        esquerda em "Ativação Sócio" e seguir os passos. <br><br><br>
                        Se tiveres qualquer questão, envias um email para suporte@aptmd.org<br><br><br>
                        Toda nossa gratidão por fazeres crescer o Coração na Nova Terra.<br><br>
                    <h3 class="signature">Associação Portuguesa de Terapia Multidimensional (APTMD)</h3>
                    </p>
                </div>
                </body>

</html>';
                $header[] = 'Content-Type: text/html;';
                wp_mail($socio, 'APTMD Validação de Sócio', $message, $header);
            } else {
                $error = 1;
            }
        }
    }
    if ($sucess === 0) :
?>
        <form class="form-container" action="" method="post">
            <label for="socio">Email do Sócio</label>
            <input type="email" name="socio" id="socio" required><br>
            <?php if ($error === 1) : ?>
                <div class="error">Utilizador Não Encontrado!</div>
            <?php endif; ?>
            <?php if ($error === 2) : ?>
                <div class="error">Utilizador Já Validado!</div>
            <?php endif; ?>
            <label for="socio-type">Tipo de Sócio</label>
            <select name="socio-type" id="socio-type">
                <option value="terapeuta-1">Sócio Terapeuta 1 Semestre</option>
                <option value="terapeuta-2">Sócio Terapeuta 2 Semestre</option>
                <option value="formador-1">Sócio Formador 1 Semestre</option>
                <option value="formador-2">Sócio Formador 2 Semestre</option>
                <option value="amigo-1">Sócio Amigo 1 Semestre</option>
                <option value="amigo-2">Sócio Amigo 2 Semestre</option>
            </select>
            <input type="submit" value="Validar Sócio" name="submitsocioemail">
        </form>
        
    <?php endif;
    if ($sucess === 1) : ?>
        <div class="sucess">Sócio Validado Com Sucesso!</div>
    <?php endif; ?>
    <style>
        .sucess {
            color: #3bb35d;
        }

        .error {
            color: red;
            margin-bottom: 20px;
        }

        .form-container {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            margin: 20px;
        }

        .form-container label {
            font-size: 18px;
            margin-bottom: 5px;
            font-family: 'Lato', sans-serif;
        }

        .form-container input[type="email"] {
            width: 300px;
            height: 40px;
            font-size: 16px;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-family: 'Lato', sans-serif;
        }

        .form-container input[type="submit"] {
            width: 300px;
            height: 50px;
            font-size: 18px;
            background-color: #4992ce;
            border: none;
            border-radius: 5px;
            color: #fff;
            cursor: pointer;
            margin-top: 20px;
            font-family: 'Lato', sans-serif;
        }

        .form-container select {
            width: 300px;
            height: 50px;
            font-size: 16px;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-family: 'Lato', sans-serif;
        }
    </style>
<?php
    return ob_get_clean();
}
add_shortcode('field_user', 'field_user');
?>