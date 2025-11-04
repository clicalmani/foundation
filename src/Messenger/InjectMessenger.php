<?php
namespace Clicalmani\Foundation\Messenger;

use Clicalmani\Foundation\Http\Controllers\InjectionLocator;

class InjectMessenger extends InjectionLocator
{
    public function handle(): ?object
    {
        if ($this->canHandle()) {
            $this->createInstance();
            return $this->instance;
        }

        return null;
    }
}