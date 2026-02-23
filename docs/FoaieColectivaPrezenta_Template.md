# Template Specification: Foaie Colectiva de Prezenta

This document defines the visual layout and data binding for the Collective Attendance Sheet export.

---

## 1. Document Layout

- **Orientation:** Landscape (A4)
- **Font:** DejaVu Sans, 8px body
- **Page margins:** 15px
- **Table:** Fixed layout, collapsed borders, 1px solid black

---

## 2. Header Block

```
┌─────────────────────────────────────────────────────────────────────┐
│          FOAIA COLECTIVA DE PREZENTA (PONTAJ)                       │
│  Luna: {{ monthLabel }}          (e.g. "Februarie 2026")            │
│  Unitatea: {{ companyName }}     (tenant name)                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 3. Table Structure

### 3.1 Column Headers (Two Header Rows)

**Row 1:**

| Col | Header | rowspan | colspan | Width |
|-----|--------|---------|---------|-------|
| A | Nr. Crt. | 2 | 1 | 15px |
| B | Nume si Prenume | 2 | 1 | 100px |
| C | Functia | 2 | 1 | 50px |
| D..D+N | Ziua | 1 | N (days in month) | - |
| Last 5 | Total Ore / Zile | 1 | 5 | - |

**Row 2:**

| Cols | Headers | Width each |
|------|---------|------------|
| D..D+N | 1, 2, 3, ... N (day numbers) | 10px |
| T1 | Total Ore | 15px |
| T2 | CO | 15px |
| T3 | CM | 15px |
| T4 | CFS | 15px |
| T5 | Abs | 15px |

### 3.2 Data Rows (One Per Employee)

```
| {index+1} | {employee.name} | {employee.department} | {day1} | {day2} | ... | {dayN} | {worked} | {co} | {cm} | {cfs} | {abs} |
```

**Cell styling:**
- Name and Functia columns: left-aligned, text truncated with ellipsis
- All other columns: center-aligned
- Weekend day columns: grey background (`#f0f0f0`)

### 3.3 Day Cell Values

| Value | Meaning | Styling |
|-------|---------|---------|
| Numeric (e.g. `8`, `7.5`) | Worked hours | Default |
| `CO` | Paid Leave (Concediu de Odihna) | Default |
| `CM` | Sick Leave (Concediu Medical) | Default |
| `CFS` | Unpaid Leave (Concediu Fara Salariu) | Default |
| Empty | Weekend / No record / Future date | Weekend cells get grey bg |

---

## 4. Footer Block (Signature Area)

```
┌──────────────────┬──────────────────┬──────────────────┐
│  Intocmit,       │ Sef Compartiment,│        Aprobat,  │
│  (left-aligned)  │   (centered)     │  (right-aligned) │
└──────────────────┴──────────────────┴──────────────────┘
```

- No borders on signature table
- 30px top margin from data table
- Three equal-width columns (33% each)

---

## 5. Implementation Reference

### 5.1 Source Files

| File | Role |
|------|------|
| `app/Http/Controllers/Admin/TeamCommandCenterController.php` | `generateAttendanceSheet()` — data assembly |
| `app/Exports/AttendanceSheetExport.php` | Maatwebsite Excel export wrapper |
| `resources/views/admin/reports/attendance_sheet.blade.php` | Blade template (shared by Excel and PDF) |

### 5.2 Route

```
GET /admin/team-command-center/export?date=YYYY-MM-DD&format=excel|pdf
```

### 5.3 Data Shape Passed to View

```php
[
    'users' => [
        [
            'name'    => 'Ion Popescu',
            'role'    => 'Productie',          // department name
            'days'    => [
                ['val' => 8, 'is_weekend' => false, 'bg_color' => ''],     // day 1
                ['val' => '', 'is_weekend' => true, 'bg_color' => ''],     // day 2 (weekend)
                ['val' => 'CO', 'is_weekend' => false, 'bg_color' => ''],  // day 3 (leave)
                // ... one entry per day of month
            ],
            'totals'  => [
                'worked' => 168,  // total hours (integer)
                'co'     => 2,    // paid leave days count
                'cm'     => 0,    // sick leave days count
                'cfs'    => 0,    // unpaid leave days count
                'abs'    => 0,    // absent days count
            ],
        ],
        // ... more employees
    ],
    'monthLabel'   => 'Februarie 2026',
    'daysInMonth'  => 28,
    'companyName'  => 'Hidraulica SRL',
]
```

### 5.4 Excel Export

Uses `Maatwebsite\Excel` with `FromView` concern — renders the same Blade template into an Excel workbook. Styles applied via `WithStyles`:
- All cells get thin borders on all sides.
- Auto-sized columns via `ShouldAutoSize`.

### 5.5 PDF Export

Uses `Barryvdh\DomPDF`:
- Same Blade template.
- Paper: A4 landscape.
- CSS embedded in the Blade template handles layout.
