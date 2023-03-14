<?php
function delete_file(){
    $output_file = 'Relatorio - ' . date("Y-m-d") . '.csv';
    unlink($output_file);
    return true;
}
echo delete_file();
?>