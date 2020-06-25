<?php

/**
 * This class get view content and assets
 * update the assets if need
 *
 * @copyright (c) 2020, Edinei J. Bauer
 */

namespace Route;

use Config\Config;

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
        $this->addJsTemplates();
        $this->createHeadMinify();
        $this->formatParam($setor);
    }

    /**
     * Format the param response data
     * @param string $setor
     */
    private function formatParam(string $setor)
    {
        $this->param['title'] = $this->formatTitle(!empty($this->param['title']) ? $this->param['title'] : $this->getFile());
        $this->param['css'] = file_exists(PATH_HOME . "assetsPublic/view/{$setor}/" . parent::getFile() . ".min.css") ? file_get_contents(PATH_HOME . "assetsPublic/view/" . $setor . "/" . parent::getFile() . ".min.css") : "";
        $this->param['variaveis'] = parent::getVariaveis();

        if(file_exists(PATH_HOME . "assetsPublic/view/{$setor}/"  . parent::getFile() . ".min.js"))
            $this->param['js'][] =  HOME . "assetsPublic/view/{$setor}/"  . parent::getFile() . ".min.js?v=" . VERSION;
    }

    /**
     * @param string $link
     * @param array $rotas
     */
    private function createLink(string $link, array $rotas)
    {
        if(!empty($link)) {
            $link .= (!preg_match("/\.css$/i", $link) ? ".css" : "");
            $id = \Helpers\Check::name($link);
            $linkName = substr($id,0,-4) . ".css";
            foreach ($rotas as $file => $dir) {
                if ($file === $link) {

                    //get the url and name of file
                    if (DEV || !file_exists(PATH_HOME . "assetsPublic/{$linkName}")) {
                        /**
                         * Minify the content, replace variables declaration and cache the file
                         */
                        $minify = new \MatthiasMullie\Minify\CSS(preg_match("/\/assets\/core\//i", $dir) ? Config::replaceVariablesConfig(file_get_contents($dir)) : Config::setPrefixToCssDefinition(Config::replaceVariablesConfig(file_get_contents($dir)), ".r-network"));
                        $f = fopen(PATH_HOME . "assetsPublic/{$linkName}", "w");
                        fwrite($f, $minify->minify());
                        fclose($f);
                    }

                    /**
                     * Update head value with the cached minify css
                     */
                    $this->param['head'][$id] = "<link id='" . $id . "' href='" . HOME . "assetsPublic/{$linkName}?v=" . VERSION . "' class='coreLinkHeader' rel='stylesheet' type='text/css' media='all' />";
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
        if (!empty($script)) {
            $script .= (!preg_match("/\.js$/i", $script) ? ".js" : "");
            $linkName = substr(\Helpers\Check::name($script),0,-3) . ".js";

            foreach ($rotas as $file => $dir) {
                if ($file === $script) {

                    //get the url and name of file
                    if (DEV || !file_exists(PATH_HOME . "assetsPublic/{$linkName}")) {
                        /**
                         * Minify the content, replace variables declaration and cache the file
                         */
                        $minify = new \MatthiasMullie\Minify\JS(file_get_contents($dir));
                        $minify->minify(PATH_HOME . "assetsPublic/{$linkName}");
                    }

                    /**
                     * Update head value with the cached minify css
                     */
                    $this->param['js'][] = HOME . "assetsPublic/{$linkName}?v=" . VERSION;
                    break;
                }
            }
        }
    }

    private function createHeadMinify()
    {
        /**
         * tag head link replace variables declaration
         */
        $rotasCss = Config::getRoutesFilesTo("assets", "css");
        if(!empty($this->param['css'])) {
            if(is_array($this->param['css'])) {
                foreach ($this->param['css'] as $link) {
                    if(is_string($link))
                        $this->createLink($link, $rotasCss);
                }

            } elseif(is_string($this->param['css'])) {
                $this->createLink($this->param['css'], $rotasCss);
            }
        }

        /**
         * if is a JS file to put on head, so Minify the content
         */
        $rotasJs = Config::getRoutesFilesTo("assets", "js");
        if(!empty($this->param['js'])) {
            $js = $this->param['js'];
            $this->param['js'] = [];
            if(is_array($js)) {
                foreach ($js as $i => $script) {
                    if(is_string($script))
                        $this->createScript($script, $rotasJs);
                }
            } elseif(is_string($js)) {
                $this->createScript($js, $rotasJs);
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

            } else {

                /**
                 * If JS view not exist on minify cache folder, then create
                 */
                if(!file_exists(PATH_HOME . "assetsPublic/view/{$setor}/" . parent::getFile() . ".min.js"))
                    Config::createPageJs($this->getFile(), $this->getJs(), $setor);

                    /**
                     * If CSS view not exist on minify cache folder, then create
                     */
                if(!file_exists(PATH_HOME . "assetsPublic/view/{$setor}/" . parent::getFile() . ".min.css"))
                    Config::createPageCss($this->getFile(), $this->getCss(), $setor);
            }
        }
    }

    /**
     * Add templates declared on scripts files included to this view
     */
    private function addJsTemplates()
    {
        if (!empty($this->param['js'])) {
            $listJson = [];
            foreach ($this->param['js'] as $viewJ)
                $listJson[] = str_replace('.js', '', $viewJ) . ".json";

            foreach ($listJson as $item) {

                foreach (Config::getRoutesFilesTo("assets", "json") as $file => $dir) {
                    if($file === $item) {
                        $tpl = json_decode(file_get_contents($dir), !0);
                        if(!empty($tpl['templates']))
                            $this->param['templates'] = array_merge($this->param['templates'], $tpl['templates']);
                    }
                }
            }
        }

        $tpl = [];
        if(is_array($this->param['templates'])) {
            foreach ($this->param['templates'] as $template) {
                if(is_string($template))
                    $tpl[$template] = Config::getTemplateContent($template);
            }
        } elseif(is_string($this->param['templates'])) {
            $tpl[$this->param['templates']] = Config::getTemplateContent($this->param['templates']);
        }

        if(!empty($this->getTemplates())) {
            foreach ($this->getTemplates() as $template => $dir)
                $tpl[$template] = file_get_contents($dir);
        }

        $this->param['templates'] = $tpl;
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