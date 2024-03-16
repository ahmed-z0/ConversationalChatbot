<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssistantsTable extends Migration
{
    public function up()
    {
        Schema::create('assistants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('assistant_id')->unique();
            $table->integer('status')->default(1);
            $table->timestamps(); // Includes both created_at and updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('assistants');
    }
}
