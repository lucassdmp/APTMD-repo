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
        $aluno_formado = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "aptmd_alunos_formados WHERE `key` = '{$link_key}'");
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
    $alunos_formados = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "aptmd_alunos_formados WHERE id_formador = $user_id");
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
        /* Bar and button */
        input[type="text"]#search {
            height: 40px;
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            border: none;
            border-bottom: 2px solid #ccc;
            font-size: 16px;
            background-color: #f1f1f1;
        }

        input[type="submit"].submitemail {
            height: 40px;
            width: 80px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease-in-out;
        }

        input[type="submit"].submitemail:hover {
            background-color: #3e8e41;
        }

        /* Table styling */
        table.meus_alunos_tmd {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            margin-top: 20px;
        }

        thead.table_header_tmd tr.header_row_tmd {
            background-color: #4CAF50;
            color: white;
        }

        th.header_elem_tmd,
        td.body_elem_tmd {
            text-align: left;
            padding: 12px;
        }

        th.header_elem_tmd:first-child,
        td.body_elem_tmd:first-child {
            text-align: center;
        }

        tbody.table_body_tmd tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tbody.table_body_tmd tr:hover {
            background-color: #ddd;
        }

        a.opcao {
            color: #4CAF50;
            text-decoration: none;
        }

        a.opcao:hover {
            color: #3e8e41;
            text-decoration: underline;
        }

        /* Phone compatibility */
        @media only screen and (max-width: 768px) {
            table.meus_alunos_tmd {
                font-size: 14px;
            }

            input[type="text"]#search {
                height: 30px;
                font-size: 14px;
            }

            input[type="submit"].submitemail {
                height: 30px;
                width: 70px;
                font-size: 14px;
            }

            th.header_elem_tmd,
            td.body_elem_tmd {
                padding: 6px;
            }
        }
    </style>
<?php
    return ob_get_clean();
}
add_shortcode('meus_certificados', 'meus_certificados');
?>