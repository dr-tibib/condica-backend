<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Add net field for group 1 (label + price already exist)
            $table->decimal('price_group1_net', 10, 2)->nullable()->after('price_group1');

            // Groups 2–5
            foreach (range(2, 5) as $n) {
                $after = $n === 2 ? 'price_group1_net' : 'price_group'.($n - 1).'_net';
                $table->string("price_group{$n}_label")->nullable()->after($after);
                $table->decimal("price_group{$n}", 10, 2)->nullable()->after("price_group{$n}_label");
                $table->decimal("price_group{$n}_net", 10, 2)->nullable()->after("price_group{$n}");
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = ['price_group1_net'];
            foreach (range(2, 5) as $n) {
                $columns[] = "price_group{$n}_label";
                $columns[] = "price_group{$n}";
                $columns[] = "price_group{$n}_net";
            }
            $table->dropColumn($columns);
        });
    }
};
