<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('bunny_image_mappings')->nullable()->after('old_image_sources');
        });

        $this->backfillBunnyMappingsFromOldSources();
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('bunny_image_mappings');
        });
    }

    /**
     * For products that already have Bunny URLs in image fields, build mappings from
     * old_image_sources so we don't re-upload if those fields are ever reverted.
     */
    private function backfillBunnyMappingsFromOldSources(): void
    {
        $cdnPattern = '.b-cdn.net/';

        DB::table('products')->whereNotNull('old_image_sources')->orderBy('id')->each(function ($row) use ($cdnPattern) {
            $oldSources = json_decode($row->old_image_sources, true);
            if (! is_array($oldSources)) {
                return;
            }

            $mappings = [];
            foreach ($oldSources as $field => $sources) {
                $currentValue = $row->{$field} ?? null;
                if (! is_string($currentValue) || strpos($currentValue, $cdnPattern) === false) {
                    continue;
                }
                $bunnyUrl = trim($currentValue);
                $list = is_array($sources) ? $sources : [$sources];
                foreach ($list as $source) {
                    if (is_string($source) && trim($source) !== '') {
                        $mappings[$field][trim($source)] = $bunnyUrl;
                    }
                }
            }

            if ($mappings !== []) {
                DB::table('products')->where('id', $row->id)->update([
                    'bunny_image_mappings' => json_encode($mappings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ]);
            }
        });
    }
};
