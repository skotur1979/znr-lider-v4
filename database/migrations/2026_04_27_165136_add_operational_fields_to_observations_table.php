<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('observations', function (Blueprint $table) {
            $table->string('priority')->default('medium')->after('observation_type');
            $table->json('notification_emails')->nullable()->after('responsible');
            $table->timestamp('sent_at')->nullable()->after('notification_emails');
            $table->text('voice_note')->nullable()->after('comments');
        });
    }

    public function down(): void
    {
        Schema::table('observations', function (Blueprint $table) {
            $table->dropColumn([
                'priority',
                'notification_emails',
                'sent_at',
                'voice_note',
            ]);
        });
    }
};