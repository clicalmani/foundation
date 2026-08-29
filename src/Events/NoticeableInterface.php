<?php
namespace Clicalmani\Foundation\Events;

interface NoticeableInterface
{
    public function getType(): string;
    public function getCommentable(): string;
}