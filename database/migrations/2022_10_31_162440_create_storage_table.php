<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('folders', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('user_id');
            $table->timestamps();
            
            $table->unique(['name', 'user_id']);
        });

        Schema::create('files', function (Blueprint $table) {
            $table->increments('id');
            $table->text('name');
            $table->text('original_name');
            $table->string('mime', 100)->default('');
            $table->string('extension')->nullable();
            $table->bigInteger('size')->default(0);
            $table->text('path');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('folder_id')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'folder_id']);

            $table->foreign('folder_id')
                ->references('id')
                ->on('folders')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('folders');
        Schema::dropIfExists('files');
    }
};
