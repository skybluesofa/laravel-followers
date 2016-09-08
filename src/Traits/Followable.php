<?php
namespace Skybluesofa\Followers\Traits;

use Skybluesofa\Followers\Traits\CanFollow;
use Skybluesofa\Followers\Traits\CanBeFollowed;

/**
 * Class Followable
 * @package Skybluesofa\Followers\Traits
 */
trait Followable
{
    use CanFollow;
    use CanBeFollowed;
}
