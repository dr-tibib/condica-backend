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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('item_identifier'); // Identificatorul_articolului
            $table->string('article_code')->nullable(); // Codul_articolului
            $table->string('article_name'); // Denumirea_articolului
            $table->string('external_reference_id')->nullable(); // ID referinta externa
            $table->text('description')->nullable(); // Descriere
            $table->decimal('price', 10, 2)->nullable(); // Pret
            $table->decimal('product_price_net', 10, 2)->nullable(); // Pret produs (net)
            $table->decimal('old_price', 10, 2)->nullable(); // Pret vechi
            $table->decimal('old_price_net', 10, 2)->nullable(); // Pret vechi (net)
            $table->decimal('tax_value', 10, 2)->nullable(); // Valoare taxa
            $table->string('main_category')->nullable(); // Categorie principala
            $table->string('manufacturer')->nullable(); // Producator
            $table->string('supplier')->nullable(); // Furnizor
            $table->string('image_link')->nullable(); // Link-ul_imaginii
            $table->string('meta_title')->nullable(); // Titlu meta
            $table->text('meta_description')->nullable(); // Descriere meta
            $table->json('images')->nullable(); // Imagini
            $table->integer('quantity')->nullable(); // Cantitate
            $table->string('product_url')->nullable(); // URL produs
            $table->integer('stock')->nullable(); // Stoc
            $table->string('availability')->nullable(); // Disponibilitate
            $table->string('status')->nullable(); // Status
            $table->string('visibility')->nullable(); // Vizibilitate
            $table->text('keywords')->nullable(); // Cuvinte cheie (tag-uri)
            $table->timestamp('added_at')->nullable(); // Data adaugarii
            $table->string('currency', 3)->nullable(); // Moneda
            $table->string('image_url_1')->nullable(); // URL imagine 1
            $table->string('image_url_2')->nullable(); // URL imagine 2
            $table->string('image_url_3')->nullable(); // URL imagine 3
            $table->string('image_url_4')->nullable(); // URL imagine 4
            $table->string('image_url_5')->nullable(); // URL imagine 5
            $table->string('image_url_6')->nullable(); // URL imagine 6
            $table->string('image_url_8')->nullable(); // URL imagine 8
            $table->string('image_url_9')->nullable(); // URL imagine 9
            $table->string('image_url_10')->nullable(); // URL imagine 10

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
