<?php

//namespace App;

use Illuminate\Database\Eloquent\Model;

class Widget extends Model
{

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'widgets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name'];
}
