<?php
namespace Clicalmani\Foundation\Http;

trait StatusErrors
{
    public function notFound()
    {
        $this->sendStatus(404);
    }

    public function unauthorized()
    {
        $this->sendStatus(401);
    }

    public function forbiden()
    {
        $this->sendStatus(403);
    }

    public function internalServerError()
    {
        $this->sendStatus(500);
    }
}