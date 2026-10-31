<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('voyager_activity_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('event_uuid')->unique();
            $table->dateTime('occurred_at', 6);
            $table->dateTime('recorded_at', 6);
            $table->string('category', 32);
            $table->string('event', 100);
            $table->string('result', 16);
            $table->string('actor_type', 191)->nullable();
            $table->string('actor_id', 191)->nullable();
            $table->string('subject_type', 191)->nullable();
            $table->string('subject_id', 191)->nullable();
            $table->string('resource', 191)->nullable();
            $table->json('changed_fields')->nullable();
            $table->json('metadata')->nullable();
            $table->uuid('request_id')->nullable();
            $table->uuid('operation_id')->nullable();
            $table->string('source', 16);
            $table->string('ip_address', 45)->nullable();
            $table->string('route_name', 191)->nullable();
            $table->unsignedSmallInteger('schema_version')->default(1);

            $table->index(['occurred_at', 'id'], 'activity_occurred_id_index');
            $table->index(['actor_type', 'actor_id', 'occurred_at'], 'activity_actor_index');
            $table->index(['subject_type', 'subject_id', 'occurred_at'], 'activity_subject_index');
            $table->index(['category', 'occurred_at'], 'activity_category_index');
            $table->index(['event', 'occurred_at'], 'activity_event_index');
            $table->index('operation_id', 'activity_operation_index');
            $table->index('request_id', 'activity_request_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voyager_activity_logs');
    }
};
