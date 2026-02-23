<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add new columns to presence_events if they don't exist
        Schema::table('presence_events', function (Blueprint $table) {
            if (!Schema::hasColumn('presence_events', 'start_at')) {
                $table->timestamp('start_at')->nullable()->after('employee_id');
            }
            if (!Schema::hasColumn('presence_events', 'end_at')) {
                $table->timestamp('end_at')->nullable()->after('start_at');
            }
            if (!Schema::hasColumn('presence_events', 'type')) {
                $table->string('type')->default('presence')->after('end_at');
            }

            // Start event data
            if (!Schema::hasColumn('presence_events', 'start_method')) {
                $table->string('start_method')->nullable()->after('type');
            }
            if (!Schema::hasColumn('presence_events', 'start_latitude')) {
                $table->decimal('start_latitude', 10, 8)->nullable()->after('start_method');
            }
            if (!Schema::hasColumn('presence_events', 'start_longitude')) {
                $table->decimal('start_longitude', 11, 8)->nullable()->after('start_latitude');
            }
            if (!Schema::hasColumn('presence_events', 'start_accuracy')) {
                $table->integer('start_accuracy')->nullable()->after('start_longitude');
            }
            if (!Schema::hasColumn('presence_events', 'start_device_info')) {
                $table->text('start_device_info')->nullable()->after('start_accuracy');
            }

            // End event data
            if (!Schema::hasColumn('presence_events', 'end_method')) {
                $table->string('end_method')->nullable()->after('start_device_info');
            }
            if (!Schema::hasColumn('presence_events', 'end_latitude')) {
                $table->decimal('end_latitude', 10, 8)->nullable()->after('end_method');
            }
            if (!Schema::hasColumn('presence_events', 'end_longitude')) {
                $table->decimal('end_longitude', 11, 8)->nullable()->after('end_latitude');
            }
            if (!Schema::hasColumn('presence_events', 'end_accuracy')) {
                $table->integer('end_accuracy')->nullable()->after('end_longitude');
            }
            if (!Schema::hasColumn('presence_events', 'end_device_info')) {
                $table->text('end_device_info')->nullable()->after('end_accuracy');
            }

            // Polymorphic link
            if (!Schema::hasColumn('presence_events', 'linkable_id')) {
                $table->nullableMorphs('linkable');
            }
        });

        // 2. Data Migration for presence_events
        if (Schema::hasColumn('presence_events', 'event_time')) {
            DB::statement("UPDATE presence_events SET 
                start_at = event_time,
                start_method = method,
                start_latitude = latitude,
                start_longitude = longitude,
                start_accuracy = accuracy,
                start_device_info = device_info,
                type = CASE 
                    WHEN event_type = 'delegation_start' OR event_type = 'delegation_end' THEN 'delegation'
                    ELSE 'presence'
                END
                WHERE event_type IN ('check_in', 'delegation_start')
            ");

            if (Schema::hasColumn('presence_events', 'pair_event_id')) {
                $events = DB::table('presence_events')
                    ->whereIn('event_type', ['check_in', 'delegation_start'])
                    ->whereNotNull('pair_event_id')
                    ->get();

                foreach ($events as $event) {
                    $pair = DB::table('presence_events')->where('id', $event->pair_event_id)->first();
                    if ($pair) {
                        DB::table('presence_events')->where('id', $event->id)->update([
                            'end_at' => $pair->event_time,
                            'end_method' => $pair->method,
                            'end_latitude' => $pair->latitude,
                            'end_longitude' => $pair->longitude,
                            'end_accuracy' => $pair->accuracy,
                            'end_device_info' => $pair->device_info,
                        ]);
                    }
                }
            }
        }

        // 3. Update delegations table and linkable in presence_events
        if (!Schema::hasColumn('delegations', 'presence_event_id')) {
            Schema::table('delegations', function (Blueprint $table) {
                $table->foreignId('presence_event_id')->nullable()->after('employee_id')->constrained('presence_events')->onDelete('set null');
            });

            $delegations = DB::table('delegations')->get();
            foreach ($delegations as $delegation) {
                if (isset($delegation->start_event_id) && $delegation->start_event_id) {
                    DB::table('delegations')->where('id', $delegation->id)->update([
                        'presence_event_id' => $delegation->start_event_id
                    ]);

                    DB::table('presence_events')->where('id', $delegation->start_event_id)->update([
                        'linkable_id' => $delegation->id,
                        'linkable_type' => 'App\Models\Delegation',
                        'type' => 'delegation'
                    ]);
                }
            }
        }

        // 4. Delete end events and cleanup columns
        if (Schema::hasColumn('presence_events', 'event_type')) {
            DB::table('presence_events')->whereIn('event_type', ['check_out', 'delegation_end'])->delete();

            Schema::table('presence_events', function (Blueprint $table) {
                $indexNames = collect(Schema::getIndexes('presence_events'))->pluck('name');
                $foreignKeys = collect(Schema::getForeignKeys('presence_events'))->pluck('name');

                if (DB::getDriverName() === 'sqlite') {
                    // In SQLite, dropping columns with foreign keys or indexes is problematic.
                    // We make them nullable instead to avoid IntegrityConstraintViolation on insert.
                    $table->string('event_type')->nullable()->change();
                    $table->timestamp('event_time')->nullable()->change();
                    $table->string('method')->nullable()->change();
                    $table->decimal('latitude', 10, 8)->nullable()->change();
                    $table->decimal('longitude', 11, 8)->nullable()->change();
                    $table->integer('accuracy')->nullable()->change();
                    $table->text('device_info')->nullable()->change();
                    $table->unsignedBigInteger('pair_event_id')->nullable()->change();
                } else {
                    // Drop foreign keys if they exist
                    if ($foreignKeys->contains('presence_events_employee_id_foreign')) { $table->dropForeign(['employee_id']); }
                    if ($foreignKeys->contains('presence_events_workplace_id_foreign')) { $table->dropForeign(['workplace_id']); }
                    if ($foreignKeys->contains('presence_events_pair_event_id_foreign')) { $table->dropForeign(['pair_event_id']); }

                    // Drop old indexes if they exist
                    if ($indexNames->contains('presence_events_event_type_index')) { $table->dropIndex('presence_events_event_type_index'); }
                    if ($indexNames->contains('presence_events_event_time_index')) { $table->dropIndex('presence_events_event_time_index'); }
                    if ($indexNames->contains('presence_events_employee_id_event_time_index')) { $table->dropIndex('presence_events_employee_id_event_time_index'); }
                    if ($indexNames->contains('presence_events_workplace_id_event_time_index')) { $table->dropIndex('presence_events_workplace_id_event_time_index'); }
                    if ($indexNames->contains('presence_events_employee_id_event_type_event_time_index')) { $table->dropIndex('presence_events_employee_id_event_type_event_time_index'); }

                    $table->dropColumn([
                        'event_type',
                        'event_time',
                        'method',
                        'latitude',
                        'longitude',
                        'accuracy',
                        'device_info',
                        'pair_event_id'
                    ]);
                }
                
                // Add new indexes
                $table->index('type');
                $table->index('start_at');
                $table->index('end_at');
                $table->index(['employee_id', 'start_at']);
                $table->index(['workplace_id', 'start_at']);
                $table->index(['employee_id', 'type', 'start_at']);

                if (DB::getDriverName() !== 'sqlite') {
                    // Re-add foreign keys
                    $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
                    $table->foreign('workplace_id')->references('id')->on('workplaces')->onDelete('cascade');
                }
            });
        }
        
        Schema::table('presence_events', function (Blueprint $table) {
            $table->timestamp('start_at')->nullable(false)->change();
        });

        if (Schema::hasColumn('delegations', 'start_event_id')) {
            Schema::table('delegations', function (Blueprint $table) {
                if (DB::getDriverName() !== 'sqlite') {
                    $foreignKeys = collect(Schema::getForeignKeys('delegations'))->pluck('name');
                    if ($foreignKeys->contains('delegations_start_event_id_foreign')) { $table->dropForeign(['start_event_id']); }
                    if ($foreignKeys->contains('delegations_end_event_id_foreign')) { $table->dropForeign(['end_event_id']); }
                    $table->dropColumn(['start_event_id', 'end_event_id']);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
