<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE documents MODIFY COLUMN document_type ENUM(
            'demand_letter',
            'formal_notice',
            'response_letter',
            'cease_desist',
            'notice_to_cure',
            'complaint_letter',
            'cease_and_desist',
            'custom',
            'uploaded'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE documents MODIFY COLUMN document_type ENUM(
            'demand_letter',
            'notice_to_cure',
            'complaint_letter',
            'cease_and_desist',
            'custom',
            'uploaded'
        ) NOT NULL");
    }
};