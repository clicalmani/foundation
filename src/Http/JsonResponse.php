<?php
namespace Clicalmani\Foundation\Http;

trait JsonResponse
{
    public function json(mixed $data) : self
    {
        return $this->sendJson($this->__json($data));
    }

    public function success(mixed $message = null) : self
    {
        return $this->sendJson(
            $this->__json([
                'success' => true,
                'data'    => $message
            ])
        );
    }

    public function error(mixed $message = null) : self
    {
        return $this->sendJson(
            $this->__json([
                'success' => false,
                'data'    => $message
            ])
        );
    }

    private function sendJson(mixed $message) : self
    {
        if ( defined('TEST_ENV') ) {
            $this->body->setContents($message);
        } else {
            $this->body->write($message);
        }

        return $this;
    }
}