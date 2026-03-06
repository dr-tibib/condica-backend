<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('price_group1_label')->nullable()->after('price_bazaronline_old');
            $table->decimal('price_group1', 10, 2)->nullable()->after('price_group1_label');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['price_group1_label', 'price_group1']);
        });
    }
};
