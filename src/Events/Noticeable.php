<?php
namespace Clicalmani\Core\Events;

abstract class Noticeable
{
    protected string $type = 'system';
    protected string $commentable = \App\Models\User::class;

    public function __construct(
        public array $data = [],
        public array $receivers = []
    )
    {}

    public function getType(): string
    {
        return $this->type;
    }

    public function getCommentable(): string
    {
        return $this->commentable;
    }
}