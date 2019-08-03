<?php

use Conn\Delete;
use Conn\Read;
use Conn\Update;
use Login\Login;

$data['data'] = 0;
$read = new Read();
$prazoTokenExpira = date('Y-m-d H:i:s', strtotime("-12 months", strtotime(date("Y-m-d H:i:s"))));

function sessionEnd() {
    if(isset($_SESSION['userlogin']['id'])) {
        $del = new Delete();
        $del->exeDelete("usuarios_token", "WHERE usuario = :u", "u={$_SESSION['userlogin']['id']}");
        unset($_SESSION['userlogin']);
    }
}

if(!empty($_COOKIE['token']) && $_COOKIE['token'] != "0") {
    //check if the cookie is the same on db
    $sql = new \Conn\SqlCommand();
    $sql->exeCommand("SELECT u.* FROM " . PRE . "usuarios as u JOIN " . PRE . "usuarios_token as t ON u.id = t.usuario WHERE t.token = '" . $_COOKIE['token'] . "' AND u.status = 1 AND t.token_expira > " . $prazoTokenExpira);
    if($sql->getResult()) {
        if(empty($_SESSION['userlogin']))
            $login = new Login(["user" => $sql->getResult()[0]['nome'], "password" => $sql->getResult()[0]['password']], !1);

        $data['data'] = 1;
    } else {
        sessionEnd();
    }

} else {
    sessionEnd();
}