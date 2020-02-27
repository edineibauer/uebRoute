<?php

/**
 * Busca por arquivo a ser carregado em um request ao sistema Singular
 *
 * @copyright (c) 2018, Edinei J. Bauer
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
    private $variaveis;

    /**
     * Route constructor.
     * @param string|null $url
     * @param string|null $dir
     */
    public function __construct(string $url = null, string $dir = null)
    {
        $this->directory = $dir ?? "view";
        $this->variaveis = [];
        if (!$url)
            $url = strip_tags(trim(filter_input(INPUT_GET, 'url', FILTER_DEFAULT)));
        else
            $url = str_replace([PATH_HOME, HOME], '', $url);

        $paths = empty($url) || $url === "/" ? ["index"] : array_filter(explode('/', $url));
        $libs = $this->getRouteFolders();
        $this->searchFile($paths, $libs);
        $this->searchOverload();
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

    /**
     * Verifica Overloads para redeterminar a rota da view
     */
    private function searchOverload()
    {
        $setor = !empty($_SESSION['userlogin']) ? $_SESSION['userlogin']['setor'] : "0";

        /**
         * Verifica overload in public para a view
         */
        if ($this->lib !== DOMINIO) {
            if (($this->lib !== DOMINIO) && (file_exists(PATH_HOME . "public/overload/{$this->lib}/" . $this->directory . "/{$setor}/" . $this->file . ".php") || file_exists(PATH_HOME . "public/overload/{$this->lib}/" . $this->directory . "/{$setor}/" . $this->file . ".html"))) {
                $this->route = "public/overload/{$this->lib}/" . $this->directory . "/{$setor}/" . $this->file . ".php";
            } elseif (($this->lib !== DOMINIO) && (file_exists(PATH_HOME . "public/overload/{$this->lib}/" . $this->directory . "/" . $this->file . ".php") || file_exists(PATH_HOME . "public/overload/{$this->lib}/" . $this->directory . "/" . $this->file . ".html"))) {
                $this->route = "public/overload/{$this->lib}/" . $this->directory . "/" . $this->file . ".php";
            } else {

                /**
                 * Verifica overload in VENDOR se não encontrou overload in public
                 */
                foreach (Helper::listFolder(PATH_HOME . VENDOR) as $lib) {
                    if (file_exists(PATH_HOME . VENDOR . $lib . "/public/overload/" . $this->lib . "/" . $this->directory . "/{$setor}/" . $this->file . ".php") || file_exists(PATH_HOME . VENDOR . $lib . "/public/overload/" . $this->lib . "/" . $this->directory . "/{$setor}/" . $this->file . ".html")) {
                        $this->route = VENDOR . $lib . "/public/overload/" . $this->lib . "/" . $this->directory . "/" . $setor . "/" . $this->file . ".php";
                        break;
                    } elseif (file_exists(PATH_HOME . VENDOR . $lib . "/public/overload/" . $this->lib . "/" . $this->directory . "/" . $this->file . ".php") || file_exists(PATH_HOME . VENDOR . $lib . "/public/overload/" . $this->lib . "/" . $this->directory . "/" . $this->file . ".html")) {
                        $this->route = VENDOR . $lib . "/public/overload/" . $this->lib . "/" . $this->directory . "/" . $this->file . ".php";
                        break;
                    }
                }
            }
        }
    }

    /**
     * Busca por File
     * @param array $paths
     * @param array $listFolder
     */
    private function searchFile(array $paths, array $listFolder)
    {
        if (count($paths) === 1) {
            if (!$this->route = $this->findRoute($paths[0], $listFolder)){
                $this->directory = "view";
                $this->route = $this->findRoute("404", $listFolder);
            }
        } else {

            $path = implode('/', $paths);
            if (!$this->route = $this->findRoute($path, $listFolder)) {
                $this->variaveis[] = array_pop($paths);
                $this->searchFile($paths, $listFolder);
            }
        }
    }

    /**
     * Busca por rota
     *
     * @param string $path
     * @param array $listFolder
     * @return null|string
     */
    private function findRoute(string $path, array $listFolder)
    {
        foreach ($listFolder as $lib) {
            foreach ($lib as $this->lib => $item) {
                if (file_exists(PATH_HOME . "{$item}/{$path}.php")) {
                    $url = explode('/', $path);
                    $this->file = array_pop($url);
                    return "{$item}/{$path}.php";
                }
            }
        }

        return null;
    }

    /**
     * Obtém a lista de diretórios onde a rota pode estar
     * @return array
     */
    private function getRouteFolders()
    {
        $rotas = Config::getViewPermissoes();
        $libsPath[] = [DOMINIO => "public/{$this->directory}"];

        //verifica rotas com o setor
        if (!empty($_SESSION['userlogin'])) {
            $libsPath[][DOMINIO] = "public/{$this->directory}/{$_SESSION['userlogin']['setor']}";
            $libsPath = array_merge($libsPath, array_map(function ($class) {
                return [$class => VENDOR . $class . "/public/{$this->directory}/{$_SESSION['userlogin']['setor']}"];
            }, $rotas));
        }

        //rotas das libs
        $libsPath = array_merge($libsPath, array_map(function ($class) {
            return [$class => VENDOR . $class . "/public/{$this->directory}"];
        }, $rotas));

        return $libsPath;
    }
}