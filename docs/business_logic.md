# Presence Management Business Logic

The user is presented with a screen that contains a workplace code input text. He has can select the flow prior to enter the code, the flow types are: Normal, Delegation, Leave.
He is also presented with a list of last presence events, this list shows a set of presence events in reverse cronological order for all employees.
He is presented with a list of employees in delegation.
He is presented with a list of employees on leave.

## Normal flow
User enters his code, based on his status the following happens:
1. If he does not have a shift started, or delegation started the systems starts a workflow shift
1.1. IF he just appears to start the shift, but it is after a configured time, example 16 PM, the system asks him if this is actualy a shift start of it is and end, providing him a time selector to specify shift start, and buttons to end shift, start shift.
2. If he has an already started work shift and no delegation, the system ends his workflow shift.
2.1 If the shift that appears to be closed was started not on the same day in the past. The user is presented with a list of days from the last workshift start till yesterday, so he can correct his hours, most likely he forgot to checkout the past day.
3. If he has a delegation started, the system behaves tha same as ending the delegation flow see: Delegation flow point 3 below
4. If he has a leave started, the leave is broken into two parts, the first part is ended with yesterday, the second part starts from tomorrow. This could be a case when he is for whatever reason asked or decided to work that day. If today was the end of leave then the second leave part is not created as this action meant he returned a day earlier.

## Delegation flow
User enters his code, based on his status the following happens
1. He has a work shift started, then he is presented with the start delegation wizard, upon completing the wizard his work shift ends and a delegation is started.
2. He does not have a work shift started, he is not currently in delegation, he can be on leave then he is presented with the start delegation wizard and upon completion the system starts a delegation. If he is on leave when starting the delegation leave interruption logic takes place similar to Normal shift point 4.
3. He is currently in delegation, in this case the system ends his delegation and a workshift is started. There are some rules see below
3.1 If the delegation start is not in the same day the system presents him with a screen, where he sees all of the days, excluding weekends, from the start of delegation, and start time and end time for each of these days. Every start and end time will be prefilled from a shift config for the company, default: 08- 17, the first start time will be the exact time the delegation was ended, the last end time is current time. Also he has a possibility to remove days, and a button to cancel, or to end delegation.
3.2 If the delegation was just started by less than a configurable amount of minutes default 10 minutes, the user is asked he wants to remove the delegation, in this case the delegation is deleted and the workshift that was ended by starting a delegation for the same day, is changed from closed to active. This is the case that a delegation was cancelled shortly after it was started. So the employee remains on normal work shift.

## Leave flow
1. He has a started work shift his shift is ended and he is presented with a screen to select the leave period, this screen has a numeric field number of days which is by default focused, he can enter the number of the days, the system calculates the leave period by not taking weekends and legal holidays in consideration, also a two calendar selector is available for start and end date of the leave with weekends and public holidays marked with distinct colors like grey etc. He can then start a leave by pressing a button. In this case his work shift is ended and a leave is recorded.
2. He has a delegation started he goes to delegation end flow point 3 and if needed a delegation refinement is shown first, after completing that he sees the leave screen.

## Main concepts
A company can have multiple workplaces; work shifts, delegations and leaves are linked to workplaces where they were initiated.
Workplaces have a name, location coordinates, and settings like default shift (start and end time)
Employees have the following attributes:
       'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'id_document_type',
        'id_document_number',
        'personal_numeric_code',
        'workplace_enter_code',
        'avatar',
        'role_id' => relation to EmployeRole
        'user_id', => relation to User / optional
        'manager_id', => relation to Employee as superior
        'department_id', => relation to Department
        'workplace_id', => relation to Workplace
Companies have departments, a department have the following attributes: name, description, manager => relation to Employee, employees => relation to employees.
Companies have Vehicles with attributes name, license plate
