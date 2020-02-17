<?php
use App\User;

class FollowersTest extends TestCase
{
    public function test_user_can_send_a_follow_request()
    {
        $sender = factory(User::class)->create();
        $recipient  = factory(User::class)->create();

        $sender->follow($recipient);

        $this->assertCount(1, $recipient->getFollowerRequests());
    }

    public function test_user_can_not_send_a_follow_request_if_follow_request_is_pending()
    {
        $sender = factory(User::class)->create();
        $recipient  = factory(User::class)->create();

        $sender->follow($recipient);
        $sender->follow($recipient);
        $sender->follow($recipient);

        $this->assertCount(1, $recipient->getFollowerRequests());
    }


    public function test_user_can_send_a_follow_request_if_follow_has_already_been_denied()
    {
        $sender = factory(User::class)->create();
        $recipient  = factory(User::class)->create();

        $sender->follow($recipient);
        $recipient->denyFollowRequestFrom($sender);

        $sender->follow($recipient);

        $this->assertCount(1, $recipient->getFollowerRequests());
    }

    public function test_user_can_remove_a_follow_request()
    {
        $sender = factory(User::class)->create();
        $recipient  = factory(User::class)->create();

        $sender->follow($recipient);
        $this->assertCount(1, $recipient->getFollowerRequests());

        $sender->unfollow($recipient);
        $this->assertCount(0, $recipient->getFollowerRequests());

        // Can resend friend request after deleted
        $sender->follow($recipient);
        $this->assertCount(1, $recipient->getFollowerRequests());

        $recipient->acceptFollowRequestFrom($sender);
        $this->assertEquals(true, $recipient->isFollowedBy($sender));
        // Can remove friend request after accepted
        $sender->unfollow($recipient);
        $this->assertEquals(false, $recipient->isFollowedBy($sender));
    }

    public function test_user_has_follow_request_from_another_user_if_he_received_a_follow_request()
    {
        $sender = factory(User::class)->create();
        $recipient  = factory(User::class)->create();
        //send fr
        $sender->follow($recipient);

        $this->assertTrue($recipient->hasFollowRequestFrom($sender));
    }

    public function test_user_has_sent_follow_request_to_this_user_if_he_already_sent_request()
    {
        $sender = factory(User::class)->create();
        $recipient  = factory(User::class)->create();
        //send fr
        $sender->follow($recipient);

        $this->assertTrue($sender->hasSentFollowRequestTo($recipient));
        $this->assertTrue($recipient->hasFollowRequestFrom($sender));
    }

    public function test_user_has_not_follow_request_from_another_user_if_he_accepted_the_follow_request()
    {
        $sender = factory(User::class)->create();
        $recipient  = factory(User::class)->create();
        //send fr
        $sender->follow($recipient);
        //accept fr
        $recipient->acceptFollowRequestFrom($sender);

        $this->assertFalse($sender->hasSentFollowRequestTo($recipient));
        $this->assertFalse($recipient->hasFollowRequestFrom($sender));
    }

    public function test_user_cannot_accept_his_own_follow_request()
    {
        $sender = factory(User::class)->create();
        $recipient  = factory(User::class)->create();

        //send fr
        $sender->follow($recipient);

        $sender->acceptFollowRequestFrom($recipient);
        $this->assertFalse($recipient->isFollowing($sender));
    }

    public function test_user_can_deny_a_follow_request()
    {
        $sender = factory(User::class)->create();
        $recipient  = factory(User::class)->create();

        $sender->follow($recipient);

        $recipient->denyFollowRequestFrom($sender);

        $this->assertFalse($recipient->isFollowedBy($sender));

        //fr has been delete
        $this->assertCount(0, $recipient->getFollowerRequests());
        $this->assertCount(1, $sender->getDeniedRequestsToFollow());
    }

    public function test_user_can_block_another_user()
    {
        $user1 = factory(User::class)->create();
        $user2  = factory(User::class)->create();

        $user1->blockBeingFollowedBy($user2);

        $this->assertTrue($user1->hasBlockedBeingFollowedBy($user2));
        //sender is not blocked by receipient
        $this->assertTrue($user2->isBlockedFromFollowing($user1));
    }

    public function test_user_can_unblock_a_blocked_user()
    {
        $user1 = factory(User::class)->create();
        $user2  = factory(User::class)->create();

        $user1->blockBeingFollowedBy($user2);
        $user1->unblockBeingFollowedBy($user2);

        $this->assertFalse($user2->isBlockedFromBeingFollowedBy($user1));
        $this->assertFalse($user1->hasBlockedBeingFollowedBy($user2));
    }

    public function test_user_block_is_permanent_unless_blocker_decides_to_unblock()
    {
        $user1 = factory(User::class)->create();
        $user2  = factory(User::class)->create();

        $user1->blockBeingFollowedBy($user2);
        $this->assertTrue($user2->isBlockedFromFollowing($user1));

        // now recipient blocks sender too
        $user2->blockBeingFollowedBy($user1);

        // expect that both users have blocked each other
        $this->assertTrue($user1->isBlockedFromFollowing($user2));
        $this->assertTrue($user2->isBlockedFromFollowing($user1));

        $user1->unblockBeingFollowedBy($user2);

        $this->assertFalse($user1->isBlockedFromBeingFollowedBy($user2));
        $this->assertTrue($user2->isBlockedFromBeingFollowedBy($user1));

        $user2->unblockBeingFollowedBy($user1);
        $this->assertFalse($user1->isBlockedFromBeingFollowedBy($user2));
        $this->assertFalse($user2->isBlockedFromBeingFollowedBy($user1));
    }

    public function test_user_can_send_friend_request_to_user_who_is_blocked()
    {
        $sender = factory(User::class)->create();
        $recipient  = factory(User::class)->create();

        $sender->blockBeingFollowedBy($recipient);
        $sender->follow($recipient);
        $sender->follow($recipient);

        $this->assertCount(1, $recipient->getFollowerRequests());
    }

    public function test_user_cannot_follow_a_nonfollowable_model()
    {
        $sender = factory(User::class)->create();
        // Widget does not have the 'CanBeFollowed' trait
        $recipient  = factory(Widget::class)->create();

        $this->assertFalse($sender->follow($recipient));
    }

    public function test_it_returns_all_user_follow_requests()
    {
        $sender = factory(User::class)->create();
        $recipients = factory(User::class, 3)->create();

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
        }

        $recipients[0]->acceptFollowRequestFrom($sender);
        $recipients[1]->acceptFollowRequestFrom($sender);
        $recipients[2]->denyFollowRequestFrom($sender);
        $this->assertCount(3, $sender->getAllFollowing());
    }

    public function test_number_of_follow_requests_is_limited()
    {
        $sender = factory(User::class)->create();
        $recipients = factory(User::class, 5)->create();

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
            $recipient->acceptFollowRequestFrom($sender);
        }

        $recipient = factory(User::class)->create();
        $sender->follow($recipient);

        $this->assertCount(5, $sender->getAllFollowing());
    }
    
    public function test_it_returns_number_of_accepted_user_following()
    {
        $senders = factory(User::class, 2)->create();
        $recipients = factory(User::class, 3)->create();

        foreach ($recipients as $recipient) {
            $senders[0]->follow($recipient);
        }

        $senders[1]->follow($recipients[0]);

        $recipients[0]->acceptFollowRequestFrom($senders[0]);
        $recipients[1]->acceptFollowRequestFrom($senders[0]);
        $recipients[2]->denyFollowRequestFrom($senders[0]);
        $this->assertEquals(2, $senders[0]->getFollowingCount());
        $this->assertEquals(1, $recipients[0]->getFollowedByCount());
    }

    public function test_it_returns_accepted_user_following()
    {
        $sender = factory(User::class)->create();
        $recipients = factory(User::class, 3)->create();

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
        }

        $recipients[0]->acceptFollowRequestFrom($sender);
        $recipients[1]->acceptFollowRequestFrom($sender);
        $recipients[2]->denyFollowRequestFrom($sender);
        $this->assertInstanceOf(Skybluesofa\Followers\Models\Follower::class, $sender->getFollowing($recipients[0]));
        $this->assertInstanceOf(Skybluesofa\Followers\Models\Follower::class, $recipients[0]->getFollowedBy($sender));
        $this->assertCount(2, $sender->getAcceptedRequestsToFollow());
        $this->assertTrue($recipients[0]->isFollowedBy($sender));
    }

    public function test_it_returns_only_accepted_user_friendships()
    {
        $sender = factory(User::class)->create();
        $recipients = factory(User::class, 4)->create();

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
        }

        $recipients[0]->acceptFollowRequestFrom($sender);
        $recipients[1]->acceptFollowRequestFrom($sender);
        $recipients[2]->denyFollowRequestFrom($sender);
        $this->assertCount(2, $sender->getAcceptedRequestsToFollow());

        $this->assertCount(1, $recipients[0]->getAcceptedRequestsToBeFollowed());
        $this->assertCount(1, $recipients[1]->getAcceptedRequestsToBeFollowed());
        $this->assertCount(0, $recipients[2]->getAcceptedRequestsToBeFollowed());
        $this->assertCount(0, $recipients[3]->getAcceptedRequestsToBeFollowed());
    }

    public function test_it_returns_pending_user_friendships()
    {
        $sender = factory(User::class)->create();
        $recipients = factory(User::class, 3)->create();

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
        }

        $recipients[0]->acceptFollowRequestFrom($sender);
        $this->assertCount(2, $sender->getPendingRequestsRequestsToFollow());
        $this->assertCount(1, $recipients[1]->getPendingRequestsToBeFollowed());
    }

    public function test_it_returns_denied_user_friendships()
    {
        $sender = factory(User::class)->create();
        $recipients = factory(User::class, 3)->create();

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
        }

        $recipients[0]->acceptFollowRequestFrom($sender);
        $recipients[1]->acceptFollowRequestFrom($sender);
        $recipients[2]->denyFollowRequestFrom($sender);
        $this->assertCount(1, $sender->getDeniedRequestsToFollow());
        $this->assertCount(1, $recipients[2]->getDeniedRequestsToBeFollowed());
    }

    public function test_it_returns_blocked_user_friendships()
    {
        $sender = factory(User::class)->create();
        $recipients = factory(User::class, 3)->create();

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
        }

        $recipients[0]->acceptFollowRequestFrom($sender);
        $recipients[1]->acceptFollowRequestFrom($sender);
        $recipients[2]->blockBeingFollowedBy($sender);
        $this->assertCount(1, $sender->getBlockedFollowing());
        $this->assertCount(1, $recipients[2]->getBlockedFollowedBy());
    }

    public function test_it_returns_followed_users()
    {
        $sender = factory(User::class)->create();
        $recipients = factory(User::class, 4)->create();

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
        }

        $recipients[0]->acceptFollowRequestFrom($sender);
        $recipients[1]->acceptFollowRequestFrom($sender);
        $recipients[2]->denyFollowRequestFrom($sender);

        $this->assertCount(2, $sender->getAcceptedRequestsToFollow());
        $this->assertCount(1, $recipients[1]->getAcceptedRequestsToBeFollowed());
        $this->assertCount(0, $recipients[2]->getAcceptedRequestsToBeFollowed());
        $this->assertCount(0, $recipients[3]->getAcceptedRequestsToBeFollowed());

        $this->containsOnlyInstancesOf(\App\User::class, $sender->getAcceptedRequestsToFollow());
        $this->containsOnlyInstancesOf(\App\User::class, $recipients[1]->getAllFollowedBy());
    }

    public function test_it_returns_user_follows_per_page()
    {
        $sender = factory(User::class)->create();
        $recipients = factory(User::class, 6)->create();

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
        }

        $recipients[0]->acceptFollowRequestFrom($sender);
        $recipients[1]->acceptFollowRequestFrom($sender);
        $recipients[2]->denyFollowRequestFrom($sender);
        $recipients[3]->acceptFollowRequestFrom($sender);
        $recipients[4]->acceptFollowRequestFrom($sender);


        $this->assertCount(2, $sender->getFollowingList(2));
        $this->assertCount(4, $sender->getFollowingList(0));
        $this->assertCount(4, $sender->getFollowingList(10));
        $this->assertCount(1, $recipients[1]->getFollowedByList());
        $this->assertCount(0, $recipients[2]->getFollowedByList());
        $this->assertCount(0, $recipients[5]->getFollowedByList(2));

        $this->containsOnlyInstancesOf(\App\User::class, $sender->getFollowingList());
        $this->containsOnlyInstancesOf(\App\User::class, $recipients[5]->getFollowedByList());
    }
}
