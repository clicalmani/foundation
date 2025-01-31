<?php
namespace Clicalmani\Foundation\Events;

interface Observer
{
    public function trigger() : void;
}