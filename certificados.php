<?php
require 'vendor/autoload.php';

use chillerlan\QRCode\QRCode;

require_once 'TmdCert.php';
function certificados()
{
    ob_start();
    $user = wp_get_current_user();
    $user_id = $user->ID;
    $user_name = get_user_meta($user_id, 'first_name', true) . ' ' . get_user_meta($user_id, 'last_name', true);
    $html = array();
    $certi = 0;
    global $wpdb;
    $error = 0;
    $redirect = 0;
    $link_key = "";
    $emitidos = array();
    $assinatura = null;
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
        foreach ($datas as $data):
            $status = $data['status'];

            foreach($data['line_items'] as $lineitem) {
                if ($lineitem['name'] != 'Certificado Workshop de Terapia Multidimensional') {
                    continue;}
                $id = $lineitem['id'];
                $name = $lineitem['name'];
                $quantity = $lineitem['quantity'];
                //TODO ADICIONAR CHECK DE NOME A BAIXO
                if ($status === 'completed') {
                    $results = $wpdb->get_results('SELECT * FROM wpre_aptmd_validar_cert WHERE id = ' . $id);
                    if (empty($results)) {
                        $info = array(
                            'id' => $id,
                            'qty' => $quantity,
                            'user_id' => $user_id,
                            'status' => 1
                        );
                        $wpdb->insert('wpre_aptmd_validar_cert', $info);

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
        if(intval($_POST['carga_horaria']) < 12){
            $error = -1;
        }else{
            $name = $_POST['aluno_name'];
            $email = $_POST['aluno_email'];
            $data_inicio = $_POST['data_inicio'];
            $data_fim = $_POST['data_fim'];
            $cidade = $_POST['cidade'];
            $pais = $_POST['pais'];
            $espacoformacao = $_POST['espacoformacao'];
            $carga_horaria = intval($_POST['carga_horaria']);
            $semester = intval(intval(explode('-', $data_fim)[1]) / 6 + 1);
            $data_aniversario = $_POST['data_aniversario'];

            if ($certi <= count($name)) {
                $error = 1;
            } else if(strtotime($data_inicio) > strtotime($data_fim)){
                $error = -2;
            }else {
                if ($semester === 1)
                    $semester = "1º Semestre";
                else
                    $semester = "2º Semestre";

                if(isset($_FILES['assinatura']) && $_FILES['assinatura']['error'] == 0){
                    $assinatura = $_FILES['assinatura'];
                    $assinatura = base64_encode(file_get_contents($assinatura['tmp_name']));
                }

                $count = count($name);
                for ($i = 0; $i < $count; $i++) {
                    if (empty($name[$i])) {
                        continue;
                    }
                    $cert_data = array(
                        'id_formador' => $user_id,
                        'nome_aluno' => $name[$i],
                        'email_aluno' => $email[$i],
                        'data_inicio' => $data_inicio,
                        'data_fim' => $data_fim,
                        'data_aniversario' => $data_aniversario[$i],
                        'carga_horaria' => $carga_horaria,
                        'local' => $pais . '/' . $cidade . '/' . $espacoformacao,
                        'key' => md5($name[$i] .' '. $data_aniversario[$i]. ' ' . 
                                     $data_inicio . ' ' . $data_fim . ' ' . $user_name)
                    );

                    $checagem = $wpdb->get_results("SELECT * FROM wpre_aptmd_alunos_formados WHERE `key` = '".$cert_data['key']."'");
                    if($checagem){
                        $error = -3;
                        $emitidos[] = $name[$i];
                        continue;
                    }
                    update_user_meta($user_id, 'certificados', $certi - 1);
                    $certi = $certi - 1;
                    $wpdb->insert(
                        'wpre_aptmd_alunos_formados',
                        $cert_data
                    );

                    $certificado = new TmdCertificado($user_name, $name[$i],$data_inicio, $data_fim,$carga_horaria, $pais . '/' . $cidade . '/' . $espacoformacao, $assinatura);

                    file_put_contents('certificado.svg', $certificado->getCertificado());
                    $imagick = new Imagick();
                    $imagick->readImage('certificado.svg');
                    $imagick->setImageFormat('pdf');
                    $imagick->writeImage('Certificado.pdf');
                    $mail = $assinatura == null ? $user->user_email : $email[$i];
                    $headers = array('Content-Type: text/html; charset=UTF-8');
                    $attachments = array( ABSPATH => 'Certificado.pdf');
                    $message = "Olá, {$user_name},<br><br>Segue em anexo o certificado da tua formação para o Formando " . $name[$i] . " que participou no Workshop de Terapia Multidimensional de ". $data_inicio ." a ".$data_fim."<br><br> Lembra-te de assinar o(s) certificado(s) antes da entrega.<br><br>Cumprimentos de Luz,<br>Equipe APTMD<br> Atenciosamente";
                    wp_mail($mail, 'Certificado de Workshop de Terapia Multidimensional', $message,$headers, $attachments);
                }
            }
        }       
    }
?>  
    <?php if ($error == -3) : ?>
        <h3 class="error">Os seguintes certificados já foram emitidos: <?php foreach ($emitidos as $emitido) {
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
    <?php if ($error <= 0 ) : ?>
        <h1 class="certificadosh1">Saldo: <?php echo $certi ?> certificados</h1>
        <form class="certificados" method='post' enctype="multipart/form-data">
            <label for="assinatura"><strong>(Opcional)</strong> Carregue uma imagem dentro dos requisitos estabelecidos contendo a tua assinatura para emitir os certificados já assinados.
            <br>Requisitos: <br> - Imagem em formato .png <br> - Tamanho máximo de 1MB
        </label>
            <input type="file" name="assinatura" class="assinatura" accept="image/*">
            <div class="container">
                <div class="localizacao">
                    <label for="cidade">Cidade:</label>
                    <input type="text" name="cidade" id="cidade" required>
                    <label for="pais">Pais:</label>
                    <input type="text" name="pais" id="pais" required>
                    <label for="espacoformacao">Espaço de Formação:</label>
                    <input type="text" name="espacoformacao" id="espacoformacao" required>
                </div>
                <div class="datas">
                    <label for="data_inicio">Inicio:</label>
                    <input type="date" name="data_inicio" id="data_inicio" required>
                    <label for="data_fim">Fim:</label>
                    <input type="date" name="data_fim" id="data_fim" required>
                    <label for="carga_horaria">Carga Horaria:</label>
                    <input type="text" name="carga_horaria" id="carga_horaria" required>
                </div>
                <div class="alunos">
                </div>
            </div>
            <input type="submit" value="Criar Certificados" name="criar_cert">
            <button class="add_cert">Adicionar Mais Um Certificado</button>
        </form>
    <?php elseif ($error == 1) : ?>
        <h1 class="certificadosh1">Teu Saldo: <?php echo $certi ?> certificados</h1>
        <h5 class="certificadosh2">Tua quantidade de certificados é insuficiente para avançar a solicitação!</h5>
        <h5 class="certificadosh2">Tens uma encomenda? Por favor, aguarde o processamento ou entre em contacto com: <a href="mailto:certificados@aptmd.org">certificados@aptmd.org</a>
</h5>
    <?php endif; ?>
    <script>
        const button = document.querySelector('.add_cert');
        const container = document.querySelector('.container');
        const aluno1 = document.createElement('div');
            aluno1.className = 'aluno';
            aluno1.innerHTML = `
                <label for="aluno_name[]">Nome Formando:</label>
                <input type="text" name="aluno_name[]" required>
                <label for="aluno_email[]">Email Formando:</label>
                <input type="text" name="aluno_email[]" required>
                <label for="data_aniversario[]">Data de Nascimento Formando:</label>
                <input type="date" name="data_aniversario[]" required>
            `;
            container.appendChild(aluno1);
        button.addEventListener('click', (e) => {
            e.preventDefault();
            const aluno = document.createElement('div');
            aluno.className = 'aluno';
            aluno.innerHTML = `
                <label for="aluno_name[]">Nome Formando:</label>
                <input type="text" name="aluno_name[]" required>
                <label for="aluno_email[]">Email Formando:</label>
                <input type="text" name="aluno_email[]" required>
                <label for="data_aniversario[]">Nome Formando:</label>
                <input type="date" name="data_aniversario[]" required>
                <button class="remover_aluno">Remover Formando</button>
            `;
            const remover = aluno.querySelector('.remover_aluno');
            remover.addEventListener('click', (e) => {
                e.preventDefault();
                container.removeChild(aluno);
            });
            container.appendChild(aluno);
        });
    </script>
    <style>
        label[for="assinatura"] {
    font-weight: bold;
    margin-bottom: 10px;
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
    border: 2px solid #5291C5;
    border-radius: 4px;
}

        .error{
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
        form.certificados input[type="date"] {
            width: 100%;
            padding: 12px 20px;
            margin: 8px 0;
            box-sizing: border-box;
            border: 2px solid #5291C5;
            border-radius: 4px;
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

        div.alunos {
            background-color: #ffffff;
            color: white;
            padding: 20px;
            margin-top: 20px;
        }

        button.remover_aluno {
            background-color: #ffffff;
            color: #4992ce;
            border: 1px solid #4992ce;
            cursor: pointer;
            float: right;
        }
        .aluno{
            margin: 10 0;
        }
    </style>
<?php
    return ob_get_clean();
}
add_shortcode('certificados', 'certificados');
?>