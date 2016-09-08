<?php
namespace Skybluesofa\Followers\Traits;

use Skybluesofa\Followers\Models\Follower;
use Skybluesofa\Followers\Status;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Followable
 * @package Skybluesofa\Followers\Traits
 */
trait CanFollow
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
    public function canFollow($recipient)
    {
        if (!property_exists($recipient, 'canBeFollowed') || !$recipient->canBeFollowed) {
            return false;
        }

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
    public function unfollow(Model $recipient)
    {
        return $this->whenFollowing($recipient)->delete();
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
     * @param Model $recipient
     *
     * @return bool
     */
    public function isFollowing(Model $recipient)
    {
        return $this->whenFollowing($recipient)->where('status', Status::ACCEPTED)->exists();
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
     * @return \Skybluesofa\Followers\Models\Follower
     */
    public function getFollowing(Model $recipient)
    {
        return $this->findFollowingRelationships($recipient)->first();
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
        return $this->getOrPaginateFollowing($this->getFollowingQueryBuilder(), $perPage);
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
    public function getPendingRequestsRequestsToFollow()
    {
        return $this->findFollowingRelationships(Status::PENDING)->get();
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
    public function getBlockedFollowing()
    {
        return $this->findFollowingRelationships(Status::BLOCKED)->get();
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

    protected function getOrPaginateFollowing($builder, $perPage)
    {
        if (is_null($perPage)) {
            return $builder->get();
        }
        return $builder->paginate($perPage);
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

}
