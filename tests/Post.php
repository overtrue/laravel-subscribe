<?php

/*
 * This file is part of the overtrue/laravel-subscribe.
 *
 * (c) overtrue <anzhengchao@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelSubscribe\Traits\Subscribable;

/**
 * Class Post.
 */
class Post extends Model
{
    use Subscribable;

    protected $fillable = ['title'];
}
