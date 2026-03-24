<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('available_emag')->default(false)->after('subcategory');
            $table->decimal('price_emag', 10, 2)->nullable()->after('available_emag');
            $table->decimal('price_emag_old', 10, 2)->nullable()->after('price_emag');

            $table->boolean('available_glovo')->default(false)->after('price_emag_old');
            $table->decimal('price_glovo', 10, 2)->nullable()->after('available_glovo');
            $table->decimal('price_glovo_old', 10, 2)->nullable()->after('price_glovo');

            $table->boolean('available_bazaronline')->default(false)->after('price_glovo_old');
            $table->decimal('price_bazaronline', 10, 2)->nullable()->after('available_bazaronline');
            $table->decimal('price_bazaronline_old', 10, 2)->nullable()->after('price_bazaronline');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'available_emag', 'price_emag', 'price_emag_old',
                'available_glovo', 'price_glovo', 'price_glovo_old',
                'available_bazaronline', 'price_bazaronline', 'price_bazaronline_old',
            ]);
        });
    }
};
