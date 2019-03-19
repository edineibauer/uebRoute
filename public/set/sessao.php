<?php

use Conn\Read;

if (isset($_SESSION['userlogin']) && !empty($_SESSION['userlogin']['token'])) {
    $read = new Read();
    $prazoTokenExpira = date('Y-m-d H:i:s', strtotime("-2 months", strtotime(date("Y-m-d H:i:s"))));
    $read->exeRead("usuarios", "WHERE token = :to", "to={$_SESSION['userlogin']['token']}");
    if ($read->getResult() && $read->getResult()[0]['status'] === "1" && $read->getResult()[0]['token_expira'] > $prazoTokenExpira) {
        $data['data'] = $_SESSION['userlogin'];
        $data['data']['setor'] = $data['data']['setor'] ?? "";
    } else {
        new Logout();
    }
} else {
    new Logout();
}