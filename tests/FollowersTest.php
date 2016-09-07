<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class FollowersTest extends TestCase
{
    // use DatabaseTransactions;

    /** @test */
    public function user_can_send_a_follow_request()
    {
        $sender    = createUser();
        $recipient = createUser();

        $sender->follow($recipient);

        $this->assertCount(1, $recipient->getFollowerRequests());
    }

    /** @test */
    public function user_can_not_send_a_follow_request_if_follow_request_is_pending()
    {
        $sender    = createUser();
        $recipient = createUser();
        $sender->follow($recipient);
        $sender->follow($recipient);
        $sender->follow($recipient);

        $this->assertCount(1, $recipient->getFollowerRequests());
    }


    /** @test */
    public function user_can_send_a_follow_request_if_follow_has_already_been_denied()
    {
        $sender    = createUser();
        $recipient = createUser();

        $sender->follow($recipient);
        $recipient->denyFollowRequestFrom($sender);

        $sender->follow($recipient);

        $this->assertCount(1, $recipient->getFollowerRequests());
    }

    /** @test */
    public function user_can_remove_a_follow_request()
    {
        $sender    = createUser();
        $recipient = createUser();

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

    /** @test */
    public function user_has_follow_request_from_another_user_if_he_received_a_follow_request()
    {
        $sender    = createUser();
        $recipient = createUser();
        //send fr
        $sender->follow($recipient);

        $this->assertTrue($recipient->hasFollowRequestFrom($sender));
    }

    /** @test */
    public function user_has_sent_follow_request_to_this_user_if_he_already_sent_request()
    {
        $sender    = createUser();
        $recipient = createUser();
        //send fr
        $sender->follow($recipient);

        $this->assertTrue($sender->hasSentFollowRequestTo($recipient));
        $this->assertTrue($recipient->hasFollowRequestFrom($sender));
    }

    /** @test */
    public function user_has_not_follow_request_from_another_user_if_he_accepted_the_follow_request()
    {
        $sender    = createUser();
        $recipient = createUser();
        //send fr
        $sender->follow($recipient);
        //accept fr
        $recipient->acceptFollowRequestFrom($sender);

        $this->assertFalse($sender->hasSentFollowRequestTo($recipient));
        $this->assertFalse($recipient->hasFollowRequestFrom($sender));
    }

    /** @test */
    public function user_cannot_accept_his_own_follow_request()
    {
        $sender    = createUser();
        $recipient = createUser();

        //send fr
        $sender->follow($recipient);

        $sender->acceptFollowRequestFrom($recipient);
        $this->assertFalse($recipient->isFollowing($sender));
    }

    /** @test */
    public function user_can_deny_a_follow_request()
    {
        $sender    = createUser();
        $recipient = createUser();

        $sender->follow($recipient);

        $recipient->denyFollowRequestFrom($sender);

        $this->assertFalse($recipient->isFollowedBy($sender));

        //fr has been delete
        $this->assertCount(0, $recipient->getFollowerRequests());
        $this->assertCount(1, $sender->getDeniedRequestsToFollow());
    }

    /** @test */
    public function user_can_block_another_user()
    {
        $sender    = createUser();
        $recipient = createUser();

        $sender->blockBeingFollowedBy($recipient);

        $this->assertTrue($sender->hasBlockedBeingFollowedBy($recipient));
        //sender is not blocked by receipient
        $this->assertTrue($recipient->isBlockedFromFollowing($sender));
    }

    /** @test */
    public function user_can_unblock_a_blocked_user()
    {
        $sender    = createUser();
        $recipient = createUser();

        $sender->blockBeingFollowedBy($recipient);
        $sender->unblockBeingFollowedBy($recipient);

        $this->assertFalse($recipient->isBlockedFromBeingFollowedBy($sender));
        $this->assertFalse($sender->hasBlockedBeingFollowedBy($recipient));
    }

    /** @test */
    public function user_block_is_permanent_unless_blocker_decides_to_unblock()
    {
        $sender    = createUser();
        $recipient = createUser();

        $sender->blockBeingFollowedBy($recipient);
        $this->assertTrue($recipient->isBlockedFromFollowing($sender));

        // now recipient blocks sender too
        $recipient->blockBeingFollowedBy($sender);

        // expect that both users have blocked each other
        $this->assertTrue($sender->isBlockedFromFollowing($recipient));
        $this->assertTrue($recipient->isBlockedFromFollowing($sender));

        $sender->unblockBeingFollowedBy($recipient);

        $this->assertFalse($sender->isBlockedFromBeingFollowedBy($recipient));
        $this->assertTrue($recipient->isBlockedFromBeingFollowedBy($sender));

        $recipient->unblockBeingFollowedBy($sender);
        $this->assertFalse($sender->isBlockedFromBeingFollowedBy($recipient));
        $this->assertFalse($recipient->isBlockedFromBeingFollowedBy($sender));
    }

    /** @test */
    public function user_can_send_friend_request_to_user_who_is_blocked()
    {
        $sender    = createUser();
        $recipient = createUser();

        $sender->blockBeingFollowedBy($recipient);
        $sender->follow($recipient);
        $sender->follow($recipient);

        $this->assertCount(1, $recipient->getFollowerRequests());
    }

    /** @test */
    public function it_returns_all_user_follow_requests()
    {
        $sender     = createUser();
        $recipients = createUser([], 3);

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
        }

        $recipients[0]->acceptFollowRequestFrom($sender);
        $recipients[1]->acceptFollowRequestFrom($sender);
        $recipients[2]->denyFollowRequestFrom($sender);
        $this->assertCount(3, $sender->getAllFollowing());
    }

    /** @test */
    public function it_returns_number_of_accepted_user_following()
    {
        $sender     = createUser();
        $recipients = createUser([], 3);

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
        }

        $recipients[0]->acceptFollowRequestFrom($sender);
        $recipients[1]->acceptFollowRequestFrom($sender);
        $recipients[2]->denyFollowRequestFrom($sender);
        $this->assertEquals(2, $sender->getFollowingCount());
    }

    /** @test */
    public function it_returns_accepted_user_following()
    {
        $sender     = createUser();
        $recipients = createUser([], 3);

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
        }

        $recipients[0]->acceptFollowRequestFrom($sender);
        $recipients[1]->acceptFollowRequestFrom($sender);
        $recipients[2]->denyFollowRequestFrom($sender);
        $this->assertCount(2, $sender->getAcceptedRequestsToFollow());
        $this->assertTrue($recipients[0]->isFollowedBy($sender));
    }

    /** @test */
    public function it_returns_only_accepted_user_friendships()
    {
        $sender     = createUser();
        $recipients = createUser([], 4);

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

    /** @test */
    public function it_returns_pending_user_friendships()
    {
        $sender     = createUser();
        $recipients = createUser([], 3);

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
        }

        $recipients[0]->acceptFollowRequestFrom($sender);
        $this->assertCount(2, $sender->getPendingRequestsRequestsToFollow());
        $this->assertCount(1, $recipients[1]->getPendingRequestsToBeFollowed());
    }

    /** @test */
    public function it_returns_denied_user_friendships()
    {
        $sender     = createUser();
        $recipients = createUser([], 3);

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
        }

        $recipients[0]->acceptFollowRequestFrom($sender);
        $recipients[1]->acceptFollowRequestFrom($sender);
        $recipients[2]->denyFollowRequestFrom($sender);
        $this->assertCount(1, $sender->getDeniedRequestsToFollow());
        $this->assertCount(1, $recipients[2]->getDeniedRequestsToBeFollowed());
    }

    /** @test */
    public function it_returns_blocked_user_friendships()
    {
        $sender     = createUser();
        $recipients = createUser([], 3);

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
        }

        $recipients[0]->acceptFollowRequestFrom($sender);
        $recipients[1]->acceptFollowRequestFrom($sender);
        $recipients[2]->blockBeingFollowedBy($sender);
        $this->assertCount(1, $sender->getBlockedFollowing());
    }

    /** @test */
    public function it_returns_followed_users()
    {
        $sender     = createUser();
        $recipients = createUser([], 4);

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
    }

    /** @test */
    public function it_returns_user_follows_per_page()
    {
        $sender     = createUser();
        $recipients = createUser([], 6);

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
