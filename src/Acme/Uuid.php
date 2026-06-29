<?php
namespace Clicalmani\Foundation\Acme;

use Symfony\Component\Uid\Uuid as Base;

trait Uuid
{
    public function uuid()
    {
        return Base::v7();
    }

    public function isValid(string $uuid)
    {
        return $this->uuid()->isValid($uuid);
    }

    public function toString(): string
    {
        return (string) $this->uuid();
    }
}