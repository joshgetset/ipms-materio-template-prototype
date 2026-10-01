<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disclosure_attachments', function (Blueprint $table) {
            $table->unique(
                ['innovation_disclosure_id', 'type'],
                'disclosure_attachments_slot_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('disclosure_attachments', function (Blueprint $table) {
            $table->dropUnique('disclosure_attachments_slot_unique');
        });
    }
};