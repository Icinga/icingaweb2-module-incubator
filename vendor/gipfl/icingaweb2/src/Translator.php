<?php

namespace gipfl\IcingaWeb2;

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
        $res = dgettext($this->domain, $string);
        if ($res === $string && $this->domain !== 'icinga') {
            return dgettext('icinga', $string);
        }

        return $res;
    }
}
