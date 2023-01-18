<?php
function validar_cert()
{
    ob_start();
    $cert = '';
    global $wpdb;
    if (isset($_GET['cert'])) {
        $cert = $_GET['cert'];
    }

    if ($cert === '') :
?>
        <form action="" method="get">
            <input type="text" name="cert" value="<?php echo $cert; ?>">
            <input type="submit" value="Validar">
        </form>
        <?php endif;
    if ($cert !== '') :
        $results = $wpdb->get_results('SELECT * FROM wpre_aptmd_alunos_formados WHERE `key` = "' . $cert . '"');
        if ($results) :
            $formador = get_user_by('id', $results[0]->id_formador); ?>
            <h3 class="validado">Certificado Emitido pela APTMD!</h3>
            <h4 class="nome_aluno">Nome Formando: <?php echo $results[0]->nome_aluno ?></h4>
            <h4 class="nome_aluno">Nome Formador: <?php echo  $formador->first_name . ' ' . $formador->last_name ?></h4>
            <h4 class="data_fim">Data De Conclusão<?php echo $results[0]->data_fim ?></h4>
            <style>
                .validado {
                    color: green;
                    text-align: center;
                    text-transform: uppercase;
                    font-weight: bold;
                }
            </style>
        <?php endif;
        if (!$results) : 
            $results = $wpdb->get_results('SELECT * FROM wpre_aptmd_formador_formados WHERE `key` = "' . $cert . '"');?>
            <?php if($results):
                $formador = get_user_by('id', $results[0]->id_formador); 
                $formador2 = $results[0]->id_formador2==null ? null : get_user_by('id', $results[0]->id_formador2)?>
                <h3 class="validado">Certificado Emitido pela APTMD!</h3>
                <h4 class="nome_aluno">Nome Formando: <?php echo $results[0]->nome_aluno ?></h4>
                <h4 class="nome_aluno">Nome Formador 1: <?php echo  $formador->first_name . ' ' . $formador->last_name ?></h4>
                <?php if($formador2):?>
                    <h4 class="nome_aluno">Nome Formador 2: <?php echo  $formador2->first_name . ' ' . $formador2->last_name ?></h4>
                <?php endif;?>
                <h4 class="data_fim">Data De Conclusão: <?php echo $results[0]->data_fim ?></h4>
                <style>
                    .validado {
                        color: green;
                        text-align: center;
                        text-transform: uppercase;
                        font-weight: bold;
                    }
                </style>
            <?php endif?>
            <?php if(!$results):?>
                <h3 class="nao-validado">Certificado Não Emitido pela APTMD!</h3>
                <style>
                    .nao-validado {
                        color: red;
                        text-align: center;
                        text-transform: uppercase;
                        font-weight: bold;
                    }
                </style>
            <?php endif?>
        <?php endif;
    endif; ?>

<?php
    return ob_get_clean();
}
add_shortcode('validar_cert', 'validar_cert');
?>