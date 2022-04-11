<?php

namespace gipfl\Web\Form\Validator;

use ipl\Stdlib\Contract\Validator;
use ipl\Stdlib\Messages;

abstract class SimpleValidator implements Validator
{
    use Messages;

    protected $settings = [];

    public function __construct(array $settings = [])
    {
        $this->settings = $settings;
    }

    public function getSetting($name, $default = null)
    {
        if (array_key_exists($name, $this->settings)) {
            return $this->settings[$name];
        } else {
            return $default;
        }
    }
}
