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
            $table->double('lat')->nullable();
            $table->double('long')->nullable();
            $table->decimal('price_day')->nullable();
            $table->decimal('price_night')->nullable();
            $table->enum('status', ['open', 'close'])->default('open');
            $table->timestamp('closing_date')->nullable();
            $table->timestamp('open_date')->nullable();
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
