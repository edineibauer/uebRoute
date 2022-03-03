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
        $find = !1;
        $param = [];

        /**
         * Find the view requested and set as route, if find
         */
        foreach (Config::getRoutesTo($this->directory . "/" . $route) as $viewFolder) {

            $findNota = -1;

            /**
             * Busca pelos assets (JS, CSS e PARAM)
             */
            foreach (Helper::listFolder($viewFolder) as $item) {
                if(!is_dir($viewFolder . $item)) {
                    $extensao = pathinfo($item, PATHINFO_EXTENSION);
                    if ($extensao === "php" || $extensao === "html" || $extensao === "mustache") {
                        $fileName = pathinfo($item, PATHINFO_FILENAME);
                        $nota = (!preg_match("/(vendor\/ueb|public\/overload)\//i", $viewFolder) ? 9 : (!preg_match("/vendor\/ueb\//i", $viewFolder) ? 6 : (preg_match("/public\/overload\//i", $viewFolder) ? 3 : 0))) + ($extensao === "html" ? 2 : ($extensao === "php" ? 1 : 0)) + ($fileName === $route ? 3 : 0) + ($fileName === "index" ? 3 : 0);
                        if($findNota < $nota) {
                            $this->file = $route;
                            $this->route = str_replace(PATH_HOME, "", $viewFolder . $item);
                            $this->lib = str_replace([PATH_HOME, VENDOR, "public/" . $this->directory . "/{$route}/{$setor}/", "public/" . $this->directory . "/{$route}/", "/"], "", $viewFolder);
                            $this->lib = $this->lib === "" ? DOMINIO : $this->lib;
                            $find = !in_array($this->lib, ["config", "dashboard", "route", "cep", "dev-ui", "entity-ui", "login", "report", "email"]) && $nota > 0;
                            $findNota = $nota;
                        }

                        if ($extensao === "mustache" && !isset($this->templates[$item]))
                            $this->templates[str_replace(".mustache", "", $item)] = $viewFolder . $item;

                    } elseif ($extensao === "js" && !isset($this->js[$item])) {
                        $this->js[$item] = $viewFolder . $item;
                    } elseif ($extensao === "css" && !isset($this->css[$item])) {
                        $this->css[$item] = $viewFolder . $item;
                    } elseif ($extensao === "json" && !isset($param[$item])) {
                        $param[$item] = $viewFolder . $item;
                    }
                }
            }

            foreach(["jsPre", "preJs", "pre"] as $preFolderName) {
                if(file_exists($viewFolder . $preFolderName)) {
                    foreach (Helper::listFolder($viewFolder . $preFolderName) as $item) {
                        if(pathinfo($item, PATHINFO_EXTENSION) === "js")
                            $this->param['jsPre'][] = str_replace(PATH_HOME, HOME, $viewFolder) . "{$preFolderName}/{$item}";
                    }
                }
            }

            if(file_exists($viewFolder . "tpl")) {
                foreach (Helper::listFolder($viewFolder . "tpl") as $item) {
                    if(pathinfo($item, PATHINFO_EXTENSION) === "mustache" && !isset($this->templates[$item]))
                        $this->templates[str_replace(".mustache", "", $item)] = $viewFolder . "tpl/" . $item;
                }
            }

            if(file_exists($viewFolder . "js")) {
                foreach (Helper::listFolder($viewFolder . "js") as $item) {
                    if (pathinfo($item, PATHINFO_EXTENSION) === "js" && !isset($this->js[$item]))
                        $this->js[$item] = $viewFolder . "js/" . $item;
                }
            }

            if(file_exists($viewFolder . "css")) {
                foreach (Helper::listFolder($viewFolder . "css") as $item) {
                    if (pathinfo($item, PATHINFO_EXTENSION) === "css" && !isset($this->js[$item]))
                        $this->css[$item] = $viewFolder . "css/" . $item;
                }
            }

            if($find)
                break;
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