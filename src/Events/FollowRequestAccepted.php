<?php
namespace Skybluesofa\Followers\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

class FollowRequestAccepted
{
    use SerializesModels;

    public $recipient;
    public $sender;

    /**
     * Create a new event instance.
     *
     * @param Model $sender
     * @param Model $recipient
     * @return void
     */
    public function __construct(Model $recipient, Model $sender)
    {
        $this->recipient = $recipient;
        $this->sender = $sender;
    }
}
