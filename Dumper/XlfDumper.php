<?php

namespace Knp\Bundle\TranslatorBundle\Dumper;

use Symfony\Component\Config\Resource\FileResource;
use Knp\Bundle\TranslatorBundle\Dumper\XliffDumper;

class XlfDumper extends XliffDumper
{

    public function supports(FileResource $resource)
    {
        return 'xlf' === pathinfo($resource->getResource(), PATHINFO_EXTENSION);
    }

}