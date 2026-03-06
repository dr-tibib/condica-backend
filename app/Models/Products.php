<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    protected function casts(): array
    {
        return [
            'available_emag' => 'boolean',
            'available_glovo' => 'boolean',
            'available_bazaronline' => 'boolean',
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

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getHasImagesAttribute(): bool
    {
        $imageFields = [
            'image_link', 'image_url_1', 'image_url_2', 'image_url_3',
            'image_url_4', 'image_url_5', 'image_url_6', 'image_url_8',
            'image_url_9', 'image_url_10',
        ];

        foreach ($imageFields as $field) {
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
}
