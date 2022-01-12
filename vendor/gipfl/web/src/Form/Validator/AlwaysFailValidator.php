<?php

namespace gipfl\Web\Form\Validator;

class AlwaysFailValidator extends SimpleValidator
{
    public function isValid($value)
    {
        $message = $this->getSetting('message');
        if ($message) {
            $this->addMessage($message);
        }

        return false;
    }
}
