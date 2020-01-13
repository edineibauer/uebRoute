<?php

/**
 * Responável por gerenciar e fornecer informações sobre o link url!
 *
 * @copyright (c) 2018, Edinei J. Bauer
 */

namespace Route;

use Config\Config;
use Config\UpdateSystem;

class Link extends Route
{
    private $param;

    /**
     * Link constructor.
     * @param string|null $url
     * @param string|null $dir
     */
    function __construct(string $url = null, string $dir = null)
    {
        $dir = $dir ?? "view";
        parent::__construct($url, $dir);

        $setor = !empty($_SESSION['userlogin']) ? $_SESSION['userlogin']['setor'] : "0";
        $this->param = Config::getViewParam(parent::getFile(), parent::getLib(), $setor);

        $this->checkAssetsExist($dir, $setor);
        $this->createParamResponse();
    }

    /**
     * @return mixed
     */
    public function getParam()
    {
        return $this->param;
    }

    private function createParamResponse()
    {
        $this->param['title'] = (empty($this->param['title']) ? $this->getTitle(parent::getFile()) : $this->prepareTitle($this->param['title'], parent::getFile()));
        $this->param['css'] = file_get_contents(PATH_HOME . "assetsPublic/view/" . parent::getFile() . ".min.css");
        $this->param['js'] = HOME . "assetsPublic/view/" . parent::getFile() . ".min.js?v=" . VERSION;
        $this->param["url"] = parent::getFile() . (!empty(parent::getVariaveis()) ? "/" . implode('/', parent::getVariaveis()) : "");
        $this->param['loged'] = !empty($_SESSION['userlogin']);
        $this->param['login'] = ($this->param['loged'] ? $_SESSION['userlogin'] : "");
        $this->param['email'] = defined("EMAIL") && !empty(EMAIL) ? EMAIL : "contato@" . DOMINIO;
        $this->param['menu'] = "";
        $this->param['variaveis'] = parent::getVariaveis();
    }

    /**
     * Verifica se os assets existem ou se esta em DEV para atualizar
     *
     * @param string $dir
     * @param string $setor
     */
    private function checkAssetsExist(string $dir, string $setor)
    {
        /**
         * Se estiver em Desenvolvimento, ou se não existir.
         * Atualiza os Core
         */
        if (!file_exists(PATH_HOME . "assetsPublic/appCore.min.js") || !file_exists(PATH_HOME . "assetsPublic/appCore.min.css"))
            new UpdateSystem(["assets", "manifest"]);

        /**
         * Se estiver em Desenvolvimento, ou se não existir.
         * Atualiza os Core
         */
        if ((DEV && $dir === "view") || !file_exists(PATH_HOME . "assetsPublic/core/" . $setor . "/core.min.js") || !file_exists(PATH_HOME . "assetsPublic/core/" . $setor . "/core.min.css"))
            Config::createCore();

        /**
         * Se estiver em Desenvolvimento, ou se não existir.
         * Atualiza o JS e CSS da view atualmente requisitada
         */
        if ((DEV && $dir === "view") || !file_exists(PATH_HOME . "assetsPublic/view/" . parent::getFile() . ".min.js") || !file_exists(PATH_HOME . "assetsPublic/view/" . parent::getFile() . ".min.css"))
            Config::createViewAssets(parent::getFile(), ["js" => $this->param['js'], "css" => $this->param['css']], parent::getLib());
    }

    /**
     * @param string $file
     * @return string
     */
    private function getTitle(string $file): string
    {
        if (empty($this->param['data']['title']))
            return ucwords(str_replace(["-", "_"], " ", $file)) . (!empty(parent::getVariaveis()) ? " | " . SITENAME : "");

        return $this->param['data']['title'];
    }

    /**
     * Prepara o formato do título caso tenha variáveis
     *
     * @param string $title
     * @param string $file
     * @return string
     */
    private function prepareTitle(string $title, string $file): string
    {
        $titulo = ucwords(str_replace(["-", "_"], " ", $file));

        $data = [
            "title" => $this->param['data']['title'] ?? $titulo,
            "titulo" => $this->param['data']['title'] ?? $titulo,
            "sitename" => SITENAME,
            "SITENAME" => SITENAME,
            "sitesub" => SITESUB,
            "SITESUB" => SITESUB
        ];

        if (preg_match('/{{/i', $title)) {
            foreach (explode('{{', $title) as $i => $item) {
                if ($i > 0) {
                    $variavel = explode('}}', $item)[0];
                    $title = str_replace('{{' . $variavel . '}}', (!empty($data[$variavel]) ? $data[$variavel] : ""), $title);
                }
            }

        } elseif (preg_match('/{\$/i', $title)) {
            foreach (explode('{$', $title) as $i => $item) {
                if ($i > 0) {
                    $variavel = explode('}', $item)[0];
                    $title = str_replace('{$' . $variavel . '}', (!empty($data[$variavel]) ? $data[$variavel] : ""), $title);
                }
            }
        }

        return $title;
    }
}