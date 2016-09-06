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

        $this->assertFalse($sender->hasSentFollowRequestTo($recipient));
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
        $this->assertCount(1, $sender->getDeniedFollowerRequest());
    }

    /** @test */
    public function user_can_block_another_user()
    {
        $sender    = createUser();
        $recipient = createUser();

        $sender->blockFollowed($recipient);

        $this->assertTrue($recipient->isBlockedByFollower($sender));
        $this->assertTrue($sender->hasBlockedFollowed($recipient));
        //sender is not blocked by receipient
        $this->assertFalse($sender->isBlockedByFollowed($recipient));
        $this->assertFalse($recipient->hasBlockedFollower($sender));
    }

    /** @test */
    public function user_can_unblock_a_blocked_user()
    {
        $sender    = createUser();
        $recipient = createUser();

        $sender->blockFollowed($recipient);
        $sender->unblockFollowed($recipient);

        $this->assertFalse($recipient->isBlockedByFollower($sender));
        $this->assertFalse($sender->hasBlockedFollowed($recipient));
    }

    /** @test */
    public function user_block_is_permanent_unless_blocker_decides_to_unblock()
    {
        $sender    = createUser();
        $recipient = createUser();

        $sender->blockFollowed($recipient);
        $this->assertTrue($recipient->isBlockedByFollower($sender));

        // now recipient blocks sender too
        $recipient->blockFollower($sender);

        // expect that both users have blocked each other
        $this->assertTrue($sender->isBlockedByFollowed($recipient));
        $this->assertTrue($recipient->isBlockedByFollower($sender));

        $sender->unblockFollowed($recipient);

        $this->assertTrue($sender->isBlockedByFollowed($recipient));
        $this->assertFalse($recipient->isBlockedByFollower($sender));

        $recipient->unblockFollower($sender);
        $this->assertFalse($sender->isBlockedByFollowed($recipient));
        $this->assertFalse($recipient->isBlockedByFollower($sender));
    }

    /** @test */
    public function user_can_send_friend_request_to_user_who_is_blocked()
    {
        $sender    = createUser();
        $recipient = createUser();

        $sender->blockFollowed($recipient);
        $sender->follow($recipient);
        $sender->follow($recipient);

        $this->assertCount(1, $recipient->getFollowerRequests());
    }

    /** @test */
    public function it_returns_all_user_friendships()
    {
        $sender     = createUser();
        $recipients = createUser([], 3);

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
        }

        $recipients[0]->acceptFollowRequestFrom($sender);
        $recipients[1]->acceptFollowRequestFrom($sender);
        $recipients[2]->denyFollowRequestFrom($sender);
        $this->assertCount(3, $sender->getAllFollowed());
    }

    /** @test */
    public function it_returns_accepted_user_friendships_number()
    {
        $sender     = createUser();
        $recipients = createUser([], 3);

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
        }

        $recipients[0]->acceptFollowRequestFrom($sender);
        $recipients[1]->acceptFollowRequestFrom($sender);
        $recipients[2]->denyFollowRequestFrom($sender);
        $this->assertEquals(2, $sender->getFollowedCount());
    }

    /** @test */
    public function it_returns_accepted_user_friendships()
    {
        $sender     = createUser();
        $recipients = createUser([], 3);

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
        }

        $recipients[0]->acceptFollowRequestFrom($sender);
        $recipients[1]->acceptFollowRequestFrom($sender);
        $recipients[2]->denyFollowRequestFrom($sender);
        $this->assertCount(2, $sender->getAcceptedFollowerRequests());
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
        $this->assertCount(2, $sender->getAcceptedFollowerRequests());

        $this->assertCount(1, $recipients[0]->getAcceptedFollowerRequests());
        $this->assertCount(1, $recipients[1]->getAcceptedFollowerRequests());
        $this->assertCount(0, $recipients[2]->getAcceptedFollowerRequests());
        $this->assertCount(0, $recipients[3]->getAcceptedFollowerRequests());
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
        $this->assertCount(2, $sender->getPendingFollowerRequests());
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
        $this->assertCount(1, $sender->getDeniedFollowerRequests());
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
        $recipients[2]->blockFollower($sender);
        $this->assertCount(1, $sender->getBlockedFollowed());
    }

    /** @test */
    private function it_returns_user_friends()
    {
        $sender     = createUser();
        $recipients = createUser([], 4);

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
        }

        $recipients[0]->acceptFollowRequestFrom($sender);
        $recipients[1]->acceptFollowRequestFrom($sender);
        $recipients[2]->denyFollowRequestFrom($sender);

        $this->assertCount(2, $sender->getFollowing());
        $this->assertCount(1, $recipients[1]->getFollowing());
        $this->assertCount(0, $recipients[2]->getFollowing());
        $this->assertCount(0, $recipients[3]->getFollowing());

        $this->containsOnlyInstancesOf(\App\User::class, $sender->getFriends());
    }

    /** @test */
    private function it_returns_user_friends_per_page()
    {
        $sender     = createUser();
        $recipients = createUser([], 6);

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
        }

        $recipients[0]->acceptFriendRequest($sender);
        $recipients[1]->acceptFriendRequest($sender);
        $recipients[2]->denyFriendRequest($sender);
        $recipients[3]->acceptFriendRequest($sender);
        $recipients[4]->acceptFriendRequest($sender);


        $this->assertCount(2, $sender->getFriends(2));
        $this->assertCount(4, $sender->getFriends(0));
        $this->assertCount(4, $sender->getFriends(10));
        $this->assertCount(1, $recipients[1]->getFriends());
        $this->assertCount(0, $recipients[2]->getFriends());
        $this->assertCount(0, $recipients[5]->getFriends(2));

        $this->containsOnlyInstancesOf(\App\User::class, $sender->getFriends());
    }

    /** @test */
    private function it_returns_user_friends_of_friends()
    {
        $sender     = createUser();
        $recipients = createUser([], 2);
        $fofs       = createUser([], 5)->chunk(3);

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
            $recipient->acceptFriendRequest($sender);

            //add some friends to each recipient too
            foreach ($fofs->shift() as $fof) {
                $recipient->follow($fof);
                $fof->acceptFriendRequest($recipient);
            }
        }

        $this->assertCount(2, $sender->getFriends());
        $this->assertCount(4, $recipients[0]->getFriends());
        $this->assertCount(3, $recipients[1]->getFriends());

        $this->assertCount(5, $sender->getFriendsOfFriends());

        $this->containsOnlyInstancesOf(\App\User::class, $sender->getFriendsOfFriends());
    }

    /** @test */
    private function it_returns_user_mutual_friends()
    {
        $sender     = createUser();
        $recipients = createUser([], 2);
        $fofs       = createUser([], 5)->chunk(3);

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
            $recipient->acceptFriendRequest($sender);

            //add some friends to each recipient too
            foreach ($fofs->shift() as $fof) {
                $recipient->follow($fof);
                $fof->acceptFriendRequest($recipient);
                $fof->befriend($sender);
                $sender->acceptFriendRequest($fof);
            }
        }

        $this->assertCount(3, $sender->getMutualFriends($recipients[0]));
        $this->assertCount(3, $recipients[0]->getMutualFriends($sender));

        $this->assertCount(2, $sender->getMutualFriends($recipients[1]));
        $this->assertCount(2, $recipients[1]->getMutualFriends($sender));

        $this->containsOnlyInstancesOf(\App\User::class, $sender->getMutualFriends($recipients[0]));
    }

    /** @test */
    private function it_returns_user_mutual_friends_per_page()
    {
        $sender     = createUser();
        $recipients = createUser([], 2);
        $fofs       = createUser([], 8)->chunk(5);

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
            $recipient->acceptFriendRequest($sender);

            //add some friends to each recipient too
            foreach ($fofs->shift() as $fof) {
                $recipient->follow($fof);
                $fof->acceptFriendRequest($recipient);
                $fof->follow($sender);
                $sender->acceptFriendRequest($fof);
            }
        }

        $this->assertCount(2, $sender->getMutualFriends($recipients[0], 2));
        $this->assertCount(5, $sender->getMutualFriends($recipients[0], 0));
        $this->assertCount(5, $sender->getMutualFriends($recipients[0], 10));
        $this->assertCount(2, $recipients[0]->getMutualFriends($sender, 2));
        $this->assertCount(5, $recipients[0]->getMutualFriends($sender, 0));
        $this->assertCount(5, $recipients[0]->getMutualFriends($sender, 10));

        $this->assertCount(1, $recipients[1]->getMutualFriends($recipients[0], 10));

        $this->containsOnlyInstancesOf(\App\User::class, $sender->getMutualFriends($recipients[0], 2));
    }

    /** @test */
    private function it_returns_user_mutual_friends_number()
    {
        $sender     = createUser();
        $recipients = createUser([], 2);
        $fofs       = createUser([], 5)->chunk(3);

        foreach ($recipients as $recipient) {
            $sender->follow($recipient);
            $recipient->acceptFriendRequest($sender);

            //add some friends to each recipient too
            foreach ($fofs->shift() as $fof) {
                $recipient->befriend($fof);
                $fof->acceptFriendRequest($recipient);
                $fof->follow($sender);
                $sender->acceptFriendRequest($fof);
            }
        }

        $this->assertEquals(3, $sender->getMutualFriendsCount($recipients[0]));
        $this->assertEquals(3, $recipients[0]->getMutualFriendsCount($sender));

        $this->assertEquals(2, $sender->getMutualFriendsCount($recipients[1]));
        $this->assertEquals(2, $recipients[1]->getMutualFriendsCount($sender));
    }
}
