<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Saml\User;

use App\Configuration\SamlConfiguration;
use App\Entity\User;
use App\Saml\Token\SamlTokenInterface;

final class SamlUserFactory
{
    private $configuration;

    public function __construct(SamlConfiguration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function createUser(SamlTokenInterface $token): User
    {
        // Not using UserService: user settings should be set via SAML attributes
        $user = new User();
        $user->setEnabled(true);
        $user->setUsername($token->getUsername());

        $this->hydrateUser($user, $token);

        return $user;
    }

    public function hydrateUser(User $user, SamlTokenInterface $token): void
    {
        $groupAttribute = $this->configuration->getRolesAttribute();
        $groupMapping = $this->configuration->getRolesMapping();

        // extract user roles from a special saml attribute
        if (!empty($groupAttribute) && $token->hasAttribute($groupAttribute)) {
            $groupMap = [];
            foreach ($groupMapping as $mapping) {
                $field = $mapping['kimai'];
                $attribute = $mapping['saml'];
                $groupMap[$attribute] = $field;
            }

            $roles = [];
            $samlGroups = $token->getAttribute($groupAttribute);
            foreach ($samlGroups as $groupName) {
                if (\array_key_exists($groupName, $groupMap)) {
                    $roles[] = $groupMap[$groupName];
                }
            }
            $user->setRoles($roles);
        }

        $mappingConfig = $this->configuration->getAttributeMapping();

        foreach ($mappingConfig as $mapping) {
            $field = $mapping['kimai'];
            $attribute = $mapping['saml'];
            $value = $this->getPropertyValue($token, $attribute);
            $setter = 'set' . ucfirst($field);
            if (method_exists($user, $setter)) {
                $user->$setter($value);
            } else {
                throw new \RuntimeException('Invalid mapping field given: ' . $field);
            }
        }

        // fill them after hydrating account, so they can't be overwritten
        $user->setUsername($token->getUsername());
        $user->setPassword('');
        $user->setAuth(User::AUTH_SAML);
    }

    private function getPropertyValue(SamlTokenInterface $token, $attribute)
    {
        $results = [];
        $attributes = $token->getAttributes();

        $parts = explode(' ', $attribute);
        foreach ($parts as $part) {
            if (empty(trim($part))) {
                continue;
            }
            if ($part[0] === '$') {
                $key = substr($part, 1);
                if (!isset($attributes[$key])) {
                    throw new \RuntimeException('Missing user attribute: ' . $key);
                }

                $results[] = $attributes[$key][0];
            } else {
                $results[] = $part;
            }
        }

        if (!empty($results)) {
            return implode(' ', $results);
        }

        return $attribute;
    }
}
