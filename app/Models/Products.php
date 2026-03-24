<?php

namespace App\Models;

use App\Services\BunnyProductImageService;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class Products extends Model
{
    use CrudTrait;
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'products';

    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    // protected $fillable = [];
    // protected $hidden = [];

    protected static function booted(): void
    {
        static::saved(function (Products $product): void {
            app(BunnyProductImageService::class)->syncProductImages($product, null, false);
        });

        static::deleted(function (Products $product): void {
            app(BunnyProductImageService::class)->deleteAllProductImagesForProduct($product);
        });
    }

    protected function casts(): array
    {
        return [
            'available_emag' => 'boolean',
            'available_glovo' => 'boolean',
            'available_bazaronline' => 'boolean',
            'old_image_sources' => 'array',
            'bunny_image_mappings' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    protected $appends = ['has_images'];

    public static function imageFields(): array
    {
        return [
            'image_link',
            'image_url_1',
            'image_url_2',
            'image_url_3',
            'image_url_4',
            'image_url_5',
            'image_url_6',
            'image_url_8',
            'image_url_9',
            'image_url_10',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getHasImagesAttribute(): bool
    {
        foreach (self::imageFields() as $field) {
            if (! empty($this->attributes[$field])) {
                return true;
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */

    public function setAttribute($key, $value)
    {
        if (in_array($key, self::imageFields(), true) && $value instanceof UploadedFile) {
            $oldSource = $this->getAttribute($key) ?: $this->getOriginal($key);
            $uploadedUrl = app(BunnyProductImageService::class)->uploadUploadedFile($value, $this, $key);

            $this->rememberOldImageSource($key, $oldSource);

            return parent::setAttribute($key, $uploadedUrl);
        }

        return parent::setAttribute($key, $value);
    }

    public function rememberOldImageSource(string $field, mixed $source): void
    {
        $normalizedSources = $this->normalizeOldImageSources($source);

        if ($normalizedSources === []) {
            return;
        }

        $existing = $this->old_image_sources ?? [];
        if (! is_array($existing)) {
            $existing = [];
        }

        foreach ($normalizedSources as $normalizedSource) {
            if (! in_array($normalizedSource, $existing, true)) {
                $existing[] = $normalizedSource;
            }
        }

        parent::setAttribute('old_image_sources', $existing);
    }

    private function normalizeOldImageSources(mixed $source): array
    {
        if (is_string($source)) {
            $trimmed = trim($source);

            return $trimmed !== '' ? [$trimmed] : [];
        }

        if (! is_array($source)) {
            return [];
        }

        $normalized = [];

        foreach ($source as $item) {
            foreach ($this->normalizeOldImageSources($item) as $normalizedItem) {
                if (! in_array($normalizedItem, $normalized, true)) {
                    $normalized[] = $normalizedItem;
                }
            }
        }

        return $normalized;
    }

    /**
     * Return the Bunny CDN URL for a source URL if it was already uploaded, null otherwise.
     * Uses normalized (trimmed) source for lookup.
     */
    public function getBunnyUrlForSource(string $field, string $sourceUrl): ?string
    {
        $key = trim($sourceUrl);

        $mappings = $this->bunny_image_mappings ?? [];

        // First check global mapping (shared across all fields)
        $global = $mappings['__all__'] ?? [];
        if (is_array($global) && isset($global[$key]) && is_string($global[$key])) {
            return $global[$key];
        }

        // Then check field-specific mapping
        $fieldMappings = $mappings[$field] ?? [];
        if (! is_array($fieldMappings)) {
            return null;
        }

        return $fieldMappings[$key] ?? null;
    }

    /**
     * Store that a source URL was uploaded to the given Bunny CDN URL (so we skip re-uploading).
     */
    public function recordBunnyMapping(string $field, string $sourceUrl, string $bunnyUrl): void
    {
        $key = trim($sourceUrl);
        $mappings = $this->bunny_image_mappings ?? [];

        // Global mapping
        $global = $mappings['__all__'] ?? [];
        if (! is_array($global)) {
            $global = [];
        }
        $global[$key] = $bunnyUrl;
        $mappings['__all__'] = $global;

        // Field-specific mapping
        $fieldMappings = $mappings[$field] ?? [];
        if (! is_array($fieldMappings)) {
            $fieldMappings = [];
        }
        $fieldMappings[$key] = $bunnyUrl;
        $mappings[$field] = $fieldMappings;

        $this->bunny_image_mappings = $mappings;
    }
}
