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
- **Status**: Environment issues prevented execution (missing PHP/Composer).
- **Goal**: Establish comprehensive feature and unit tests.

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

*   **Setup**: Ensure PHP 8.4+ and Composer are available. Run `php artisan test`.

#### A. Feature Tests (`tests/Feature`)
*   **Authentication**:
    *   `POST /api/auth/validate-code`: Test valid/invalid codes, rate limiting.
*   **Events**:
    *   `POST /api/events/checkin`: Test successful check-in, duplicate check-in prevention.
    *   `POST /api/events/checkout`: Test checkout logic.
    *   `POST /api/events/delegation-start` & `delegation-end`.
*   **Locations**:
    *   `POST /api/delegation-locations`: Test saving new locations.
*   **Media**:
    *   `POST /api/events/{id}/media`: Test file upload (image/video) and validation.

#### B. Unit Tests (`tests/Unit`)
*   **Models**: Test relationships and scopes.
*   **Services**: Test any isolated business logic services.

### 3. Continuous Integration
*   Configure CI pipeline to run:
    *   `npm run test` (Vitest)
    *   `php artisan test` (Pest)
    *   Enforce minimum coverage thresholds.
