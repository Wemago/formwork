<?php

namespace Formwork\Users;

use Formwork\Utils\Str;

class Permissions
{
    /**
     * The permission values
     *
     * @var array<string, bool>
     */
    protected array $permissions = [
        'dashboard' => false,
        'cache'     => false,
        'backup'    => false,
        'pages'     => false,
        'options'   => false,
        'updates'   => false,
        'users'     => false,
    ];

    /**
     * @param array<string, bool> $permissions
     */
    public function __construct(array $permissions)
    {
        $this->permissions = [...$this->permissions, ...$permissions];
    }

    /**
     * Return whether a permission is granted
     */
    public function has(string $permission): bool
    {
        if (array_key_exists($permission, $this->permissions)) {
            return $this->permissions[$permission];
        }

        // If $permission is not found try with the upper level one (super permission),
        // e.g. try with 'options' if 'options.updates' is not found

        $superPermission = Str::beforeLast($permission, '.');

        if ($superPermission !== $permission) {
            return $this->has($superPermission);
        }

        return false;
    }
}
