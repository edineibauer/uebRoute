<?php
ob_start();
?>
<div class="row">
    <div class="panel align-center" style="max-width: 900px; margin: auto; float: initial">

        <div class="row">
            <div class="col s12 m6">
                <br>
                <div class="panel font-xlarge font-light padding-32">
                    Algo deu errado...
                </div>
            </div>
            <div class="col s12 m6">
                <h1 class="font-jumbo">404</h1>
            </div>
        </div>

        <br><br>
        <div class="align-center">
            <a class="btn-large opacity hover-shadow color-white" style="text-decoration: none; margin: auto; float: initial" href="<?= HOME ?>">Home</a>
        </div>

    </div>
</div>
<?php
$data['data'] = ob_get_contents();
ob_end_clean();