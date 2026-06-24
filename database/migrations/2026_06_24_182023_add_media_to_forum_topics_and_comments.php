<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('forum_topics', function (Blueprint $table) {
            $table->string('file_url')->nullable()->after('content');
            $table->string('media_type')->nullable()->after('file_url');
        });

        Schema::table('forum_comments', function (Blueprint $table) {
            $table->string('file_url')->nullable()->after('content');
            $table->string('media_type')->nullable()->after('file_url');
        });
    }

    public function down(): void
    {
        Schema::table('forum_topics', function (Blueprint $table) {
            $table->dropColumn(['file_url', 'media_type']);
        });

        Schema::table('forum_comments', function (Blueprint $table) {
            $table->dropColumn(['file_url', 'media_type']);
        });
    }
};
