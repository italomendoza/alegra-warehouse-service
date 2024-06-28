<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePendingIngredientsTable extends Migration
{
    public function up()
    {
        Schema::create('pending_ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('ingredient_name');
            $table->integer('required_quantity');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pending_ingredients');
    }
}

