<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\ProductSyncLog;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ProductSyncLogCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup(): void
    {
        CRUD::setModel(ProductSyncLog::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/products/sync-logs');
        CRUD::setEntityNameStrings('product sync log', 'product sync logs');
    }

    protected function setupListOperation(): void
    {
        CRUD::column('id')->label('ID');
        CRUD::column('status')->label('Status');
        CRUD::column('source_type')->label('Source type');
        CRUD::column('source')->label('Source');
        CRUD::column('total_rows')->label('Total rows');
        CRUD::column('created_rows')->label('Created');
        CRUD::column('updated_rows')->label('Updated');
        CRUD::column('skipped_rows')->label('Skipped');
        CRUD::column('failed_rows')->label('Failed');
        CRUD::column('started_at')->label('Started at')->type('datetime');
        CRUD::column('finished_at')->label('Finished at')->type('datetime');
        CRUD::column('created_at')->label('Logged at')->type('datetime');

        CRUD::filter('status')
            ->type('dropdown')
            ->label('Status')
            ->values([
                'pending' => 'Pending',
                'running' => 'Running',
                'completed' => 'Completed',
                'failed' => 'Failed',
            ])
            ->whenActive(fn (string $value) => $this->crud->addClause('where', 'status', $value));

        CRUD::filter('source_type')
            ->type('dropdown')
            ->label('Source type')
            ->values([
                'local' => 'Local',
                'remote' => 'Remote',
                'bunny' => 'Bunny',
                'google_drive' => 'Google Drive',
            ])
            ->whenActive(fn (string $value) => $this->crud->addClause('where', 'source_type', $value));
    }

    protected function setupShowOperation(): void
    {
        CRUD::column('id')->label('ID');
        CRUD::column('status')->label('Status');
        CRUD::column('source_type')->label('Source type');
        CRUD::column('source')->label('Source');
        CRUD::column('total_rows')->label('Total rows');
        CRUD::column('created_rows')->label('Created');
        CRUD::column('updated_rows')->label('Updated');
        CRUD::column('skipped_rows')->label('Skipped');
        CRUD::column('failed_rows')->label('Failed');
        CRUD::column('message')->label('Message');
        CRUD::addColumn([
            'name' => 'meta',
            'label' => 'Meta',
            'type' => 'closure',
            'function' => function (ProductSyncLog $entry): string {
                return $this->renderPrettyJson($entry->meta);
            },
            'escaped' => false,
        ]);
        CRUD::addColumn([
            'name' => 'failure_details',
            'label' => 'Failure details',
            'type' => 'closure',
            'function' => function (ProductSyncLog $entry): string {
                return $this->renderPrettyJson($entry->failure_details);
            },
            'escaped' => false,
        ]);
        CRUD::column('started_at')->label('Started at')->type('datetime');
        CRUD::column('finished_at')->label('Finished at')->type('datetime');
        CRUD::column('created_at')->label('Logged at')->type('datetime');
    }

    private function renderPrettyJson(mixed $value): string
    {
        $json = json_encode($value ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return '<pre style="white-space: pre-wrap; word-break: break-word; max-width: 100%;'
            .' max-height: 420px; overflow: auto;'
            .' background: #f8fafc; color: #111827; border: 1px solid #dbe4f0; border-radius: 6px;'
            .' padding: 12px; font-size: 12px; line-height: 1.45;">'
            .e($json ?: '{}')
            .'</pre>';
    }
}
