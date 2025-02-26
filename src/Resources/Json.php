<?php
namespace Clicalmani\Foundation\Resources;

class Json 
{
    private $value;

    private int $flags;

    private ?bool $associative;

    private int $depth;

    public function __construct($value = null, ?bool $associative = null, int $depth = 512, int $flags = 0)
    {
        $this->value = $value;
        $this->associative = $associative;
        $this->depth = $depth;
        $this->flags = $flags;
    }

    public function encode(mixed $value = null) : string|false
    {
        return json_encode($value ?? $this->value, $this->flags, $this->depth);
    }

    public function decode(?string $json = null) : mixed
    {
        return json_decode($json ?? $this->value, $this->associative, $this->depth, $this->flags);
    }
}