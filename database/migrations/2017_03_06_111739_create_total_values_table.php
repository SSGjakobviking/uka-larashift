<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTotalValuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('total_values', function (Blueprint $table) {
            $table->increments('id')->unsigned();
            $table->integer('total_id')->unsigned();
            $table->foreign('total_id')->references('id')->on('totals')->onDelete('cascade');

            $table->integer('column_id')->unsigned();
            $table->foreign('column_id')->references('id')->on('total_columns')->onDelete('cascade');

            $table->double('value')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('total_values');
    }
}
