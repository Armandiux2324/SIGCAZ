<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('registers', function (Blueprint $table) {
            $table->id();
            $table->enum('origin_type', ['national','state',]);
            $table->string('state');
            $table->string('municipality');
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->boolean('is_first_time');
            $table->unsignedInteger('participation_count')->default(0);
            $table->enum('attendance_type', ['alone','accompanied',]);
            $table->unsignedInteger('participant_count')->default(1);
            $table->enum('accommodation_type', ['airbnb','hotel','own_home','family_or_friends',]);
            $table->string('lodging')->nullable();
            $table->unsignedTinyInteger('stay_days');
            $table->enum('folio_delivery_method', ['email','phone']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registers');
    }
};
