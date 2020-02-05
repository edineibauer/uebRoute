<?php

use Conn\Delete;
use Conn\Read;
use Conn\Update;
use Login\Login;

$data['data'] = 0;
$read = new Read();
$prazoTokenExpira = date('Y-m-d', strtotime("-12 months", strtotime(date("Y-m-d"))));

/**
 * Termina a sessão corrente
 */
function sessionEnd()
{
    $del = new Delete();
    $del->exeDelete("usuarios_token", "WHERE usuario = :u", "u={$_SESSION['userlogin']['id']}");
    unset($_SESSION['userlogin']);
}

/**
 * Se tiver um token válido
 */
if (!empty($_COOKIE['token']) && $_COOKIE['token'] != "0") {
    $sql = new \Conn\SqlCommand();
    $sql->exeCommand("SELECT u.* FROM " . PRE . "usuarios as u JOIN " . PRE . "usuarios_token as t ON u.id = t.usuario WHERE t.token = '" . $_COOKIE['token'] . "' AND u.status = 1 AND t.token_expira > " . $prazoTokenExpira);
    if ($sql->getResult()) {
        if (empty($_SESSION['userlogin'])) {
            /**
             * Se não tiver sessão no back, cria a sessão
             */
            $login = new Login(["user" => $sql->getResult()[0]['nome'], "password" => $sql->getResult()[0]['password']], !1);
            $data['data'] = $login->getResult();

        } else {
            /**
             * Se tiver sessão no back, atualiza a sessão
             */
            $usuario = $sql->getResult()[0];
            if (!empty($usuario['setor'])) {
                $read = new Read();
                $read->exeRead($usuario['setor'], "WHERE usuarios_id = :ui", "ui={$usuario['id']}");
                if ($read->getResult()) {
                    $usuario['setorData'] = $read->getResult()[0];
                    if (!empty($usuario['imagem'])) {
                        $usuario['imagem'] = json_decode($usuario['imagem'], !0)[0];
                        unset($usuario['preview']);
                    }
                    $_SESSION['userlogin'] = $usuario;
                    $data['data'] = $usuario;

                } elseif($usuario['setor'] === null) {
                    $usuario['setor'] = "admin";
                    $usuario['setorData'] = [];
                    $data['data'] = $usuario;

                } else {
                    sessionEnd();
                }
            } else {
                $data['data'] = 2;
            }
        }

    } else {
        /**
         * Se não encontrar o token corrente, termina a sessão
         */
        if (isset($_SESSION['userlogin']['id'])) {
            sessionEnd();
        } else {
            $data['data'] = 2;
        }
    }

} else {
    /**
     * Se não tiver token corrente, termina a sessão
     */
    if (isset($_SESSION['userlogin']['id'])) {
        sessionEnd();
    } else {
        $data['data'] = 2;
    }
}