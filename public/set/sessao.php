<?php

use Conn\Read;
use Login\Login;

$login = new Login();
if (isset($_SESSION['userlogin']) && !empty($_SESSION['userlogin']['token'])) {
    $read = new Read();
    $prazoTokenExpira = date('Y-m-d H:i:s', strtotime("-2 months", strtotime(date("Y-m-d H:i:s"))));
    $read->exeRead("usuarios", "WHERE token = :to", "to={$_SESSION['userlogin']['token']}");

    if ($read->getResult() && $read->getResult()[0]['status'] === "1" && $read->getResult()[0]['token_expira'] > $prazoTokenExpira) {
        $data['data'] = $read->getResult()[0];
        $data['data']['imagem'] = !empty($data['data']['imagem']) && $data['data']['imagem'] !== "[]" && \Helpers\Check::isJson($data['data']['imagem']) ? json_decode($data['data']['imagem'], true)[0]['image'] : "";
    } else {
        $login->logout();
    }

} else {
    $login->logout();
}