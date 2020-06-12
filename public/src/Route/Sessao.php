<?php
/**
 * Class to start SESSION on PHP
 * and check consistent COOKIE data
 *
 * Require Login module
 */

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

        /**
         * If not loged in
         */
        if (empty($_SESSION['userlogin']['token'])) {

            /**
             * Try login with cookies
             */
            if(!empty($_COOKIE['token']) && $_COOKIE['token'] !== "0")
                $this->cookieLogin();

            /**
             * If cookie token not like SESSION token
             * so logout
             */
        } else if (isset($_COOKIE['token']) && $_COOKIE['token'] !== $_SESSION['userlogin']['token']) {
            setcookie("token", '', -1);
            new Logout();
        }
    }

    /**
     * Check if the cookie login data is valid
     * if so, login
     */
    private function cookieLogin()
    {
        /**
         * Read cookie data on database
         */
        $prazoTokenExpira = date('Y-m-d', strtotime("-2 months", strtotime(date("Y-m-d"))));
        $sql = new SqlCommand();
        $sql->exeCommand("SELECT u.* FROM " . PRE . "usuarios as u JOIN " . PRE . "usuarios_token as t ON u.id = t.usuario WHERE t.token = '" . $_COOKIE['token'] . "' AND u.status = 1 AND t.token_expira > " . $prazoTokenExpira);

        if ($sql->getResult() && !empty($sql->getResult()[0]['nome'])) {

            /**
             * If cookie login pass, so login
             */
            $login = new Login(["user" => $sql->getResult()[0]['nome'], "password" => $sql->getResult()[0]['password']], !1);
            $_SESSION['userlogin'] = $login->getResult();

        } else {

            /**
             * If cookie login not pass, so logout
             */
            setcookie("token", '', -1);
            new Logout();
        }
    }
}