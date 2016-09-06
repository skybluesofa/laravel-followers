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
        return $this->hasManyThrough(Follower::class, 'App\User');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function recipient()
    {
        return $this->belongsTo('App\User', 'recipient_id');
    }

    /**
     * @param Model $recipient
     * @return $this
     */
    public function fillRecipient($recipient)
    {
        return $this->fill([
            'recipient_id' => $recipient->getKey(),
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
        ]);
    }

    /**
     * @param $query
     * @param Model $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereRecipient($query, $model)
    {
        return $query->where('recipient_id', $model->getKey());
    }

    /**
     * @param $query
     * @param Model $model
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereSender($query, $model)
    {
        return $query->where('sender_id', $model->getKey());
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
                $q->whereSender($sender)->whereRecipient($recipient);
            });
        });
    }


}
