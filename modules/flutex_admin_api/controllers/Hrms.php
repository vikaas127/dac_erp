<?php

defined('BASEPATH') || exit('No direct script access allowed');

require_once __DIR__ . '/RestController.php';

use FlutexAdminApi\RestController;

class Hrms extends RestController
{
    protected $staffInfo;

    public function __construct()
    {
        parent::__construct();

        register_language_files('flutex_admin_api');
        load_admin_language();

        $this->load->helper('flutex_admin_api');

        if (!isset(isAuthorized()['status'])) {

            $this->response(
                isAuthorized()['response'],
                isAuthorized()['response_code']
            );
        }

        $this->staffInfo = isAuthorized();

        // Load HRMS model
        $this->load->model('hrms/hrms_model');
          $this->load->model('hrms/Employee_model', 'employee_model');
          $this->load->model('hrms/Attendance_model');
          $this->load->model('hrms/Payroll_model', 'payroll_model');
    }

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function dashboard_get()
    {
        try {

            $data = [

                // Employees
                'employees' => $this->hrms_model
                    ->get_employees(),

                // Upcoming Holidays
                'upcoming_holidays' => $this->hrms_model
                    ->get_upcoming_holidays(),

                // Birthdays
                'birthdays' => $this->hrms_model
                    ->get_birthdays(),

                // Anniversaries
                'anniversaries' => $this->hrms_model
                    ->get_anniversaries(),
            ];

            $this->response([
                'status' => true,
                'data'   => $data
            ], 200);

        } catch (\Throwable $e) {

            log_message('error', $e->getMessage());

            $this->response([
                'status'  => false,
                'message' => 'Failed to load dashboard',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EMPLOYEES
    |--------------------------------------------------------------------------
    */

    

    /*
    |--------------------------------------------------------------------------
    | UPCOMING HOLIDAYS
    |--------------------------------------------------------------------------
    */

    public function holidays_get()
    {
        try {

            $holidays = $this->hrms_model
                ->get_upcoming_holidays();

            $this->response([
                'status' => true,
                'count'  => count($holidays),
                'data'   => $holidays
            ], 200);

        } catch (\Throwable $e) {

            $this->response([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | BIRTHDAYS
    |--------------------------------------------------------------------------
    */

    public function birthdays_get()
    {
        try {

            $birthdays = $this->hrms_model
                ->get_birthdays();

            $this->response([
                'status' => true,
                'count'  => count($birthdays),
                'data'   => $birthdays
            ], 200);

        } catch (\Throwable $e) {

            $this->response([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ANNIVERSARIES
    |--------------------------------------------------------------------------
    */

    public function anniversaries_get()
    {
        try {

            $anniversaries = $this->hrms_model
                ->get_anniversaries();

            $this->response([
                'status' => true,
                'count'  => count($anniversaries),
                'data'   => $anniversaries
            ], 200);

        } catch (\Throwable $e) {

            $this->response([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function employees_get()
    {
        try {

            $employees = $this->employee_model->get_all();

            $this->response([
                'status' => true,
                'count'  => count($employees),
                'data'   => $employees
            ], 200);

        } catch (\Throwable $e) {

            $this->response([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE DETAIL
    |--------------------------------------------------------------------------
    */

    public function employee_get($id)
    {
        try {

            $employee = $this->employee_model
                ->get_full_profile($id);

            if (!$employee) {

                return $this->response([
                    'status' => false,
                    'message' => 'Employee not found'
                ], 404);
            }

            $this->response([
                'status' => true,
                'data'   => $employee
            ], 200);

        } catch (\Throwable $e) {

            $this->response([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE EMPLOYEE
    |--------------------------------------------------------------------------
    */

    public function employee_post()
    {
        try {

            $data = $this->post();

            $employee_id = $this->employee_model
                ->create_employee($data);

            if (!$employee_id) {

                return $this->response([
                    'status' => false,
                    'message' => 'Failed to create employee'
                ], 400);
            }

            $this->response([
                'status' => true,
                'message' => 'Employee created successfully',
                'employee_id' => $employee_id
            ], 200);

        } catch (\Throwable $e) {

            log_message('error', $e->getMessage());

            $this->response([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE EMPLOYEE
    |--------------------------------------------------------------------------
    */

    public function employee_put($id)
    {
        try {

            $data = $this->put();

            $updated = $this->employee_model
                ->update_employee($id, $data);

            if (!$updated) {

                return $this->response([
                    'status' => false,
                    'message' => 'Failed to update employee'
                ], 400);
            }

            $this->response([
                'status' => true,
                'message' => 'Employee updated successfully'
            ], 200);

        } catch (\Throwable $e) {

            $this->response([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE EMPLOYEE
    |--------------------------------------------------------------------------
    */

    public function employee_delete($id)
    {
        try {

            $deleted = $this->employee_model
                ->delete($id);

            if (!$deleted) {

                return $this->response([
                    'status' => false,
                    'message' => 'Failed to delete employee'
                ], 400);
            }

            $this->response([
                'status' => true,
                'message' => 'Employee deleted successfully'
            ], 200);

        } catch (\Throwable $e) {

            $this->response([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /*
|--------------------------------------------------------------------------
| ATTENDANCE DASHBOARD
|--------------------------------------------------------------------------
*/

public function attendance_dashboard_get()
{
    try {

        $date = $this->get('date') ?: date('Y-m-d');

        $summary = $this->Attendance_model
            ->get_summary($date);

        $attendance = $this->Attendance_model
            ->get_attendance_list($date);

        $this->response([
            'status' => true,
            'data' => [
                'summary' => $summary,
                'attendance' => $attendance
            ]
        ], 200);

    } catch (\Throwable $e) {

        $this->response([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

/*
|--------------------------------------------------------------------------
| DAILY ATTENDANCE
|--------------------------------------------------------------------------
*/

public function attendance_get()
{
    try {

        $date = $this->get('date') ?: date('Y-m-d');

        $attendance = $this->Attendance_model
            ->get_attendance_list($date);

        $this->response([
            'status' => true,
            'count'  => count($attendance),
            'data'   => $attendance
        ], 200);

    } catch (\Throwable $e) {

        $this->response([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

/*
|--------------------------------------------------------------------------
| MONTHLY ATTENDANCE
|--------------------------------------------------------------------------
*/

public function monthly_attendance_get()
{
    try {

        $month = $this->get('month') ?: date('m');
        $year  = $this->get('year') ?: date('Y');

        $report = $this->Attendance_model
            ->get_monthly_report($month, $year);

        $this->response([
            'status' => true,
            'data'   => $report
        ], 200);

    } catch (\Throwable $e) {

        $this->response([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

/*
|--------------------------------------------------------------------------
| SHIFTS
|--------------------------------------------------------------------------
*/

public function shifts_get()
{
    try {

        $shifts = $this->Attendance_model
            ->get_all_shifts();

        $this->response([
            'status' => true,
            'count'  => count($shifts),
            'data'   => $shifts
        ], 200);

    } catch (\Throwable $e) {

        $this->response([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

/*
|--------------------------------------------------------------------------
| ATTENDANCE SUMMARY
|--------------------------------------------------------------------------
*/

public function attendance_summary_post()
{
    try {

        $employee_id = $this->post('employee_id');
        $date        = $this->post('date');

        $this->db->where('employee_id', $employee_id);
        $this->db->where('attendance_date', $date);

        $row = $this->db
            ->get(db_prefix().'hrms_attendance_summary')
            ->row();

        if ($row) {

            $this->response([
                'status' => true,
                'data' => [
                    'first_in' => $row->first_in,
                    'last_out' => $row->last_out,
                    'total_work_hours' => $row->total_work_hours,
                    'overtime_hours' => $row->overtime_hours
                ]
            ], 200);

        } else {

            $this->response([
                'status' => true,
                'data' => [
                    'first_in' => null,
                    'last_out' => null,
                    'total_work_hours' => '0.00',
                    'overtime_hours' => '0.00'
                ]
            ], 200);
        }

    } catch (\Throwable $e) {

        $this->response([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
/*
|--------------------------------------------------------------------------
| MY SHIFT
|--------------------------------------------------------------------------
*/

public function my_shift_get()
{
    try {

        $employee_id = $this->staffInfo['data']->staff_id;

        $shift = $this->db
            ->select('s.*')
            ->from(db_prefix().'hrms_employee_shifts es')
            ->join(
                db_prefix().'hrms_shifts s',
                's.id = es.shift_id',
                'left'
            )
            ->where('es.employee_id', $employee_id)
            ->where('es.effective_to IS NULL', null, false)
            ->get()
            ->row();

        $this->response([
            'status' => true,
            'data' => $shift
        ], 200);

    } catch (\Throwable $e) {

        $this->response([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
/*
|--------------------------------------------------------------------------
| MY ATTENDANCE CALENDAR
|--------------------------------------------------------------------------
*/

public function my_attendance_calendar_get()
{
    try {

        $employee_id = $this->staffInfo['data']->staff_id;

        $month = $this->get('month') ?: date('m');
        $year  = $this->get('year') ?: date('Y');

        $calendar = $this->db
            ->where('employee_id', $employee_id)
            ->where('MONTH(attendance_date)', $month)
            ->where('YEAR(attendance_date)', $year)
            ->get(db_prefix().'hrms_attendance_summary')
            ->result();

        $holidays = $this->db
            ->where('MONTH(holiday_date)', $month)
            ->where('YEAR(holiday_date)', $year)
            ->get(db_prefix().'hrms_holidays')
            ->result();

        $this->response([
            'status' => true,
            'data' => [
                'attendance' => $calendar,
                'holidays' => $holidays
            ]
        ], 200);

    } catch (\Throwable $e) {

        $this->response([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
/*
|--------------------------------------------------------------------------
| MY MONTHLY ATTENDANCE
|--------------------------------------------------------------------------
*/

public function my_monthly_attendance_get()
{
    try {

        $employee_id = $this->staffInfo['data']->staff_id;

        $month = $this->get('month') ?: date('m');
        $year  = $this->get('year') ?: date('Y');

        $attendance = $this->db
            ->where('employee_id', $employee_id)
            ->where('MONTH(attendance_date)', $month)
            ->where('YEAR(attendance_date)', $year)
            ->order_by('attendance_date', 'ASC')
            ->get(db_prefix().'hrms_attendance_summary')
            ->result();

        $this->response([
            'status' => true,
            'count'  => count($attendance),
            'data'   => $attendance
        ], 200);

    } catch (\Throwable $e) {

        $this->response([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
/*
|--------------------------------------------------------------------------
| MY DAILY LOGS
|--------------------------------------------------------------------------
*/

public function my_attendance_logs_get()
{
    try {

        $employee_id = $this->staffInfo['data']->staff_id;

        $date = $this->get('date') ?: date('Y-m-d');

        $logs = $this->db
            ->where('employee_id', $employee_id)
            ->where('DATE(punch_time)', $date)
            ->order_by('punch_time', 'ASC')
            ->get(db_prefix().'hrms_attendance_logs')
            ->result();

        $this->response([
            'status' => true,
            'count'  => count($logs),
            'data'   => $logs
        ], 200);

    } catch (\Throwable $e) {

        $this->response([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
/*
|--------------------------------------------------------------------------
| MY ATTENDANCE DASHBOARD
|--------------------------------------------------------------------------
*/

public function my_attendance_dashboard_get()
{
    try {

        $employee_id = $this->staffInfo['data']->staff_id;
        $today = date('Y-m-d');

        // Today's summary
        $summary = $this->db
            ->where('employee_id', $employee_id)
            ->where('attendance_date', $today)
            ->get(db_prefix().'hrms_attendance_summary')
            ->row();

        // Current shift
        $shift = $this->db
            ->select('s.*')
            ->from(db_prefix().'hrms_employee_shifts es')
            ->join(
                db_prefix().'hrms_shifts s',
                's.id = es.shift_id',
                'left'
            )
            ->where('es.employee_id', $employee_id)
            ->where('es.effective_to IS NULL', null, false)
            ->get()
            ->row();

        $this->response([
            'status' => true,
            'data' => [
                'today_summary' => $summary,
                'current_shift' => $shift
            ]
        ], 200);

    } catch (\Throwable $e) {

        $this->response([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
/*
|--------------------------------------------------------------------------
| MY PAYROLL OVERVIEW
|--------------------------------------------------------------------------
*/

public function my_payroll_get()
{
    try {

        $employee_id = $this->staffInfo['data']->staff_id;

        $month = $this->get('month') ?: date('Y-m');

        $payroll = $this->payroll_model
            ->get_by_employee_month(
                $employee_id,
                $month
            );

        $this->response([
            'status' => true,
            'data'   => $payroll
        ], 200);

    } catch (\Throwable $e) {

        $this->response([
            'status'  => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
/*
|--------------------------------------------------------------------------
| MY SALARY SLIPS
|--------------------------------------------------------------------------
*/

public function my_salary_slips_get()
{
    try {

        $employee_id = $this->staffInfo['data']->staff_id;

        $slips = $this->db
            ->where('employee_id', $employee_id)
            ->order_by('payroll_month', 'DESC')
            ->get(db_prefix().'hrms_payroll_runs')
            ->result();

        $this->response([
            'status' => true,
            'count'  => count($slips),
            'data'   => $slips
        ], 200);

    } catch (\Throwable $e) {

        $this->response([
            'status'  => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
/*
|--------------------------------------------------------------------------
| SINGLE SALARY SLIP
|--------------------------------------------------------------------------
*/

public function my_salary_slip_get($id)
{
    try {

        $employee_id = $this->staffInfo['data']->staff_id;

        $slip = $this->db
            ->where('id', $id)
            ->where('employee_id', $employee_id)
            ->get(db_prefix().'hrms_payroll_runs')
            ->row();

        if (!$slip) {

            return $this->response([
                'status' => false,
                'message' => 'Salary slip not found'
            ], 404);
        }

        $this->response([
            'status' => true,
            'data'   => $slip
        ], 200);

    } catch (\Throwable $e) {

        $this->response([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
/*
|--------------------------------------------------------------------------
| SALARY COMPONENTS
|--------------------------------------------------------------------------
*/

public function my_salary_components_get($payroll_id)
{
    try {

        $components = $this->payroll_model
            ->get_components($payroll_id);

        $this->response([
            'status' => true,
            'count'  => count($components),
            'data'   => $components
        ], 200);

    } catch (\Throwable $e) {

        $this->response([
            'status' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
}