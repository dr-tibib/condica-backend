# Business Rules: Foaie Colectiva de Prezenta (Collective Attendance Sheet)

This document defines the conceptual business rules for generating the **Foaia Colectiva de Prezenta (Pontaj)** — the official monthly collective attendance sheet used by Romanian companies for payroll and labour compliance.

---

## 1. Report Purpose and Scope

The Foaie Colectiva de Prezenta is a **monthly per-company report** that shows, for every employee, the daily attendance status and accumulated totals. It is a **legal document** required under Romanian labour law.

- **Report period:** One calendar month (1st to last day of month).
- **Scope:** All employees of the tenant/company.
- **Output formats:** Excel (.xlsx), PDF, HTML preview.
- **Filename convention:** `Foaia_Colectiva_YYYY_MM.xlsx`

---

## 2. Report Header

| Field | Description |
|-------|-------------|
| Title | "FOAIA COLECTIVA DE PREZENTA (PONTAJ)" |
| Luna (Month) | Translated month name + year (e.g. "Februarie 2026") |
| Unitatea (Company) | Tenant/company name |

---

## 3. Employee Row Structure

Each employee occupies one row with these sections:

| Section | Columns | Description |
|---------|---------|-------------|
| Nr. Crt. | 1 | Sequential row number |
| Nume si Prenume | 1 | Employee full name (`first_name last_name`) |
| Functia | 1 | Department name (role/position) |
| Days 1..N | N (= days in month) | One column per calendar day |
| Totals | 5 | Summary counters |

---

## 4. Daily Cell Values

For each employee and each calendar day, the cell value is determined by this priority:

### Priority 1: Approved Leave

If the employee has an **approved leave request** (`status = APPROVED`) where the current day falls between `start_date` and `end_date`:

| Leave Type Property | Cell Code | Romanian Name | Total Counter |
|---------------------|-----------|---------------|---------------|
| `medical_code_required = true` | **CM** | Concediu Medical (Sick Leave) | `totals.cm` |
| `is_paid = false` | **CFS** | Concediu Fara Salariu (Unpaid Leave) | `totals.cfs` |
| All other (default, paid leave) | **CO** | Concediu de Odihna (Paid Leave / Vacation) | `totals.co` |

### Priority 2: Weekend

If the day is a **Saturday or Sunday**: cell is **empty**, background is shaded (grey).

> **Note:** Public holidays are NOT currently handled distinctly from weekdays. This is a gap to address — public holidays from the `public_holidays` table should be treated like weekends (empty, shaded) or given a dedicated code.

### Priority 3: Worked Day (Presence Events)

If the employee has **presence events** (`check_in`/`check_out` pairs) on that day:

- **Cell value:** Calculated worked hours = `SUM(check_out.event_time - check_in.event_time)` in hours, rounded to 1 decimal.
- **Total accumulator:** `totals.worked` += minutes worked.
- If calculated hours = 0, cell is empty.

**Open check-in handling (no check-out):**
- If the day is **today**: count time from check-in until now (live calculation).
- If the day is in the **past**: count 0 hours (indicates missing checkout — employee must correct their timesheet).

### Priority 4: No Record (Absent)

If none of the above apply and the day is a **past weekday**: the cell is empty.

> **Note:** Absence tracking (`Abs` counter) is defined in the data structure but currently commented out in the code. When enabled, past weekdays with no presence and no leave should increment `totals.abs` and display "Abs".

---

## 5. Time Calculation Rules

### 5.1 Pairing Logic

Events are processed **chronologically** per employee per day. The algorithm pairs sequential `check_in` → `check_out` events:

1. Iterate events ordered by `event_time`.
2. On `check_in`: save as current open session.
3. On `check_out`: if a `check_in` is open, calculate `diffInMinutes` and add to day total. Close the session.
4. After all events: if a `check_in` remains open and the day is today, add minutes from check-in until now.

### 5.2 Event Types That Count as Check-In/Check-Out

| Action | Event Types |
|--------|------------|
| Check-in (opens a session) | `check_in`, `delegation_start` |
| Check-out (closes a session) | `check_out`, `delegation_end` |

> In the current `generateAttendanceSheet` implementation, only `check_in`/`check_out` pairs are counted for hours. Delegation events (`delegation_start`/`delegation_end`) are not yet counted toward worked hours in the report. This is a gap — delegation hours should contribute to worked time.

### 5.3 Hours Rounding

- Minutes are calculated via `Carbon::diffInMinutes()`.
- Displayed as hours with 1 decimal: `round(minutes / 60, 1)`.
- Summary total (`totals.worked`) is displayed as whole hours: `floor(totalMinutes / 60)`.

---

## 6. Summary Totals (Per Employee)

| Column | Field | Description |
|--------|-------|-------------|
| Total Ore | `worked` | Total worked hours across the month (floor of total minutes / 60) |
| CO | `co` | Count of Paid Leave days (Concediu de Odihna) |
| CM | `cm` | Count of Sick Leave days (Concediu Medical) |
| CFS | `cfs` | Count of Unpaid Leave days (Concediu Fara Salariu) |
| Abs | `abs` | Count of Absent days (currently inactive) |

---

## 7. Report Footer

The report includes a signature block at the bottom with three roles:

| Position | Romanian | Purpose |
|----------|----------|---------|
| Left | Intocmit | Prepared by (HR / author) |
| Center | Sef Compartiment | Department Head |
| Right | Aprobat | Approved by (Director) |

---

## 8. Data Sources

### 8.1 Models and Relationships

| Model | Table | Role in Report |
|-------|-------|----------------|
| `Employee` | `employees` | Row per employee; `name`, `department` |
| `Department` | `departments` | Employee's department name (Functia column) |
| `PresenceEvent` | `presence_events` | Daily attendance events for hour calculation |
| `LeaveRequest` | `leave_requests` | Approved leave overlapping the month |
| `LeaveType` | `leave_types` | Determines CO/CM/CFS code via `medical_code_required`, `is_paid` |
| `PublicHoliday` | `public_holidays` | (Not yet integrated) Should mark non-working days |

### 8.2 Key Queries (Eager Loading)

The report loads all employees with:

```
Employee::with([
    'department',
    'presenceEvents' => filtered to month range, ordered by event_time,
    'leaveRequests'  => status=APPROVED, overlapping the month range
])
```

---

## 9. Business Rules from Legacy System (to be carried forward)

These rules existed in the old system (`presence_reports.php` / `FoaieColectivaPrezenta20243.xls`) and should be considered for implementation:

### 9.1 Overtime Tracking

The old system tracked two categories of overtime:

| Category | Condition | Rate |
|----------|-----------|------|
| `oreSuplomentare75` | Extra hours on weekdays (Mon-Fri) | 75% |
| `oreSuplomentare100` | All hours on weekends (Sat-Sun) | 100% |

**Weekday overtime thresholds:**

| Actual Hours Worked | Reported Hours | Overtime Hours |
|---------------------|---------------|----------------|
| < 9.5 | 8 | 0 |
| 9.5 - 10.5 | 9 | 1 |
| 10.5 - 11.5 | 10 | 2 |
| 11.5 - 12.5 | 11 | 3 |
| 12.5+ | 12
 | 4 |

**Weekend:** All hours rounded to nearest integer, counted at 100% rate.

### 9.2 Break Deduction

The old system deducted a **30-minute lunch break** (0.5 hours) for days where:
- Employee worked more than 6 hours.
- The day had only `NONE`-type attendance records (no mixed delegation).

### 9.3 Standard Work Schedule

| Parameter | Value |
|-----------|-------|
| Standard start | 08:00 |
| Standard end | 16:30 |
| Standard day | 8 hours |

### 9.4 Day Type Codes (Legacy Reference)

| Code | Meaning | New Equivalent |
|------|---------|----------------|
| P + hours | Present | Numeric hours |
| C | Concediu (Vacation) | CO |
| Bo | Boala (Sick Leave) | CM |
| I | Invoire (Permission) | CO or CFS depending on type |
| X | Non-working day | Empty cell |

### 9.5 Delegation Normalization

In the old system, `DELEGATION` and `HOLIDAYPAST` log types were **normalized to NONE** (counted as regular worked time). The new system should similarly count `delegation_start`/`delegation_end` paired sessions as worked hours.

---

## 10. Known Gaps (Current Implementation vs. Full Requirements)

| Gap | Description | Priority |
|-----|-------------|----------|
| Public Holidays | `public_holidays` table exists but is not checked in report generation — public holidays should be treated as non-working days | High |
| Delegation Hours | `delegation_start`/`delegation_end` pairs are not counted in worked hours | High |
| Absence Tracking | `Abs` counter is defined but commented out | Medium |
| Overtime Columns | No overtime tracking (75%/100%) in new report | Medium |
| Break Deduction | No automatic lunch break deduction | Medium |
| Standard Hours | No enforcement of 8h standard day (actual hours shown as-is) | Low |
| Department Filter | No filter by department/workplace | Low |
| Pagination | Old system paginated 200 employees per export; new system exports all | Info |
