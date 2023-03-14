<?php
require 'vendor/autoload.php';

require_once 'TmdCert.php';
function certificados()
{
    ob_start();
    #GLOBALS
    global $wpdb;

    #USER DATA
    $user = wp_get_current_user();
    $user_id = $user->ID;
    $user_name = $user->display_name;
    $user_email = $user->user_email;


    $certi = 0;
    $error = 0;
    $emitidos = array();
    $assinatura = null;
    $assinatura2 = null;
    $formador = 'null';
    $english = false;
    $handle = null;
    $formador2_email = null;
    $formador2_id = null;

    #GET FORMADOR FROM GET REQUEST
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
                $formador2_email = $formador2->user_email;
                $formador2_id = $formador2->ID;
                $formador2 = $formador->display_name;
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
                $formador2_email = $formador2->user_email;
                $formador2_id = $formador2->ID;
                $formador2 = $formador2->display_name;
            }
        }
    }

    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, "r");
    }

    $url = "https://aptmd.org/wp-json/wc/v3/orders?customer=" . $user_id;

    $response = wp_remote_get($url, array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode(WC_CONSUMER_KEY . ':' . WC_CONSUMER_SECRET)
        )
    ));

    $datas = json_decode(wp_remote_retrieve_body($response), true);
    if (!empty($datas)) {
        foreach ($datas as $data) :
            $status = $data['status'];
            foreach ($data['line_items'] as $lineitem) {
                if ($lineitem['name'] != 'Certificado Workshop de Terapia Multidimensional') {
                    continue;
                }
                $id = $lineitem['id'];
                $name = $lineitem['name'];
                $quantity = $lineitem['quantity'];
                //TODO ADICIONAR CHECK DE NOME A BAIXO
                if ($status === 'completed') {
                    $results = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'aptmd_validar_cert WHERE id = ' . $id);
                    if (empty($results)) {
                        $info = array(
                            'id' => $id,
                            'qty' => $quantity,
                            'user_id' => $user_id,
                            'status' => 1
                        );
                        $wpdb->insert($wpdb->prefix . 'aptmd_validar_cert', $info);

                        $certi = intval(get_user_meta($user_id, 'certificados', true));
                        $certi = $certi + $quantity;
                        update_user_meta($user_id, 'certificados', $certi);
                    }
                }
            }
        endforeach;
    }

    $certi = intval(get_user_meta($user_id, 'certificados', true));
    if ($certi == 0) {
        $error = 1;
    }

    if (isset($_POST['criar_cert']) && isset($_POST['aluno_name']) && isset($_POST['aluno_email']) && isset($_POST['data_inicio']) && isset($_POST['data_fim']) && isset($_POST['cidade']) && isset($_POST['pais']) && isset($_POST['espacoformacao']) && isset($_POST['carga_horaria'])) {
        if (intval($_POST['carga_horaria']) < 12) {
            $error = -1;
        } else {
            $name = $_POST['aluno_name'];
            $email = $_POST['aluno_email'];
            $englishC = $_POST['english'];
            $data_inicio = $_POST['data_inicio'];
            $data_fim = $_POST['data_fim'];
            $cidade = $_POST['cidade'];
            $pais = $_POST['pais'];
            $espacoformacao = $_POST['espacoformacao'];
            $carga_horaria = intval($_POST['carga_horaria']);
            $semester = intval(intval(explode('-', $data_fim)[1]) / 6 + 1);
            $data_aniversario = $_POST['data_aniversario'];

            if ($certi < count($name)) {
                $error = 1;
            } else if (strtotime($data_inicio) > strtotime($data_fim)) {
                $error = -2;
            } else {
                if ($semester === 1)
                    $semester = "1º Semestre";
                else
                    $semester = "2º Semestre";

                if (isset($_FILES['assinatura']) && $_FILES['assinatura']['error'] == 0) {
                    $assinatura = $_FILES['assinatura'];
                    $assinatura = base64_encode(file_get_contents($assinatura['tmp_name']));
                }

                if (isset($_FILES['assinaturad']) && $_FILES['assinaturad']['error'] == 0) {
                    $assinatura2 = $_FILES['assinaturad'];
                    $assinatura2 = base64_encode(file_get_contents($assinatura2['tmp_name']));
                }

                $count = count($name);
                for ($i = 0; $i < $count; $i++) {
                    if (empty($name[$i])) {
                        continue;
                    }
                    if ($email[$i] == $user_email) {
                        continue;
                    }
                    $cert_data = array(
                        'id_formador' => $user_id,
                        'id_formador2' => $formador2_id,
                        'nome_aluno' => $name[$i],
                        'email_aluno' => $email[$i],
                        'data_inicio' => $data_inicio,
                        'data_fim' => $data_fim,
                        'data_aniversario' => $data_aniversario[$i],
                        'carga_horaria' => $carga_horaria,
                        'local' => $pais . '/' . $cidade . '/' . $espacoformacao,
                        'key' => md5($name[$i] . ' ' . $data_inicio . ' ' . $data_fim . ' ' . $pais . '/' . $cidade . '/' . $espacoformacao),
                    );

                    $checagem = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "aptmd_alunos_formados WHERE `key` = '" . $cert_data['key'] . "'");
                    if ($checagem) {
                        $error = -3;
                        $emitidos[] = $name[$i];
                        continue;
                    }
                    update_user_meta($user_id, 'certificados', $certi - 1);
                    $certi = $certi - 1;
                    $wpdb->insert(
                        $wpdb->prefix . 'aptmd_alunos_formados',
                        $cert_data
                    );
                    if ($englishC[$i] == 'on')
                        $english = true;
                    else
                        $english = false;

                    $certificado = new TmdCertificado(
                        $user_name,
                        $formador2,
                        $name[$i],
                        $data_inicio,
                        $data_fim,
                        $carga_horaria,
                        $pais . '/' . $cidade . '/' .
                            $espacoformacao,
                        $assinatura,
                        $assinatura2,
                        $english
                    );

                    file_put_contents("Certificado TMD - " . $name[$i] . ".svg", $certificado->getCertificado());
                    $imagick = new Imagick();
                    $imagick->readImage("Certificado TMD - " . $name[$i] . ".svg");
                    $imagick->setImageFormat('pdf');
                    $imagick->writeImage("Certificado TMD - " . $name[$i] . ".pdf");


                    $array = is_array($user_name);
                    $$user_name = $array == true ? $user_name[0] : $user_name;
                    $headers = array('Content-Type: text/html; charset=UTF-8');
                    $attachments = array(ABSPATH => "Certificado TMD - " . $name[$i] . ".pdf");

                    if (!$english) {
                        $message = "Segue em anexo o certificado da tua formação para " . $name[$i] . " que participou no Workshop de Terapia Multidimensional de " . $data_inicio . " a " . $data_fim . "<br><br> Se não colocaste a tua assinatura digital no formulário, assina o certificado antes da entrega.<br><br>Cumprimentos de Luz,<br>Equipe APTMD<br> Atenciosamente";
                        $message2 = "Olá, " . $name[$i] . ",<br><br>Segue em anexo o certificado da tua formação que ocorreu no Workshop de Terapia Multidimensional de " . $data_inicio . " a " . $data_fim . "<br><br><br><br>Cumprimentos de Luz,<br>Equipe APTMD<br> Atenciosamente";

                        if ($assinatura && $assinatura2 && $array) {
                            wp_mail($email[$i], 'Teu Certificado de Workshop de Terapia Multidimensional', $message2, $headers, $attachments);
                        }
                        if ($assinatura && !$array) {
                            wp_mail($email[$i], 'Teu Certificado de Workshop de Terapia Multidimensional', $message2, $headers, $attachments);
                        }
                        wp_mail($user->user_email, 'Certificado de Workshop de Terapia Multidimensional', $message, $headers, $attachments);
                    } else {
                        $message = "Follows the certified attached as pdf to " . $name[$i] . " who completed the Multidimensional Healing Workshop from " . $data_inicio . " to " . $data_fim . "<br><br> If you did not upload your signature, please sign before sending the the certificates.<br><br>Light Greetings,<br>Sincerely<br>APTMD Team<br>";
                        $message2 = "Dear Multidimensional Healer!<br><br>
                        It is with great pleasure that I send you the certificate for your Multidimensional Therapy workshop, completed on" . $data_fim . "<br>
                        We recommend that you read the “Multidimensional Heart” Training Manual available for sale online.<br>
                        Join our Telegram group for news on Multidimensional Healing: https://t.me/+iEchRfNxGzMyZWM8<br><br>
                        
                        Thank you for being part of this world of the heart<br>
                        on behalf of APTMD Portuguese Association of Multidimensional Healing.<br>";

                        if ($assinatura && $assinatura2 && $array) {
                            wp_mail($email[$i], 'Your Multidimensional Healing Certificate', $message2, $headers, $attachments);
                        }
                        if ($assinatura && !$array) {
                            wp_mail($email[$i], 'Your Multidimensional Healing Certificate', $message2, $headers, $attachments);
                        }
                        wp_mail($user->user_email, 'Multidimensional Healing Certificate', $message, $headers, $attachments);
                    }
                    unlink("Certificado TMD - " . $name[$i] . ".svg");
                    unlink("Certificado TMD - " . $name[$i] . ".pdf");
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
            <h1 class="formador_pergunta">ATENÇÃO<br><br>
                Este workshop tem mais do que 1 Formador? <br>
                Se sim adiciona o email ou número de sócio.<br>
                Se não, deixa VAZIO.</h1>
            <label for="formador" class="formador_extra_label"></label>
            <input type="text" name="formador" class="formador_extra" placeholder="Email ou Número do SEGUNDO formador">
            <input type="submit" class="formador_extra_submit" value="Próximo">
        </form>
        <style>
            .certificadosh1 .certificadosh2 {
                text-align: center;
                color: #5291C5;
            }

            form.formador_extra_form {
                background-color: #ffffff;
                color: #ce8549;
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
        <?php if (!empty($emitidos)) : ?>
            <h3 class="error">Os seguintes certificados já foram emitidos: <?php $inte = 0;
                                                                            foreach ($emitidos as $emitido) {
                                                                                $inte += 1;
                                                                                if (count($emitidos) == $inte)
                                                                                    echo $emitido . ".";
                                                                                else
                                                                                    echo $emitido . ", ";
                                                                            }
                                                                            ?></h3>
        <?php endif; ?>
        <?php if ($error == -2) : ?>
            <h3 class="error">A data de inicio deve ser maior que a data de fim!</h3>
        <?php endif; ?>
        <?php if ($error == -1) : ?>
            <h3 class="error">A carga horária deve ser maior que 12H</h3>
        <?php endif; ?>
        <?php if ($error <= 0) : ?>
            <div class="modal">
                <div class="modal-content">
                    <div class="modal-message">
                        Por favor espere enquanto os certificados são gerados...
                    </div>
                    <div class="modal-spinner"></div>
                </div>
            </div>
            <h1 class="certificadosh1">Saldo: <?php echo $certi ?> certificados</h1>
            <form class="formfile" action="" method="post" enctype="multipart/form-data">
                <p class="assinatudasCheckLabel" for="csv_file">Se quiseres pré-carregar multiplos alunos simultaneamente, faz o upload de um ficheiro .csv com NOME, EMAIL, DATA DE ANIVERSARIO no formato YYYY-MM-DD<br></p>
                <p class="assinatudasCheckLabel" for="csv_file">Exemplo: Formador Nome, formador@email.com, 1900-12-31<br></p>
                <input type="file" id="csv_file" name="csv_file" accept=".csv">
                <input type="submit" name="submit" value="Adicionar Alunos">
            </form>

            <form class="certificados" method='post' enctype="multipart/form-data">
                <div class='flex'>
                    <div class="flex_start">
                        <input type="checkbox" name="assinatudasCheck" class='assinatudasCheck'>
                        <p class='assinatudasCheckLabel'>*Marca a caixa para emitir e enviar o(s) certificado(s) assinados digitalmente <span style="text-decoration:underline;">automaticamente</span>:<br></p>
                    </div>
                    <p class="SUBassinatudasCheckLabel">*Para emitir o (s) certificado (s) para impressão avança sem marcar a caixa ou cria novamente um novo conjunto de certificado (s).</p>
                </div>
                <div class="assinaturasDIV" hidden>
                    <label for="assinatura"><strong>(Opcional)</strong> Para emitir certificados já assinados, carrega uma imagem com a tua assinatura com estes requisitos:<br>
                        - Imagem em formato .png (fundo transparente)<br>
                        - Tamanho máximo 2MB
                    </label>
                    <input type="file" name="assinatura" class="assinatura" accept="image/*">
                    <?php if ($formador != 'null') : ?>
                        <label for="assinaturad"><strong>(Opcional)</strong> Carregue uma imagem dentro dos mesmos requisitos estabelecidos contendo a assinatura do outro formador para emitir os certificados já assinados.
                        </label>
                        <input type="file" name="assinaturad" class="assinatura" accept="image/*">
                    <?php endif; ?>
                    <h1 class="subtitle" style="font-size: small;">Se enviares a imagem da tua assinatura, o teu aluno recebe o certificado diretamente por email.</h1><br>
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
                    <?php if ($handle != null) :
                        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                            echo '<div class="aluno">
                                    <p class="titulo">Formando</p>
                                    <label for="aluno_name[]">Nome Formando:</label>
                                    <input type="text" name="aluno_name[]" value="' . $data[0] . '" required>
                                    <label for="aluno_email[]">Email Formando:</label>
                                    <input type="email" name="aluno_email[]" value="' . $data[1] . '" required>
                                    <label for="data_aniversario[]">Data De Nascimento do Formando:</label>
                                    <input type="date" name="data_aniversario[]" value="' . $data[2] . '" required>
                                    <button class="remover_aluno">Remover Formando</button>
                                </div>';
                        }
                    ?>
                        <script>
                            const alunos = document.querySelectorAll('.aluno');

                            alunos.forEach(aluno => {
                                const button = aluno.querySelector('.remover_aluno');
                                button.addEventListener('click', () => {
                                    aluno.parentElement.removeChild(aluno);
                                })
                                const dateInput = aluno.querySelector('input[type="date"]');
                                dateInput.addEventListener('change', (e) => {
                                    const inputDate = new Date(e.target.value);
                                    // console.log(inputDate);
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

                            });
                        </script>
                    <?php
                    endif; ?>
                </div>

                <button class="add_cert">Adicionar Mais Um Certificado</button>
                <input type="submit" value="Criar Certificados" name="criar_cert">
            </form>
        <?php elseif ($error == 1) : ?>
            <h1 class="certificadosh1">Teu Saldo: <?php echo $certi ?> certificados</h1>
            <h5 class="certificadosh2">Tua quantidade de certificados é insuficiente para avançar a solicitação!</h5>
            <h5 class="certificadosh2">Tens uma encomenda? Por favor, aguarde o processamento ou entre em contacto com: <a href="mailto:certificados@aptmd.org">certificados@aptmd.org</a>
            </h5>
        <?php endif; ?>
        <script>
             <?php if ($handle === null) : ?>
            const button = document.querySelector('.add_cert');
            const container = document.querySelector('.container');
            const aluno1 = document.createElement('div');
            const userEmail = '<?php echo $user_email ?>';
            aluno1.className = 'aluno';
            aluno1.innerHTML = `
                    <p class="titulo">Formando</p>
                    <label for="english[]">Marca a caixa para emitir este Certificado em Inglês:</label>
                    <input type="checkbox" name="english[]" class="english">
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
                const check = aluno1.querySelector('input.responsavel');
                if (age < 18 && !check) {
                    const label = document.createElement('label');
                    label.innerHTML = 'Os responsáveis do formando assinaram o termo de responsabilidade?';
                    label.className = 'responsavel'
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.name = 'responsavel[]';
                    checkbox.className = 'responsavel';
                    checkbox.required = true;
                    aluno1.appendChild(label);
                    aluno1.appendChild(checkbox);
                } else if (age >= 18) {
                    const checkbox = aluno1.querySelector('input.responsavel');
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
                    errorMessage.innerHTML = "Se o email for igual ao teu, o certificado não será emitido.";
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
            <? endif; ?>

            button.addEventListener('click', (e) => {
                e.preventDefault();
                const aluno = document.createElement('div');
                aluno.className = 'aluno';
                aluno.innerHTML = `
                    <p class="titulo">Formando</p>
                    <label for="english[]">Marca a caixa para emitir este Certificado em Inglês:</label>
                    <input type="checkbox" name="english[]" class="english">
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
                    const check = aluno.querySelector('input.responsavel');
                    if (age < 18 && !check) {
                        const label = document.createElement('label');
                        label.innerHTML = 'Os responsáveis do formando assinaram o termo de responsabilidade?';
                        label.className = 'responsavel'
                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.name = 'responsavel[]';
                        checkbox.className = 'responsavel';
                        checkbox.required = true;
                        aluno.appendChild(label);
                        aluno.appendChild(checkbox);
                    } else if (age >= 18) {
                        const checkbox = aluno.querySelector('input.responsavel');
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
                        errorMessage.innerHTML = "Se o email for igual ao teu, o certificado não será emitido.";
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
                color: #5291C5;
            }

            .flex_start {
                display: flex;
                justify-content: flex-start;
                align-items: flex-start;
            }

            .flex {
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                align-items: center;
                padding-left: 15%;
                padding-right: 15%;
                margin-bottom: 20px;
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
                text-align: center;
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
                text-transform: capitalize !important;
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
            }.modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background-color: rgba(0, 0, 0, 0.5);
                    display: none;
                    justify-content: center;
                    align-items: center;
                    z-index: 9999;
                }

                .modal-content {
                    background-color: #fff;
                    padding: 20px;
                    text-align: center;
                    border-radius: 5px;
                }

                .modal-message {
                    font-size: 24px;
                    margin-bottom: 20px;
                }

                .modal-spinner {
                    border: 10px solid #f3f3f3;
                    border-top: 10px solid #3498db;
                    border-radius: 50%;
                    width: 50px;
                    height: 50px;
                    animation: spin 2s linear infinite;
                    margin-bottom: 20px;
                }

                @keyframes spin {
                    0% {
                        transform: rotate(0deg);
                    }

                    100% {
                        transform: rotate(360deg);
                    }
                }
                <?php if($handle):?>
                    .formfile{
                        display: none;
                    }
                <?php endif;?>
            </style>
            <script>
                const form = document.querySelector('.certificados');
                form.addEventListener('submit', () => {
                    const modal = document.querySelector('.modal');
                    modal.style.display = 'flex';
                });
            </script>
<?php endif;
    return ob_get_clean();
}
add_shortcode('certificados', 'certificados');
?>