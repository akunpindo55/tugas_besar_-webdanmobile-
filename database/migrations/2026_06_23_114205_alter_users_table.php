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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->unique()->after('id');
            $table->string('avatar', 255)->nullable()->after('password');
            $table->text('bio')->nullable()->after('avatar');
            $table->enum('role', ['student', 'admin'])->default('student')->after('bio');
            $table->timestamp('last_seen_at')->nullable()->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'avatar', 'bio', 'role', 'last_seen_at']);
        });
    }
};
