<?php
/**
 * Create a user
 *
 * @param array $overrides
 * @param int   $amount
 *
 * @return \App\User
 */
function createUser($overrides = [], $amount = 1){
    return factory(\App\User::class, $amount)->create($overrides);
}

function createWidget($overrides = [], $amount = 1){
    require_once(__dir__."/Widget.php");
    return new Widget();
}
