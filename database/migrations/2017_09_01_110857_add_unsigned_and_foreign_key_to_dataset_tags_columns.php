<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUnsignedAndForeignKeyToDatasetTagsColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dataset_tags', function (Blueprint $table) {
            $table->integer('dataset_id')->unsigned()->change();
            $table->foreign('dataset_id')->references('id')->on('datasets')->onDelete('cascade')->change();

            $table->integer('tag_id')->unsigned()->change();
            $table->foreign('tag_id')->references('id')->on('tags')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dataset_tags', function (Blueprint $table) {
            $table->dropForeign('dataset_tags_dataset_id_foreign');
            $table->dropForeign('dataset_tags_tag_id_foreign');
        });
    }
}
