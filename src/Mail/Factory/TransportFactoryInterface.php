<?php
namespace Clicalmani\Core\Mail\Factory;

interface TransportFactoryInterface
{
    /**
     * Creates a mailer transport instance.
     *
     * @return \Symfony\Component\Mailer\Transport\TransportInterface
     */
    public function create() : \Symfony\Component\Mailer\Transport\TransportInterface;
}