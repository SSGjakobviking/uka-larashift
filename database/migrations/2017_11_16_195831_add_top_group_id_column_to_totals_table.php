<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTopGroupIdColumnToTotalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('totals', function (Blueprint $table) {
            $table->integer('top_group_id')->unsigned()->nullable()->after('group_parent_id');
            $table->foreign('top_group_id')->references('id')->on('groups');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('totals', function (Blueprint $table) {
            $table->dropIndex('top_group_id');
            $table->dropColumn('top_group_id');
        });
    }
}
