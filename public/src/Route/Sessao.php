<?php

namespace Route;

use Conn\Read;
use Entity\Dicionario;
use Login\Login;
use Login\Logout;

class Sessao
{
    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE)
            session_start();

        if (empty($_SESSION['userlogin']['token']) && isset($_COOKIE['token']) && $_COOKIE['token'] !== "0") {
            //não tenho sessão, mas tenho cookies
            $this->cookieLogin();

        } else if (!empty($_SESSION['userlogin']['token']) && isset($_COOKIE['token']) && $_COOKIE['token'] !== $_SESSION['userlogin']['token']) {
            //tenho ambos, cookie e login, mas são diferentes
            new Logout();
        }
    }

    /**
     * Verifica se as informações mantidas no cookie condizem com um login válido
     */
    private function cookieLogin()
    {
        $read = new Read();
        $prazoTokenExpira = date('Y-m-d H:i:s', strtotime("-2 months", strtotime(date("Y-m-d H:i:s"))));
        $read->exeRead("usuarios", "WHERE token = :to", "to={$_COOKIE['token']}");

        if ($read->getResult() && $read->getResult()[0]['status'] == 1 && $read->getResult()[0]['token_expira'] > $prazoTokenExpira) {
            new Login(["user" => $read->getResult()[0]['nome'], "password" => $read->getResult()[0]['password']]);
        } else {
            new Logout();
        }
    }
}