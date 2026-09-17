<?php
namespace Clicalmani\Core\Events;

interface NoticeableInterface
{
    public function getType(): string;
    public function getCommentable(): string;
}