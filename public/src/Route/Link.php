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
     * @param string|null $url
     * @param string|null $dir
     */
    function __construct(string $url = null, string $dir = null)
    {
        parent::__construct($url, $dir);

        $pathFile = (parent::getLib() === DOMINIO ? (str_replace([PATH_HOME, "/" . parent::getFile() . ".php", "view"], ['', '', ''], parent::getRoute())) : VENDOR . parent::getLib() . "/public/");
        $this->param = $this->getBaseParam(parent::getFile(), $pathFile);
        $this->param['data'] = $this->readData(parent::getFile());

        if($this->haveAccessPermission()) {
            $this->checkAssetsExist($pathFile);
            $this->createParamResponse();
        } else {
            $this::__construct("403");
        }
    }

    private function createParamResponse()
    {
        $this->param['title'] = (empty($this->param['title']) ? $this->getTitle(parent::getFile()) : $this->prepareTitle($this->param['title'], parent::getFile()));
        $this->param['css'] = file_get_contents(PATH_HOME . "assetsPublic/view/" . parent::getFile() . ".min.css");
        $this->param['js'] = HOME . "assetsPublic/view/" . parent::getFile() . ".min.js?v=" . VERSION;
        $this->param["vendor"] = VENDOR;
        $this->param["url"] = parent::getFile() . (!empty(parent::getVariaveis()) ? "/" . implode('/', parent::getVariaveis()) : "");
        $this->param['loged'] = !empty($_SESSION['userlogin']);
        $this->param['login'] = ($this->param['loged'] ? $_SESSION['userlogin'] : "");
        $this->param['email'] = defined("EMAIL") && !empty(EMAIL) ? EMAIL : "contato@" . DOMINIO;
        $this->param['menu'] = "";
    }

    /**
     * @param string $pathFile
     */
    private function checkAssetsExist(string $pathFile)
    {
        /* Se não existir os assets Core, cria eles */
        if (!file_exists(PATH_HOME . "assetsPublic/core.min.js") || !file_exists(PATH_HOME . "assetsPublic/core.min.css"))
            new UpdateSystem(['assets']);

        if(DEV) {
            if(file_exists(PATH_HOME . "assetsPublic/view/" . parent::getFile() . ".min.js"))
                unlink(PATH_HOME . "assetsPublic/view/" . parent::getFile() . ".min.js");
            if(file_exists(PATH_HOME . "assetsPublic/view/" . parent::getFile() . ".min.css"))
                unlink(PATH_HOME . "assetsPublic/view/" . parent::getFile() . ".min.css");
        }

        if (!file_exists(PATH_HOME . "assetsPublic/view/" . parent::getFile() . ".min.js") || !file_exists(PATH_HOME . "assetsPublic/view/" . parent::getFile() . ".min.css")) {

            if (!empty($this->param['js']) || !empty($this->param['css'])) {
                $list = implode('/', array_unique(array_merge((is_array($this->param['js']) ? $this->param['js'] : []), (is_array($this->param['css']) ? $this->param['css'] : []))));
                $data = json_decode(file_get_contents(REPOSITORIO . "app/library/{$list}"), true);
                $data = !empty($data) ? $data : [];
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
    }

    /**
     * @return bool
     */
    private function haveAccessPermission(): bool
    {
        $allow = !0;
        $mySetor = (!empty($_SESSION['userlogin']) ? $_SESSION['userlogin']['setor'] : "0");
        if(isset($this->param['setor']) && (!empty($this->param['setor']) || $this->param['setor'] == '0')) {
            $allow = !1;
            if(is_array($this->param['setor'])) {
                foreach ($this->param['setor'] as $seto) {
                    if(is_string($seto) && $seto === $mySetor) {
                        $allow = !0;
                        return !1;
                    }
                }
            } elseif(is_string($this->param['setor']) && $this->param['setor'] === $mySetor) {
                $allow = !0;
            }
        } elseif(isset($this->param['!setor']) && (!empty($this->param['!setor']) || $this->param['!setor'] == '0')) {
            if(is_array($this->param['!setor'])) {
                foreach ($this->param['!setor'] as $seto) {
                    if(is_string($seto) && $seto === $mySetor) {
                        $allow = !1;
                        break;
                    }
                }
            } elseif(is_string($this->param['!setor']) && $this->param['!setor'] === $mySetor) {
                $allow = !1;
            }
        }

        return $allow;
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
                    $read->exeRead($file, "WHERE id = :nn || {$name} = :nn", "nn=" . parent::getVariaveis()[0]);
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
     * @param string $asset
     * @param string $pathFile
     * @param string $extension
     * @return false|string
     */
    private function getAssetsContent(string $asset, string $pathFile, string $extension) {
        if(DOMINIO !== parent::getLib() && file_exists(PATH_HOME . "public/overload/" . parent::getLib() . "/assets/" . $asset . ".{$extension}")) {
            return @file_get_contents(PATH_HOME . "public/overload/" . parent::getLib() . "/assets/" . $asset . ".{$extension}");
        } elseif(DOMINIO !== parent::getLib() && file_exists(PATH_HOME . "public/overload/" . parent::getLib() . "/public/assets/" . $asset . ".{$extension}")) {
            return @file_get_contents(PATH_HOME . "public/overload/" . parent::getLib() ."/public/assets/" . $asset . ".{$extension}");
        } elseif (file_exists(PATH_HOME . $pathFile . "assets/" . $asset . ".{$extension}")) {
            return @file_get_contents(PATH_HOME . $pathFile . "assets/" . $asset . ".{$extension}");
        }
        return "";
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

        if(!empty($this->param['js'])) {
            if(is_string($this->param['js'])) {
                $minifier->add($this->getAssetsContent($this->param['js'], $pathFile, 'js'));
            } elseif(is_array($this->param['js'])) {
                foreach ($this->param['js'] as $j)
                    $minifier->add($this->getAssetsContent($j, $pathFile, 'js'));
            }
        }

        if (!empty($_SESSION['userlogin']['setor']) && file_exists(PATH_HOME . $pathFile . "assets/" . $_SESSION['userlogin']['setor'] . "/{$name}.js")) {

            //setor Assets
            $minifier->add(file_get_contents(PATH_HOME . $pathFile . "assets/" . $_SESSION['userlogin']['setor'] . "/{$name}.js"));
        } elseif (file_exists(PATH_HOME . $pathFile . "assets/{$name}.js")) {

            //default Assets
            $minifier->add(file_get_contents(PATH_HOME . $pathFile . "assets/{$name}.js"));
        }

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

        if(!empty($this->param['css'])) {
            if(is_string($this->param['css'])) {
                $minifier->add($this->getAssetsContent($this->param['css'], $pathFile, 'css'));
            } elseif(is_array($this->param['css'])) {
                foreach ($this->param['css'] as $j)
                    $minifier->add($this->getAssetsContent($j, $pathFile, 'css'));
            }
        }

        if (!empty($_SESSION['userlogin']['setor']) && file_exists(PATH_HOME . $pathFile . "assets/" . $_SESSION['userlogin']['setor'] . "/{$name}.css")) {

            //setor Assets
            $minifier->add(file_get_contents(PATH_HOME . $pathFile . "assets/" . $_SESSION['userlogin']['setor'] . "/{$name}.css"));
        } elseif (file_exists(PATH_HOME . $pathFile . "assets/{$name}.css")) {

            //default Assets
            $minifier->add(file_get_contents(PATH_HOME . $pathFile . "assets/{$name}.css"));
        }

        /**
         * Busca Sistemas que tenham assets nessa página
         */
        foreach (Helper::listFolder(PATH_HOME . VENDOR) as $lib) {
            if(file_exists(PATH_HOME . VENDOR . "/" . $lib . "/public/_config/") && (file_exists(PATH_HOME . VENDOR . "/" . $lib . "/public/assets/{$name}.css") || file_exists(PATH_HOME . VENDOR . "/" . $lib . "/public/assets/{$name}.min.css"))) {
                if (file_exists(PATH_HOME . VENDOR . "/" . $lib . "/public/assets/{$name}.min.css"))
                    $minifier->add(file_get_contents(PATH_HOME . VENDOR . "/" . $lib . "/public/assets/{$name}.min.css"));
                else
                    $minifier->add(file_get_contents(PATH_HOME . VENDOR . "/" . $lib . "/public/assets/{$name}.css"));
            }
        }

        $minifier->minify(PATH_HOME . "assetsPublic/view/{$name}.min.css");

        $config = json_decode(file_get_contents(PATH_HOME . "_config/config.json"), true);

        $dirTheme = (file_exists(PATH_HOME . "public/assets/theme.min.css") ? PATH_HOME . "public/assets/theme.min.css" : PATH_HOME . VENDOR . "config/public/assets/theme.min.css");
        $themeFile = file_get_contents($dirTheme);
        $theme = explode("}", explode(".theme{", $themeFile)[1])[0];
        $themeColor = explode("}", explode(".theme-text-aux{", $themeFile)[1])[0];
        $theme = explode("!important", explode("background-color:", $theme)[1])[0];
        $themeColor = explode("!important", explode("color:", $themeColor)[1])[0];

        $arrayReplace = ["../" => "", '{$home}' => HOME, '{$vendor}' => VENDOR, '{$version}' => $config['version'], '{$favicon}' => $config['favicon'], '{$logo}' => $config['logo'], '{$theme}' => $theme, '{$theme-aux}' => $themeColor, '{$publico}' => PUBLICO,
            '{{home}}' => HOME, '{{vendor}}' => VENDOR, '{{version}}' => $config['version'], '{{favicon}}' => $config['favicon'], '{{logo}}' => $config['logo'], '{{theme}}' => $theme, '{{theme-aux}}' => $themeColor, '{{publico}}' => PUBLICO];

        //Ajusta diretório dos assets
        $file = file_get_contents(PATH_HOME . "assetsPublic/view/{$name}.min.css");
        $file = str_replace(array_keys($arrayReplace), array_values($arrayReplace), $file);
        $file = $this->getPrefixedCss($file, ".r-" . $name);

        $f = fopen(PATH_HOME . "assetsPublic/view/{$name}.min.css", "w");
        fwrite($f, $file);
        fclose($f);

    }

    /**
     * @param string $css
     * @param string $prefix
     * @return string|string[]|null
     */
    private function getPrefixedCss(string $css, string $prefix)
    {
        # Wipe all block comments
        $css = preg_replace('!/\*.*?\*/!s', '', $css);

        $parts = explode('}', $css);
        $keyframeStarted = false;
        $mediaQueryStarted = false;

        foreach($parts as &$part)
        {
            $part = trim($part); # Wht not trim immediately .. ?
            if(empty($part)) {
                $keyframeStarted = false;
                continue;
            }
            else # This else is also required
            {
                $partDetails = explode('{', $part);

                if (strpos($part, 'keyframes') !== false) {
                    $keyframeStarted = true;
                    continue;
                }

                if($keyframeStarted) {
                    continue;
                }

                if(substr_count($part, "{")==2)
                {
                    $mediaQuery = $partDetails[0]."{";
                    $partDetails[0] = $partDetails[1];
                    $mediaQueryStarted = true;
                }

                $subParts = explode(',', $partDetails[0]);
                foreach($subParts as &$subPart)
                {
                    if(trim($subPart)==="@font-face") continue;
                    else $subPart = $prefix . (preg_match('/^(html|body)/i', $subPart) ? str_replace(['html ', 'body ', 'html', 'body'], [" ", " ", "", ""], $subPart) : ' ' . trim($subPart));
                }

                if(substr_count($part,"{")==2)
                {
                    $part = $mediaQuery."\n".implode(', ', $subParts)."{".$partDetails[2];
                }
                elseif(empty($part[0]) && $mediaQueryStarted)
                {
                    $mediaQueryStarted = false;
                    $part = implode(', ', $subParts)."{".$partDetails[2]."}\n"; //finish media query
                }
                else
                {
                    if(isset($partDetails[1]))
                    {   # Sometimes, without this check,
                        # there is an error-notice, we don't need that..
                        $part = implode(', ', $subParts)."{".$partDetails[1];
                    }
                }

                unset($partDetails, $mediaQuery, $subParts); # Kill those three ..
            }   unset($part); # Kill this one as well
        }

        # Finish with the whole new prefixed string/file in one line
        return(preg_replace('/\s+/',' ',implode("} ", $parts)));
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
            "isAdmin" => !empty($_SESSION['userlogin']['setor']) && $_SESSION['userlogin']['setor'] === "admin",
            "header" => !0,
            "navbar" => !0,
            "setor" => "",
            "!setor" => "",
            "analytics" => defined("ANALYTICS") ? ANALYTICS : ""
        ];

        if(file_exists(PATH_HOME . "public/overload/" . parent::getLib() . "/param/{$file}.json")) {
            $param = json_decode(file_get_contents(PATH_HOME . "public/overload/" . parent::getLib() . "/param/{$file}.json"), !0);
        } elseif(file_exists(PATH_HOME . "public/overload/" . parent::getLib() . "/public/param/{$file}.json")) {
            $param = json_decode(file_get_contents(PATH_HOME . "public/overload/" . parent::getLib() . "/public/param/{$file}.json"), !0);
        } elseif (file_exists(PATH_HOME . $pathFile . "param/{$file}.json")) {
            $param = json_decode(file_get_contents(PATH_HOME . $pathFile . "param/{$file}.json"), !0);
        }

        if (!empty($param))
            return array_merge($base, $param);

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