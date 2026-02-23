## Scenario: checkin

### Goal
Test a regular check-in flow for employee 001. Navigate to / and enter code 001.

### AI Conclusion
The regular check-in flow for employee 001 failed. The expected success message "Checked in successfully." did not appear after entering the code and pressing "OK". Upon reviewing the logs, a `TenantDatabaseDoesNotExistException` was found, indicating that the `oaksoft_test.sqlite` database is missing. This is an infrastructure issue related to the multi-tenancy setup, preventing the application from performing the check-in operation. A screenshot named "check_in_failure" was taken.
