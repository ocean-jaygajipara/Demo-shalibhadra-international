<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            if (!Schema::hasColumn('inquiries', 'whatsapp_general_sent_count')) {
                $table->unsignedInteger('whatsapp_general_sent_count')->default(0)->after('whatsapp_no');
            }
            if (!Schema::hasColumn('inquiries', 'last_whatsapp_general_sent_at')) {
                $table->dateTime('last_whatsapp_general_sent_at')->nullable()->after('whatsapp_general_sent_count');
            }
            if (!Schema::hasColumn('inquiries', 'last_whatsapp_general_sent_by')) {
                $table->string('last_whatsapp_general_sent_by')->nullable()->after('last_whatsapp_general_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            if (Schema::hasColumn('inquiries', 'whatsapp_general_sent_count')) {
                $table->dropColumn('whatsapp_general_sent_count');
            }
            if (Schema::hasColumn('inquiries', 'last_whatsapp_general_sent_at')) {
                $table->dropColumn('last_whatsapp_general_sent_at');
            }
            if (Schema::hasColumn('inquiries', 'last_whatsapp_general_sent_by')) {
                $table->dropColumn('last_whatsapp_general_sent_by');
            }
        });
    }
};
