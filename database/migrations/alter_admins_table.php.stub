<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table(config('kit.admins_table_name'), function (Blueprint $table) {
            $table->string('locale')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists(config('kit.admins_table_name'));
    }
};
