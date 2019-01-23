<?php

namespace Route;

use \Conn\TableCrud;
use Entity\Dicionario;
use Login\Login;

class Sessao
{
    public function __construct()
    {
        if (session_status() == PHP_SESSION_NONE)
            session_start();

        if (empty($_SESSION['userlogin']) && isset($_COOKIE['token']) && $_COOKIE['token'] !== "0")
            $this->cookieLogin();
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

            $dados = $token->getDados();
            $dic = new Dicionario("usuarios");
            $email = $dic->searchSemantic('email')->getColumn();
            $pass = $dic->searchSemantic('password')->getColumn();

            $login = new Login(["email" => $dados[$email], "password" => $dados[$pass]]);
        } else {

            $login = new Login();
            $login->logOut();
        }
    }
}