<?php
namespace Skybluesofa\Followers\Traits;

use Skybluesofa\Followers\Models\Follower;
use Skybluesofa\Followers\Status;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Followable
 * @package Skybluesofa\Followers\Traits
 */
trait CanBeFollowed
{
    /**
     * @var boolean
     */
    protected $canBeFollowed = true;

    /**
     * @param Model $sender
     *
     * @return bool
     */
    public function canBeFollowedBy(Model $sender)
    {
        // if sender is following the recipient return false
        if ($followed = $this->getFollowedBy($sender)) {
            if ($followed->status != Status::DENIED) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param Model $sender
     *
     * @return bool
     */
    public function isFollowedBy(Model $sender)
    {
        return $sender->whenFollowing($this)->where('status', Status::ACCEPTED)->exists();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function followers()
    {
        return $this->morphMany(Follower::class, 'recipient');
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
     * @param Model $sender
     *
     * @return bool
     */
    public function isBlockedFromBeingFollowedBy(Model $sender)
    {
        return $this->hasBlockedBeingFollowedBy($sender);
    }

    /**
     * @param Model $recipient
     *
     * @return bool
     */
    public function hasBlockedBeingFollowedBy(Model $sender)
    {
        return $this->followers()->whereFollowedBy($sender)->whereStatus(Status::BLOCKED)->exists();
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
        return $this->whenFollowedBy($sender)->first();
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
        return $this->getOrPaginateFollowedBy($this->getFollowedByQueryBuilder(), $perPage);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFollowerRequests()
    {
        return Follower::whereFollowing($this)->whereStatus(Status::PENDING)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @param string $groupSlug
     *
     */
    public function getAllFollowedBy()
    {
        return $this->findFollowedBy()->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     * @param string $groupSlug
     *
     */
    public function getAcceptedRequestsToBeFollowed()
    {
        return $this->findFollowedBy(Status::ACCEPTED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
    public function getPendingRequestsToBeFollowed()
    {
        return $this->findFollowedBy(Status::PENDING)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
    public function getDeniedRequestsToBeFollowed()
    {
        return $this->findFollowedBy(Status::DENIED)->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     *
     */
    public function getBlockedFollowedBy()
    {
        return $this->findFollowedBy(Status::BLOCKED)->get();
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
     * Get the number of friends
     *
     * @param string $groupSlug
     *
     * @return integer
     */
    public function getFollowedByCount()
    {
        return $this->findFollowedBy(Status::ACCEPTED)->count();
    }

    protected function getOrPaginateFollowedBy($builder, $perPage)
    {
        if (is_null($perPage)) {
            return $builder->get();
        }
        return $builder->paginate($perPage);
    }

    /**
     * @param $status
     * @param string $groupSlug
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function findFollowedBy($status = null)
    {

        $query = Follower::where(function ($query) {
            $query->whereFollowing($this);
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
    private function getFollowedByQueryBuilder()
    {

        $following = $this->findFollowedBy(Status::ACCEPTED)->get(['sender_id', 'recipient_id']);
        $recipients  = $following->pluck('recipient_id')->all();
        $senders     = $following->pluck('sender_id')->all();

        return $this->where('id', '!=', $this->getKey())->whereIn('id', array_merge($recipients, $senders));
    }
}
