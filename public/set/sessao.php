<?php

use Conn\Delete;
use Login\Login;

if (!empty($_COOKIE['token']) && $_COOKIE['token'] !== "0") {
    $login = new Login(["token" => $_COOKIE['token']]);
    $data['data'] = $login->getResult();
} else {
    if (isset($_SESSION['userlogin']['id'])) {
        $del = new Delete();
        $del->exeDelete("usuarios_token", "WHERE usuario = :u", "u={$_SESSION['userlogin']['id']}");
        unset($_SESSION['userlogin']);
    } else {
        $data['data'] = 2;
    }
}