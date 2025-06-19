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
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'meta_title')) {
                $table->string('meta_title', 255)->nullable()->after('slug');
            }
            if (!Schema::hasColumn('categories', 'meta_description')) {
                $table->text('meta_description')->nullable()->after('meta_title');
            }
            if (!Schema::hasColumn('categories', 'status')) {
                $table->enum('status', ['active', 'inactive', 'banned'])->default('active')->after('meta_title');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'meta_title')) {
                $table->dropColumn('meta_title');
            }
            if (Schema::hasColumn('categories', 'meta_description')) {
                $table->dropColumn('meta_description');
            }
            if (Schema::hasColumn('categories', 'status')) {
                $table->dropColumn('meta_description');
            }
        });
    }
};
