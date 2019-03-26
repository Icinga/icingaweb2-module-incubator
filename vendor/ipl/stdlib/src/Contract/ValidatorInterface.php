<?php

namespace ipl\Stdlib\Contract;

interface ValidatorInterface
{
    /**
     * Get whether the given value is valid
     *
     * @param   mixed   $value
     *
     * @return  bool
     */
    public function isValid($value);

    /**
     * Get the validation error messages
     *
     * @return  array
     */
    public function getMessages();
}
