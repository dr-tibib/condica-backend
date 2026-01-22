# Full Unit Testing Implementation Plan

## Current Status

### Frontend (React/Vitest)
- **Overall Coverage**: ~34% Statement Coverage.
- **Pages**: ~40% Coverage.
    - `IdleScreen`: 100%
    - `CodeEntryScreen`: ~5% (Test skipped due to missing implementation)
    - `DelegationLocationsScreen`: ~61% (Missing interactions)
- **Services**: ~34% (Untested/mocked)
- **Store**: ~15% (Untested)

### Backend (Laravel/Pest)
- **Status**: Tests Executed. 53 Passed, 0 Failed.
- **Environment**: PHP 8.4 and Composer installed and configured.
- **Fixes Applied**:
    - **ExampleTest**: Removed redundant/broken `ExampleTest`.
    - **DeviceTest**:
        - Updated migration `2025_12_12_170720_create_devices_table` to allow same `device_token` for different users (changed from global unique to composite unique `[user_id, device_token]`).
        - Fixed test authentication persistence issue by clearing auth guards between requests.
    - **PresenceTest**: Fixed timing logic to use past time (`now()->subHours(1)`) instead of future time (`today()->addHours(9)`) to prevent negative duration assertions.

## Implementation Plan

### 1. Frontend Testing (Vitest)

#### A. Components
*   **`CodeEntryScreen.tsx`**:
    *   **Fix Implementation**: Update component to call `validateCode` API so the existing test passes.
    *   **Expand Tests**:
        *   Test error handling when `validateCode` fails.
        *   Test `handleDelete` (backspace) functionality.
        *   Test full keypad input.
*   **`DelegationLocationsScreen.tsx`**:
    *   Test searching functionality (interaction with input).
    *   Test clicking on a location item and verifying navigation arguments.
    *   Test back button navigation.
*   **New Components**:
    *   Ensure any new components have corresponding test files in `__tests__`.

#### B. Services (`resources/js/react/services/`)
*   **`api.ts`**:
    *   Create `services/__tests__/api.test.ts`.
    *   Mock `axios` using `vi.mock('axios')`.
    *   Verify each export function (`validateCode`, `checkIn`, etc.) calls the correct API endpoint with expected payload and headers.

#### C. State Management (`resources/js/react/store/`)
*   **`appStore.ts`**:
    *   Create `store/__tests__/appStore.test.ts`.
    *   Test initial state.
    *   Test each setter action (`setEmployee`, `setUi`, etc.) updates the state correctly.
    *   Test `reset` action clears the state to defaults.

### 2. Backend Testing (Pest)

*   **Maintenance**: Ensure tests continue to pass locally and in CI.
*   **Expansion**:
    *   **Authentication**: Add more edge cases for login/logout and token expiration.
    *   **Events**: Test complex scenarios like cross-day sessions, timezones, and concurrent check-ins.
    *   **Performance**: Consider adding tests for large datasets (e.g., presence history).

### 3. Continuous Integration
*   Configure CI pipeline to run:
    *   `npm run test` (Vitest)
    *   `php artisan test` (Pest)
    *   Enforce minimum coverage thresholds.
