<?php

namespace Route;

use Conn\SqlCommand;
use Login\Login;
use Login\Logout;

class Sessao
{
    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE)
            session_start();

        if (empty($_SESSION['userlogin']['token'])) {

            //não tenho sessão, mas tenho cookies
            if(!empty($_COOKIE['token']) && $_COOKIE['token'] !== "0")
                $this->cookieLogin();

        } else if (isset($_COOKIE['token']) && $_COOKIE['token'] !== $_SESSION['userlogin']['token']) {
            //tenho ambos, cookie e login, mas são diferentes
            setcookie("token", '', -1);
            new Logout();
        }
    }

    /**
     * Verifica se as informações mantidas no cookie condizem com um login válido
     */
    private function cookieLogin()
    {
        $prazoTokenExpira = date('Y-m-d', strtotime("-2 months", strtotime(date("Y-m-d"))));
        $sql = new SqlCommand();
        $sql->exeCommand("SELECT u.* FROM " . PRE . "usuarios as u JOIN " . PRE . "usuarios_token as t ON u.id = t.usuario WHERE t.token = '" . $_COOKIE['token'] . "' AND u.status = 1 AND t.token_expira > " . $prazoTokenExpira);

        if ($sql->getResult() && !empty($sql->getResult()[0]['nome'])) {
            $login = new Login(["user" => $sql->getResult()[0]['nome'], "password" => $sql->getResult()[0]['password']], !1);
            $_SESSION['userlogin'] = $login->getResult();
        } else {
            setcookie("token", '', -1);
            new Logout();
        }
    }
}