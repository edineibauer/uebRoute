<?php

/**
 * This class get view content and assets
 * update the assets if need
 *
 * @copyright (c) 2020, Edinei J. Bauer
 */

namespace Route;

use Config\Config;
use Config\UpdateSystem;

class Link extends Route
{
    private $directory;

    /**
     * @param string|null $url
     * @param string|null $dir
     */
    function __construct(string $url = null, string $dir = null)
    {
        $this->directory = $dir ?? "view";
        parent::__construct($url, $this->directory);

        $setor = Config::getSetor();
        $this->viewAssetsUpdate($setor);
        $this->formatParam($setor);
    }

    /**
     * Format the param response data
     * @param string $setor
     */
    private function formatParam(string $setor)
    {
        $this->param['title'] = $this->formatTitle($this->param['title'] ?? parent::getFile());
        $this->param['css'] = file_exists(PATH_HOME . "assetsPublic/view/{$setor}/" . parent::getFile() . ".min.css") ? file_get_contents(PATH_HOME . "assetsPublic/view/" . $setor . "/" . parent::getFile() . ".min.css") : "";
        $this->param['js'] = file_exists(PATH_HOME . "assetsPublic/view/{$setor}/"  . parent::getFile() . ".min.js") ? HOME . "assetsPublic/view/{$setor}/"  . parent::getFile() . ".min.js?v=" . VERSION : "";
        $this->param['variaveis'] = parent::getVariaveis();

        $this->createHeadMinify();
    }

    /**
     * @param string $link
     */
    private function createLink(string $link, array $rotas)
    {
        if(!empty($link)) {
            $fileLink = pathinfo($link, PATHINFO_BASENAME) . '.css';
            foreach ($rotas as $file => $dir) {
                if ($file === $fileLink) {

                    //get the url and name of file
                    if (DEV || !file_exists(PATH_HOME . "assetsPublic/{$file}")) {
                        /**
                         * Minify the content, replace variables declaration and cache the file
                         */
                        $minify = new \MatthiasMullie\Minify\CSS(preg_match("/\/assets\/core\//i", $dir) ? file_get_contents($dir) : Config::setPrefixToCssDefinition(file_get_contents($dir), "#core-content"));
                        $f = fopen(PATH_HOME . "assetsPublic/{$file}", "w");
                        fwrite($f, Config::replaceVariablesConfig($minify->minify()));
                        fclose($f);
                    }

                    /**
                     * Update head value with the cached minify css
                     */
                    $id = \Helpers\Check::name($file);
                    $this->param['head'][$id] = "<link id='" . $id . "' href='" . HOME . "assetsPublic/{$file}?v=" . VERSION . "' rel='stylesheet' type='text/css' media='all' />";
                    break;
                }
            }
        }
    }

    /**
     * @param string $script
     * @param array $rotas
     */
    private function createScript(string $script, array $rotas)
    {
        $fileLink = pathinfo($script, PATHINFO_BASENAME) . '.js';
        foreach ($rotas as $file => $dir) {
            if($file === $fileLink) {

                //get the url and name of file
                if(DEV || !file_exists(PATH_HOME . "assetsPublic/{$file}")) {
                    /**
                     * Minify the content, replace variables declaration and cache the file
                     */
                    $minify = new \MatthiasMullie\Minify\JS(file_get_contents($dir));
                    $minify->minify(PATH_HOME . "assetsPublic/{$file}");
                }

                /**
                 * Update head value with the cached minify css
                 */
                $id = \Helpers\Check::name($file);
                $this->param['head'][$id] = "<script id='" . $id . "' src='" . HOME . "assetsPublic/{$file}?v=" . VERSION . "'></script>";
                break;
            }
        }
    }

    private function createHeadMinify()
    {
        /**
         * tag head link replace variables declaration
         */
        $rotasCss = Config::getRoutesFilesTo("assets", "css");
        if(!empty($this->param['link'])) {
            if(is_array($this->param['link'])) {
                foreach ($this->param['link'] as $link) {
                    if(is_string($link))
                        $this->createLink($link, $rotasCss);
                }

            } elseif(is_string($this->param['link'])) {
                $this->createLink($this->param['link'], $rotasCss);
            }
        }

        /**
         * if is a JS file to put on head, so Minify the content
         */
        $rotasJs = Config::getRoutesFilesTo("assets", "js");
        if(!empty($this->param['script'])) {
            if(is_array($this->param['script'])) {
                foreach ($this->param['script'] as $i => $script) {
                    if(is_string($script))
                        $this->createScript($script, $rotasJs);
                }
            } elseif(is_string($this->param['script'])) {
                $this->createScript($this->param['script'], $rotasJs);
            }
        }

        /**
         * tag head replace variables declaration
         */
        if(!empty($this->param['meta'])) {
            if(is_array($this->param['meta'])) {
                foreach ($this->param['meta'] as $i => $meta){
                    if(is_string($meta))
                        $this->param['head'][] = Config::replaceVariablesConfig($meta);
                }
            } elseif(is_string($this->param['meta'])) {
                $this->param['head'][] = Config::replaceVariablesConfig($this->param['meta']);
            }
        }
    }

    /**
     * Check if the view Assests need to be updated
     * @param string $setor
     */
    private function viewAssetsUpdate(string $setor)
    {
        if($this->directory === "view") {
            /**
             * If in DEV mode, so update JS and CSS from view
             */
            if (DEV) {
                Config::createPageJs($this->getFile(), $this->getJs(), $setor);
                Config::createPageCss($this->getFile(), $this->getCss(), $setor);

                /**
                 * If JS view not exist on minify cache folder, then create
                 */
            } elseif(!file_exists(PATH_HOME . "assetsPublic/view/{$setor}/" . parent::getFile() . ".min.js")) {
                Config::createPageJs($this->getFile(), $this->getJs(), $setor);

                /**
                 * If CSS view not exist on minify cache folder, then create
                 */
            } elseif(!file_exists(PATH_HOME . "assetsPublic/view/{$setor}/" . parent::getFile() . ".min.css")) {
                Config::createPageCss($this->getFile(), $this->getCss(), $setor);
            }
        }
    }

    /**
     * return page title formated
     *
     * @param string $title
     * @return string
     */
    private function formatTitle(string $title): string
    {
        return ucwords(str_replace(
            ["{{sitename}}", '{$sitename}', "{{sitesub}}", '{$sitesub}', "{{sitedesc}}", '{$sitedesc}', "-", "_"],
            [SITENAME, SITENAME, SITESUB, SITESUB, SITEDESC, SITEDESC, " ", " "],
            $title
        ));
    }
}