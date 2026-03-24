<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('category')->nullable()->after('main_category');
            $table->string('subcategory')->nullable()->after('category');
        });

        // Populate from existing main_category data ("CATEGORY > SUBCATEGORY")
        DB::table('products')->whereNotNull('main_category')->orderBy('id')->each(function ($row) {
            $parts = array_map('trim', explode('>', $row->main_category, 2));
            DB::table('products')->where('id', $row->id)->update([
                'category' => $parts[0] ?? null,
                'subcategory' => $parts[1] ?? null,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['category', 'subcategory']);
        });
    }
};
