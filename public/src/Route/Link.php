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

    private function createHeadMinify()
    {
        /**
         * tag head replace variables declaration
         */
        if(!empty($this->param['head'])) {
            foreach ($this->param['head'] as $i => $head) {

                /**
                 * if is a css file to put on head, so Minify the content
                 */
                if(preg_match("/^<link /i", $head)) {

                    //get the url and name of file
                    $href = trim(explode( "'", explode("href='", str_replace('"', "'", $head))[1])[0]);
                    $hrefFinal = Config::replaceVariablesConfig($href);
                    $name = pathinfo($href, PATHINFO_BASENAME);
                    if(DEV || !file_exists(PATH_HOME . "assetsPublic/{$name}") && file_exists($hrefFinal)) {
                        /**
                         * Minify the content, replace variables declaration and cache the file
                         */
                        $minify = new \MatthiasMullie\Minify\CSS(file_get_contents($hrefFinal));
                        $f = fopen(PATH_HOME . "assetsPublic/{$name}", "w");
                        fwrite($f, Config::replaceVariablesConfig($minify->minify()));
                        fclose($f);
                    }

                    /**
                     * Update head value with the cached minify css
                     */
                    $this->param['head'][$i] = str_replace("<link ", "<link id='{$i}' ", str_replace($href, HOME . "assetsPublic/{$name}", $head));

                    /**
                     * if is a JS file to put on head, so Minify the content
                     */
                } elseif(preg_match("/^<script /i", $head)) {

                    //get the url and name of file
                    $href = trim(explode( "'", explode("src='", str_replace('"', "'", $head))[1])[0]);
                    $hrefFinal = Config::replaceVariablesConfig($href);
                    $name = pathinfo($href, PATHINFO_BASENAME);
                    if(DEV || !file_exists(PATH_HOME . "assetsPublic/{$name}") && file_exists($hrefFinal)) {
                        /**
                         * Minify the content, replace variables declaration and cache the file
                         */
                        $minify = new \MatthiasMullie\Minify\JS(file_get_contents($hrefFinal));
                        $minify->minify(PATH_HOME . "assetsPublic/{$name}");
                    }

                    /**
                     * Update head value with the cached minify css
                     */
                    $this->param['head'][$i] = str_replace("<script ", "<script id='{$i}' ", str_replace($href, HOME . "assetsPublic/{$name}", $head));
                } elseif(preg_match("/^<meta /i", $head)) {

                    /**
                     * Default just replace variables of the string declaration
                     */
                    $this->param['head'][$i] = str_replace("<meta ", "<meta id='{$i}' ", Config::replaceVariablesConfig($head));
                }
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
                Config::createPageJs($this->getFile(), $this->getLib(), $setor);

                /**
                 * If CSS view not exist on minify cache folder, then create
                 */
            } elseif(!file_exists(PATH_HOME . "assetsPublic/view/{$setor}/" . parent::getFile() . ".min.css")) {
                Config::createPageCss($this->getFile(), $this->getLib(), $setor);
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