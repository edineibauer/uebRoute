<?php
ob_start();
?>
    <div class="r-network">
        <div class="row">
            <div class="panel align-center container-900">

                <div class="row padding-48 padding-bottom">
                    <div class="col s12">
                        <br>
                        <div class="col s12">
                            <img src="<?= HOME ?>assetsPublic/img/nonetwork.svg?v=<?= VERSION ?>">
                        </div>
                        <div class="panel font-xlarge font-light padding-32">
                            sem conexão
                        </div>
                        <p>
                            acesse quando tiver conectado
                        </p>
                    </div>
                </div>

                <div class="align-center">
                    <a class="btn-large opacity hover-shadow color-white relative"
                       style="text-decoration: none; margin: auto; float: initial; padding-left: 45px;border-radius: 5px;background: #eee;color: #222222;"
                       href="<?= HOME ?>">
                        <i class="material-icons padding-right"
                           style="font-size: 29px;position: absolute;left: 15px;top: 13px;">home</i>
                        <span class="upper">Home</span>
                    </a>
                </div>

            </div>
        </div>
    </div>
<?php
$data['data'] = ob_get_contents();
ob_end_clean();