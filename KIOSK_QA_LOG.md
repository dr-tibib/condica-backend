# Kiosk Frontend QA & Fix Log

This document tracks the QA runs for all kiosk flows, including state setup, verification, and fixes.

## Test Environment
- **Tenant**: oaksoft-condica.lndo.site
- **Hardware Profile**: 1024x768 Kiosk & Mobile
- **Employee Code**: 001 (Laszlo Olah)

---

## 1. Flow: Regular Check-in
- **Scenario Setup**: `lando artisan kiosk:qa 001 reset oaksoft`
- **Expected**: Employee starts absent. Entering code results in "Checked in successfully".
- **Result**: PASS
- **Notes**: Correctly shows digital clock and success message.

## 2. Flow: Regular Check-out
- **Scenario Setup**: `lando artisan kiosk:qa 001 shift-active oaksoft`
- **Expected**: Employee starts in-shift. Entering code results in "Checked out successfully".
- **Result**: PASS
- **Notes**: Correctly ends the active presence event.

## 3. Flow: Shift Correction (Multi-day)
- **Scenario Setup**: `lando artisan kiosk:qa 001 shift-forgot-checkout oaksoft`
- **Expected**: System detects long-running shift. Displays "Corecție Pontaj" screen with a timeline of missing days.
- **Result**: PASS
- **Notes**: User can pick times for each day and save. Correctly redirects to Home on success.

## 4. Flow: Late Start Confirm
- **Scenario Setup**: `lando artisan kiosk:qa 001 threshold-low oaksoft` (Mocks threshold to 00:00)
- **Expected**: Entering code after 00:00 (which is always) shows "Activitate Târzie" screen.
- **Result**: PASS
- **Notes**: Verified 2 buttons: "Încep Tura" and "Închei Tura". Reset threshold with `lando artisan kiosk:qa 001 threshold-reset oaksoft`.

## 5. Flow: Delegation Start
- **Scenario Setup**: `lando artisan kiosk:qa 001 reset oaksoft`
- **Expected**: Select "Delegație", enter code. Shows Wizard (Places -> Vehicles).
- **Result**: PASS
- **Notes**: Verified Google Places autocomplete and vehicle selection.

## 6. Flow: Delegation End (Multi-day Refinement)
- **Scenario Setup**: `lando artisan kiosk:qa 001 delegation-multi oaksoft`
- **Expected**: Entering code shows "Confirmare Program Delegație" with timeline.
- **Result**: PASS (Fixed)
- **Fix Run**: Updated `DelegationSchedule.tsx` to handle `timeline` key instead of `schedule_days`.

## 7. Flow: Leave Request
- **Scenario Setup**: `lando artisan kiosk:qa 001 reset oaksoft`
- **Expected**: Select "Concediu", enter code. Shows Leave Wizard.
- **Result**: PASS
- **Notes**: Verified calendar selection and day count calculation.

## 8. Flow: Leave Interruption
- **Scenario Setup**: `lando artisan kiosk:qa 001 leave-active oaksoft`
- **Expected**: Entering code during approved leave automatically splits/interrupts the leave and starts a shift.
- **Result**: PASS
- **Notes**: User receives "Checked in successfully" and backend handles the `LeaveRequest` split logic.

---

## Automated E2E Verification Script
The following tool sequence can be used by the agent to re-verify all flows:

1. `lando artisan kiosk:qa 001 reset oaksoft`
2. `navigate_page https://oaksoft-condica.lndo.site`
3. `fill code 001` -> `click OK` -> Verify Check-in
4. `lando artisan kiosk:qa 001 shift-forgot-checkout oaksoft`
5. `fill code 001` -> `click OK` -> Verify Correction Screen
... (etc)
