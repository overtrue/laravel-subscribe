<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create(config('subscribe.subscriptions_table'), function (Blueprint $table) {
            if (config('subscribe.uuids')) {
                $table->uuid('id')->unique()->primary();
                $table->foreignUuid(config('subscribe.user_foreign_key'))->index()->comment('user_id');
            } else {
                $table->bigIncrements('id');
                $table->unsignedBigInteger(config('subscribe.user_foreign_key'))->index()->comment('user_id');
            }
            if (config('subscribe.uuids')) {
                $table->uuidMorphs('votable');
            } else {
                $table->morphs('subscribable');
            }
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists(config('subscribe.subscriptions_table'));
    }
}
