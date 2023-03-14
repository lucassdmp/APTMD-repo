<?php

/**
 * Plugin Name: Aptmd Plugin
 * 
 */
include('KEYS.php');
function remove_admin_bar()
{
    if (!current_user_can('administrator') && !is_admin()) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 'remove_admin_bar');


function abd_redirect()
{
    ob_start();

    global $wpdb;
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $prefix = $wpdb->prefix;


    $results = $wpdb->get_results("SELECT * FROM `qzorn_posts` where post_type like '%at_biz_dir%' and post_author = {$user_id}")[0];

    if ($results) {
        $url = "https://aptmd.org/enviar-listagem/edit/" . $results->ID;
    } else {
        $url = "https://aptmd.org/enviar-listagem/";
    }
    wp_redirect($url);

    return ob_get_clean();
}
add_shortcode('abd_redirect', 'abd_redirect');

include('load_csv.php');
include('add_socio_touser.php');
include('socio_card.php');
include('confirmar_socio.php');
include('validar_socio.php');
include('certificados.php');
include('validar_cert.php');
include('canalizacao.php');
include('listar_meus_certificados.php');
include('meus_canalizacao.php');
include('corrigir_datas.php');
include('resetpass.php');
include('emitir_relatorio.php');
include('listar_certificados_adm.php');
include('add_certs.php');
include('listar_canalizacao_adm.php');
include('corrigir_novo.php');
include('perfil.php');

function create_event()
{
    ob_start();

    $current_user = wp_get_current_user();
    global $wpdb;

    if (isset($_POST['event_creator']) && isset($_POST['event_creator_email']) && isset($_POST['event_title']) && isset($_POST['event_description']) && isset($_POST['event_start_date']) && isset($_POST['event_end_date']) && isset($_POST['event_start_hour']) && isset($_POST['event_end_hour']) && isset($_POST['categories']) && isset($_POST['zoom_required']) && isset($_POST['url'])) {
        $event_creator = $_POST['event_creator'];
        $event_creator_email = $_POST['event_creator_email'];
        $event_title = $_POST['event_title'];
        $event_description = $_POST['event_description'];
        $event_start_date = $_POST['event_start_date'];
        $event_end_date = $_POST['event_end_date'];
        $event_start_hour = $_POST['event_start_hour'];
        $event_end_hour = $_POST['event_end_hour'];
        $categories = intval($_POST['categories']);
        $zoom_required = $_POST['zoom_required'];
        $url = $_POST['url'];

        // echo $event_creator . '<br>'. $event_creator_email . '<br>'. $event_title . '<br>'. $event_description . '<br>'. $event_start_date . '<br>'. $event_end_date . '<br>'. $event_start_hour . '<br>'. $event_end_hour . '<br>'. $categories . '<br>'. $zoom_required . '<br>'. $url . '<br>';

        $start_hour = explode(':', $event_start_hour)[0];
        $end_hour = explode(':', $event_end_hour)[0];
        $start_minutes = explode(':', $event_start_hour)[1];
        $end_minutes = explode(':', $event_end_hour)[1];

        // $query = "SELECT * FROM `qzorn_posts` where post_type like '%organizer%' and post_title like '%".$event_creator."%'";
        // $organizer_found = $wpdb->get_results($query);
        // if($organizer_found) {
        //     $organizer_id = $organizer_found[0]->ID;
        // } else {
        //     $organizer_id = tribe_create_organizer(array(
        //         'Organizer' => $event_creator,
        //         'Email' => $event_creator_email,
        //     ));
        // }

        $args = array(
            'post_title' => $event_title,
            'post_content' => $event_description." <br> <br><br> <a href='".$url."'>Link para o evento</a>",
            'post_status' => 'pending',
            'post_category' => array($categories),
            'EventStartDate' => $event_start_date,
            'EventEndDate' => $event_end_date,
            'EventStartHour' => $start_hour,
            'EventStartMinute' => $start_minutes,
            'EventEndHour' => $end_hour,
            'EventEndMinute' => $end_minutes,
            'EventHideFromUpcoming' => false,
            'EventShowMapLink' => true,
            'EventShowMap' => true,
            'Organizer' => array(
                'Organizer' => $event_creator,
                'Email' => $event_creator_email,
                'post_status' => 'publish',
            ),
        );

        $result = tribe_create_event($args);

        // if (is_wp_error($result)) {
        //     // Event creation failed, display the error message
        //     echo 'Falha ao criar evento: ' . $result->get_error_message();
        // } else {
        //     // Event creation successful, display the new event ID
        //     echo 'Novo evento enviado com ID: ' . $result;
        // }
    }
?>

    <form class="add_event_form" action="" method="post">
        <label for="event_creator">Nome do Facilitador/Formador: <span class="requiredfield"> * </span></label>
        <input type="text" name="event_creator" id="event_creator" value="<?php echo $current_user->display_name; ?>" required><br>
        <label for="event_creator_email">Email do Facilitador/Formador: <span class="requiredfield"> * </span></label>
        <input type="email" name="event_creator_email" id="event_creator_email" value="<?php echo $current_user->user_email; ?>" required><br>

        <label for="event_title">Título da Atividade: <span class="requiredfield"> * </span></label>
        <input type="text" name="event_title" id="event_title" required><br>
        <label for="event_description">Descrição da Atividade: <span class="requiredfield"> * </span></label>
        <textarea name="event_description" id="event_description" cols="30" rows="10" required></textarea><br>
        <div class="dates">
            <label for="event_start_date">Data de Início: <span class="requiredfield"> * </span></label>
            <input type="date" name="event_start_date" id="event_start_date" required><br>
            <label for="event_end_date">Data de Fim: <span class="requiredfield"> * </span></label>
            <input type="date" name="event_end_date" id="event_end_date" required><br>
        </div>
        <div class="hours">
            <label for="event_start_hour">Hora de Início: <span class="requiredfield"> * </span></label>
            <input type="time" name="event_start_hour" id="event_start_hour" required><br>
            <label for="event_end_hour">Hora de Fim: <span class="requiredfield"> * </span></label>
            <input type="time" name="event_end_hour" id="event_end_hour" required><br>
        </div>
        <label for="categories">Categorias:<span class="requiredfield"> * </span></label>
        <select name="categories" id="categories" required>
            <option value="232">Clube de Leitura</option>
            <option value="236">Clínica Virtual</option>
            <option value="228">Prática de Terapia Multidimensional</option>
            <option value="771">Workshop de Terapia Multidimensional</option>
            <option value="773">Workshop de Canalização</option>
            <option value="755">LIVE sobre a Terapia Multidimensional | Palestra</option>
        </select><br>
        <label for="zoom_required">Requer Zoom? <span class="requiredfield"> * </span></label>
        <select name="zoom_required" id="zoom_required" required>
            <option value="Yes">Sim</option>
            <option value="No">Não</option>
        </select><br>
        <label for="url">URL</label>
        <input type="url" name="url" id="url"><br>
        <p class="subtitle">Informe o link para a inscrição/informações da tua atividade para adquirir os ingressos, se necessário.</p>

        <input type="submit" value="Enviar Evento para Aprovação">
    </form>
    <style>
        /* set primary and secondary colors */
        :root {
            --primary-color: #4992ce;
            --secondary-color: #ffffff;
        }

        /* set form width and center it */
        .add_event_form {
            max-width: 800px;
            margin: 0 auto;
        }

        /* style input fields */
        input[type="text"],
        input[type="email"],
        input[type="url"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border-radius: 4px;
            border: 1px solid #4992ce;
            margin-bottom: 10px;
            box-sizing: border-box;
            background-color: var(--secondary-color);
            color: #333;
            font-size: 16px;
        }

        /* style required fields */
        .requiredfield {
            color: red;
        }

        /* style date and hour fields */
        .dates,
        .hours {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        /* style date and hour labels */
        .dates label,
        .hours label {
            width: 45%;
        }

        /* style submit button */
        input[type="submit"] {
            background-color: var(--primary-color);
            color: var(--secondary-color);
            border: none;
            border-radius: 4px;
            padding: 12px 20px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        /* style submit button hover effect */
        input[type="submit"]:hover {
            background-color: #3e81b5;
        }

        /* style form title */
        .add_event_form h2 {
            font-size: 24px;
            margin-top: 0;
            color: var(--primary-color);
        }

        /* style form subtitle */
        .subtitle {
            font-size: 14px;
            color: #555;
            margin-top: 0;
        }

        /* style select fields */
        select {
            width: 100%;
            padding: 12px;
            border-radius: 4px;
            border: 1px solid #4992ce;;
            margin-bottom: 10px;
            box-sizing: border-box;
            background-color: var(--secondary-color);
            color: #333;
            font-size: 16px;
        }

        /* style date and hour input fields */
        input[type="date"],
        input[type="time"] {
            width: 45%;
            padding: 12px;
            border-radius: 4px;
            border: 1px solid #4992ce;;
            margin-bottom: 10px;
            box-sizing: border-box;
            background-color: var(--secondary-color);
            color: #333;
            font-size: 16px;
            margin-right: 10px;
        }

        /* style URL input field */
        input[type="url"] {
            width: 100%;
            padding: 12px;
            border-radius: 4px;
            border: 1px solid #4992ce;;
            margin-bottom: 10px;
            box-sizing: border-box;
            background-color: var(--secondary-color);
            color: #333;
            font-size: 16px;
        }

        /* style form labels */
        label {
            display: block;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        /* style required fields */
        .requiredfield {
            color: red;
        }

        /* style form fieldset */
        fieldset {
            border: 1px solid #4992ce;
            margin: 0;
            padding: 0;
        }
    </style>

<?php


    return ob_get_clean();
}
add_shortcode('create_event', 'create_event');
