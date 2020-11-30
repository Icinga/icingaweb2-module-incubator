<?php

namespace gipfl\IcingaWeb2;

use Icinga\Util\Translator as WebTranslator;
use gipfl\Translation\TranslatorInterface;

class Translator implements TranslatorInterface
{
    /** @var string */
    private $domain;

    public function __construct($domain)
    {
        $this->domain = $domain;
    }

    public function translate($string)
    {
        return WebTranslator::translate($string, $this->domain);
    }
}
