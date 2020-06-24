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
            "templates" => [],
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
             * Busca pelos assets (JS, CSS e PARAM)
             */
            foreach (Helper::listFolder($viewFolder) as $item) {
                $extensao = pathinfo($item, PATHINFO_EXTENSION);
                if ($extensao === "php" || $extensao === "html") {
                    if(!$find) {
                        $find = !0;
                        $this->file = $route;
                        $this->route = str_replace(PATH_HOME, "", $viewFolder . $item);
                        $this->lib = str_replace([PATH_HOME, VENDOR, "public/" . $this->directory . "/{$route}/{$setor}/", "public/" . $this->directory . "/{$route}/", "/"], "", $viewFolder);
                        $this->lib = $this->lib === "" ? DOMINIO : $this->lib;

                    }

                } elseif ($extensao === "js" && !isset($this->js[$item])) {
                    $this->js[$item] = $viewFolder . $item;
                } elseif ($extensao === "css" && !isset($this->css[$item])) {
                    $this->css[$item] = $viewFolder . $item;
                } elseif ($extensao === "json" && !isset($param[$item])) {
                    $param[$item] = $viewFolder . $item;
                } elseif ($extensao === "mustache" && !isset($this->templates[$item])) {
                    $this->templates[str_replace(".mustache", "", $item)] = $viewFolder . $item;
                }
            }
        }

        /**
         * turn array list of param into a unique object
         */
        foreach ($param as $item) {
            if(!empty($item)) {
                $item = json_decode(file_get_contents($item), !0);
                if(!empty($item) && is_array($item))
                    $this->param = array_merge($this->param, $item);
            }
        }
    }
}