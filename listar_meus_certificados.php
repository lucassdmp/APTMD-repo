<?php

require_once 'TmdCert.php';

function meus_certificados()
{
    ob_start();

    global $wpdb;

    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $user_name = get_user_meta($user_id, 'first_name', true) . ' ' . get_user_meta($user_id, 'last_name', true);
    $error = 0;
    $link_key = '';
    $mail = '';
    $aluno = '';

    if (isset($_GET['mail'])) {
        $mail = $_GET['mail'];
    }

    if ($mail) {
        $link_key = $mail;
        $aluno_formado = $wpdb->get_results("SELECT * FROM wpre_aptmd_alunos_formados WHERE `key` = '{$link_key}'");
        if (!empty($aluno_formado)) {
            $aluno_formado = $aluno_formado[0];
            if ($aluno_formado->id_formador === $user_id) {
                $error = 1;
            } else {
                $data_fim = $aluno_formado->data_fim;
                $data_inicio = $aluno_formado->data_inicio;
                $name = $aluno_formado->nome_aluno;
                $local = $aluno_formado->local;
                $carga_horaria = $aluno_formado->carga_horaria;
                $certificado = new TmdCertificado(
                    $user_name,
                    $name,
                    $data_inicio,
                    $data_fim,
                    $carga_horaria,
                    $local,
                );
                $svg = $certificado->getCertificado();
                
                if ($mail) {
                    $file = tempnam(sys_get_temp_dir(), 'svg');
                    file_put_contents($file, $svg);

                    $imagick = new Imagick();
                    $imagick->readImage($file);
                    $imagick->setImageFormat('pdf');
                    $imagick->writeImage('certificado.pdf');

                    $headers = array('Content-Type: text/html; charset=UTF-8');
                    $attachments = array(ABSPATH => 'certificado.pdf');

                    wp_mail($current_user->user_email, 'Certificado de Workshop de Terapia Multidimenssional', "Segue em anexo o certificado da tua formação para " . $name . " que participou no Workshop de Terapia Multidimensional de " . $data_inicio . " a " . $data_fim . "<br><br> Se não colocaste a tua assinatura digital no formulário, assina o certificado antes da entrega.<br><br>Cumprimentos de Luz,<br>Equipe APTMD<br> Atenciosamente", $headers, $attachments);
                    unlink($file);
                    unlink('certificado.pdf');

                    remove_query_arg('mail');
                }
            }
        }
    }
    $alunos_formados = $wpdb->get_results("SELECT * FROM wpre_aptmd_alunos_formados WHERE id_formador = $user_id");
    if ($link_key) :
?>
        <h1 class="confirmacao">Certificado Enviado Para Seu Email!</h1>
    <?php endif; ?>
    <table class="meus_alunos">
        <thead class="table_header">
            <tr class="header_row">
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
            <?php foreach ($alunos_formados as $aluno) : ?>
                <tr class="body_row">
                    <td><?php echo $aluno->nome_aluno; ?></td>
                    <td><?php echo $aluno->email_aluno; ?></td>
                    <td><?php echo $aluno->carga_horaria; ?></td>
                    <td><?php echo $aluno->data_inicio; ?></td>
                    <td><?php echo $aluno->data_fim; ?></td>
                    <td><?php echo $aluno->local; ?></td>
                    <td>
                        <a class="opcao" href="<?php echo add_query_arg(array("mail" => $aluno->key)) ?>">Enviar Para Teu Email</a>
                    </td>
                </tr>
            <?php endforeach; ?>
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
add_shortcode('meus_certificados', 'meus_certificados');
?>