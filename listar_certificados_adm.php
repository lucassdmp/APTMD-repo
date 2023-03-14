<?php

require_once 'TmdCert.php';

function certificadosadm()
{
    ob_start();

    global $wpdb;

    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $user_name = get_user_meta($user_id, 'first_name', true) . ' ' . get_user_meta($user_id, 'last_name', true);
    if (!current_user_can('administrator')) {
        echo "Acesso negado";
        return;
    }
    $error = 0;
    $delete = '';
    $aluno = '';

    if (isset($_GET['delete'])) {
        $delete = $_GET['delete'];
        $aluno = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "aptmd_alunos_formados WHERE `key` = '{$delete}'")[0];
        $formador_edit = $aluno->id_formador;
        update_user_meta($formador_edit, 'certificados', intval(get_user_meta($formador_edit, 'certificados', true)) + 1);
        $wpdb->delete($wpdb->prefix . 'aptmd_alunos_formados', array('key' => $delete));

        echo "<h1>Certificado apagado com sucesso!</h1>";
    }

    $search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    if (!empty($search_term)) {
        $alunos_formados = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "aptmd_alunos_formados WHERE nome_aluno LIKE '%$search_term%' OR email_aluno LIKE '%$search_term%' or local LIKE '%$search_term%'");
    } else {
        $alunos_formados = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "aptmd_alunos_formados");
    }

?>
    <form method="get">
        <input type="text" name="search" id="search">
        <input type="submit" value="Search">
    </form>

    <table class="meus_alunos">
        <thead class="table_header">
            <tr class="header_row">
                <th class="header_elem">Formador</th>
                <th class="header_elem">Nome Formando</th>
                <th class="header_elem">Email</th>
                <th class="header_elem">Carga Horária</th>
                <th class="header_elem">Data de Início</th>
                <th class="header_elem">Data de Fim</th>
                <th class="header_elem">Localização</th>
                <th class="header_elem">Opções</th>
            </tr>
        </thead>
        <tbody class="table_body">
            <?php foreach ($alunos_formados as $aluno) :
                $id_formador = $aluno->id_formador;
                $formador = get_user_by('id', $id_formador);
            ?>
                <tr class="body_row">
                    <td class="body_elem"><?php echo $formador->first_name; ?></td>
                    <td class="body_elem"><?php echo $aluno->nome_aluno; ?></td>
                    <td class="body_elem"><?php echo $aluno->email_aluno; ?></td>
                    <td class="body_elem"><?php echo $aluno->carga_horaria; ?></td>
                    <td class="body_elem"><?php echo $aluno->data_inicio; ?></td>
                    <td class="body_elem"><?php echo $aluno->data_fim; ?></td>
                    <td class="body_elem"><?php echo $aluno->local; ?></td>
                    <td class="body_elem">
                        <a class="opcao" href="<?php echo add_query_arg(array("delete" => $aluno->key)) ?>">Apagar Certificado e Devolver Credito</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <style>
        .header_elem{
            background-color: #4992ce;
            color: white;
        }
        /* Common styles for all devices */
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

        @media only screen and (max-width: 767px) {
            h1.confirmacao {
                font-size: 2em;
                margin: 1.5em 0;
            }

            table.meus_alunos {
                margin: 1.5em 0;
            }

            table.meus_alunos th,
            table.meus_alunos td {
                padding: 0.8em;
                font-size: 0.9em;
            }

            a.opcao {
                font-size: 0.9em;
            }

            /* Stacking columns */
            table.meus_alunos thead {
                display: none;
            }

            table.meus_alunos tr {
                display: block;
                margin-bottom: 1em;
                border: 1px solid #4992ce;
            }

            table.meus_alunos td {
                display: block;
                text-align: center;
                font-weight: bold;
                border-bottom: 1px solid #ccc;
                margin-bottom: 0.5em;
            }

            table.meus_alunos td:nth-of-type(odd) {
                background-color: #f2f2f2;
            }
        }
    </style>
<?php
    return ob_get_clean();
}
add_shortcode('certificadosadm', 'certificadosadm');
?>