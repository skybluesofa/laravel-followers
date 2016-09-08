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
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function following()
    {
        return $this->morphMany(Follower::class, 'sender');
    }

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

        $following = (new Follower)->fillRecipient($recipient)->fill([
            'status' => Status::PENDING,
        ]);

        $this->following()->save($following);

        return $following;
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
    public function canFollow(Model $recipient)
    {
        if (!property_exists($recipient, 'canBeFollowed') || !$recipient->canBeFollowed) {
            return false;
        }

        // if user has Blocked the recipient and changed his mind
        // he can send a follow request after unblocking
        if ($this->hasBlockedBeingFollowedBy($recipient)) {
            $this->unblockBeingFollowedBy($recipient);
            return true;
        }

        if (!$recipient->canBeFollowedBy($this)) {
            return false;
        }

        return true;
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
     * @param Model $recipient
     *
     * @return \Skybluesofa\Followers\Models\Follower
     */
    public function getFollowing(Model $recipient)
    {
        return $this->findFollowing($recipient)->first();
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
        return $this->findFollowing()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @param string $groupSlug
     *
     */
    public function getAcceptedRequestsToFollow()
    {
        return $this->findFollowing(Status::ACCEPTED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @param string $groupSlug
     *
     */
    public function getPendingRequestsRequestsToFollow()
    {
        return $this->findFollowing(Status::PENDING)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
   public function getDeniedRequestsToFollow()
   {
       return $this->findFollowing(Status::DENIED)->get();
   }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
    public function getBlockedFollowing()
    {
        return $this->findFollowing(Status::BLOCKED)->get();
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
        return $this->findFollowing(Status::ACCEPTED)->count();
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
    private function findFollowing($status = null)
    {
        $query = Follower::where(function ($query) {
            $query->where(function ($q) {
                $q->whereFollowedBy($this);
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

        $following = $this->findFollowing(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $recipients  = $following->pluck('recipient_id')->all();
        $senders     = $following->pluck('sender_id')->all();

        return $this->where('id', '!=', $this->getKey())->whereIn('id', array_merge($recipients, $senders));
    }

}
