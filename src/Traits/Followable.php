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
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
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
    public function unfollow(Model $recipient)
    {

        return $this->whenFollowing($recipient)->delete();
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
        if ($this->hasBlockedFollowed($recipient)) {
            $this->unblockFollowed($recipient);
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
    public function hasBlockedFollowed(Model $recipient)
    {
        return $this->followed()->whereRecipient($recipient)->whereStatus(Status::BLOCKED)->exists();
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function hasBlockedFollower(Model $sender)
    {
        return $this->following()->whereSender($sender)->whereStatus(Status::BLOCKED)->exists();
    }
    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function isBlockedByFollowed(Model $recipient)
    {
        return $recipient->hasBlockedFollower($this);
    }

    /**
     * @param Model $sender
     *
     * @return bool
     */
    public function isBlockedByFollower(Model $sender)
    {
        return $this->hasBlockedFollower($sender);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function followed()
    {
        return Follower::where('sender_id', $this->id);
    }

    /**
     * @param Model $sender
     *
     * @return \Skybluesofa\Followers\Models\Follower
     */
    public function blockFollower(Model $sender)
    {
        if (!$sender->isBlockedByFollowed($this)) {
            $this->whenFollowedBy($sender)->delete();
        }

        return (new Follower)->fillSender($sender)->fillRecipient($this)->fill([
            'status' => Status::BLOCKED,
        ])->save();
    }

    /**
     * @param Model $recipient
     *
     * @return \Skybluesofa\Followers\Models\Follower
     */
    public function blockFollowed(Model $recipient)
    {
        if (!$recipient->isBlockedByFollower($this)) {
            $this->whenFollowing($recipient)->delete();
        }

        return (new Follower)->fillSender($this)->fillRecipient($recipient)->fill([
            'status' => Status::BLOCKED,
        ])->save();
    }

    /**
     * @param Model $sender
     *
     * @return mixed
     */
    public function unblockFollower(Model $sender)
    {
        return $this->whenFollowedBy($sender)->delete();
    }

    /**
     * @param Model $recipient
     *
     * @return mixed
     */
    public function unblockFollowed(Model $recipient)
    {
        return $this->whenFollowing($recipient)->delete();
    }

    /**
     * @param Model $recipient
     *
     * @return \Skybluesofa\Followers\Models\Follower
     */
    public function getFollowedBy(Model $sender)
    {
        return $this->findFollowedRelationships($sender)->first();
    }

    /**
     * @param Model $sender
     *
     * @return \Skybluesofa\Followers\Models\Follower
     */
    public function getFollowing(Model $recipient)
    {
        return $this->findFollowerRelationships($recipient)->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @param string $groupSlug
     *
     */
    public function getAllFollowers()
    {
        return $this->findFollowerRelationships()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @param string $groupSlug
     *
     */
    public function getAllFollowed()
    {
        return $this->findFollowedRelationships()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @param string $groupSlug
     *
     */
    public function getAcceptedFollowerRequests()
    {
        return $this->findFollowerRelationships(Status::ACCEPTED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @param string $groupSlug
     *
     */
    public function getAcceptedFollowedRequests()
    {
        return $this->findFollowedRelationships(Status::ACCEPTED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @param string $groupSlug
     *
     */
    public function getPendingFollowerRequests()
    {
        return $this->findFollowerRelationships(Status::PENDING)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
     public function getPendingFollowedRequests()
     {
         return $this->findFollowedRelationships(Status::PENDING)->get();
     }

     /**
      * @return \Illuminate\Database\Eloquent\Collection
      *
      */
    public function getDeniedFollowerRequests()
    {
        return $this->findFollowerRelationships(Status::DENIED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
    public function getDeniedFollowedRequests()
    {
        return $this->findFollowedRelationships(Status::DENIED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
    public function getBlockedFollowers()
    {
        return $this->findFollowerRelationships(Status::BLOCKED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
    public function getBlockedFollowed()
    {
        return $this->findFollowedRelationships(Status::BLOCKED)->get();
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
    private function findFollowerRelationships($status = null)
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
    private function findFollowedRelationships($status = null)
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
     * Get the number of friends
     *
     * @param string $groupSlug
     *
     * @return integer
     */
    public function getFollowedCount()
    {
        return $this->findFollowedRelationships(Status::ACCEPTED)->count();
    }
}
