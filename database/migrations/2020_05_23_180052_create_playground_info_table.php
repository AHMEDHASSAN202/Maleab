<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlaygroundInfoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('playground_info', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('playground_id');
            $table->double('lat');
            $table->double('long');
            $table->decimal('price_day')->nullable();
            $table->decimal('price_night')->nullable();
            $table->text('images')->nullable();
            $table->timestamps();

            $table->foreign('playground_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('playground_info');
    }
}
