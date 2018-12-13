<?php

namespace Route;

use \Conn\TableCrud;

class Sessao
{
    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE)
            session_start();

        if (class_exists('\Login\Login')) {
            //Cookie Operations
            if (isset($_COOKIE['token']) && empty($_SESSION['userlogin']))
                $this->cookieLogin();
        }
    }

    /**
     * Verifica se as informações mantidas no cookie condizem com um login válido
     */
    private function cookieLogin()
    {
        $beforeDate = date('Y-m-d H:i:s', strtotime("-2 months", strtotime(date("Y-m-d H:i:s"))));
        $token = new TableCrud("usuarios");
        $token->load("token", $_COOKIE['token']);
        if ($token->exist() && $token->status === 1 && $token->token_expira > $beforeDate) {

            //Obtém os dados de login
            $_SESSION['userlogin'] = $token->getDados();

            //Atualiza tempo de expiração do Token no banco
            $token->token_expira = date("Y-m-d H:i:s");
            $token->save();

            //seta cookies para 2 meses de validade
            setcookie("token", $token, time() + (86400 * 30 * 3), "/"); // 2 meses de cookie

            //redireciona para dashboard
            header("Location: " . HOME . "dashboard");

        } else {

            //remove cookie não integro
            $this->unsetCookie();
        }
    }

    /**
     * Remover Cookie
     */
    private function unsetCookie()
    {
        $token = new TableCrud("usuarios");
        $token->load("token", $_COOKIE['token']);
        if ($token->exist()) {

            //Remove token da base de dados
            $token->token = null;
            $token->token_expira = null;
            $token->save();
        }

        //remove cookie
        setcookie("token", 0, time() - 1, "/");
    }
}