<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmailQueue extends Migration {

	public function up()
	{
        Schema::create("email_queue", function(Blueprint $table) {
            $table->integer('id',true);
            $table->text('to');
            $table->text('cc');
            $table->string('from');
            $table->string('from_name');
            $table->string('subject');
            $table->text('message');
            $table->integer('sent_flag');
            $table->string('email_template');
            $table->text('data');
            $table->timestamps();
            $table->softDeletes();
        });
	}

	public function down()
	{
        Schema::dropIfExists("email_queue");
	}

}
