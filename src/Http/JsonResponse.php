<?php
namespace Clicalmani\Foundation\Http;

trait JsonResponse
{
    public function json(mixed $data) : self
    {
        $this->sendBody($this->__json($data));
        return $this;
    }

    public function success(mixed $message = null) : self
    {
        $this->body->write(
            $this->__json([
                'success' => true,
                'data'    => $message
            ])
        );
        return $this;
    }

    public function error(mixed $message = null) : self
    {
        $this->body->write(
            $this->__json([
                'success' => false,
                'data'    => $message
            ])
        );
        return $this;
    }
}