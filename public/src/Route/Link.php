<?php

/**
 * Responável por gerenciar e fornecer informações sobre o link url!
 *
 * @copyright (c) 2018, Edinei J. Bauer
 */

namespace Route;

use Conn\Read;
use Config\UpdateSystem;
use Entity\Dicionario;
use Helpers\Helper;
use MatthiasMullie\Minify;

class Link extends Route
{
    private $param;

    /**
     * Link constructor.
     */
    function __construct(string $url = null, string $dir = null)
    {
        parent::__construct($url, $dir);

        $pathFile = (parent::getLib() === DOMINIO ? "public/" : VENDOR . parent::getLib() . "/public/");
        $this->param = $this->getBaseParam(parent::getFile(), $pathFile);

        //verifica se possui restrição de acesso por setor nesta rota
        if(!empty($this->param['setor']) && (empty($_SESSION['userlogin']['setor']) || (is_numeric($this->param['setor']) && $this->param['setor'] < $_SESSION['userlogin']['setor']) || (is_array($this->param['setor']) && !in_array($_SESSION['userlogin']['setor'], $this->param['setor'])))){

            //usuário não tem permissão de acesso a esta rota, retorna 404
            $l = new Link('404');
            $this->param = $l->getParam();
            parent::setFile($l->getFile());
            parent::setLib($l->getLib());
            parent::setRoute($l->getRoute());
            parent::setVariaveis($l->getVariaveis());

        } else {

            $this->param['data'] = $this->readData(parent::getFile());

            if (empty($this->param['title']))
                $this->param['title'] = $this->getTitle(parent::getFile());
            else
                $this->param['title'] = $this->prepareTitle($this->param['title'], parent::getFile());

            /* Se não existir os assets Core, cria eles */
            if (!file_exists(PATH_HOME . "assetsPublic/core.min.js") || !file_exists(PATH_HOME . "assetsPublic/core.min.css"))
                new UpdateSystem(['assets']);

            if (!file_exists(PATH_HOME . "assetsPublic/view/" . parent::getFile() . ".min.js") || !file_exists(PATH_HOME . "assetsPublic/view/" . parent::getFile() . ".min.css")) {
                if (!empty($this->param['js']) || !empty($this->param['css'])) {
                    $list = implode('/', array_unique(array_merge((is_array($this->param['js']) ? $this->param['js'] : []), (is_array($this->param['css']) ? $this->param['css'] : []))));
                    $data = json_decode(file_get_contents(REPOSITORIO . "app/library/{$list}"), true);
                    $data = $data['response'] === 1 && !empty($data['data']) ? $data['data'] : [];
                } else {
                    $data = [];
                }

                Helper::createFolderIfNoExist(PATH_HOME . "assetsPublic");
                Helper::createFolderIfNoExist(PATH_HOME . "assetsPublic/view");

                /* Se não existir os assets View, cria eles */
                if (!file_exists(PATH_HOME . "assetsPublic/view/" . parent::getFile() . ".min.js"))
                    $this->createPageJs(parent::getFile(), $data, $pathFile);

                /* Se não existir os assets View, cria eles */
                if (!file_exists(PATH_HOME . "assetsPublic/view/" . parent::getFile() . ".min.css"))
                    $this->createPageCss(parent::getFile(), $data, $pathFile);

                /* Se não existir os assets de Imagem, cria eles*/
                if (!file_exists(PATH_HOME . "assetsPublic/img/" . parent::getFile() . ""))
                    $this->createImagens(parent::getFile(), $data, $pathFile);
            }

            /* Adiciona o arquivo css da view na variável */
            $this->param['css'] = file_get_contents(PATH_HOME . "assetsPublic/view/" . parent::getFile() . ".min.css");
            $this->param['js'] = HOME . "assetsPublic/view/" . parent::getFile() . ".min.js";
            $this->param["vendor"] = VENDOR;
            $this->param["url"] = parent::getFile() . (!empty(parent::getVariaveis()) ? "/" . implode('/', parent::getVariaveis()) : "");
            $this->param['loged'] = !empty($_SESSION['userlogin']);
            $this->param['login'] = ($this->param['loged'] ? $_SESSION['userlogin'] : "");
            $this->param['email'] = defined("EMAIL") && !empty(EMAIL) ? EMAIL : "contato@" . DOMINIO;
            $this->param['menu'] = "";
        }
    }

    /**
     * @param string $file
     * @return array
     */
    private function readData(string $file): array
    {
        if (count(parent::getVariaveis()) === 1) {
            if (file_exists(PATH_HOME . "entity/cache/{$file}.json")) {
                $dic = new Dicionario($file);

                if ($name = $dic->search($dic->getInfo()['link'])) {
                    $name = $name->getColumn();

                    $read = new Read();
                    $read->exeRead($file, "WHERE id = :nn || {$name} = :nn", "nn={parent::getVariaveis()[0]}");
                    if ($read->getResult()) {
                        $dados = $read->getResult()[0];
                        if (!isset($dados['title']))
                            $dados["title"] = $dados[$dic->search($dic->getInfo()['title'])->getColumn()];

                        return $dados;
                    }
                }
            }
        }

        return [];
    }

    /**
     * Cria as imagens
     * @param string $file
     * @param array $data
     * @param string $pathFile
     */
    private function createImagens(string $file, array $data, string $pathFile)
    {
        Helper::createFolderIfNoExist(PATH_HOME . "assetsPublic/img");
        foreach ($data as $datum) {
            if (!empty($datum['imagens']) && !file_exists(PATH_HOME . "assetsPublic/img/{$datum['nome']}")) {
                Helper::createFolderIfNoExist(PATH_HOME . "assetsPublic/img/{$datum['nome']}");
                foreach ($datum['imagens'] as $file) {
                    if (!file_exists(PATH_HOME . "assetsPublic/img/{$datum['nome']}/{$file['name']}"))
                        copy($file['content'], PATH_HOME . "assetsPublic/img/{$datum['nome']}/{$file['name']}");
                }
            }
        }
    }

    /**
     * Cria View Assets JS
     * @param string $name
     * @param array $data
     * @param string $pathFile
     */
    private function createPageJs(string $name, array $data, string $pathFile)
    {
        $minifier = new Minify\JS("");

        foreach ($data as $datum) {
            if (in_array($datum['nome'], $this->param['js'])) {
                foreach ($datum['arquivos'] as $file) {
                    if ($file['type'] === "text/javascript")
                        $minifier->add($file['content']);
                }
            }
        }

        if (file_exists(PATH_HOME . $pathFile . "assets/{$name}.min.js"))
            $minifier->add(file_get_contents(PATH_HOME . $pathFile . "assets/{$name}.min.js"));
        elseif (file_exists(PATH_HOME . $pathFile . "assets/{$name}.js"))
            $minifier->add(file_get_contents(PATH_HOME . $pathFile . "assets/{$name}.js"));

        $minifier->minify(PATH_HOME . "assetsPublic/view/{$name}.min.js");
    }

    /**
     * Cria View Assets CSS
     * @param string $name
     * @param array $data
     * @param string $pathFile
     */
    private function createPageCss(string $name, array $data, string $pathFile)
    {
        $minifier = new Minify\CSS("");

        if (!empty($this->param['css']) && is_array($this->param['css'])) {
            foreach ($this->param['css'] as $item) {
                $datum = array_values(array_filter(array_map(function ($d) use ($item) {
                    return $d['nome'] === $item ? $d : [];
                }, $data)));

                if (!empty($datum[0])) {
                    $datum = $datum[0];

                    if (!empty($datum['arquivos'])) {
                        foreach ($datum['arquivos'] as $file) {
                            if ($file['type'] === "text/css")
                                $minifier->add($file['content']);
                        }
                    }
                }
            }
        }

        if (file_exists(PATH_HOME . $pathFile . "assets/{$name}.css"))
            $minifier->add(file_get_contents(PATH_HOME . $pathFile . "assets/{$name}.css"));
        elseif (file_exists(PATH_HOME . $pathFile . "assets/{$name}.min.css"))
            $minifier->add(file_get_contents(PATH_HOME . $pathFile . "assets/{$name}.min.css"));

        $minifier->minify(PATH_HOME . "assetsPublic/view/{$name}.min.css");

        //Ajusta diretório dos assets
        $file = file_get_contents(PATH_HOME . "assetsPublic/view/{$name}.min.css");
        $file = str_replace("../", "", $file);
        $f = fopen(PATH_HOME . "assetsPublic/view/{$name}.min.css", "w");
        fwrite($f, $file);
        fclose($f);

    }

    /**
     * @return mixed
     */
    public function getParam()
    {
        return $this->param;
    }

    /**
     * @param string $file
     * @param string $pathFile
     * @return array
     */
    private function getBaseParam(string $file, string $pathFile)
    {
        $base = [
            "version" => VERSION,
            "meta" => "",
            "css" => [],
            "js" => [],
            "font" => "",
            "descricao" => "",
            "data" => 0,
            "analytics" => defined("ANALYTICS") ? ANALYTICS : ""
        ];

        if (file_exists(PATH_HOME . $pathFile . "param/{$file}.json")) {
            $param = json_decode(file_get_contents(PATH_HOME . $pathFile . "param/{$file}.json"), true);
            if (!empty($param))
                $base = array_merge($base, $param);
        }

        return $base;
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

        $data = array_merge($this->param['data'], [
            "title" => $this->param['data']['title'] ?? $titulo,
            "titulo" => $this->param['data']['title'] ?? $titulo,
            "sitename" => SITENAME,
            "SITENAME" => SITENAME,
            "sitesub" => SITESUB,
            "SITESUB" => SITESUB,
        ]);

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