<?php

declare(strict_types=1);

namespace Tests\Feature\MerchantPro;

use App\Models\Products;
use Illuminate\Support\Facades\Config;
use Tests\TenantTestCase;

class ProductFeedExportTest extends TenantTestCase
{
    public function test_exports_all_product_columns_as_semicolon_quoted_csv(): void
    {
        Products::withoutEvents(function (): void {
            Products::query()->create([
                'item_identifier' => 'SKU-FEED-1',
                'article_code' => 'ART-1',
                'article_name' => 'Name with "quotes" inside',
                'stock' => 3,
                'price' => 12.5,
                'status' => 'active',
                'image_url_1' => 'https://cdn.example.com/a.jpg',
            ]);
        });

        $domain = $this->tenant->domains->first()->domain;
        $response = $this->get("http://{$domain}/merchantpro/products/feed/export.csv");

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $lines = array_values(array_filter(explode("\n", trim($content, "\r\n"))));

        $this->assertNotEmpty($lines);

        $header = str_getcsv($lines[0], ';', '"', '\\');
        $this->assertContains('item_identifier', $header);
        $this->assertContains('article_name', $header);

        $this->assertStringStartsWith('"', $lines[0]);

        $dataRow = str_getcsv($lines[1], ';', '"', '\\');
        $articleNameIndex = array_search('article_name', $header, true);
        $this->assertNotFalse($articleNameIndex);
        $this->assertSame('Name with "quotes" inside', $dataRow[$articleNameIndex]);
    }

    public function test_exports_header_when_catalog_is_empty(): void
    {
        $domain = $this->tenant->domains->first()->domain;
        $response = $this->get("http://{$domain}/merchantpro/products/feed/export.csv");

        $response->assertOk();

        $content = trim($response->streamedContent(), "\r\n");
        $this->assertNotSame('', $content);

        $header = str_getcsv($content, ';', '"', '\\');
        $this->assertContains('id', $header);
    }

    public function test_excludes_products_that_fail_merchantpro_export_filters(): void
    {
        Products::withoutEvents(function (): void {
            Products::query()->create([
                'item_identifier' => 'SKU-NO-IMG',
                'article_name' => 'No image row',
                'status' => 'active',
            ]);
        });

        $domain = $this->tenant->domains->first()->domain;
        $response = $this->get("http://{$domain}/merchantpro/products/feed/export.csv");

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringNotContainsString('SKU-NO-IMG', $content);
    }

    public function test_includes_products_without_images_when_filters_are_empty(): void
    {
        Config::set('merchantpro_export.filters', []);

        Products::withoutEvents(function (): void {
            Products::query()->create([
                'item_identifier' => 'SKU-PLAIN',
                'article_name' => 'Plain row',
                'status' => 'draft',
            ]);
        });

        $domain = $this->tenant->domains->first()->domain;
        $response = $this->get("http://{$domain}/merchantpro/products/feed/export.csv");

        $response->assertOk();
        $this->assertStringContainsString('SKU-PLAIN', $response->streamedContent());
    }

    public function test_feed_on_central_host_initializes_tenant_from_config(): void
    {
        Config::set('merchantpro_export.tenant_id', $this->tenant->getKey());

        Products::withoutEvents(function (): void {
            Products::query()->create([
                'item_identifier' => 'SKU-CENTRAL-HOST',
                'article_name' => 'Central host row',
                'status' => 'active',
                'image_url_1' => 'https://example.com/x.jpg',
            ]);
        });

        $response = $this->get('http://127.0.0.1/merchantpro/products/feed/export.csv');

        $response->assertOk();
        $this->assertStringContainsString('SKU-CENTRAL-HOST', $response->streamedContent());
    }

    public function test_exports_well_formed_xml_with_same_filters_as_csv(): void
    {
        Products::withoutEvents(function (): void {
            Products::query()->create([
                'item_identifier' => 'SKU-XML-1',
                'article_code' => 'ART-XML',
                'article_name' => 'Product & Co <special>',
                'stock' => 2,
                'price' => 9.99,
                'status' => 'active',
                'image_url_1' => 'https://cdn.example.com/b.jpg',
            ]);
        });

        $domain = $this->tenant->domains->first()->domain;
        $response = $this->get("http://{$domain}/merchantpro/products/feed/export.xml");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/xml; charset=UTF-8');

        $xml = simplexml_load_string($response->streamedContent());
        $this->assertNotFalse($xml);
        $this->assertSame('products', $xml->getName());

        $products = $xml->xpath('/products/product');
        $this->assertCount(1, $products);
        $this->assertSame('SKU-XML-1', (string) $products[0]->item_identifier);
        $this->assertSame('Product & Co <special>', (string) $products[0]->article_name);
    }

    public function test_xml_feed_is_empty_when_no_products_match_filters(): void
    {
        Products::withoutEvents(function (): void {
            Products::query()->create([
                'item_identifier' => 'SKU-XML-NO-IMG',
                'article_name' => 'Filtered out',
                'status' => 'active',
            ]);
        });

        $domain = $this->tenant->domains->first()->domain;
        $response = $this->get("http://{$domain}/merchantpro/products/feed/export.xml");

        $response->assertOk();
        $xml = simplexml_load_string($response->streamedContent());
        $this->assertNotFalse($xml);
        $this->assertCount(0, $xml->xpath('/products/product'));
    }
}
