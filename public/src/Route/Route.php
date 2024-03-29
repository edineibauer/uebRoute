<?php

/**
 * Find and return the view file and where the view file is
 *
 * @copyright (c) 2020, Edinei J. Bauer
 */

namespace Route;

use Config\Config;
use Helpers\Helper;

class Route
{
    private $directory;
    private $route;
    private $lib;
    private $file;
    protected $param;
    private $css = [];
    private $js = [];
    private $jsPre = [];
    private $templates = [];
    private $variaveis;

    /**
     * @param string|null $url
     * @param string|null $dir
     */
    public function __construct(string $url = null, string $dir = null, string $setor = null)
    {
        /**
         * format the url
         */
        $url = (!$url ? strip_tags(trim(filter_input(INPUT_GET, 'url', FILTER_DEFAULT))) : str_replace([PATH_HOME, HOME], '', $url));
        $url = empty($url) || $url === "/" ? "index" : $url;

        /**
         * Set default view as 404
         */
        $this->directory = $dir ?? "view";
        $this->file = "404";
        $this->lib = "config";
        $this->route = VENDOR . $this->lib . "/public/view/404/404.php";
        $this->variaveis = array_filter(explode('/', $url));
        $this->param = $this->getParamBase();
        $route = array_shift($this->variaveis);

        $this->findRoute($route, $setor);

        if ($route !== "404" && $this->file === "404")
            $this->findRoute("404", $setor);
    }

    /**
     * @return array
     */
    public function getVariaveis(): array
    {
        return $this->variaveis ?? [];
    }

    /**
     * @return mixed
     */
    public function getCss()
    {
        return $this->css;
    }

    /**
     * @return mixed
     */
    public function getJs()
    {
        return $this->js;
    }

    /**
     * @return mixed
     */
    public function getJsPre()
    {
        return $this->jsPre;
    }

    /**
     * @return array
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * @return mixed
     */
    public function getParam()
    {
        return $this->param;
    }

    /**
     * @return mixed
     */
    public function getRoute()
    {
        return $this->route ? PATH_HOME . $this->route : null;
    }

    /**
     * @return mixed
     */
    public function getLib()
    {
        return $this->lib;
    }

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param string $content
     */
    public function setContent(string $content) {
        $this->param["content"] = $content;
    }

    /**
     * default response Param from a view
     * @return array
     */
    private function getParamBase()
    {
        return [
            "css" => "",
            "js" => [],
            "jsPre" => [],
            "head" => [],
            "title" => "",
            "descricao" => "",
            "templates" => [],
            "header" => true,
            "navbar" => true,
            "cache" => false,
            "setor" => [],
            "!setor" => [],
            "redirect" => "403",
            "content" => ""
        ];
    }

    /**
     * @param string $route
     */
    private function findRoute(string $route, string $setor = null)
    {
        $setor = $setor ?? Config::getSetor();
        $param = [];

        /**
         * Busca view que deverá ser utilizada
         */
        $folderChoose = Config::getRouteTo($this->directory . "/" . $route);

        /**
         * Busca por alias para esta rota
         */
        foreach (Helper::listFolder($folderChoose) as $item) {
            if(is_dir($folderChoose . $item) || pathinfo($item, PATHINFO_EXTENSION) !== "json")
                continue;

            $f = json_decode(file_get_contents($folderChoose . $item), true);
            if(!empty($f["alias"])) {
                $folderChoose = Config::getRouteTo($this->directory . "/" . $f["alias"], $folderChoose);
                break;
            }
        }

        /**
         * Busca pelos assets (JS, CSS e PARAM)
         */
        $findIndexNota = -1;
        foreach (Helper::listFolder($folderChoose) as $item) {
            if(!is_dir($folderChoose . $item)) {
                $extensao = pathinfo($item, PATHINFO_EXTENSION);
                if ($extensao === "php" || $extensao === "html" || $extensao === "mustache") {
                    $fileName = pathinfo($item, PATHINFO_FILENAME);
                    $nota = ($extensao === "html" ? 2 : ($extensao === "php" ? 1 : 0)) + ($fileName === $route ? 3 : 0) + ($fileName === "index" ? 3 : 0);
                    if($findIndexNota < $nota) {
                        $this->file = $route;
                        $this->route = str_replace(PATH_HOME, "", $folderChoose . $item);
                        $this->lib = str_replace([PATH_HOME, VENDOR, "public/" . $this->directory . "/{$route}/{$setor}/", "public/" . $this->directory . "/{$route}/", "/"], "", $folderChoose);
                        $this->lib = $this->lib === "" ? DOMINIO : $this->lib;
                        $findIndexNota = $nota;
                    }

                    if ($extensao === "mustache" && !isset($this->templates[$item]))
                        $this->templates[str_replace(".mustache", "", $item)] = $folderChoose . $item;

                } elseif ($extensao === "js" && !isset($this->js[$item])) {
                    $this->js[$item] = $folderChoose . $item;
                } elseif ($extensao === "css" && !isset($this->css[$item])) {
                    $this->css[$item] = $folderChoose . $item;
                } elseif ($extensao === "json" && !isset($param[$item])) {
                    $param[$item] = $folderChoose . $item;
                }
            }
        }

        foreach(["jsPre", "preJs", "pre"] as $preFolderName) {
            if(file_exists($folderChoose . $preFolderName)) {
                foreach (Helper::listFolder($folderChoose . $preFolderName) as $item) {
                    if(pathinfo($item, PATHINFO_EXTENSION) === "js")
                        $this->jsPre[] = $folderChoose . "{$preFolderName}/{$item}";
                }
            }
        }

        if(file_exists($folderChoose . "tpl")) {
            foreach (Helper::listFolder($folderChoose . "tpl") as $item) {
                if(pathinfo($item, PATHINFO_EXTENSION) === "mustache" && !isset($this->templates[$item]))
                    $this->templates[str_replace(".mustache", "", $item)] = $folderChoose . "tpl/" . $item;
            }
        }

        if(file_exists($folderChoose . "js")) {
            foreach (Helper::listFolder($folderChoose . "js") as $item) {
                if (pathinfo($item, PATHINFO_EXTENSION) === "js" && !isset($this->js[$item]))
                    $this->js[$item] = $folderChoose . "js/" . $item;
            }
        }

        if(file_exists($folderChoose . "css")) {
            foreach (Helper::listFolder($folderChoose . "css") as $item) {
                if (pathinfo($item, PATHINFO_EXTENSION) === "css" && !isset($this->js[$item]))
                    $this->css[$item] = $folderChoose . "css/" . $item;
            }
        }

        ksort($this->js);
        ksort($this->css);

        /**
         * turn array list of param into a unique object
         */
        foreach ($param as $item) {
            if (!empty($item)) {
                $item = json_decode(file_get_contents($item), !0);

                $this->setParamMerge("jsPre", $item['jsPre'] ?? "");
                $this->setParamMerge("js", $item['js'] ?? "");
                $this->setParamMerge("css", $item['css'] ?? "");
                $this->setParamMerge("templates", $item['template'] ?? "");
                $this->setParamMerge("templates", $item['templates'] ?? "");
                $this->setParamMerge("head", $item['head'] ?? "");
                $this->setParamMerge("meta", $item['meta'] ?? "");
                $this->setParamMerge("setor", $item['setor'] ?? "");
                $this->setParamMerge("!setor", $item['!setor'] ?? "");

                if(!empty($item['title']))
                    $this->param['title'] = $item['title'];

                if(!empty($item['descricao']))
                    $this->param['descricao'] = $item['descricao'];

                if(isset($item['header']) && is_bool($item['header']))
                    $this->param['header'] = $item['header'];

                if(isset($item['navbar']) && is_bool($item['navbar']))
                    $this->param['navbar'] = $item['navbar'];

                if(!empty($item['redirect']))
                    $this->param['redirect'] = $item['redirect'];

                if(!empty($item['cache']))
                    $this->param['cache'] = $item['cache'];
            }
        }
    }

    private function setParamMerge(string $param, $value)
    {
        if (!empty($value) || $value === "0") {
            if(is_string($this->param[$param]))
                $this->param[$param] = [$this->param[$param]];

            if (is_array($value)) {
                foreach ($value as $j) {
                    if (!in_array($j, $this->param[$param]))
                        $this->param[$param][] = $j;
                }
            } elseif (is_string($value)) {
                $this->param[$param][] = $value;
            }
        }
    }
}