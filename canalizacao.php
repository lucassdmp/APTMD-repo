<?php
require 'vendor/autoload.php';
require 'CanalCert.php';

use chillerlan\QRCode\QRCode;

function canalizacao()
{
    ob_start();
    $user = wp_get_current_user();
    $user_id = $user->ID;
    $user_name = get_user_meta($user_id, 'first_name', true) . ' ' . get_user_meta($user_id, 'last_name', true);
    $certi = 0;
    $error = 0;
    $emitidos = array();
    $formador2 = null;
    $formador2_id = null;
    $solo = false;
    global $wpdb;

    $assinatura = null;
    $assinatura2 = null;

    $formador = '';

    //WOOCOMMERCE REST API
    //Comsumer key: ck_1de2660a530ed390b4f1eb7e7ad0eab4a73aaa2c
    //Secret: cs_b8dd7c690aa6f659946d390f56605168026bd9c9
    $url = "https://aptmd.org/wp-json/wc/v3/orders?customer=" . $user_id;

    $response = wp_remote_get($url, array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode('ck_1de2660a530ed390b4f1eb7e7ad0eab4a73aaa2c' . ':' . 'cs_b8dd7c690aa6f659946d390f56605168026bd9c9')
        )
    ));

    $datas = json_decode(wp_remote_retrieve_body($response), true);
    if (!empty($datas)) {
        foreach ($datas as $data) :
            $status = $data['status'];
            foreach ($data['line_items'] as $lineitem) {
                if ($lineitem['name'] != 'Certificado Workshop de Canalização') {
                    continue;
                }
                $id = $lineitem['id'];
                $name = $lineitem['name'];
                $quantity = $lineitem['quantity'];
                //TODO ADICIONAR CHECK DE NOME A BAIXO
                if ($status === 'completed') {
                    $results = $wpdb->get_results('SELECT * FROM wpre_aptmd_validar_canal WHERE id = ' . $id);
                    if (empty($results)) {
                        $info = array(
                            'id' => $id,
                            'qty' => $quantity,
                            'user_id' => $user_id,
                            'status' => 1
                        );
                        $wpdb->insert('wpre_aptmd_validar_canal', $info);

                        $certi = intval(get_user_meta($user_id, 'canalizacao', true));
                        $certi = $certi + $quantity;
                        update_user_meta($user_id, 'canalizacao', $certi);
                    }
                }
            }
        endforeach;
    }

    $formador = isset($_GET['formador']) ? $_GET['formador'] : 'null';
    if ($formador == '') {
        $formador = 'null';
        $solo = true;
    }
    if ($formador != 'null') {
        if (filter_var($formador, FILTER_VALIDATE_EMAIL)) {
            $formador2 = get_user_by('email', $formador);
            if ($formador2 == false) {
                $formador2 = null;
                $error = 144;
            } else {
                $formador2_id = $formador2->ID;
                $formador2 = $formador2->display_name; //get_user_meta($formador->ID, 'first_name', true) . ' ' . get_user_meta($formador->ID, 'last_name', true);
            }
        } else {
            $args = array(
                'meta_key'   => 'Socio',
                'meta_value' => $formador,
            );
            $formador2 = get_users($args);
            if (empty($formador2)) {
                $formador2 = null;
                $error = 144;
            } else {
                $formador2 = $formador2[0];
                $formador2_id = $formador2->ID;
                $formador2 = $formador2->display_name; //get_user_meta($formador->ID, 'first_name', true) . ' ' . get_user_meta($formador->ID, 'last_name', true);
            }
        }
        $user_name = array($user_name, $formador2);
    }

    $certi = intval(get_user_meta($user_id, 'canalizacao', true));
    if ($certi == 0) {
        $error = 1;
    }

    if (
        isset($_POST['criar_cert'])
        &&  isset($_POST['aluno_name'])
        &&  isset($_POST['aluno_email'])
        &&  isset($_POST['data_inicio'])
        &&  isset($_POST['data_fim'])
        &&  isset($_POST['cidade'])
        &&  isset($_POST['pais'])
        &&  isset($_POST['espacoformacao'])
        &&  isset($_POST['carga_horaria'])
    ) {
        if (intval($_POST['carga_horaria']) < 12) {
            $error = -1;
        } else {
            $name = $_POST['aluno_name'];
            $email = $_POST['aluno_email'];
            $nascimento = $_POST['nascimento'];
            $data_inicio = $_POST['data_inicio'];
            $data_fim = $_POST['data_fim'];
            if (count($name) > $certi) {
                $error = -1;
            } else if (strtotime($data_inicio) > strtotime($data_fim)) {
                $error = -2;
            } else {
                $cidade = $_POST['cidade'];
                $pais = $_POST['pais'];
                $espacoformacao = $_POST['espacoformacao'];
                $carga_horaria = intval($_POST['carga_horaria']);
                $counts = count($name);

                if (isset($_FILES['assinaturaC']) && $_FILES['assinaturaC']['error'] == 0) {
                    $assinatura = $_FILES['assinaturaC'];
                    $assinatura = base64_encode(file_get_contents($assinatura['tmp_name']));
                }

                if (isset($_FILES['assinaturad']) && $_FILES['assinaturad']['error'] == 0) {
                    $assinatura2 = $_FILES['assinaturad'];
                    $assinatura2 = base64_encode(file_get_contents($assinatura2['tmp_name']));
                }

                for ($i = 0; $i < $counts; $i++) {
                    $certificado = new CanalCert(
                        $user_name,
                        $name[$i],
                        $email[$i],
                        $nascimento[$i],
                        $data_inicio,
                        $data_fim,
                        $carga_horaria,
                        $cidade,
                        $pais,
                        $espacoformacao,
                        $assinatura,
                        $assinatura2
                    );
                    $checagem = $wpdb->get_results("SELECT * FROM wpre_aptmd_formador_formados 
                        WHERE `key` = '" . $certificado->get_key() . "'");
                    if ($checagem) {
                        $error = -3;
                        $emitidos[] = $name[$i];
                        continue;
                    }
                    if (is_array($user_name)) {
                        $cert_data = array(
                            'id_formador' => $user_id,
                            'id_formador2' => $formador2_id,
                            'nome_aluno' => $name[$i],
                            'email_aluno' => $email[$i],
                            'nascimento' => $nascimento[$i],
                            'data_inicio' => $data_inicio,
                            'data_fim' => $data_fim,
                            'carga_horaria' => $carga_horaria,
                            'local' => $pais . '/' . $cidade . '/' . $espacoformacao,
                            'key' => $certificado->get_key(),
                        );
                    } else {
                        $cert_data = array(
                            'id_formador' => $user_id,
                            'nome_aluno' => $name[$i],
                            'email_aluno' => $email[$i],
                            'nascimento' => $nascimento[$i],
                            'data_inicio' => $data_inicio,
                            'data_fim' => $data_fim,
                            'carga_horaria' => $carga_horaria,
                            'local' => $pais . '/' . $cidade . '/' . $espacoformacao,
                            'key' => $certificado->get_key(),
                        );
                    }
                    update_user_meta($user_id, 'canalizacao', $certi - 1);
                    $certi = $certi - 1;
                    $wpdb->insert(
                        'wpre_aptmd_formador_formados',
                        $cert_data
                    );
                    file_put_contents("Certificado Canalização - " . $name[$i] . ".svg", $certificado->get_certificado());
                    $imagick = new Imagick();
                    $imagick->readImage("Certificado Canalização - " . $name[$i] . ".svg");
                    $imagick->setImageFormat('pdf');
                    $imagick->writeImage("Certificado Canalização - " . $name[$i] . ".pdf");

                    $headers = array('Content-Type: text/html; charset=UTF-8');
                    $attachments = array(ABSPATH => "Certificado Canalização - " . $name[$i] . ".pdf");
                    $array = is_array($user_name);
                    $user_name = is_array($user_name) ? $user_name[0] : $user_name;
                    $message = "Segue em anexo o certificado da tua formação para " . $name[$i] . " 
                    que participou no Workshop de Canalização de " . $data_inicio . " a " . $data_fim . "<br><br> 
                    Se não colocaste a tua assinatura digital no formulário, assina o certificado antes da entrega.<br><br>
                    Cumprimentos de Luz,<br>
                    Equipe APTMD<br>
                    Atenciosamente";
                    $message2 = "Olá, " . $name[$i] . ",<br><br>
                    Segue em anexo teu certificado da tua formação que participou no Workshop de Canalização de " . $data_inicio . " a " . $data_fim . "<br><br><br><br>
                    Cumprimentos de Luz,<br>
                    Equipe APTMD<br>
                    Atenciosamente";
                    if ($assinatura && $assinatura2 && $array) {
                        wp_mail($email[$i], 'Certificado de Workshop de Canalização', $message2, $headers, $attachments);
                    }
                    if ($array == false && $assinatura) {
                        wp_mail($email[$i], 'Certificado de Workshop de Canalização', $message2, $headers, $attachments);
                    }
                    wp_mail($user->user_email, 'Certificado de Workshop de Canalização', $message, $headers, $attachments);

                    unlink("Certificado Canalização - " . $name[$i] . ".svg");
                    unlink("Certificado Canalização - " . $name[$i] . ".pdf");
                }
            }
        }
    }
    if ($error == 144) : ?>
        <h3 class="error">Sócio não encontrado!</h3>
    <?php $formador = 'null';
    endif;
    if ($formador === 'null' && !$solo) : ?>
        <form class="formador_extra_form" method="get">
            <h1 class="formador_pergunta">Este workshop tem mais mais do que 1 Formador?
                Se sim adiciona o email ou número de sócio.
                Se não, deixa em <strong>branco</strong>.</h1>
            <label for="formador" class="formador_extra_label"></label>
            <input type="text" name="formador" class="formador_extra" placeholder="Email ou Número de Sócio">
            <input type="submit" class="formador_extra_submit" value="Próximo">
        </form>
        <style>
            form.formador_extra_form {
                background-color: #ffffff;
                color: black;
                padding: 20px;
            }

            h1.formador_pergunta {
                text-align: center;
                font-weight: bold;
                margin-bottom: 20px;
                font-size: 1.5em;
            }

            form.formador_extra_form label.formador_extra_label {
                display: block;
                margin-bottom: 10px;
            }

            form.formador_extra_form input[type="text"] {
                width: 100%;
                padding: 12px 20px;
                margin: 8px 0;
                box-sizing: border-box;
                border: 2px solid #5291C5;
                border-radius: 4px;
            }

            form.formador_extra_form input[type="submit"] {
                width: 100%;
                background-color: #5291C5;
                color: white;
                padding: 14px 20px;
                margin-top: 20px;
                margin-bottom: 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }

            .error {
                color: red;
                text-align: center;
            }
        </style>
    <?php else : ?>
        <?php if ($error == -3) : ?>
            <h3 class="error">Os seguintes certificados já foram emitidos: <?php foreach ($emitidos as $emitido) {
                                                                                echo $emitido . ", ";
                                                                            } ?></h3>
        <?php endif; ?>

        <?php if ($error == -2) : ?>
            <h3 class="error">A Data de Inicio tem que ser menor ou Igual a Data de Fim!</h3>
        <?php endif; ?>

        <?php if ($error == -1) : ?>
            <h3 class="error">A carga horária deve ser maior que 12H</h3>
        <?php endif; ?>

        <?php if ($error <= 0) : ?>
            <h1 class="certificadosh1">Saldo: <?php echo $certi ?> certificados</h1>
            <form class="certificados" method='post' enctype="multipart/form-data">
                <div class='flex'>
                    <p class='assinatudasCheckLabel'>Marca a caixa para emitir e enviar o(s) certificado(s) assinados digitalmente:<br></p>
                    <p class="SUBassinatudasCheckLabel">Para Emitir o certificado para impressão não marque a caixa ou cria novamente um novo bloco de certificados.</p>
                    <input type="checkbox" name="assinatudasCheck" class='assinatudasCheck'>
                </div>
                <div class="assinaturasDIV" hidden>
                    <label for="assinaturaC"><strong>(Opcional)</strong> Para emitir certificados já assinados, carrega uma imagem com a tua assinatura com estes requisitos:<br>
                        - Imagem em formato .png (fundo transparente)<br>
                        - Tamanho máximo 2MB
                    </label>
                    <input type="file" name="assinaturaC" class="assinaturaC" accept="image/*">
                    <?php if ($formador != 'null') : ?>
                        <label for="assinaturad"><strong>(Opcional)</strong> Carregue uma imagem dentro dos mesmos requisitos estabelecidos contendo a assinatura do outro formador para emitir os certificados já assinados.
                        </label>
                        <input type="file" name="assinaturad" class="assinaturad" accept="image/*">
                    <?php endif; ?>
                    <h1 class="subtitle" style="color: gray; font-size: small;">Se enviares a imagem da tua assinatura, o teu aluno recebe o certificado diretamente por email.</h1>
                </div>
                <script>
                    const assCheck = document.querySelector('.assinatudasCheck');
                    const assDIVS = document.querySelector('.assinaturasDIV');
                    assCheck.addEventListener('change', () => {
                        if (assCheck.checked) {
                            assDIVS.removeAttribute('hidden');;
                        } else {
                            assDIVS.setAttribute('hidden', '');
                        }
                    })
                </script>
                <div class="container">
                    <div class="localizacao">
                        <p class="titulo">Localização</p>
                        <label for="cidade">Cidade:</label>
                        <input type="text" name="cidade" id="cidade" required>
                        <label for="pais">Pais:</label>
                        <input type="text" name="pais" id="pais" required>
                        <label for="espacoformacao">Espaço de Formação:</label>
                        <input type="text" name="espacoformacao" id="espacoformacao" required>
                    </div>
                    <div class="datas">
                        <p class="titulo">Datas</p>
                        <label for="data_inicio">Inicio:</label>
                        <input type="date" name="data_inicio" id="data_inicio" required>
                        <label for="data_fim">Fim:</label>
                        <input type="date" name="data_fim" id="data_fim" required>
                        <label for="carga_horaria">Carga Horaria:</label>
                        <input type="text" name="carga_horaria" id="carga_horaria" required>
                    </div>
                </div>
                <input type="submit" value="Criar Certificados" name="criar_cert">
                <button class="add_cert">Adicionar Mais Um Certificado</button>
            </form>
            <script>
                const button = document.querySelector('.add_cert');
                const container = document.querySelector('.container');
                const aluno1 = document.createElement('div');
                aluno1.className = 'aluno';
                aluno1.innerHTML = `
                    <p class="titulo">Formando</p>
                    <label for="aluno_name[]">Nome Formando:</label>
                    <input type="text" name="aluno_name[]" required>
                    <label for="aluno_email[]">Email Formando:</label>
                    <input type="email" name="aluno_email[]" required>
                    <label for="data_aniversario[]">Data de Nascimento Formando:</label>
                    <input type="date" name="data_aniversario[]" required>
                `;
                const dateInput = aluno1.querySelector('input[type="date"]');
                dateInput.addEventListener('change', (e) => {
                    const inputDate = new Date(e.target.value);
                    console.log(inputDate);
                    const today = new Date();
                    const age = today.getFullYear() - inputDate.getFullYear();
                    const check = aluno1.querySelector('input[type="checkbox"]');
                    if (age < 18 && !check) {
                        const label = document.createElement('label');
                        label.innerHTML = 'Os responsáveis do formando assinaram o termo de responsabilidade?';
                        label.className = 'responsavel'
                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.name = 'responsavel[]';
                        checkbox.required = true;
                        aluno1.appendChild(label);
                        aluno1.appendChild(checkbox);
                    } else if (age >= 18) {
                        const checkbox = aluno1.querySelector('input[type="checkbox"]');
                        const label = aluno1.querySelector('label.responsavel');
                        if (checkbox) {
                            aluno1.removeChild(checkbox);
                            aluno1.removeChild(label);
                        }
                    }
                });

                const email = aluno1.querySelector('input[type="email"]');
                email.addEventListener('change', (e) => {
                    const inputEmail = e.target.value;
                    if (inputEmail == userEmail) {
                        const errorMessage = document.createElement("div");
                        errorMessage.className = "errorEmail";
                        errorMessage.innerHTML = "O Email não pode ser igual ao teu e não será gerado!";
                        errorMessage.style.color = "red";
                        errorMessage.style.fontSize = "14px";
                        email.after(errorMessage);
                    } else {
                        const errorMessage = document.querySelector(".errorEmail");
                        if (errorMessage) {
                            errorMessage.remove();
                        }
                    }
                });

                container.appendChild(aluno1);


                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    const aluno = document.createElement('div');
                    aluno.className = 'aluno';
                    aluno.innerHTML = `
                    <p class="titulo">Formando</p>
                    <label for="aluno_name[]">Nome Formando:</label>
                    <input type="text" name="aluno_name[]" required>
                    <label for="aluno_email[]">Email Formando:</label>
                    <input type="email" name="aluno_email[]" required>
                    <label for="data_aniversario[]">Data De Nascimento do Formando:</label>
                    <input type="date" name="data_aniversario[]" required>
                    <button class="remover_aluno">Remover Formando</button>
                `;
                    const remover_aluno = aluno.querySelector('.remover_aluno');
                    remover_aluno.addEventListener('click', (e) => {
                        e.preventDefault();
                        container.removeChild(aluno);
                    });
                    const dateInput = aluno.querySelector('input[type="date"]');
                    dateInput.addEventListener('change', (e) => {
                        const inputDate = new Date(e.target.value);
                        console.log(inputDate);
                        const today = new Date();
                        const age = today.getFullYear() - inputDate.getFullYear();
                        const check = aluno.querySelector('input[type="checkbox"]');
                        if (age < 18 && !check) {
                            const label = document.createElement('label');
                            label.innerHTML = 'Os responsáveis do formando assinaram o termo de responsabilidade?';
                            label.className = 'responsavel'
                            const checkbox = document.createElement('input');
                            checkbox.type = 'checkbox';
                            checkbox.name = 'responsavel[]';
                            checkbox.required = true;
                            aluno.appendChild(label);
                            aluno.appendChild(checkbox);
                        } else if (age >= 18) {
                            const checkbox = aluno.querySelector('input[type="checkbox"]');
                            const label = aluno.querySelector('label.responsavel');
                            if (checkbox) {
                                aluno.removeChild(checkbox);
                                aluno.removeChild(label);
                            }
                        }
                    });

                    const email = aluno.querySelector('input[type="email"]');
                    email.addEventListener('change', (e) => {
                        const inputEmail = e.target.value;
                        if (inputEmail == userEmail) {
                            const errorMessage = document.createElement("div");
                            errorMessage.className = "errorEmail";
                            errorMessage.innerHTML = "O Email não pode ser igual ao teu e não será gerado!";
                            errorMessage.style.color = "red";
                            errorMessage.style.fontSize = "14px";
                            email.after(errorMessage);
                        } else {
                            const errorMessage = document.querySelector(".errorEmail");
                            if (errorMessage) {
                                errorMessage.remove();
                            }
                        }
                    });

                    container.appendChild(aluno);
                });
            </script>
            <style>
                .titulo {
                    text-align: center;
                    font-size: 25px;
                    font-weight: bold;
                    margin-bottom: 10px;
                }

                .flex {
                    display: flex;
                    flex-direction: column;
                    justify-content: space-between;
                    align-items: center;
                    padding-left: 15%;
                    padding-right: 15%;
                }

                label[for="assinatura"] {
                    font-weight: bold;
                    margin-bottom: 10px;
                    display: block;
                }

                .assinatudasCheckLabel {
                    color: black;
                    font-weight: bold;
                    font-size: 13px;
                    display: block;
                }

                label[for="assinatura"] strong {
                    font-weight: normal;
                }

                input[type="file"].assinatura {
                    width: 100%;
                    padding: 12px 20px;
                    margin: 8px 0;
                    box-sizing: border-box;
                    border-radius: 4px;
                }

                .error {
                    color: red;
                    text-align: center;
                }

                form.certificados {
                    background-color: #ffffff;
                    color: black;
                    padding: 20px;
                }

                form.certificados label {
                    display: block;
                    margin-bottom: 10px;
                    font-weight: bold;
                }

                form.certificados input[type="text"],
                form.certificados input[type="email"],
                form.certificados input[type="date"] {
                    width: 100%;
                    padding: 12px 20px;
                    margin: 8px 0;
                    box-sizing: border-box;
                    border: 2px solid #5291C5;
                    border-radius: 8px;
                }

                .assinaturasDIV {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    box-sizing: border-box;
                    border: 2px solid #5291C5;
                    border-radius: 8px;
                    padding: 12px 20px;
                    margin-bottom: 30px;
                }

                form.certificados input[type="submit"] {
                    width: 100%;
                    background-color: #5291C5;
                    color: white;
                    padding: 14px 20px;
                    margin-top: 20px;
                    margin-bottom: 20px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                }

                button.add_cert {
                    background-color: #5291C5;
                    color: white;
                    padding: 14px 20px;
                    margin: 8px 0;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                }

                button.remover_aluno {
                    background-color: #4992ce;
                    color: #ffffff;
                    border: 1px solid #ffffff;
                    border-radius: 5px;
                    cursor: pointer;
                    float: right;
                }

                .aluno {
                    display: flex;
                    flex-direction: column;
                    align-items: start;
                    margin-top: 25px;
                    margin-bottom: 25px;
                    padding: 25px;
                    border: #4992ce 2px solid;
                    border-radius: 5px;
                }

                .certificadosh1 {
                    text-align: center;
                    color: #5291C5;
                }

                .certificadosh2 {
                    text-align: center;
                    color: #5291C5;
                }

                .assinatudasCheck,
                .english,
                input[type="checkbox"].responsavel {
                    border-color: blue;
                    width: 20px;
                    height: 20px;
                    margin-bottom: 35px;
                }

                .english {
                    margin-bottom: 20px;
                }

                .input[type="checkbox"].responsavel {
                    margin-bottom: 0px;
                }

                .SUBassinatudasCheckLabel {
                    font-size: small;
                    color: black;
                    font-weight: normal;
                    text-align: center;
                }

                .localizacao,
                .datas {
                    display: flex;
                    flex-direction: column;
                    align-items: start;
                    border: 2px solid #5291C5;
                    border-radius: 5px;
                    padding: 25px;
                    margin-bottom: 20px;
                }
            </style>
        <?php elseif ($error == 1) : ?>
            <h1 class="certificadosh1">Teu Saldo: <?php echo $certi ?> certificados</h1>
            <h5 class="certificadosh2">Tua quantidade de certificados é insuficiente para avança a solicitação!</h5>
            <h5 class="certificadosh2">Tens uma encomenda? Por favor, aguarde o processamento ou entre em contacto com: <a href="mailto:certificados@aptmd.org">certificados@aptmd.org</a></h5>
        <?php endif; ?>
<?php endif;
    return ob_get_clean();
}
add_shortcode('canalizacao', 'canalizacao');
?>