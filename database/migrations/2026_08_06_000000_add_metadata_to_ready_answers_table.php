<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ready_answers', function (Blueprint $table) {
            $table->string('category', 60)->nullable()->after('title');
            $table->boolean('is_pinned')->default(false)->after('body');
            $table->unsignedInteger('usage_count')->default(0)->after('is_pinned');
            $table->timestamp('last_used_at')->nullable()->after('usage_count');

            $table->index(['is_pinned', 'usage_count']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::table('ready_answers', function (Blueprint $table) {
            $table->dropIndex(['is_pinned', 'usage_count']);
            $table->dropIndex(['category']);
            $table->dropColumn(['category', 'is_pinned', 'usage_count', 'last_used_at']);
        });
    }
};
