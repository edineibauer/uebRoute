<?php

namespace Route;

use Conn\Read;
use Conn\Update;
use Conn\SqlCommand;
use Entity\Dicionario;
use Entity\Entity;
use Entity\Metadados;
use Login\Login;
use Login\Logout;

class Sessao
{
    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE)
            session_start();

        if (empty($_SESSION['userlogin']['token']) && !empty($_COOKIE['token']) && $_COOKIE['token'] !== "0") {

            //não tenho sessão, mas tenho cookies
            $this->cookieLogin();

        } else if (!empty($_SESSION['userlogin']['token']) && isset($_COOKIE['token']) && $_COOKIE['token'] !== $_SESSION['userlogin']['token']) {
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
            $this->exeLogin($sql->getResult()[0]);
        } else {
            setcookie("token", '', -1);
            new Logout();
        }
    }

    /**
     * @param array $users
     */
    private function exeLogin(array $users)
    {

        unset($users['password']);

        $user = null;
        $read = new Read();

        if (!empty($users['setor']) && $users['setor'] !== "admin") {
            $read->exeRead($users['setor'], "WHERE usuarios_id = :uid", "uid={$users['id']}");
            if ($read->getResult()) {
                $users['setorData'] = $read->getResult()[0];
                unset($users['setorData']['usuarios_id']);
                foreach (Metadados::getDicionario($users['setor']) as $col => $meta) {
                    if ($meta['format'] === "password" || $meta['key'] === "information")
                        unset($users['setorData'][$meta['column']]);
                }
                $user = $users;
            }
        } else {
            $users['setor'] = "admin";
            $users['setorData'] = "";
            $user = $users;
        }

        $_SESSION['userlogin'] = $user;
        if(!empty($_SESSION['userlogin']['imagem'])) {
            $_SESSION['userlogin']['imagem'] = json_decode($_SESSION['userlogin']['imagem'], !0)[0];
            unset($_SESSION['userlogin']['imagem']['preview']);
        } else {
            $_SESSION['userlogin']['imagem'] = "";
        }
        $_SESSION['userlogin']['token'] = $_COOKIE['token'];
    }
}