<?php

namespace Skybluesofa\Followers\Models;

use Skybluesofa\Followers\Status;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Follower
 * @package Skybluesofa\Followers\Models
 */
class Follower extends Model
{

    /**
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = array())
    {
        $this->table = config('followers.tables.followers');

        parent::__construct($attributes);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function sender()
    {
        return $this->morphTo('sender');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function recipient()
    {
        return $this->morphTo('recipient');
    }

    /**
     * @param Model $recipient
     * @return $this
     */
     public function fillRecipient($recipient)
     {
         return $this->fill([
             'recipient_id' => $recipient->getKey(),
             'recipient_type' => $recipient->getMorphClass()
         ]);
     }

    /**
     * @param Model $recipient
     * @return $this
     */
     public function fillSender($sender)
         {
             return $this->fill([
                 'sender_id' => $sender->getKey(),
                 'sender_type' => $sender->getMorphClass()
             ]);
         }

    /**
     * @param $query
     * @param Model $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
     public function scopeWhereFollowing($query, $recipient)
     {
         return $query->where('recipient_id', $recipient->getKey())
             ->where('recipient_type', $recipient->getMorphClass());
     }

    /**
     * @param $query
     * @param Model $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
     public function scopeWhereFollowedBy($query, $sender)
     {
         return $query->where('sender_id', $sender->getKey())
             ->where('sender_type', $sender->getMorphClass());
     }

    /**
     * @param $query
     * @param Model $sender
     * @param Model $recipient
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFollowing($query, $sender, $recipient)
    {
        return $query->where(function ($queryIn) use ($sender, $recipient){
            $queryIn->where(function ($q) use ($sender, $recipient) {
                $q->whereFollowedBy($sender)->whereFollowing($recipient);
            });
        });
    }


}
