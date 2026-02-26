<?php

namespace App\Jobs;

use App\Models\Employee;
use App\Models\PresenceEvent;
use App\Models\Delegation;
use App\Models\DelegationStop;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Tenant;
use App\Models\Workplace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class SyncOldCondicaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tenantId;
    protected $startDate;
    protected $shouldClean;

    /**
     * Create a new job instance.
     */
    public function __construct(string $tenantId, ?string $startDate = null, bool $shouldClean = true)
    {
        $this->tenantId = $tenantId;
        $this->startDate = !empty($startDate) ? $startDate : '2000-01-01 00:00:00';
        $this->shouldClean = $shouldClean;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Starting sync for tenant: {$this->tenantId}");
        $tenant = Tenant::find($this->tenantId);
        if (!$tenant) {
            Log::error("Tenant {$this->tenantId} not found for sync.");
            return;
        }

        tenancy()->initialize($tenant);

        if ($this->shouldClean) {
            $this->cleanTenantData();
        }

        // 1. Sync Employees
        Log::info("Syncing employees...");
        $this->syncEmployees();

        // 2. Sync Attendance Registry
        Log::info("Syncing attendance registry from " . ($this->startDate ?: 'null') . "...");
        $this->syncAttendance();
        Log::info("Sync completed for tenant: {$this->tenantId}");
    }

    private function cleanTenantData(): void
    {
        Log::info("Cleaning existing data for tenant {$this->tenantId}...");
        Schema::disableForeignKeyConstraints();
        PresenceEvent::truncate();
        Delegation::truncate();
        DelegationStop::truncate();
        LeaveRequest::truncate();
        Schema::enableForeignKeyConstraints();
        Log::info("Data cleaned.");
    }

    private function syncEmployees(): void
    {
        $oldEmployees = DB::connection('condica_old')->table('employee')->get();
        $workplace = Workplace::first() ?? Workplace::create(['name' => 'Default Office']);

        foreach ($oldEmployees as $old) {
            Employee::updateOrCreate(
                ['workplace_enter_code' => $old->LoginCode],
                [
                    'first_name' => $old->FirstName,
                    'last_name' => $old->LastName,
                    'email' => $old->Email,
                    'phone' => $old->Phone,
                    'address' => $old->Address,
                    'personal_numeric_code' => $old->CNP,
                    'workplace_id' => $workplace->id,
                ]
            );
        }
        Log::info("Synced " . count($oldEmployees) . " employees.");
    }

    private function syncAttendance(): void
    {
        $date = $this->startDate ?: '2000-01-01 00:00:00';
        $query = DB::connection('condica_old')->table('attendance_registry')
            ->where('LogInDate', '>=', $date)
            ->orderBy('LogInDate', 'asc');

        $total = $query->count();
        Log::info("Total records to process: {$total}");

        $query->chunk(100, function ($records) {
            static $processed = 0;
            foreach ($records as $record) {
                $this->processRecord($record);
            }
            $processed += count($records);
            if ($processed % 1000 === 0) {
                Log::info("Processed {$processed} records...");
            }
        });
    }

    private function processRecord($record): void
    {
        $oldEmployee = DB::connection('condica_old')->table('employee')
            ->where('IdEmployee', $record->IdEmployee)
            ->first();

        if (!$oldEmployee) return;

        $employee = Employee::where('workplace_enter_code', $oldEmployee->LoginCode)->first();
        if (!$employee) return;

        $logType = trim($record->LogType);
        
        // Determine primary type
        $type = 'presence';
        $leaveTypeName = null;

        // Map Old LogTypes
        switch (strtoupper($logType)) {
            case 'DELEGATION':
            case 'DELEGATIE':
                $type = 'delegation';
                break;
            
            case 'HOLIDAY':
            case 'CONCEDIU':
            case 'CONCEDIU 2025':
            case 'CONCEDIU 2024':
                $type = 'leave';
                $leaveTypeName = 'Concediu Odihnă';
                break;

            case 'HOLIDAYPAST':
                $type = 'leave';
                $leaveTypeName = 'Concediu Vechi';
                break;

            case 'SICKLEAVE':
            case 'CONCEDIU MEDICAL':
                $type = 'leave';
                $leaveTypeName = 'Concediu Medical';
                break;

            case 'PERMIT':
            case 'INVOIRE':
                $type = 'leave';
                $leaveTypeName = 'Învoire';
                break;
            
            case 'NONE':
            case 'NORMAL':
            case 'PRESENCE':
                $type = 'presence';
                break;

            default:
                $type = 'presence';
                Log::warning("Unknown LogType: {$logType} for Employee {$employee->id}. Defaulting to presence.");
                break;
        }

        $event = PresenceEvent::create([
            'employee_id' => $employee->id,
            'start_at' => $record->LogInDate,
            'end_at' => $record->LogOutDate,
            'type' => $type,
            'notes' => $record->Comment . ($type !== 'delegation' && $type !== 'presence' ? " (Source Type: {$logType})" : ""),
            'workplace_id' => $employee->workplace_id,
            'start_method' => 'sync',
            'end_method' => 'sync',
        ]);

        if ($type === 'delegation') {
            $this->createDelegation($event, $record);
        } elseif ($type === 'leave' && $leaveTypeName) {
            $this->createLeave($event, $record, $leaveTypeName);
        }
    }

    private function createLeave($event, $record, $leaveTypeName): void
    {
        $leaveType = LeaveType::firstOrCreate(['name' => $leaveTypeName]);

        $startDate = Carbon::parse($event->start_at);
        $endDate = $event->end_at ? Carbon::parse($event->end_at) : $startDate;

        $totalDays = $startDate->diffInDays($endDate) + 1;

        $leaveRequest = LeaveRequest::withoutEvents(function () use ($event, $leaveType, $startDate, $endDate, $totalDays, $leaveTypeName, $record) {
            return LeaveRequest::create([
                'employee_id' => $event->employee_id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'total_days' => $totalDays,
                'reason' => $record->Comment ?? "Imported from Old Condica ($leaveTypeName)",
                'status' => 'APPROVED',
            ]);
        });

        $event->update([
            'linkable_id' => $leaveRequest->id,
            'linkable_type' => LeaveRequest::class,
        ]);
    }

    private function createDelegation($event, $record): void
    {
        // 1. Parse Vehicle
        $vehicleId = null;
        if (preg_match('/Automobil:(.*)/i', $record->Comment, $matches)) {
            $plate = strtoupper(trim(explode("\n", $matches[1])[0]));
            if ($plate && $plate !== 'NONE' && $plate !== '') {
                $vehicle = \App\Models\Vehicle::firstOrCreate(
                    ['license_plate' => $plate],
                    ['name' => 'Auto ' . $plate]
                );
                $vehicleId = $vehicle->id;
            }
        }

        // 2. Parse Destination(s)
        $destinations = [];
        if (preg_match('/Destinatie:(.*)/i', $record->Comment, $matches)) {
            $rawDest = trim(explode("\n", $matches[1])[0]);
            // Split by comma or semicolon
            $destinations = array_map('trim', preg_split('/[,;]/', $rawDest));
        }

        $delegation = Delegation::updateOrCreate(
            ['presence_event_id' => $event->id],
            [
                'employee_id' => $event->employee_id,
                'vehicle_id' => $vehicleId,
                'name' => !empty($destinations) ? $destinations[0] : 'Unknown',
                'notes' => $record->Comment,
            ]
        );

        // 3. Resolve and Create Stops
        $googleService = app(\App\Services\GooglePlacesService::class);
        
        // Manual mapping for common destinations to ensure accuracy and save API calls
        $manualPlaces = [
            'KRONOSPAN' => ['name' => 'Kronospan Romania', 'address' => 'Str. Strungarilor 1, Brașov', 'lat' => 45.6297, 'lng' => 25.6425],
            'NBHX' => ['name' => 'NBHX Trim Group', 'address' => 'Cristian, Brașov', 'lat' => 45.6258, 'lng' => 25.4831],
            'ROLEM' => ['name' => 'Rolem S.R.L.', 'address' => 'Cristian, Brașov', 'lat' => 45.6258, 'lng' => 25.4831],
            'QUIN' => ['name' => 'Joysonquin Automotive Systems', 'address' => 'Ghimbav, Brașov', 'lat' => 45.6633, 'lng' => 25.5067],
            'MADINGER' => ['name' => 'Madinger Romania', 'address' => 'Ghimbav, Brașov', 'lat' => 45.6633, 'lng' => 25.5067],
            'SCHAEFFLER' => ['name' => 'Schaeffler Romania', 'address' => 'Cristian, Brașov', 'lat' => 45.6258, 'lng' => 25.4831],
            'MIOVENI' => ['name' => 'Mioveni', 'address' => 'Mioveni, Argeș', 'lat' => 44.9531, 'lng' => 24.9442],
            'GHIMBAV' => ['name' => 'Ghimbav', 'address' => 'Ghimbav, Brașov', 'lat' => 45.6633, 'lng' => 25.5067],
            'CRISTIAN' => ['name' => 'Cristian', 'address' => 'Cristian, Brașov', 'lat' => 45.6258, 'lng' => 25.4831],
            'METROM' => ['name' => 'Metrom Brașov', 'address' => 'Brașov', 'lat' => 45.6427, 'lng' => 25.5887],
        ];

        foreach ($destinations as $index => $destName) {
            $destName = trim($destName);
            if (empty($destName) || $destName === '.') continue;

            $existingPlace = null;
            $normalizedSearch = strtoupper($destName);

            // 1. Try Manual Mapping
            foreach ($manualPlaces as $key => $data) {
                if (str_contains($normalizedSearch, $key)) {
                    $existingPlace = \App\Models\DelegationPlace::updateOrCreate(
                        ['name' => $data['name']],
                        [
                            'address' => $data['address'],
                            'latitude' => $data['lat'],
                            'longitude' => $data['lng'],
                        ]
                    );
                    break;
                }
            }

            // 2. Try Exact Match in DB
            if (!$existingPlace) {
                $existingPlace = \App\Models\DelegationPlace::where('name', 'LIKE', $destName)->first();
            }

            // 3. Try Google Places (Only if not resolved yet)
            if (!$existingPlace) {
                $placeData = $googleService->searchPlace($destName);
                if ($placeData) {
                    $existingPlace = \App\Models\DelegationPlace::updateOrCreate(
                        ['google_place_id' => $placeData['google_place_id']],
                        [
                            'name' => $placeData['name'],
                            'address' => $placeData['address'],
                            'latitude' => $placeData['latitude'],
                            'longitude' => $placeData['longitude'],
                            'photo_reference' => $placeData['photo_reference'],
                            'metadata' => [
                                'sync_source' => 'old_condica',
                                'original_name' => $destName,
                                'resolution_method' => 'google_places',
                                'google_data' => $placeData['full_result'],
                                'synced_at' => now()->toDateTimeString(),
                            ]
                        ]
                    );
                }
            } else {
                // If it exists but has no metadata, or we want to update it
                $currentMetadata = $existingPlace->metadata ?? [];
                if (!isset($currentMetadata['original_name'])) {
                    $currentMetadata['original_name'] = $destName;
                    $currentMetadata['sync_source'] = 'old_condica';
                    $existingPlace->update(['metadata' => $currentMetadata]);
                }
            }

            // 4. Create Stop
            DelegationStop::updateOrCreate(
                [
                    'delegation_id' => $delegation->id,
                    'name' => $existingPlace ? $existingPlace->name : $destName,
                ],
                [
                    'delegation_place_id' => $existingPlace?->id,
                    'place_id' => $existingPlace?->google_place_id,
                    'address' => $existingPlace?->address ?? $destName,
                    'latitude' => $existingPlace?->latitude,
                    'longitude' => $existingPlace?->longitude,
                ]
            );

            // Update main delegation header with first place info
            if ($index === 0) {
                $delegation->update([
                    'delegation_place_id' => $existingPlace?->id,
                    'name' => $existingPlace ? $existingPlace->name : $destName,
                    'address' => $existingPlace?->address ?? $destName,
                    'latitude' => $existingPlace?->latitude,
                    'longitude' => $existingPlace?->longitude,
                ]);
            }
        }

        $event->update([
            'linkable_id' => $delegation->id,
            'linkable_type' => Delegation::class,
        ]);
    }
}
