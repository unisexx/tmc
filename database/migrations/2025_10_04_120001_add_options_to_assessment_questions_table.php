<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_questions', function (Blueprint $t) {
            if (!Schema::hasColumn('assessment_questions', 'options')) {
                $t->text('options')->nullable()->after('answer_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assessment_questions', function (Blueprint $t) {
            if (Schema::hasColumn('assessment_questions', 'options')) {
                $t->dropColumn('options');
            }
        });
    }
};
