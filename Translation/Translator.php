<?php
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Knp\Bundle\TranslatorBundle\Translation;

use Symfony\Bundle\FrameworkBundle\Translation\Translator as BaseTranslator;
use Symfony\Component\Config\Resource\ResourceInterface;
use Knp\Bundle\TranslatorBundle\Dumper\DumperInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Knp\Bundle\TranslatorBundle\Exception\InvalidTranslationKeyException;

/**
 * Translator that adds write capabilites on translation files
 *
 * @author Florian Klein <florian.klein@free.fr>
 *
 */
class Translator extends BaseTranslator
{

    private $dumpers = array();
    private $locales = array();
    private $fallbackLocale;
    private $currentPageMessages = array();

    /**
     * {@inheritdoc}
     *
     * @api
     */
    public function trans($id, array $parameters = array(), $domain = 'messages', $locale = null)
    {
        $trans = parent::trans($id, $parameters, $domain, $locale);
        $index = md5($id);

        if (!$locale) {
            $locale = $this->getLocale();
        }

        $value = $this->getCatalog($locale)->get($id, $domain);
        
        $this->currentPageMessages[$index] = compact('id', 'domain', 'locale', 'index', 'value', 'parameters', 'trans');        return $trans;
        
        return $trans;
    }

    public function all()
    {
        $translations = array();
        foreach ($this->getLocales() as $locale) {
            $translations[$locale] = $this->getCatalog($locale)->all();
        }

        return $translations;
    }

    public function getCurrentPageMessages($index = NULL)
    {
        if ($index) {
            return array_key_exists($index, $this->currentPageMessages) ? $this->currentPageMessages[$index] : NULL;
        }
        return $this->currentPageMessages;
    }

    public function isTranslated($id, $domain, $locale)
    {
        return $id === $this->getCatalog($locale)->get((string) $id, $domain);
    }

    /**
     * Adds a dumper to the ones used to dump a resource
     */
    public function addDumper(DumperInterface $dumper)
    {
        $this->dumpers[] = $dumper;
    }

    public function addLocale($locale)
    {
        $this->locales[$locale] = $locale;
    }

    public function getLocales()
    {
        return $this->locales;
    }

    /**
     *
     * @return DumperInterface
     */
    private function getDumper(ResourceInterface $resource)
    {
        foreach ($this->dumpers as $dumper) {
            if ($dumper->supports($resource)) {
                return $dumper;
            }
        }

        return null;
    }

    /**
     *
     * Gets a catalog for a given locale
     *
     * @return MessageCatalogue
     */
    public function getCatalog($locale)
    {
        $this->loadCatalogue($locale);

        if (isset($this->catalogues[$locale])) {

            return $this->catalogues[$locale];
        }

        throw new \InvalidArgumentException(
                sprintf('The locale "%s" does not exist in Translations catalogues', $locale)
        );
    }

    /**
     * {@inheritdoc}
     *
     * Forced to override because of private visibility
     */
    public function setFallbackLocale($locale)
    {
        // needed as the fallback locale is used to fill-in non-yet translated messages
        $this->catalogues = array();

        $this->fallbackLocale = $locale;
    }

    public function getFallbackLocale()
    {
        return $this->fallbackLocale;
    }

    /**
     * Updates the value of a given trans id for a specified domain and locale
     *
     * @param string $id the trans id
     * @param string $value the translated value
     * @param string domain the domain name
     * @param string $locale
     *
     * @return boolean true if success
     */
    public function update($id, $value, $domain, $locale)
    {
        $success = FALSE;

        if (empty($id)) {
            throw new InvalidTranslationKeyException('Empty key not allowed');
        }
        $resources = $this->getResources($locale, $domain);
        foreach ($resources as $resource) {
            if ($dumper = $this->getDumper($resource)) {
                $success = $dumper->update($resource, $id, $value);
            }
        }

        $this->loadCatalogue($locale);

        return $success;
    }

    /**
     * Gets the resources that matches a domain and a locale on a particular catalog
     *
     * @param MessageCatalogue $catalog the catalog
     * @param string $domain the domain name (default is 'messages')
     * @param string $locae the locale, to filter fallbackLocale
     * @return array of FileResource objects
     */
    private function getMatchedResources(MessageCatalogue $catalog, $domain, $locale)
    {
        $matched = array();
        foreach ($catalog->getResources() as $resource) {

            // @see Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
            // filename is domain.locale.format
            $basename = \basename($resource->getResource());
            list($resourceDomain, $resourceLocale, $format) = explode('.', $basename);

            if ($domain === $resourceDomain && $locale === $resourceLocale) {
                $matched[] = $resource;
            }
        }

        return $matched;
    }

    public function getResources($locale, $domain)
    {
        $catalog = $this->getCatalog($locale);
        $resources = $this->getMatchedResources($catalog, $domain, $locale);

        return $resources;
    }
}
