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

        if($this->file === "404")
            $this->findRoute("404", $setor);

        $this->prepareAssets();
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
     * default response Param from a view
     * @return array
     */
    private function getParamBase()
    {
        return [
            "version" => VERSION,
            "css" => [],
            "js" => [],
            "meta" => "",
            "head" => [],
            "title" => "",
            "descricao" => "",
            "data" => 0,
            "front" => [],
            "header" => !0,
            "navbar" => !0,
            "setor" => "",
            "!setor" => "",
            "redirect" => "403",
            "vendor" => VENDOR
        ];
    }

    /**
     * Prepare Assets JS and CSS to include in view
     */
    private function prepareAssets()
    {
        /**
         * for each css declared on param
         * search on assets folder on each lib
         */
        if(!empty($this->param['js'])) {
            foreach (Config::getRoutesFilesTo("assets", "js") as $f => $item) {
                if (is_array($this->param['js'])) {
                    foreach ($this->param['js'] as $js) {
                        if(is_string($js) && $f === pathinfo($js, PATHINFO_BASENAME) . ".js") {
                            $this->js[$f] = $item;
                            break;
                        }
                    }
                } elseif(is_string($this->param['js']) && $f === str_replace(".js", "", $this->param['js']) . ".js") {
                    $this->js[$f] = $item;
                }
            }
        }

        /**
         * for each javascript declared on param
         * search on assets folder on each lib
         */
        if(!empty($this->param['css'])) {
            foreach (Config::getRoutesFilesTo("assets", "css") as $f => $item) {
                if (is_array($this->param['css'])) {
                    foreach ($this->param['css'] as $css) {
                        if(is_string($css) && $f === pathinfo($css, PATHINFO_BASENAME) . ".css") {
                            $this->css[$f] = $item;
                            break;
                        }
                    }
                } elseif(is_string($this->param['css']) && $f === str_replace(".css", "", $this->param['css']) . ".css") {
                    $this->css[$f] = $item;
                }
            }
        }
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

            /**
             * Busca pelo arquivo de HTML ou PHP da view
             */
            if ((($viewExtensionPhp = file_exists($viewFolder . $route . ".php")) || file_exists($viewFolder . $route . ".html")) && !$find) {
                $this->file = $route;
                $this->route = str_replace(PATH_HOME, "", $viewFolder . $this->file . ($viewExtensionPhp ? ".php" : ".html"));
                $this->lib = str_replace([PATH_HOME, VENDOR, "public/" . $this->directory . "/{$route}/{$setor}/", "public/" . $this->directory . "/{$route}/", "/"], "", $viewFolder);
                $this->lib = $this->lib === "" ? DOMINIO : $this->lib;
                $find = !0;
            }

            /**
             * Busca pelos assets (JS, CSS e PARAM)
             */
            foreach (Helper::listFolder($viewFolder) as $item) {
                $extensao = pathinfo($item, PATHINFO_EXTENSION);
                if ($extensao === "js" && !isset($this->js[$item]))
                    $this->js[$item] = $viewFolder . $item;
                elseif ($extensao === "css" && !isset($this->css[$item]))
                    $this->css[$item] = $viewFolder . $item;
                elseif ($extensao === "json" && !isset($param[$item]))
                    $param[$item] = $viewFolder . $item;
            }
        }

        /**
         * turn array list of param into a unique object
         */
        foreach ($param as $item)
            $this->param = array_merge($this->param, json_decode(file_get_contents($item), !0));

    }
}