<?php

namespace Formwork\Admin\Security;

class Password
{
    /**
     * Hash a password
     *
     * @param string $password
     *
     * @return string
     */
    public static function hash($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verify if given password matches an hash
     *
     * @param string $password
     * @param string $hash
     *
     * @return bool
     */
    public static function verify($password, $hash)
    {
        return password_verify($password, $hash);
    }
}
