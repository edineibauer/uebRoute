<?php
ob_start();
?>
<div class="row">
    <div class="panel align-center" style="max-width: 900px; margin: auto; float: initial">

        <div class="row">
            <div class="col s12 m6">
                <br>
                <div class="panel font-xlarge font-light padding-32">
                    Acesso Negado
                </div>
            </div>
        </div>

        <br><br>
        <div class="align-center">
            <a class="btn-large opacity hover-shadow color-white" style="text-decoration: none; margin: auto; float: initial" href="<?= HOME ?>login">Fazer Login</a>
        </div>

    </div>
</div>
<?php
$data['data'] = ob_get_contents();
ob_end_clean();