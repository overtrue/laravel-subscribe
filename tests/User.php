<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelSubscribe\Traits\Subscribable;
use Overtrue\LaravelSubscribe\Traits\Subscriber;

class User extends Model
{
    use Subscribable;
    use Subscriber;

    protected $fillable = ['name'];
}
