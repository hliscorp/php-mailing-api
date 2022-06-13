<?php

namespace Lucinda\Mail;

/**
 * Class that encapsulates an email address along with its optional owner name
 */
class Address implements \Stringable
{
    private string $email;
    private ?string $name = null;

    /**
     * Registers and validates email address along with its optional owner name
     *
     * @param  string      $email
     * @param  string|NULL $name
     * @throws Exception
     */
    public function __construct(string $email, ?string $name=null)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email address is invalid!");
        }
        $this->email = $email;
        $this->name = $name;
    }

    /**
     * Converts entity to string
     *
     * @return string
     */
    public function __toString(): string
    {
        if ($this->name) {
            return $this->name." <".$this->email.">";
        } else {
            return $this->email;
        }
    }
}
