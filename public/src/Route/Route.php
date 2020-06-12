<?php

/**
 * Find and return the view file and where the view file is
 *
 * @copyright (c) 2020, Edinei J. Bauer
 */

namespace Route;

use Config\Config;

class Route
{
    private $directory;
    private $route;
    private $lib;
    private $file;
    private $variaveis;

    /**
     * @param string|null $url
     * @param string|null $dir
     */
    public function __construct(string $url = null, string $dir = null)
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
        $this->route = VENDOR . $this->lib ."/public/view/404/404.php";
        $this->variaveis = array_filter(explode('/', $url));
        $route = array_shift($this->variaveis);
        $setor = Config::getSetor();

        /**
         * Find the view requested and set as route, if find
         */
        foreach (Config::getRoutesTo($this->directory) as $viewFolder) {

            if(($viewExtensionPhp = file_exists($viewFolder . $route . "/" . $route . ".php")) || file_exists($viewFolder . $route . "/" . $route . ".html")) {
                $this->file = $route;
                $this->route = str_replace(PATH_HOME, "", $viewFolder . $route . "/" . $this->file . ($viewExtensionPhp ? ".php" : ".html"));
                $this->lib = str_replace([PATH_HOME, VENDOR, "public/" . $this->directory . "/" . $setor . "/", "public/" . $this->directory . "/"], "", $viewFolder);
                $this->lib = $this->lib === "" ? DOMINIO : $this->lib;

                break;
            }
        }
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
     * @param mixed $file
     */
    protected function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @param mixed $lib
     */
    protected function setLib($lib)
    {
        $this->lib = $lib;
    }

    /**
     * @param mixed $route
     */
    protected function setRoute($route)
    {
        $this->route = $route;
    }

    /**
     * @param array $variaveis
     */
    protected function setVariaveis(array $variaveis)
    {
        $this->variaveis = $variaveis;
    }
}