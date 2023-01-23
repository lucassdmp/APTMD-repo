<?php

function create_load_csv_menu(){
    add_menu_page('Load CSV', 'Load CSV', 'manage_options', 'load-csv-plugin', 'load_csv_options');
}

add_action('admin_menu', 'create_load_csv_menu');

function load_csv_options() {
    $check = array();
    if (isset($_POST['submit'])) {

        $file = $_FILES['file']['tmp_name'];

        if (($handle = fopen($file, "r")) !== FALSE) {

            $header = fgetcsv($handle);


            while (($row = fgetcsv($handle)) !== FALSE) {
                $data = array_combine($header, $row);

                $socio = intval($data['Socio']);
                $email = $data['Email'];
                $nome = $data['Nome'];
                echo $email;
                if($email == '')
                    $check[] = array($data['Nome'], $socio); 
                
                $user = get_user_by('email', $email);

                if($user !== false){
                    update_user_meta($user->ID, "Socio", $socio);
                }
            }
            fclose($handle);
        }
    }
    ?>
    <div>
        <h1>Carregar CSV</h1>
        <form method="post" enctype="multipart/form-data">
            <label for="file">Seleciona um arquivo csv para carregar:</label><br>
            <input type="file" name="file" id="file"><br>
            <input type="submit" value="Submit" name="submit">
        </form>
    </div>
    <?php
}

?>