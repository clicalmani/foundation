<?php
namespace Clicalmani\Core\Events\Listeners;

use App\Models\Comment;
use App\Models\Commentable;
use App\Models\User;
use Clicalmani\Core\Events\NoticeableInterface;
use Clicalmani\Core\Support\Facades\DB;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class NoticeableListener
{
    public function __invoke(NoticeableInterface $event, EventDispatcherInterface $dispatcher)
    {
        DB::deadlock(function() use($event) {
            $comment = Comment::create([
                'data' => json_encode($event->data),
                'date' => now(),
                'author_id' => auth()?->id,
                'type' => $event->getType()
            ]);

            if ($comment) {
                foreach ($event->receivers as $id) {
                    $commentable = Commentable::create([
                        'comment_id' => $comment->id,
                        'commentable_id' => $id,
                        'commentable_type' => $event->getCommentable(),
                    ]);
                    if (!$commentable) return false;
                }
                return true;
            }
            return false;
        });
    }
}