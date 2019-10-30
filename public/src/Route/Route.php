<?php

/**
 * Busca por arquivo a ser carregado em um request ao sistema Singular
 *
 * @copyright (c) 2018, Edinei J. Bauer
 */

namespace Route;

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

        $this->searchRoute($url);
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
     * @param string $url
     */
    private function searchRoute(string $url)
    {
        $paths = empty($url) || $url === "/" ? ["index"] : array_filter(explode('/', $url));
        $this->searchFile($paths, $this->getRouteFolders());
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
                $this->route = $this->findRoute("404", $this->getRouteFolders());
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
        $rotas = \Config\Config::getViewPermissoes();
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