<?php
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Knp\Bundle\TranslatorBundle\Listener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\templating\Helper\CoreAssetsHelper;
use Symfony\Component\Routing\RouterInterface;
use Knp\Bundle\TranslatorBundle\Translation\Translator;

/**
 * ResponseListener injects the translator js code.
 *
 * The handle method must be connected to the onCoreResponse event.
 *
 * The js is only injected on well-formed HTML (with a proper </body> tag).
 *
 */
class ResponseListener
{

    private $assetHelper;
    private $router;
    private $includeVendorAssets;

    /**
     *
     * @var Translator 
     */
    private $translator;

    public function __construct(CoreAssetsHelper $assetHelper, RouterInterface $router, Translator $translator, $includeVendorAssets = true)
    {
        $this->assetHelper = $assetHelper;
        $this->router = $router;
        $this->includeVendorAssets = $includeVendorAssets;
        $this->translator = $translator;
    }

    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        // do not capture redirects or modify XML HTTP Requests
        if ($request->isXmlHttpRequest()) {
            return;
        }

        $this->injectScripts($response);
        $this->injectCss($response);
    }

    /**
     * Injects the js scripts into the given Response.
     *
     * @param Response $response A Response instance
     */
    protected function injectScripts(Response $response)
    {
        if (function_exists('mb_stripos')) {
            $posrFunction = 'mb_strripos';
            $substrFunction = 'mb_substr';
        } else {
            $posrFunction = 'strripos';
            $substrFunction = 'substr';
        }

        $content = $response->getContent();

        if (false !== $pos = $posrFunction($content, '</body>')) {

            $url = $this->router->generate('knplabs_translator_put');

            $scripts = <<<HTML
<script type="text/javascript">var TranslatorURL = "$url";</script>
<script type="text/tpl" id="tpl-translator-form">
    <a href="#" class="translator-link" title="<%= value %>"><%= id %></a>
    <div class="translator-modal">
        <h3>Editar Etiqueta</h3>
        <span class="translator-close">Close</span>
        <hr/>
        <div class="translator-form">
            <div><label>ID :</label><input type="text" name="id" value="<%= id %>" readonly /></div>
            <div><label>Valor: </label><textarea name="value"><%= value %></textarea></div>
            <div><label>Parametros: </label><textarea name="parameters"><%= JSON.stringify(parameters) %></textarea></div>
            <div><label>Dominio: </label>
                <input name="domain" value="<%= domain %>" type="text" />
            </div>
            <div><label>Locale: </label>
            <select name="locale" class="translator-domain-select"></select>
            </div>
        </div>
        <hr/>
        <div class="translator-buttons">
            <input type="button" class="translator-save" value="Guardar"/>
            <input type="button" class="translator-close" value="Cerrar"/>
        </div>
    </div>
</script>
HTML
            ;

            $url = $this->assetHelper->getUrl('bundles/knptranslator/js/jquery.min.js');
            $scripts .= PHP_EOL . sprintf('<script type="text/javascript" src="%s"></script>', $url) . PHP_EOL;

            $url = $this->assetHelper->getUrl('bundles/knptranslator/js/underscore-min.js');
            $scripts .= sprintf('<script type="text/javascript" src="%s"></script>', $url) . PHP_EOL;

            $url = $this->assetHelper->getUrl('bundles/knptranslator/js/backbone-min.js');
            $scripts .= sprintf('<script type="text/javascript" src="%s"></script>', $url) . PHP_EOL;

            $url = $this->assetHelper->getUrl('bundles/knptranslator/js/translator.js');
            $scripts .= sprintf('<script type="text/javascript" src="%s"></script>', $url) . PHP_EOL;

            $models = json_encode(array_values($this->translator->getCurrentPageMessages()));
            
            $locales = $this->translator->getLocales();
            natsort($locales);
            $locales = json_encode(array_values($locales));
            

            $script = <<<HTML
<div id="translator-modal-background"></div>
<div id="translator-list"></div>
<script type="text/javascript">
    var Messages = new MessagesCollection($models);
    var TranslatorLanguages = $locales;
    Messages.each(function(message){
        $("#translator-list").append(new MessageView({ model: message }).render());
    });
    $("#translator-list").append("<hr/><h4 style='margin: 5px;'>Editar Etiquetas</h4>");
</script>
HTML
            ;


            $scripts .= $script . PHP_EOL;

            $content = $substrFunction($content, 0, $pos) . $scripts . $substrFunction($content, $pos);
            $replacement = '<span class="translator-label translator-label-$1">$2<span class="translator-edit">Editar</span></span>';
            //$content = preg_replace('/\[T-(.+?)\](.+?)\[\/T\]/mi', $replacement, $content);
            $response->setContent($content);
        }
    }

    /**
     * Injects the css links into the given Response.
     *
     * @param Response $response A Response instance
     */
    protected function injectCss(Response $response)
    {
        if (function_exists('mb_stripos')) {
            $posrFunction = 'mb_strripos';
            $substrFunction = 'mb_substr';
        } else {
            $posrFunction = 'strripos';
            $substrFunction = 'substr';
        }

        $content = $response->getContent();

        if (false !== $pos = $posrFunction($content, '</head>')) {

            $url = $this->assetHelper->getUrl('bundles/knptranslator/css/translator.css');
            $links = sprintf('<link rel="stylesheet" href="%s" />', $url);

            $content = $substrFunction($content, 0, $pos) . $links . $substrFunction($content, $pos);
            $response->setContent($content);
        }
    }

}
