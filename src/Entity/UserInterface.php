<?php

namespace Pushword\Core\Entity;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface as BaseUserInterface;

interface UserInterface extends PasswordAuthenticatedUserInterface, BaseUserInterface
{
    public const ROLE_DEFAULT = 'ROLE_USER';

    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public function getPlainPassword(): ?string;

    public function setPassword(string $password): self;

    public function setEmail(string $email): self;

    /** @param string[] $roles */
    public function setRoles(array $roles): self;

    public function __toString(): string;
}
