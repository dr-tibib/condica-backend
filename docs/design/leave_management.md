# Feature Specification: Leave Management Module (LMS-01)
Version: 1.0
Target System: Existing Workplace Presence Tracker
Legal Context: Romania (Codul Muncii) & EU (GDPR)

## 1. Executive Summary
We are adding a module to manage employee leave requests, approvals, and balances. This module must integrate with the current time-tracking data to ensure that "Leave" status overrides "Absent" status in daily logs. It must support Romanian statutory requirements, specifically regarding medical leave codes and public holiday exclusions.

## 2. Data Model Extensions
Proposed Schema Changes (SQL-relational style)

### 2.1 New Table: leave_types
Defines the categories of leave.
*   `id` (PK)
*   `name` (string) - e.g., "Concediu Odihnă", "Concediu Medical", "Telemuncă"
*   `is_paid` (boolean)
*   `requires_document` (boolean) - True for Sick Leave.
*   `affects_annual_quota` (boolean) - True for Annual Leave; False for Sick/Maternity.
*   `medical_code_required` (boolean) - Specific for Romanian sick leave (Code 01, 08, etc.).

### 2.2 New Table: leave_balances
Tracks how many days an employee has available.
*   `user_id` (FK)
*   `year` (integer) - e.g., 2024
*   `total_entitlement` (float) - e.g., 21 days.
*   `carried_over` (float) - Days left from previous year.
*   `taken` (float) - Calculated sum of approved requests.

### 2.3 New Table: leave_requests
*   `id` (PK)
*   `user_id` (FK)
*   `approver_id` (FK) - Line manager at time of request.
*   `leave_type_id` (FK)
*   `start_date` (date)
*   `end_date` (date)
*   `total_days` (float) - Business Logic: Must exclude weekends/holidays.
*   `status` (enum) - PENDING, APPROVED, REJECTED, CANCELLED.
*   `medical_certificate_series` (string, nullable)
*   `medical_certificate_number` (string, nullable)
*   `medical_code` (string, nullable) - e.g., "01", "08".
*   `attachment_path` (string) - Encrypted path to file storage.

### 2.4 New Table: public_holidays
*   `date` (date)
*   `description` (string)
*   Note: Needs to be seeded annually with Romanian holidays (Dec 1, Jan 24, Orthodox Easter, etc.).

## 3. Business Logic & Validation Rules (Backend)

### 3.1 Date Calculation Algorithm (Romanian Logic)
When a user requests leave from Start_Date to End_Date:
1.  Iterate through each day in the range.
2.  Exclude weekends (Saturday/Sunday).
3.  Exclude dates found in the `public_holidays` table.
4.  Result: The count of remaining days is the `total_days`.
5.  Example: Requesting Dec 23rd to Jan 3rd. Exclude Dec 25/26 (Xmas) and Jan 1/2 (New Year) and weekends.

### 3.2 Overlap Prevention
*   Query `leave_requests` where status is PENDING or APPROVED.
*   Throw Error if new request dates overlap with existing records for that `user_id`.

### 3.3 Balance Check
*   If `leave_type.affects_annual_quota` is TRUE:
    *   Check `(balance.total_entitlement + balance.carried_over) - balance.taken`.
    *   If `request.total_days > remaining balance`, Block Request.

### 3.4 Sick Leave (Concediu Medical) Logic
*   If `leave_type` = "Concediu Medical":
    *   Validation: `medical_code` is mandatory.
    *   Validation: attachment is mandatory (unless configured to allow later upload).
    *   No balance check applied (Sick leave is not limited by a quota).

## 4. Functional Requirements (User Stories)

### 4.1 Employee Actions
*   **VIEW BALANCE:** "As an employee, I want to see a widget on my dashboard showing 'Days Available', 'Days Taken', and 'Carry-over'."
*   **CREATE REQUEST:** "As an employee, I want to select dates, choose a leave type, and submit. If it is sick leave, I must be prompted to enter the Certificate Code (required for Payroll)."

### 4.2 Manager Actions
*   **APPROVAL QUEUE:** "As a manager, I need a list of pending requests with the ability to 'Approve' or 'Reject' (with a mandatory comment for rejection)."
*   **TEAM CALENDAR:** "Before approving, I need to see a visual timeline of my team to ensure I don't approve overlapping leaves for critical roles."

### 4.3 HR/Admin Actions
*   **MANUAL ADJUSTMENT:** "As HR, I need to manually adjust a balance (e.g., add extra days for blood donation) with an audit note."
*   **PAYROLL EXPORT:** "As HR, I need to download a CSV/XML for the month containing: Employee Name, Leave Type, Start Date, End Date, and Medical Code (if applicable)."

## 5. Integration with Presence System
Timesheet Generation: When generating the daily presence log (Pontaj):
*   **IF** `leave_requests` has an entry for date AND status = APPROVED:
    *   **THEN** Status = [Leave Type Name] (e.g., "CO", "CM").
*   **ELSE** Status = "Present/Absent" (Standard logic).
*   **Crucial:** Approved leave must lock the day. The employee cannot "clock in" if they are on approved leave.

## 6. Security & GDPR (Non-Functional Requirements)

### 6.1 Access Control
*   **Sick Leave Attachments:** Access restricted strictly to HR Role and Super Admin. Direct Managers should see the status "Sick Leave" but should not be able to download the medical certificate (sensitive health data).

### 6.2 Data Retention
Implement a cron job or retention policy:
*   `leave_requests` (metadata): Retain for 5 years (Standard labor dispute window).
*   `attachments` (medical certs): Retain according to specific payroll archive laws (usually synchronized with payroll records).

## 7. API Endpoints Draft

| Method | Endpoint | Description | Payload Key Params |
| :--- | :--- | :--- | :--- |
| GET | `/api/v1/leave/balance` | Get current user balance | `user_id`, `year` |
| POST | `/api/v1/leave/request` | Submit new request | `type_id`, `start_date`, `end_date`, `medical_code` |
| POST | `/api/v1/leave/approve` | Manager action | `request_id`, `status` (APPROVED/REJECTED), `reason` |
| GET | `/api/v1/leave/team-calendar` | Get team availability | `manager_id`, `month` |
| GET | `/api/v1/admin/export/payroll` | Payroll data dump | `month`, `year`, `format` (csv/xml) |

## Next Steps for the Lead Developer:
1.  **Migration Script:** Create SQL script to populate `leave_balances` for all existing active users (defaulting to standard 20/21 days).
2.  **Holiday Seeding:** Import the Romanian Public Holidays list for the current and next year.
3.  **UI Mockup:** Design the "Request Modal" to handle the conditional logic (e.g., hiding the "Medical Code" field unless Sick Leave is selected).
