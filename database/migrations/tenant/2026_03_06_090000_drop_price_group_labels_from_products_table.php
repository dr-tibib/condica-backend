<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'price_group1_label',
                'price_group2_label',
                'price_group3_label',
                'price_group4_label',
                'price_group5_label',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('price_group1_label')->nullable()->after('price_bazaronline_old');
            $table->string('price_group2_label')->nullable()->after('price_group1_net');
            $table->string('price_group3_label')->nullable()->after('price_group2_net');
            $table->string('price_group4_label')->nullable()->after('price_group3_net');
            $table->string('price_group5_label')->nullable()->after('price_group4_net');
        });
    }
};
