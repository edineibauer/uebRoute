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
    private $param;
    private $directory;

    /**
     * @param string|null $url
     * @param string|null $dir
     */
    function __construct(string $url = null, string $dir = null)
    {
        $this->directory = $dir ?? "view";
        parent::__construct($url, $this->directory);

        $this->param = Config::getViewParam(parent::getFile(), parent::getLib());

        $setor = Config::getSetor();
        $this->coreAssetsUpdate($setor);
        $this->viewAssetsUpdate($setor);
        $this->formatParam($setor);
    }

    /**
     * @return mixed
     */
    public function getParam()
    {
        return $this->param;
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
    }

    /**
     * Check if the view Core need to be updated
     * @param string $setor
     */
    private function coreAssetsUpdate(string $setor)
    {
        /**
         * If the appCore assets not exist, so create
         */
        if (!file_exists(PATH_HOME . "assetsPublic/appCore.min.js") || !file_exists(PATH_HOME . "assetsPublic/appCore.min.css"))
            new UpdateSystem();

        /**
         * If the core setor assets not exist or if in DEV mode, so update
         */
        if (DEV || !file_exists(PATH_HOME . "assetsPublic/core/" . $setor . "/core.min.js") || !file_exists(PATH_HOME . "assetsPublic/core/" . $setor . "/core.min.css"))
            Config::createCore();
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
                Config::createPageJs($this->getFile(), $this->getLib(), $setor);
                Config::createPageCss($this->getFile(), $this->getLib(), $setor);

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