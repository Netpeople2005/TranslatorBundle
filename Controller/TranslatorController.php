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

use Symfony\Component\HttpFoundation\JsonResponse;
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
        $parameters = (array) $this->request->get('parameters');

        $this->translator->trans($id, $parameters, $domain, $locale);

        $data = $this->translator->getCurrentPageMessages(md5($id));

        return new JsonResponse($data);
    }

    public function postAction()
    {
        $error = NULL;
        $id = $this->request->get('id');
        $value = $this->request->get('value');
        $domain = $this->request->get('domain');
        $locale = $this->request->get('locale');
        $parameters = (array) $this->request->get('parameters');
        $defaultLanguages = (array) $this->request->get('defaultLanguages');
        try {
            $success = $this->translator->update($id, $value, $domain, $locale);
            $trans = $value;

            foreach ($defaultLanguages as $locale => $value) {
                $success = $this->translator->update($id, $value, $domain, $locale);
            }
        } catch (InvalidTranslationKeyException $e) {
            $success = false;
            $trans = $this->translator->trans($id, $parameters, $domain, $locale);
            $error = $e->getMessage();
        }

        return new JsonResponse(compact('id', 'value', 'domain'));
    }

}
