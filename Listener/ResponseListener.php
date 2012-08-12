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

            $url = $this->assetHelper->getUrl('bundles/knptranslator/js/jquery.min.js');
            $scripts = PHP_EOL . sprintf('<script type="text/javascript" src="%s"></script>', $url) . PHP_EOL;

            $url = $this->router->generate('knplabs_translator_put');
            $locales = $this->translator->getLocales();
            natsort($locales);
            $locales = json_encode(array_values($locales));
            $scripts .= sprintf('<script type="text/javascript">var TRANSLATOR_URL = "%s";var TRANSLATOR_LOCALES = %s;</script>', $url, $locales) . PHP_EOL;

            $url = $this->assetHelper->getUrl('bundles/knptranslator/js/translator.js');
            $scripts .= sprintf('<script type="text/javascript" src="%s"></script>', $url) . PHP_EOL;

            $html = $scripts . '<div id="translator-list"><div class="translator-list"><ul>' . PHP_EOL;
            foreach ($this->translator->getCurrentPageMessages()as $e) {
                $html .= '<li><a href="#" title="' . htmlspecialchars($e['trans']) . '" data-json="'
                        . htmlspecialchars(json_encode($e)) . '">'
                        . htmlspecialchars(substr($e['id'], 0, 80)) . '</a></li>' . PHP_EOL;
            }
            
            $img = $this->assetHelper->getUrl('bundles/knptranslator/img/cargando.gif');

            $html .= <<<HTML
</ul><hr/><h4 style="margin: 5px;">Editar Etiquetas</h4></div>
<div id="translator-form">
    <form onsubmit="return false;" method="post">
        <h3>Editar Etiqueta</h3>
        <hr/>
        <div class="translator-form">
            <div><label>Locale: </label><select name="locale"></select></div>
            <div><label>Dominio: </label><input name="domain" type="text" readonly="readonly" /></div>
            <div><label>Valor: </label><textarea name="value"></textarea></div>
        </div>
        <hr/>
        <div class="translator-buttons">
            <span id="translator-message"></span><img src="$img" />
            <input type="submit" value="Guardar"/>
        </div>
    <form>
</div></div>
HTML;

            $content = $substrFunction($content, 0, $pos) . $html . $substrFunction($content, $pos);
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
