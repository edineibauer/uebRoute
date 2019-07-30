<?php

use Conn\Read;
use Conn\Update;
use Login\Login;

$data['data'] = 0;
$read = new Read();
$prazoTokenExpira = date('Y-m-d H:i:s', strtotime("-12 months", strtotime(date("Y-m-d H:i:s"))));

function sessionEnd() {
    $up = new Update();
    $up->exeUpdate("usuarios", ["token" => "", "token_expira" => ""], "WHERE id = :id", "id={$_SESSION['userlogin']['id']}");
    if(!empty($_SESSION['userlogin']))
        unset($_SESSION['userlogin']);
}

if(!empty($_COOKIE['token']) && $_COOKIE['token'] != "0") {
    //check if the cookie is the same on db
    $read->exeRead("usuarios", "WHERE token = :to", "to={$_COOKIE['token']}");
    if ($read->getResult() && $read->getResult()[0]['status'] === "1" && $read->getResult()[0]['token_expira'] > $prazoTokenExpira) {
        if(empty($_SESSION['userlogin']))
            $login = new Login(["user" => $read->getResult()[0]['nome'], "password" => $read->getResult()[0]['password']], !1);

        $data['data'] = 1;
    } else {
        sessionEnd();
    }

} else {
    sessionEnd();
}