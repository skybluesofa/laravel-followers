# Laravel 5 Followers
[![Build Status](https://travis-ci.org/skybluesofa/laravel-followers.svg?branch=master)](https://travis-ci.org/skybluesofa/laravel-followers) [![Code Climate](https://codeclimate.com/github/skybluesofa/laravel-followers/badges/gpa.svg)](https://codeclimate.com/github/skybluesofa/laravel-followers) [![Test Coverage](https://codeclimate.com/github/skybluesofa/laravel-followers/badges/coverage.svg)](https://codeclimate.com/github/skybluesofa/laravel-followers/coverage) [![Total Downloads](https://img.shields.io/packagist/dt/skybluesofa/laravel-followers.svg?style=flat)](https://packagist.org/packages/skybluesofa/laravel-followers) [![Version](https://img.shields.io/packagist/v/skybluesofa/laravel-followers.svg?style=flat)](https://packagist.org/packages/skybluesofa/laravel-followers) [![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat)](LICENSE)


Gives Eloquent models the ability to manage their followers.

##Models can:
- Send Follow Requests
- Accept Follow Requests
- Deny Follow Requests
- Block Another Model

## Installation

First, install the package through Composer.

```php
composer require skybluesofa/laravel-followers
```

Then include the service provider inside `config/app.php`.

```php
'providers' => [
    ...
    Skybluesofa\Followers\ServiceProvider::class,
    ...
];
```
Publish config and migrations

```
php artisan vendor:publish --provider="Skybluesofa\Followers\ServiceProvider"
```
Configure the published config in
```
config\followers.php
```
Finally, migrate the database
```
php artisan migrate
```

## Setup a Model
```php
use Skybluesofa\Followers\Traits\Followable;
class User extends Model
{
    use Followable;
    ...
}
```

## How to use
[Check the Test file to see the package in action](https://github.com/skybluesofa/laravel-followers/blob/master/tests/FollowersTest.php)

### Methods

#### Send a Follow Request

Will trigger a `Skybluesofa\LaravelFollowers\Events\FollowRequest` event.
```php
$user->follow($recipient);
```

#### Accept a Follow Request

Will trigger a `Skybluesofa\LaravelFollowers\Events\FollowRequestAccepted` event.
```php
$recipient->acceptFollowRequestFrom($user);
```

#### Deny a Follow Request

Will trigger a `Skybluesofa\LaravelFollowers\Events\FollowRequestDenied` event.
```php
$recipient->denyFollowRequestFrom($user);
```

#### Remove Follow

Will trigger a `Skybluesofa\LaravelFollowers\Events\Unfollow` event.
```php
$user->unfollow($recipient);
```

#### Block a User

Will trigger a `Skybluesofa\LaravelFollowers\Events\FollowingBlocked` event.
```php
$user->blockBeingFollowedBy($recipient);
```

#### Unblock a User

Will trigger a `Skybluesofa\LaravelFollowers\Events\FollowingUnblocked` event.
```php
$user->unblockBeingFollowedBy($recipient);
```

#### Check if User is Following another User
```php
$user->isFollowing($recipient);
```

#### Check if User is being Followed by another User
```php
$recipient->isFollowedBy($user);
```

#### Check if User has a pending Follow request from another User
```php
$recipient->hasFollowRequestFrom($user);
```

#### Check if User sent a pending Follow request to another User
```php
$user->hasSentFollowRequestTo($recipient);
```

#### Check if User has blocked another User
```php
$recipient->hasBlockedBeingFollowedBy($user);
```

#### Check if User is blocked by another User
```php
$user->isBlockedFromFollowing($recipient);
```

#### Get a single friendship
```php
$user->getFriendship($recipient);
```

#### Get a list of all Friendships
```php
$user->getAllFriendships();
```

#### Get a list of pending Friendships
```php
$user->getPendingFriendships();
```

#### Get a list of accepted Friendships
```php
$user->getAcceptedFriendships();
```

#### Get a list of denied Friendships
```php
$user->getDeniedFriendships();
```

#### Get a list of blocked Friendships
```php
$user->getBlockedFriendships();
```

#### Get a list of pending Friend Requests
```php
$user->getFriendRequests();
```

#### Get the number of Friends
```php
$user->getFriendsCount();
```

## Friends
To get a collection of friend models (ex. User) use the following methods:
#### Get Friends
```php
$user->getFriends();
```

#### Get Friends Paginated
```php
$user->getFriends($perPage = 20);
```

### Events

These events are triggered during the lifecycle of following/unfollowing/accept/deny followers:

```php
Skybluesofa\LaravelFollowers\Events\FollowingBlocked(Model $recipient, Model $sender);
Skybluesofa\LaravelFollowers\Events\FollowingUnblocked(Model $recipient, Model $sender);
Skybluesofa\LaravelFollowers\Events\FollowRequest(Model $recipient, Model $sender);
Skybluesofa\LaravelFollowers\Events\FollowRequestAccepted(Model $recipient, Model $sender);
Skybluesofa\LaravelFollowers\Events\FollowRequestDenied(Model $recipient, Model $sender);
Skybluesofa\LaravelFollowers\Events\Unfollow(Model $recipient, Model $sender);
```

To listen for and react to these events, follow the [instructions available in the Laravel Documentation](https://laravel.com/docs/7.x/events#defining-listeners).

## Thank you
The basis of this code was garnered from [https://github.com/hootlex/laravel-friendships](https://github.com/hootlex/laravel-friendships). Although it was a jumping off point, much of the code has been rewritten to allow for Following as opposed to Mutual Friendship.

## Contributing
See the [CONTRIBUTING](CONTRIBUTING.md) guide.
