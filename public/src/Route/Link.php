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
     * Link constructor.
     * @param string|null $url
     * @param string|null $dir
     * @param bool $useOldCssSyntax
     */
    function __construct(string $url = null, string $dir = null, bool $useOldCssSyntax = false)
    {
        $setor = Config::getSetor();
        $this->directory = $dir ?? "view";
        parent::__construct($url, $this->directory, $setor);

        $this->addJsTemplates();
        $this->viewAssetsUpdate($setor);
        $this->createHeadMinify($useOldCssSyntax, $setor);
        $this->formatParam($setor, $useOldCssSyntax);
    }

    /**
     * Format the param response data
     *
     * @param string $setor
     * @param bool $useOldCssSyntax
     */
    private function formatParam(string $setor, bool $useOldCssSyntax = false)
    {
        $nameCssFile = parent::getFile() . ($useOldCssSyntax ? ".min.old.css" : ".min.css");
        $this->param['title'] = $this->formatTitle(!empty($this->param['title']) ? $this->param['title'] : $this->getFile());
        $this->param['css'] = file_exists(PATH_HOME . "assetsPublic/view/{$setor}/" . $nameCssFile) ? file_get_contents(PATH_HOME . "assetsPublic/view/" . $setor . "/" . $nameCssFile) : "";
        $this->param['variaveis'] = parent::getVariaveis();

        if(file_exists(PATH_HOME . "assetsPublic/view/{$setor}/"  . parent::getFile() . ".min.js"))
            $this->param['js'][] =  HOME . "assetsPublic/view/{$setor}/"  . parent::getFile() . ".min.js?v=" . VERSION;
    }

    /**
     * @param string $link
     * @param array $rotas
     * @param bool $useOldCssSyntax
     */
    private function createLink(string $link, array $rotas, bool $useOldCssSyntax = false)
    {
        if(!empty($link)) {
            $link .= (!preg_match("/\.css$/i", $link) ? ".css" : "");
            $id = \Helpers\Check::name($link);
            $linkName = substr($id,0,-4) . ".css";
            foreach ($rotas as $file => $dir) {
                if ($file === $link) {
                    $linkNameOld = str_replace(".css", ".old.css", $linkName);

                    //get the url and name of file
                    if (DEV || !file_exists(PATH_HOME . "assetsPublic/{$linkName}")) {
                        /**
                         * Minify the content, replace variables declaration and cache the file
                         */
                        $minify = new \MatthiasMullie\Minify\CSS(Config::setPrefixToCssDefinition(Config::replaceVariablesConfig(file_get_contents($dir)), ".{$id}"));
                        $f = fopen(PATH_HOME . "assetsPublic/{$linkName}", "w");
                        fwrite($f, $minify->minify());
                        fclose($f);

                        /**
                         * Old version without :not support
                         */
                        if(file_exists(PATH_HOME . "assetsPublic/appCore.min.css") && $this->checkCommand('postcss')) {

                            $cssAdd = "";
                            $rootCss = file_get_contents(PATH_HOME . "assetsPublic/appCore.min.css");
                            foreach ([":root {", ":root{"] as $rootDeclaration) {
                                foreach (explode($rootDeclaration, $rootCss) as $i => $item) {
                                    if($i === 0)
                                        continue;

                                    $rootCssStyle = explode("}", $item)[0];
                                    $cssAdd .= ":root {" . $rootCssStyle . "}";
                                }
                            }

                            $minifyo = new \MatthiasMullie\Minify\CSS($cssAdd);
                            $minifyo->add(Config::setPrefixToCssDefinition(Config::replaceVariablesConfig(file_get_contents($dir)), ".{$id}", true));
                            $fo = fopen(PATH_HOME . "assetsPublic/{$linkNameOld}", "w");
                            fwrite($fo, $minifyo->minify());
                            fclose($fo);

                            exec("postcss " . PATH_HOME . "assetsPublic/{$linkNameOld}" . " -o " . PATH_HOME . "assetsPublic/{$linkNameOld}");

                        } else {
                            $minifyo = new \MatthiasMullie\Minify\CSS(Config::setPrefixToCssDefinition(Config::replaceVariablesConfig(file_get_contents($dir)), ".{$id}", true));
                            $fo = fopen(PATH_HOME . "assetsPublic/{$linkNameOld}", "w");
                            fwrite($fo, $minifyo->minify());
                            fclose($fo);
                        }
                    }

                    /**
                     * Update head value with the cached minify css
                     */
                    $this->param['head'][$id] = "<link id='" . $id . "' href='" . HOME . "assetsPublic/" . ($useOldCssSyntax ? $linkNameOld : $linkName) . "?v=" . VERSION . "' class='coreLinkHeader' rel='stylesheet' type='text/css' media='all' />";
                    break;
                }
            }
        }
    }

    /**
     * @param string $command
     * @return bool
     */
    private function checkCommand(string $command): bool {
        $checkCommand = (stripos(PHP_OS, 'WIN') === 0) ? "where $command" : "which $command";
        exec($checkCommand, $output, $return_var);
        return $return_var === 0;
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

    /**
     * @param bool $useOldCssSyntax
     * @param string $setor
     * @return void
     */
    private function createHeadMinify(bool $useOldCssSyntax, string $setor)
    {
        /**
         * tag head link replace variables declaration
         */
        $rotasCss = Config::getRoutesFilesTo("assets", "css");
        if(!empty($this->param['css'])) {
            if(is_array($this->param['css'])) {
                foreach ($this->param['css'] as $link) {
                    if(is_string($link))
                        $this->createLink($link, $rotasCss, $useOldCssSyntax);
                }

            } elseif(is_string($this->param['css'])) {
                $this->createLink($this->param['css'], $rotasCss, $useOldCssSyntax);
            }
        }

        /**
         * JS definidos no param file do view, não são processados e são colocados no Global
         */
        $rotasJs = Config::getRoutesFilesTo("assets", "js");
        if(!empty($this->param['js'])) {
            $js = $this->param['js'];

            //zera param js pois irá adicionar novamente esse script na variável com a url atualizada
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

        $jsPre = [];
        if(!empty($this->getJsPre()))
            $jsPre[] = HOME . "assetsPublic/view/{$setor}/" . parent::getFile() . ".pre.min.js?v=" . VERSION;

        if(!empty($this->param['jsPre'])) {
            if(is_array($this->param['jsPre'])) {
                foreach ($this->param['jsPre'] as $i => $script) {
                    if(is_string($script) && !empty($script)) {
                        $script .= (!preg_match("/\.js$/i", $script) ? ".js" : "");
                        $fileName = "assetsPublic/" . substr(\Helpers\Check::name(str_replace([HOME, PATH_HOME], "", $script)),0,-3) . ".js";
                        $cc = Config::getScriptContent($script);
                        if(!empty($cc)) {
                            Config::createFile(PATH_HOME . $fileName, $cc);
                            $jsPre[] = HOME . $fileName . "?v=" . VERSION;
                        }
                    }
                }
            } elseif(is_string($this->param['jsPre'])&& !empty($this->param['jsPre'])) {
                $script .= (!preg_match("/\.js$/i", $this->param['jsPre']) ? ".js" : "");
                $fileName = "assetsPublic/" . substr(\Helpers\Check::name(str_replace([HOME, PATH_HOME], "", $script)),0,-3) . ".js";
                $cc = Config::getScriptContent($this->param['jsPre']);
                if(!empty($cc)) {
                    Config::createFile(PATH_HOME . $fileName, $cc);
                    $jsPre[] = HOME . $fileName . "?v=" . VERSION;
                }
            }
        }

        $this->param['jsPre'] = $jsPre;

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
     *
     * @param string $setor
     */
    private function viewAssetsUpdate(string $setor)
    {
        if($this->directory === "view") {
            /**
             * If JS view not exist on minify cache folder, then create
             */
            if(DEV || !file_exists(PATH_HOME . "assetsPublic/view/{$setor}/" . $this->getFile() . ".min.js"))
                Config::createPageJs($this->getFile(), $this->getJs(), $setor);

            /**
             * If CSS view not exist on minify cache folder, then create
             */
            if(DEV || !file_exists(PATH_HOME . "assetsPublic/view/{$setor}/" . $this->getFile() . ".min.css"))
                Config::createPageCss($this->getFile(), $this->getCss(), $setor);

            /**
             * JS PRE LOAD
             */
            if(!empty($this->getJsPre()) && (DEV || !file_exists(PATH_HOME . "assetsPublic/view/{$setor}/" . $this->getFile() . ".pre.min.js")))
                Config::createPageJs($this->getFile() . ".pre", $this->getJsPre(), $setor);
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

            if(is_string($this->param['js']))
                $this->param['js'] = [$this->param['js']];
            elseif(empty($this->param['js']))
                $this->param['js'] = [];

            if(is_string($this->param['css']))
                $this->param['css'] = [$this->param['css']];
            elseif(empty($this->param['css']))
                $this->param['css'] = [];

            foreach ($listJson as $item) {

                foreach (Config::getRoutesFilesTo("assets", "json") as $file => $dir) {
                    if($file === $item) {
                        $tpl = json_decode(file_get_contents($dir), !0);

                        if(!empty($tpl['templates']))
                            $this->param['templates'] = array_merge($this->param['templates'], (is_string($tpl['templates']) ? [$tpl['templates']] : $tpl['templates']));

                        if(!empty($tpl['template']))
                            $this->param['templates'] = array_merge($this->param['templates'], (is_string($tpl['template']) ? [$tpl['template']] : $tpl['template']));

                        if(!empty($tpl['js']))
                            $this->param['js'] = array_merge($this->param['js'], (is_string($tpl['js']) ? [$tpl['js']]: $tpl['js']));

                        if(!empty($tpl['css']))
                            $this->param['css'] = array_merge($this->param['css'], (is_string($tpl['css']) ? [$tpl['css']] : $tpl['css']));
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

        if(!empty($this->param['js']))
            sort($this->param['js']);

        if(!empty($this->param['css']))
            sort($this->param['css']);
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
            ["{{sitesub}}", '{$sitesub}', "{{sitedesc}}", '{$sitedesc}', "-", "_"],
            [SITESUB, SITESUB, SITEDESC, SITEDESC, " ", " "],
            $title
        ));
    }
}