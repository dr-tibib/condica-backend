<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ProductsRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ProductsCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;

    public function setup(): void
    {
        CRUD::setModel(\App\Models\Products::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/products/products');
        CRUD::setEntityNameStrings('product', 'products');
    }

    protected function setupListOperation(): void
    {
        CRUD::column('id')->label('ID')->type('number');
        CRUD::column('item_identifier')->label(__('products.fields.item_identifier'));
        CRUD::column('article_code')->label(__('products.fields.article_code'));
        CRUD::column('article_name')->label(__('products.fields.article_name'));
        CRUD::column('category')->label(__('products.fields.category'));
        CRUD::column('subcategory')->label(__('products.fields.subcategory'));
        CRUD::column('price')->label(__('products.fields.price'))->type('number')->suffix(' RON');
        CRUD::column('stock')->label(__('products.fields.stock'))->type('number');
        CRUD::column('availability')->label(__('products.fields.availability'));
        CRUD::column('status')->label(__('products.fields.status'));
        CRUD::column('image_link')->label(__('products.fields.image_link'))->type('image');

        CRUD::column('has_images')
            ->label('Has images')
            ->type('boolean')
            ->trueLabel('Yes')
            ->falseLabel('No')
            ->orderable(false)
            ->searchLogic(false);

        // ── Filters ───────────────────────────────────────────────────────────────

        CRUD::filter('has_images')
            ->type('dropdown')
            ->label('Has images')
            ->values([1 => 'Yes', 0 => 'No'])
            ->whenActive(function (string $value) {
                $imageFields = [
                    'image_link', 'image_url_1', 'image_url_2', 'image_url_3',
                    'image_url_4', 'image_url_5', 'image_url_6', 'image_url_8',
                    'image_url_9', 'image_url_10',
                ];

                if ($value == '1') {
                    $this->crud->addClause(function ($query) use ($imageFields) {
                        $query->where(function ($q) use ($imageFields) {
                            foreach ($imageFields as $field) {
                                $q->orWhere(function ($inner) use ($field) {
                                    $inner->whereNotNull($field)->where($field, '!=', '');
                                });
                            }
                        });
                    });
                } else {
                    $this->crud->addClause(function ($query) use ($imageFields) {
                        foreach ($imageFields as $field) {
                            $query->where(function ($q) use ($field) {
                                $q->whereNull($field)->orWhere($field, '');
                            });
                        }
                    });
                }
            });

        CRUD::filter('status')
            ->type('dropdown')
            ->label(__('products.fields.status'))
            ->values(['active' => 'Active', 'inactive' => 'Inactive'])
            ->whenActive(fn (string $value) => $this->crud->addClause('where', 'status', $value));

        CRUD::filter('availability')
            ->type('dropdown')
            ->label(__('products.fields.availability'))
            ->values([
                'Disponibil in stoc' => 'Disponibil in stoc',
                'Acest produs nu este disponibil in stoc' => 'Acest produs nu este disponibil in stoc',
            ])
            ->whenActive(fn (string $value) => $this->crud->addClause('where', 'availability', $value));

        CRUD::filter('category')
            ->type('select2_multiple')
            ->label(__('products.fields.category'))
            ->values(fn () => \App\Models\Products::query()
                ->whereNotNull('category')
                ->distinct()
                ->orderBy('category')
                ->pluck('category', 'category')
                ->toArray())
            ->whenActive(function (string $value) {
                $categories = json_decode($value, true);
                if (! empty($categories)) {
                    $this->crud->addClause('whereIn', 'category', $categories);
                }
            });

        CRUD::filter('stock')
            ->type('range')
            ->label(__('products.fields.stock'))
            ->label_from('Min')
            ->label_to('Max')
            ->whenActive(function (string $value) {
                $range = json_decode($value);
                if (isset($range->from) && $range->from !== '') {
                    $this->crud->addClause('where', 'stock', '>=', (int) $range->from);
                }
                if (isset($range->to) && $range->to !== '') {
                    $this->crud->addClause('where', 'stock', '<=', (int) $range->to);
                }
            });
    }

    protected function setupCreateOperation(): void
    {
        CRUD::setValidation(ProductsRequest::class);

        // ── Tab: General ─────────────────────────────────────────────────────────
        // Row: identifier (50%) | article code (25%) | external ref (25%)
        CRUD::field('item_identifier')
            ->label(__('products.fields.item_identifier'))
            ->type('text')
            ->wrapper(['class' => 'form-group col-md-6'])
            ->tab('General');

        CRUD::field('article_code')
            ->label(__('products.fields.article_code'))
            ->type('text')
            ->wrapper(['class' => 'form-group col-md-3'])
            ->tab('General');

        CRUD::field('external_reference_id')
            ->label(__('products.fields.external_reference_id'))
            ->type('text')
            ->wrapper(['class' => 'form-group col-md-3'])
            ->tab('General');

        // Row: article name (75%) | added_at (25%)
        CRUD::field('article_name')
            ->label(__('products.fields.article_name'))
            ->type('text')
            ->wrapper(['class' => 'form-group col-md-8'])
            ->tab('General');

        CRUD::field('added_at')
            ->label(__('products.fields.added_at'))
            ->type('datetime')
            ->wrapper(['class' => 'form-group col-md-4'])
            ->tab('General');

        // Row: description (100%)
        CRUD::field('description')
            ->label(__('products.fields.description'))
            ->type('textarea')
            ->wrapper(['class' => 'form-group col-md-12'])
            ->tab('General');

        // Row: category (25%) | subcategory (25%) | manufacturer (25%) | supplier (25%)
        // Build a map: { "CATEGORY": ["SUB1", "SUB2", ...], ... } from existing data
        $categoryMap = \App\Models\Products::query()
            ->whereNotNull('category')
            ->orderBy('category')
            ->orderBy('subcategory')
            ->get(['category', 'subcategory'])
            ->groupBy('category')
            ->map(fn ($rows) => $rows->pluck('subcategory')->filter()->unique()->values()->toArray())
            ->toArray();

        $topCategories = array_combine(array_keys($categoryMap), array_keys($categoryMap));

        CRUD::field('category')
            ->label(__('products.fields.category'))
            ->type('select_from_array')
            ->options($topCategories)
            ->allows_null(true)
            ->wrapper(['class' => 'form-group col-md-3'])
            ->tab('General');

        CRUD::field('subcategory')
            ->label(__('products.fields.subcategory'))
            ->type('select_from_array')
            ->options([])
            ->allows_null(true)
            ->wrapper(['class' => 'form-group col-md-3'])
            ->tab('General');

        // JS cascade: populate subcategory when category changes
        $categoryMapJson = json_encode($categoryMap, JSON_UNESCAPED_UNICODE);
        CRUD::field('_category_cascade')
            ->type('custom_html')
            ->label('')
            ->wrapper(['class' => 'form-group col-md-0 p-0'])
            ->value(<<<HTML
                <script>
                (function () {
                    var map = {$categoryMapJson};
                    function initCascade() {
                        var catSel = document.querySelector('[name="category"]');
                        var subSel = document.querySelector('[name="subcategory"]');
                        if (!catSel || !subSel) { return; }
                        function populate(selectedSub) {
                            var subs = map[catSel.value] || [];
                            subSel.innerHTML = '<option value=""></option>';
                            subs.forEach(function(s) {
                                var opt = document.createElement('option');
                                opt.value = s; opt.text = s;
                                if (s === selectedSub) { opt.selected = true; }
                                subSel.appendChild(opt);
                            });
                        }
                        var initialSub = subSel.value;
                        populate(initialSub);
                        catSel.addEventListener('change', function() { populate(''); });
                    }
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', initCascade);
                    } else {
                        initCascade();
                    }
                })();
                </script>
                HTML)
            ->tab('General');

        CRUD::field('manufacturer')
            ->label(__('products.fields.manufacturer'))
            ->type('text')
            ->wrapper(['class' => 'form-group col-md-4'])
            ->tab('General');

        CRUD::field('supplier')
            ->label(__('products.fields.supplier'))
            ->type('text')
            ->wrapper(['class' => 'form-group col-md-4'])
            ->tab('General');

        // Row: keywords (100%)
        CRUD::field('keywords')
            ->label(__('products.fields.keywords'))
            ->type('text')
            ->wrapper(['class' => 'form-group col-md-12'])
            ->tab('General');

        // ── Tab: Pricing ──────────────────────────────────────────────────────────
        // Row: price (25%) | net (25%) | old (25%) | old net (25%)
        CRUD::field('price')
            ->label(__('products.fields.price'))
            ->type('number')
            ->attributes(['step' => '0.01'])
            ->prefix('RON')
            ->wrapper(['class' => 'form-group col-md-6'])
            ->tab('Pricing');

        CRUD::field('old_price')
            ->label(__('products.fields.old_price'))
            ->type('number')
            ->attributes(['step' => '0.01'])
            ->prefix('RON')
            ->wrapper(['class' => 'form-group col-md-6'])
            ->tab('Pricing');

        CRUD::field('product_price_net')
            ->label(__('products.fields.product_price_net'))
            ->type('number')
            ->attributes(['step' => '0.01'])
            ->prefix('RON')
            ->wrapper(['class' => 'form-group col-md-6'])
            ->tab('Pricing');

        CRUD::field('old_price_net')
            ->label(__('products.fields.old_price_net'))
            ->type('number')
            ->attributes(['step' => '0.01'])
            ->prefix('RON')
            ->wrapper(['class' => 'form-group col-md-6'])
            ->tab('Pricing');

        // Row: tax (25%) | currency (25%)
        CRUD::field('tax_value')
            ->label(__('products.fields.tax_value'))
            ->type('number')
            ->attributes(['step' => '0.01'])
            ->suffix('%')
            ->wrapper(['class' => 'form-group col-md-6'])
            ->tab('Pricing');

        CRUD::field('currency')
            ->label(__('products.fields.currency'))
            ->type('select_from_array')
            ->options(['RON' => 'RON', 'EUR' => 'EUR'])
            ->allows_null(true)
            ->wrapper(['class' => 'form-group col-md-2'])
            ->tab('Pricing');

        // ── Price Groups (1–5) ────────────────────────────────────────────────────
        foreach (range(1, 5) as $n) {
            CRUD::field("_pricegroup_{$n}_spacer")
                ->type('custom_html')
                ->label('')
                ->wrapper(['class' => 'form-group col-md-12'])
                ->value('<hr class="my-2">')
                ->tab('Pricing');

            CRUD::field("price_group{$n}")
                ->label(__("products.fields.price_group{$n}"))
                ->type('number')
                ->attributes(['step' => '0.01'])
                ->prefix('RON')
                ->wrapper(['class' => 'form-group col-md-4'])
                ->tab('Pricing');

            CRUD::field("price_group{$n}_net")
                ->label(__("products.fields.price_group{$n}_net"))
                ->type('number')
                ->attributes(['step' => '0.01'])
                ->prefix('RON')
                ->wrapper(['class' => 'form-group col-md-4'])
                ->tab('Pricing');
        }

        // ── Tab: Stock ────────────────────────────────────────────────────────────
        // Row: quantity (16%) | stock (16%) | availability (34%) | status (17%) | visibility (17%)
        CRUD::field('quantity')
            ->label(__('products.fields.quantity'))
            ->type('number')
            ->wrapper(['class' => 'form-group col-md-4'])
            ->tab('Stock');

        CRUD::field('stock')
            ->label(__('products.fields.stock'))
            ->type('number')
            ->wrapper(['class' => 'form-group col-md-4'])
            ->tab('Stock');

        CRUD::field('availability')
            ->label(__('products.fields.availability'))
            ->type('select_from_array')
            ->options([
                'Disponibil in stoc' => 'Disponibil in stoc',
                'Acest produs nu este disponibil in stoc' => 'Acest produs nu este disponibil in stoc',
            ])
            ->allows_null(true)
            ->wrapper(['class' => 'form-group col-md-5'])
            ->tab('Stock');

        CRUD::field('status')
            ->label(__('products.fields.status'))
            ->type('select_from_array')
            ->options(['active' => 'Active', 'inactive' => 'Inactive'])
            ->allows_null(false)
            ->wrapper(['class' => 'form-group col-md-2'])
            ->tab('Stock');

        CRUD::field('visibility')
            ->label(__('products.fields.visibility'))
            ->type('select_from_array')
            ->options(['visible' => 'Visible', 'hidden' => 'Hidden'])
            ->allows_null(false)
            ->wrapper(['class' => 'form-group col-md-2'])
            ->tab('Stock');

        // Row: product_url (100%)
        CRUD::field('product_url')
            ->label(__('products.fields.product_url'))
            ->type('url')
            ->wrapper(['class' => 'form-group col-md-12'])
            ->tab('Stock');

        // ── Tab: Images ───────────────────────────────────────────────────────────
        // Row: image_link (50%) | preview (50%)
        CRUD::field('image_link')
            ->label(__('products.fields.image_link'))
            ->type('upload')
            ->wrapper(['class' => 'form-group col-md-6'])
            ->tab('Images');

        CRUD::field('image_link_preview')
            ->label('Main image preview')
            ->type('custom_html')
            ->wrapper(['class' => 'form-group col-md-6'])
            ->value($this->imagePreviewHtml('image_link', null, 160))
            ->tab('Images');

        // images JSON (100%)
        CRUD::field('images')
            ->label(__('products.fields.images'))
            ->type('textarea')
            ->hint('JSON array of image URLs')
            ->wrapper(['class' => 'form-group col-md-12'])
            ->tab('Images');

        CRUD::field('old_image_sources_preview')
            ->label(__('products.fields.old_image_sources'))
            ->type('custom_html')
            ->wrapper(['class' => 'form-group col-md-12'])
            ->value($this->jsonPreviewHtml([]))
            ->tab('Images');

        // Each image URL (50%) + preview (50%) side by side
        foreach ([1, 2, 3, 4, 5, 6, 8, 9, 10] as $n) {
            $fieldName = "image_url_{$n}";
            $label = __("products.fields.{$fieldName}");

            CRUD::field($fieldName)
                ->label($label)
                ->type('upload')
                ->wrapper(['class' => 'form-group col-md-6'])
                ->tab('Images');

            CRUD::field("{$fieldName}_preview")
                ->label("{$label} preview")
                ->type('custom_html')
                ->wrapper(['class' => 'form-group col-md-6'])
                ->value($this->imagePreviewHtml($fieldName, null, 120))
                ->tab('Images');
        }

        // ── Tab: Platforms ────────────────────────────────────────────────────────
        foreach ([
            'emag' => 'eMAG',
            'glovo' => 'Glovo',
            'bazaronline' => 'Bazaronline',
        ] as $platform => $platformLabel) {
            CRUD::field("available_{$platform}")
                ->label(__("products.fields.available_{$platform}"))
                ->type('boolean')
                ->wrapper(['class' => 'form-group col-md-12'])
                ->tab('Platforms');

            CRUD::field("price_{$platform}")
                ->label(__("products.fields.price_{$platform}"))
                ->type('number')
                ->attributes(['step' => '0.01'])
                ->prefix('RON')
                ->wrapper(['class' => 'form-group col-md-3'])
                ->tab('Platforms');

            CRUD::field("price_{$platform}_old")
                ->label(__("products.fields.price_{$platform}_old"))
                ->type('number')
                ->attributes(['step' => '0.01'])
                ->prefix('RON')
                ->wrapper(['class' => 'form-group col-md-3'])
                ->tab('Platforms');

            CRUD::field("_platform_{$platform}_spacer")
                ->type('custom_html')
                ->label('')
                ->wrapper(['class' => 'form-group col-md-12'])
                ->value('<hr class="my-2">')
                ->tab('Platforms');
        }

        CRUD::field('_platform_js')
            ->type('custom_html')
            ->label('')
            ->wrapper(['class' => 'form-group col-md-0 p-0'])
            ->value($this->platformToggleJs())
            ->tab('Platforms');

        // ── Tab: SEO ──────────────────────────────────────────────────────────────
        // Row: meta_title (50%)
        CRUD::field('meta_title')
            ->label(__('products.fields.meta_title'))
            ->type('text')
            ->wrapper(['class' => 'form-group col-md-6'])
            ->tab('SEO');

        // Row: meta_description (100%)
        CRUD::field('meta_description')
            ->label(__('products.fields.meta_description'))
            ->type('textarea')
            ->wrapper(['class' => 'form-group col-md-12'])
            ->tab('SEO');
    }

    protected function setupUpdateOperation(): void
    {
        $this->setupCreateOperation();

        $entry = $this->crud->getCurrentEntry();
        if (! $entry) {
            return;
        }

        // Pre-populate subcategory options for the entry's current category
        // so Backpack can pre-select the saved value on load.
        if ($entry->category) {
            $subcategories = \App\Models\Products::query()
                ->where('category', $entry->category)
                ->whereNotNull('subcategory')
                ->distinct()
                ->orderBy('subcategory')
                ->pluck('subcategory', 'subcategory')
                ->toArray();

            CRUD::field('subcategory')->options($subcategories);
        }

        // Image previews
        CRUD::field('old_image_sources_preview')
            ->value($this->jsonPreviewHtml($entry->old_image_sources ?? []));

        CRUD::field('image_link_preview')
            ->value($this->imagePreviewHtml('image_link', $entry->image_link, 160));

        foreach ([1, 2, 3, 4, 5, 6, 8, 9, 10] as $n) {
            $fieldName = "image_url_{$n}";
            CRUD::field("{$fieldName}_preview")
                ->value($this->imagePreviewHtml($fieldName, $entry->$fieldName, 120));
        }
    }

    /**
     * JS that:
     *  - hides/shows platform price fields based on the availability checkbox
     *  - pre-fills the platform price with the regular price when a checkbox is first checked
     */
    private function platformToggleJs(): string
    {
        return <<<'HTML'
            <script>
            (function () {
                var platforms = ['emag', 'glovo', 'bazaronline'];

                function getInput(name) {
                    return document.querySelector('[name="' + name + '"]');
                }

                function prefillPlatformPrices(platform) {
                    var priceInput    = getInput('price_' + platform);
                    var oldPriceInput = getInput('price_' + platform + '_old');
                    var regularPrice  = getInput('price');
                    var regularOld    = getInput('old_price');

                    if (priceInput && !priceInput.value && regularPrice && regularPrice.value) {
                        priceInput.value = regularPrice.value;
                    }
                    if (oldPriceInput && !oldPriceInput.value && regularOld && regularOld.value) {
                        oldPriceInput.value = regularOld.value;
                    }
                }

                function init() {
                    platforms.forEach(function (platform) {
                        prefillPlatformPrices(platform);
                    });
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', init);
                } else {
                    init();
                }
            })();
            </script>
            HTML;
    }

    private function jsonPreviewHtml(mixed $value): string
    {
        $json = json_encode($value ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return '<pre style="white-space: pre-wrap; word-break: break-word; max-width: 100%;'
            .' max-height: 260px; overflow: auto;'
            .' background: #f8fafc; color: #111827; border: 1px solid #dbe4f0; border-radius: 6px;'
            .' padding: 12px; font-size: 12px; line-height: 1.45;">'
            .e($json ?: '{}')
            .'</pre>';
    }

    /**
     * Build a custom_html value that:
     *  - shows the current image (if $currentUrl is set) in edit mode
     *  - updates the preview via FileReader when the user picks a new file
     */
    private function imagePreviewHtml(string $fieldName, ?string $currentUrl, int $maxHeight): string
    {
        $style = "max-height:{$maxHeight}px;border-radius:6px;border:1px solid #e0e0e0;";

        if ($currentUrl && ! str_starts_with($currentUrl, 'http')) {
            $currentUrl = Storage::url($currentUrl);
        }

        $currentImg = $currentUrl
            ? "<img src=\"{$currentUrl}\" style=\"{$style}\" onerror=\"this.style.display='none'\">"
            : '';

        return <<<HTML
            <div id="preview-{$fieldName}" class="mt-1">{$currentImg}</div>
            <script>
            document.addEventListener("DOMContentLoaded", function () {
                var input = document.querySelector("[name={$fieldName}]");
                var preview = document.getElementById("preview-{$fieldName}");
                if (input) {
                    input.addEventListener("change", function () {
                        if (input.files && input.files[0]) {
                            var reader = new FileReader();
                            reader.onload = function (e) {
                                preview.innerHTML = "<img src=\'" + e.target.result + "\' style=\'{$style}\'>";
                            };
                            reader.readAsDataURL(input.files[0]);
                        }
                    });
                }
            });
            </script>
            HTML;
    }
}
