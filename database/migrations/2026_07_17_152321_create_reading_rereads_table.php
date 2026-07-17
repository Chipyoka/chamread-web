<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{

    public function up(): void
    {

        Schema::create('reading_rereads', function (Blueprint $table) {


            $table->id();


            $table->foreignId('reading_id')
                ->constrained('readings')
                ->cascadeOnDelete();


            $table->foreignId('supervisor_id')
                ->constrained('users')
                ->cascadeOnDelete();


            $table->foreignId('billing_cycle_id')
                ->constrained('billing_cycles')
                ->cascadeOnDelete();



            $table->decimal('old_value',10,2)
                ->nullable();


            $table->decimal('new_value',10,2)
                ->nullable();



            $table->text('reason');


            $table->enum('status',[
                'pending',
                'completed',
                'cancelled'
            ])
            ->default('pending');


            $table->timestamps();


        });

    }



    public function down(): void
    {
        Schema::dropIfExists('reading_rereads');
    }

};