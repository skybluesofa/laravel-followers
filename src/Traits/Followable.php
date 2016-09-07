<?php

namespace Skybluesofa\Followers\Traits;

use Skybluesofa\Followers\Models\Follower;
use Skybluesofa\Followers\Status;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Followable
 * @package Skybluesofa\Followers\Traits
 */
trait Followable
{
    /**
     * @param Model $recipient
     *
     * @return \Skybluesofa\Followers\Models\Follower|false
     */
    public function follow(Model $recipient)
    {
        if (!$this->canFollow($recipient)) {
            return false;
        }

        return (new Follower)->fillSender($this)->fillRecipient($recipient)->fill([
            'status' => Status::PENDING,
        ])->save();

    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function unfollow(Model $recipient)
    {
        return $this->whenFollowing($recipient)->delete();
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function isFollowing(Model $recipient)
    {
        return $this->whenFollowing($recipient)->where('status', Status::ACCEPTED)->exists();
    }

    /**
     * @param Model $sender
     *
     * @return bool
     */
    public function isFollowedBy(Model $sender)
    {
        //return Follower::whereRecipient($this)->whereSender($sender)->where('status', Status::ACCEPTED)->exists();
        return $sender->whenFollowing($this)->where('status', Status::ACCEPTED)->exists();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function following()
    {
        return $this->hasMany(Follower::class, 'sender_id');
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function hasFollowRequestFrom(Model $sender)
    {
        return $this->whenFollowedBy($sender)->whereStatus(Status::PENDING)->exists();
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function hasSentFollowRequestTo(Model $recipient)
    {
        return $this->whenFollowing($recipient)->whereStatus(Status::PENDING)->exists();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFollowerRequests()
    {
        return Follower::whereRecipient($this)->whereStatus(Status::PENDING)->get();
    }

    /**
     * @param Model $sender
     *
     * @return bool|int
     */
    public function acceptFollowRequestFrom(Model $sender)
    {
        return $sender->whenFollowing($this)->update([
            'status' => Status::ACCEPTED,
        ]);
    }

    /**
     * @param Model $sender
     *
     * @return bool|int
     */
    public function denyFollowRequestFrom(Model $sender)
    {
        return $sender->whenFollowing($this)->update([
            'status' => Status::DENIED,
        ]);
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function canFollow($recipient)
    {
        // if user has Blocked the recipient and changed his mind
        // he can send a friend request after unblocking
        if ($this->hasBlockedBeingFollowedBy($recipient)) {
            $this->unblockBeingFollowedBy($recipient);
            return true;
        }

        // if sender is following the recipient return false
        if ($followed = Follower::whereRecipient($recipient)->whereSender($this)->first()) {
        //if ($followed = $recipient->getFollowedBy($this)) {
            if ($followed->status != Status::DENIED) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function hasBlockedBeingFollowedBy(Model $sender)
    {
        return $this->followers()->whereSender($sender)->whereStatus(Status::BLOCKED)->exists();
    }
    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function isBlockedFromFollowing(Model $recipient)
    {
        return $recipient->hasBlockedBeingFollowedBy($this);
    }

    /**
     * @param Model $sender
     *
     * @return bool
     */
    public function isBlockedFromBeingFollowedBy(Model $sender)
    {
        return $this->hasBlockedBeingFollowedBy($sender);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function followers()
    {
        return Follower::where('recipient_id', $this->id);
    }

    /**
     * @param Model $sender
     *
     * @return \Skybluesofa\Followers\Models\Follower
     */
    public function blockBeingFollowedBy(Model $sender)
    {
        if (!$this->hasBlockedBeingFollowedBy($sender)) {
            $this->whenFollowedBy($sender)->delete();
        }

        return (new Follower)->fillSender($sender)->fillRecipient($this)->fill([
            'status' => Status::BLOCKED,
        ])->save();
    }

    /**
     * @param Model $sender
     *
     * @return mixed
     */
    public function unblockBeingFollowedBy(Model $sender)
    {
        return $this->whenFollowedBy($sender)->delete();
    }

    /**
     * @param Model $recipient
     *
     * @return \Skybluesofa\Followers\Models\Follower
     */
    public function getFollowedBy(Model $sender)
    {
        return $this->findFollowedByRelationships($sender)->first();
    }

    /**
     * This method will not return Friendship models
     * It will return the 'friends' models. ex: App\User
     *
     * @param int $perPage Number
     * @param string $groupSlug
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFollowingList($perPage = null)
    {
        return $this->getOrPaginate($this->getFollowingQueryBuilder(), $perPage);
    }

    /**
     * This method will not return Friendship models
     * It will return the 'friends' models. ex: App\User
     *
     * @param int $perPage Number
     * @param string $groupSlug
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFollowedByList($perPage = null)
    {
        return $this->getOrPaginate($this->getFollowedByQueryBuilder(), $perPage);
    }

    /**
     * @param Model $sender
     *
     * @return \Skybluesofa\Followers\Models\Follower
     */
    public function getFollowing(Model $recipient)
    {
        return $this->findFollowingRelationships($recipient)->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @param string $groupSlug
     *
     */
    public function getAllFollowedBy()
    {
        return $this->findFollowedByRelationships()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @param string $groupSlug
     *
     */
    public function getAllFollowing()
    {
        return $this->findFollowingRelationships()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @param string $groupSlug
     *
     */
    public function getAcceptedRequestsToFollow()
    {
        return $this->findFollowingRelationships(Status::ACCEPTED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @param string $groupSlug
     *
     */
    public function getAcceptedRequestsToBeFollowed()
    {
        return $this->findFollowedByRelationships(Status::ACCEPTED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @param string $groupSlug
     *
     */
    public function getPendingRequestsRequestsToFollow()
    {
        return $this->findFollowingRelationships(Status::PENDING)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
     public function getPendingRequestsToBeFollowed()
     {
         return $this->findFollowedByRelationships(Status::PENDING)->get();
     }

     /**
      * @return \Illuminate\Database\Eloquent\Collection
      *
      */
    public function getDeniedRequestsToFollow()
    {
        return $this->findFollowingRelationships(Status::DENIED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
    public function getDeniedRequestsToBeFollowed()
    {
        return $this->findFollowedByRelationships(Status::DENIED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
    public function getBlockedFollowing()
    {
        return $this->findFollowingRelationships(Status::BLOCKED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
    public function getBlockedFollowedBy()
    {
        return $this->findFollowedByRelationships(Status::BLOCKED)->get();
    }

    /**
     * @param Model $recipient
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function whenFollowedBy(Model $sender)
    {
        return Follower::following($sender, $this);
    }

    /**
     * @param Model $recipient
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function findFollowingRelationships($status = null)
    {
        $query = Follower::where(function ($query) {
            $query->where(function ($q) {
                $q->whereSender($this);
            });
        });

        //if $status is passed, add where clause
        if (!is_null($status)) {
            $query->where('status', $status);
        }

        return $query;
    }

    /**
     * @param Model $recipient
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function whenFollowing(Model $recipient)
    {
        return Follower::following($this, $recipient);
    }

    /**
     * @param $status
     * @param string $groupSlug
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function findFollowedByRelationships($status = null)
    {

        $query = Follower::where(function ($query) {
            $query->where(function ($q) {
                $q->whereRecipient($this);
            });
        });

        //if $status is passed, add where clause
        if (!is_null($status)) {
            $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Get the number of friends
     *
     * @param string $groupSlug
     *
     * @return integer
     */
    public function getFollowingCount()
    {
        return $this->findFollowingRelationships(Status::ACCEPTED)->count();
    }

    protected function getOrPaginate($builder, $perPage)
    {
        if (is_null($perPage)) {
            return $builder->get();
        }
        return $builder->paginate($perPage);
    }

    /**
     * Get the query builder of the 'friend' model
     *
     * @param string $groupSlug
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getFollowingQueryBuilder()
    {

        $following = $this->findFollowingRelationships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $recipients  = $following->pluck('recipient_id')->all();
        $senders     = $following->pluck('sender_id')->all();

        return $this->where('id', '!=', $this->getKey())->whereIn('id', array_merge($recipients, $senders));
    }

    /**
     * Get the query builder of the 'friend' model
     *
     * @param string $groupSlug
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getFollowedByQueryBuilder()
    {

        $following = $this->findFollowedByRelationships(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $recipients  = $following->pluck('recipient_id')->all();
        $senders     = $following->pluck('sender_id')->all();

        return $this->where('id', '!=', $this->getKey())->whereIn('id', array_merge($recipients, $senders));
    }

}
