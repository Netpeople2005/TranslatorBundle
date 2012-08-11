<?php
/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Knp\Bundle\TranslatorBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Knp\Bundle\TranslatorBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\Request;
use Knp\Bundle\TranslatorBundle\Exception\InvalidTranslationKeyException;

class TranslatorController
{

    private $translator;
    private $request;
    private $logger;

    public function __construct(Request $request, Translator $translator, $logger)
    {
        $this->request = $request;
        $this->translator = $translator;
        $this->logger = $logger;
    }

    public function getAction()
    {
        $id = urldecode($this->request->get('id'));
        $domain = urldecode($this->request->get('domain'));
        $locale = urldecode($this->request->get('locale'));
        $parameters = $this->request->get('parameters');
        
        $this->translator->trans($id, $parameters, $domain, $locale);
        
        $data = $this->translator->getCurrentPageMessages(md5($id));

        return new Response(json_encode($data),200, array('Content-Type' => 'application/json'));
    }

    public function putAction()
    {
        $error = NULL;
        $data = json_decode($this->request->getContent(), TRUE);
        extract($data, EXTR_OVERWRITE);
        try {
            $success = $this->translator->update($id, $value, $domain, $locale);
            $trans = $value;
        } catch (InvalidTranslationKeyException $e) {
            $success = false;
            $trans = $this->translator->trans($id, array(), $domain, $locale);
            $error = $e->getMessage();
        }

        return new Response(json_encode($data), $error ? 500 : 200, array('Content-Type' => 'application/json'));
    }

}
