<?php

require 'vendor/autoload.php';

use chillerlan\QRCode\QRCode;

require_once 'CanalCert.php';
function meus_canalizacao(){
    ob_start();

    global $wpdb;

    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $user_name = get_user_meta($user_id, 'first_name', true) . ' ' . get_user_meta($user_id, 'last_name', true);
    $error = 0;
    $link_key = '';
    $mail = '';
    $aluno = '';

    if(isset($_GET['mail'])){
        $mail = $_GET['mail'];
        $aluno = $wpdb->get_row("SELECT * FROM wpre_aptmd_formador_formados WHERE key = '$mail'");
        $formador1 = get_user_by('id', $aluno->id_formador);
        $formador1 = $formador1->first_name . ' ' . $formador1->last_name;
        $formador2 = get_user_by('id', $aluno->id_formador2);
        $formador1 = $formador2 ? array($formador1, $formador2->first_name . ' ' . $formador2->last_name) : $formador1;
        $pais = explode('/', $aluno->local)[0];
        $cidade = explode('/', $aluno->local)[1];
        $espaco = explode('/', $aluno->local)[2];
        $certificado = new CanalCert(
            $formador1,
            $aluno->nome_aluno,
            $aluno->email_aluno,
            $aluno->nascimento,
            $aluno->data_inicio,
            $aluno->data_fim,
            $aluno->carga_horaria,
            $cidade,
            $pais,
            $espaco
        );
        $file = tempnam('./', 'svg');
        file_put_contents($file, $certificado->get_certificado());

        $imagick = new Imagick();
        $imagick->readImage($file);
        $imagick->setImageFormat('pdf');
        $imagick->writeImage('certificado.pdf');

        $headers = array('Content-Type: text/html; charset=UTF-8');
        $attachments = array(ABSPATH => 'certificado.pdf');

        wp_mail($current_user->user_email, 'Certificado de Workshop de Terapia Multidimenssional', 'Certificado de Workshop de Terapia Multidimenssional', $headers, $attachments);
        unlink($file);
        echo "<h2>Certificado enviado para seu email</h2>";
        remove_query_arg('mail');
    }


    $alunos_formados = $wpdb->get_results("SELECT * FROM wpre_aptmd_formador_formados WHERE id_formador = $user_id");
    ?>
        <table class="meus_alunos">
            <thead class="table_header">
                <tr class="header_row">
                    <th>Formador</th>
                    <th>Formador Parceiro</th>
                    <th>Nome Formando</th>
                    <th>Email</th>
                    <th>Carga Horária</th>
                    <th>Data de Início</th>
                    <th>Data de Fim</th>
                    <th>Localização</th>
                    <th>Opções</th>
                </tr>
            </thead>
            <tbody class="table_body">
                <?php foreach($alunos_formados as $aluno):
                    $formador2 = get_user_by('id', $aluno->id_formador2);
                    ?>
                    <tr class="body_row">
                        <td><?php echo $current_user->first_name . ' ' . $current_user->last_name;?></td>
                        <td><?php echo $formador2->first_name . ' ' . $formador2->last_name;?></td>
                        <td><?php echo $aluno->nome_aluno;?></td>
                        <td><?php echo $aluno->email_aluno;?></td>
                        <td><?php echo $aluno->carga_horaria;?></td>
                        <td><?php echo $aluno->data_inicio;?></td>
                        <td><?php echo $aluno->data_fim;?></td>
                        <td><?php echo $aluno->local;?></td>
                        <td>
                            <a class="opcao" href="<?php echo add_query_arg(array("mail" => $aluno->key))?>">Enviar Para Seu Email</a>
                        </td>
                    </tr>
                <?php endforeach;?>
            </tbody>
        </table>
        <style>
        h1.confirmacao {
            color: #4992ce;
            font-size: 2.5em;
            text-align: center;
            margin: 2em 0;
        }

        table.meus_alunos {
            width: 100%;
            border-collapse: collapse;
            margin: 2em 0;
        }

        table.meus_alunos thead {
            background-color: #4992ce;
            color: #ffffff;
        }

        table.meus_alunos th,
        table.meus_alunos td {
            padding: 1em;
            border: 1px solid #ccc;
        }

        table.meus_alunos tbody tr:hover {
            background-color: #f2f2f2;
        }

        a.opcao {
            display: block;
            width: 100%;
            text-align: center;
            color: #4992ce;
            text-decoration: none;
        }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('meus_canalizacao', 'meus_canalizacao');
?>