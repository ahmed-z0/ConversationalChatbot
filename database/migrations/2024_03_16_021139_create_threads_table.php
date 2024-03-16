<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateThreadsTable extends Migration
{
    public function up()
    {
        Schema::create('threads', function (Blueprint $table) {
            $table->increments('id');
            $table->string('assistant_id');
            $table->string('thread_id');
            $table->timestamps(); // Includes both created_at and updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('threads');
    }
}
