# Condica: Pragmatic Laravel Manifesto

This document defines the architectural standards and domain logic for the Condica project. All future development must adhere to these principles.

## 1. THE CORE GOAL: Pragmatic Maintainability
The primary objective is to build a **safe, maintainable, and scalable** application using Laravel's native strengths.
- **Service-Model-Action Pattern**: Business logic is centralized in stateless Services.
- **Laravel Idiomatic**: Use Eloquent, FormRequests, Policies, and Observers as intended.
- **Backpack Integrated**: Maintain deep compatibility with Backpack for Laravel for all administrative tasks.

---

## 2. DIRECTORY MAP

### `app/Models` (The Data Layer)
Eloquent models represent the data structure and simple attributes.
- **Rules**:
    - No complex business logic or multi-model orchestration.
    - Use for relationships, scopes, and UI-specific accessors (e.g., `getNameAttribute`).
    - Standard Eloquent features to ensure Backpack compatibility.

### `app/Services` (The Logic Layer)
Stateless services are the **Single Source of Truth** for all business processes.
- **Rules**:
    - All state transitions (e.g., Check-in, Approve Leave) must go through a Service.
    - Services throw custom Exceptions for business rule violations.
    - Services are agnostic of the delivery mechanism (Web, API, or Kiosk).

### `app/Http/Controllers` (The Presentation Layer)
Controllers coordinate the flow between requests and services.
- **Rules**:
    - "Skinny Controllers": Validate -> Call Service -> Return Response.
    - Use `FormRequests` for all input validation.
    - In Backpack, override `store()`/`update()` only when business logic beyond simple CRUD is required.

---

## 3. THE RULES

1.  **Stateless Services**: Services should not hold state; they receive data via parameters (Models or typed arrays/DTOs).
2.  **Custom Exceptions**: Use specific exceptions (e.g., `GeofenceValidationException`) to handle business rule failures.
3.  **Atomic Transactions**: All multi-step state transitions must be wrapped in a database transaction within the Service.
4.  **No Logic in Controllers**: If a logic block is more than 3 lines or involves multiple models, it belongs in a Service.

---

## 4. DOMAIN LOGIC & STATE MACHINE

### Shift & Presence
- **Single Row Presence**: Every presence entity (Shift, Delegation, Leave) is a single row in `presence_events` with `start_at` and `end_at`.
- **The 16:00 Rule**: Shifts started after 16:00 are flagged for potential overnight status and require specific validation.
- **Geofencing**: Location-based validation is enforced in the `PresenceService`.

### Leave & Interruptions
- **Split Logic**: When a check-in occurs during an approved leave, the `LeaveService` (or Observer) must handle the split (Part A: Leave, Part B: Presence).
- **Quota Management**: Handled by `LeaveRequestObserver` to ensure balances are updated atomically.

### Delegations
- **Polymorphic Links**: `presence_events` are linked to `delegations` via a polymorphic `linkable` relationship.
- **Multi-day Refinement**: Kiosk flow allows employees to refine their schedule for multi-day delegations.

---

## 5. TECH STACK (Infrastructure)

- **Backend**: Laravel 12 (PHP 8.4) | **Tenancy**: `stancl/tenancy` (DB per tenant).
- **Environment**: Lando. **IMPORTANT**: Always use `lando artisan` instead of `php artisan` for all CLI operations to ensure correct environment and database routing.
- **Admin**: Backpack for Laravel.
- **Frontend**: React 19 (Web), Expo (Mobile Kiosk).
- **Kiosk Hardware (v2)**: Touchscreen device with a dedicated physical **Numerical Keypad** only. UI must prioritize large touch targets and numerical-only data entry.
- **Testing**: Pest (Backend), Vitest (Frontend).
    - Run backend tests: `lando php vendor/bin/pest`.
    - Run frontend tests: `npm test`.
