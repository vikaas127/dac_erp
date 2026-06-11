<?php

defined('BASEPATH') || exit('No direct script access allowed');

require_once __DIR__ . '/RestController.php';

use FlutexAdminApi\RestController;

class Attendance extends RestController
{

    protected $staffInfo;
    private $mobileAttendanceSchemaReady = false;
    private $visitTableName = null;
    private $salesVisitsSchemaReady = false;
    private function getInputValue($key = null)
    {
        // 1️⃣ Try normal POST (form-data / x-www-form-urlencoded)
        $value = $this->post($key);

        if ($value !== null) {
            return $value;
        }

        // 2️⃣ Fallback to JSON body
        static $json = null;
        if ($json === null) {
            $json = json_decode($this->input->raw_input_stream, true) ?? [];
        }

        return $key ? ($json[$key] ?? null) : $json;
    }


   public function __construct()
{
    parent::__construct();

    register_language_files('flutex_admin_api');
    load_admin_language();

    $this->load->helper('flutex_admin_api');

    /* =====================================================
     * STAFF SESSION LOGIN
     * ===================================================== */
    if (is_staff_logged_in()) {

        $staff = get_staff(get_staff_user_id());

        // Normalize
        if ($staff && isset($staff->staffid)) {
            $staff->staff_id = $staff->staffid;
        }

        $this->staffInfo = [
            'status' => true,
            'data'   => $staff
        ];

        log_message(
            'error',
            '[AUTH][SESSION STAFF] ' . json_encode($this->staffInfo)
        );

        return;
    }

    /* =====================================================
     * TOKEN AUTH
     * ===================================================== */
    $auth = isAuthorized();

    if (!isset($auth['status'])) {

        log_message(
            'error',
            '[AUTH][FAILED] ' . json_encode($auth)
        );

        return $this->response(
            $auth['response'],
            $auth['response_code']
        );
    }

    // Normalize again for safety
    if (
        isset($auth['data']) &&
        isset($auth['data']->staffid)
    ) {
        $auth['data']->staff_id = $auth['data']->staffid;
    }

    $this->staffInfo = $auth;

    log_message(
        'error',
        '[AUTH][FINAL STAFF INFO] ' .
        json_encode($this->staffInfo)
    );

    $this->load->model(
        'timesheets/Timesheets_model',
        'timesheets_model'
    );

    $this->load->model('staff_model');
    $this->load->helper('admin');
    $this->load->model('distribution_channel/sales_assignment_model');
}

    /**
     * ---------------------------------------------------------
     * CHECK IN
     * ---------------------------------------------------------
     */
    public function currentstatus_get()
    {
        log_message('info', '[Attendance][CurrentStatus] Request received');

        try {
            $user_id = $this->staffInfo['data']->staff_id;
            $today   = date('Y-m-d');
            $now     = date('Y-m-d H:i:s');

            $this->ensureMobileAttendanceSchema();

            // Last attendance record
            $attendance = $this->db
                ->where('staff_id', $user_id)
                ->where('DATE(date)', $today)
                ->order_by('id', 'DESC')
                ->get(db_prefix() . 'tblcheck_in_out')
                ->row_array();

            if (!$attendance) {
                return $this->response([
                    'success' => true,
                    'message' => 'No attendance record found for today',
                    'data'    => null
                ], RestController::HTTP_OK);
            }

            $type_check = (int)$attendance['type_check'];
            $status     = $type_check === 1 ? 'checked_in' : 'checked_out';

            // --------------------------------------------------
            // 🔥 CALCULATE TOTAL TIME
            // --------------------------------------------------
            $checkin = $this->db->where('staff_id', $user_id)
                ->where('DATE(date)', $today)
                ->where('type_check', 1)
                ->order_by('id', 'ASC')
                ->get(db_prefix() . 'tblcheck_in_out')
                ->row();

            $total_time = '00h 00m';

            if ($checkin) {
                $start = new DateTime($checkin->date);
                $end   = new DateTime($status === 'checked_in' ? $now : $attendance['date']);

                $seconds = max(0, $end->getTimestamp() - $start->getTimestamp());
                $hours   = floor($seconds / 3600);
                $minutes = floor(($seconds % 3600) / 60);

                $total_time = sprintf('%02dh %02dm', $hours, $minutes);
            }

            return $this->response([
                'success' => true,
                'message' => 'Status fetched successfully',
                'data' => [
                    'type_check' => $type_check,
                    'date'       => $attendance['date'],
                    'status'     => $status,
                    'total_time' => $total_time
                ]
            ], RestController::HTTP_OK);
        } catch (\Throwable $e) {
            log_message('error', '[Attendance][CurrentStatus] ' . $e->getMessage());
            return $this->response(
                ['message' => _l('something_went_wrong')],
                RestController::HTTP_INTERNAL_ERROR
            );
        }
    }

    public function checkin_post()
    {
        log_message('info', '[Attendance][CheckIn] ===== START =====');

        try {

            $staff = $this->staffInfo['data'] ?? null;

            if (!$staff) {
                return $this->response([
                    'message' => 'Unauthorized'
                ], RestController::HTTP_UNAUTHORIZED);
            }

            $staffId = $staff->staff_id;

            $lat = $this->post('lat');
            $lng = $this->post('lng');

            if (!$lat || !$lng) {
                return $this->response([
                    'message' => 'Latitude and longitude required'
                ], RestController::HTTP_BAD_REQUEST);
            }

            if (!is_numeric($lat) || !is_numeric($lng)) {
                return $this->response([
                    'message' => 'Invalid coordinates'
                ], RestController::HTTP_BAD_REQUEST);
            }

            $lat = (float)$lat;
            $lng = (float)$lng;

            $now   = date('Y-m-d H:i:s');
            $today = date('Y-m-d');
            $accuracy = 10;

            $address = $this->getAddressFromLatLong($lat, $lng);

            $this->ensureMobileAttendanceSchema();

            $this->db->trans_begin();

            /* ================= 1️⃣ Insert Into tblcheck_in_out ================= */

            $this->db->insert(db_prefix() . 'tblcheck_in_out', [
                'staff_id'   => $staffId,
                'lat'        => $lat,
                'long'       => $lng,
                'address'    => $address,
                'date'       => $now,
                'type_check' => 1 // Office
            ]);

            /* ================= 2️⃣ Insert Into checkout_history ================= */

            $this->db->insert(db_prefix() . 'tblcheckout_history', [
                'staff_id'    => $staffId,
                'latitude'    => $lat,
                'longitude'   => $lng,
                'distance'    => 0,
                'address'     => $address,
                'recorded_at' => $now,
                'accuracy_m'  => $accuracy,
                'created_by'  => $staffId,
                'device_id'   => $this->input->user_agent(),
                'type_check'  => 2,     // Office
                'type'        => 'OI'   // Office Checkin
            ]);

            /* ================= 3️⃣ Update Daily Summary ================= */

            $summaryTable = db_prefix() . 'tblstaff_daily_summary';

            $sql = "
        INSERT INTO {$summaryTable}
(staff_id,date,last_lat,last_lng,last_ping,last_accuracy,distance,final_check_in_at,checkin_json)
VALUES (?,?,?,?,?,?,?,?,?)
ON DUPLICATE KEY UPDATE
last_lat = VALUES(last_lat),
last_lng = VALUES(last_lng),
last_ping = VALUES(last_ping),
last_accuracy = VALUES(last_accuracy),
final_check_in_at = VALUES(final_check_in_at),
checkin_json = VALUES(checkin_json)

        ";

            $this->db->query($sql, [
                $staffId,
                $today,
                $lat,
                $lng,
                $now,
                $accuracy,
                0,
                $now,
                json_encode([
                    'lat' => $lat,
                    'lng' => $lng,
                    'address' => $address
                ])
            ]);
try {
                $this->insertAttendanceLog([
                    'employee_id'   => $staffId,
                    'punch_type'    => 'in',
                    'punch_time'    => $now,
                    'latitude'      => $lat,
                    'longitude'     => $lng,
                    'device_id'     => $this->input->user_agent(),
                    'face_verified' => 1,
                    'source'        => 'mobile',
                ]);
            } catch (\Throwable $hrmsLogError) {
                log_message('error', '[Attendance][CheckIn] HRMS log skipped: ' . $hrmsLogError->getMessage());
            }

            /* ================= TRANSACTION CHECK ================= */

            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();

                return $this->response([
                    'message' => 'Check-in failed'
                ], RestController::HTTP_INTERNAL_ERROR);
            }

            $this->db->trans_commit();

            log_message('info', '[Attendance][CheckIn] ===== SUCCESS =====');

            return $this->response([
                'message' => 'Checked in successfully',
                'data'    => ['check_in_at' => $now]
            ], RestController::HTTP_OK);
        } catch (\Throwable $e) {

            log_message('error', '[Attendance][CheckIn] ' . $e->getMessage());

            return $this->response([
                'message' => 'Something went wrong'
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }
private function attendanceTableExists($table)
{
    if ($this->db->table_exists($table)) {
        return true;
    }

    $result = $this->db->query('SHOW TABLES LIKE ' . $this->db->escape($table));

    return $result && $result->num_rows() > 0;
}

private function isAttendanceSchemaAlreadyExistsError(\Throwable $e)
{
    $message = strtolower($e->getMessage());

    return strpos($message, 'already exists') !== false
        || strpos($message, 'duplicate column') !== false;
}

private function hrmsUsesLegacyTables()
{
    return $this->attendanceTableExists(db_prefix().'tblhrms_employees');
}

private function hrmsTable($name)
{
    if ($this->hrmsUsesLegacyTables()) {
        return db_prefix().'tblhrms_'.$name;
    }

    return db_prefix().'hrms_'.$name;
}

private function ensureAttendanceTable($table, $createSql)
{
    if ($this->attendanceTableExists($table)) {
        return;
    }

    try {
        $this->db->query($createSql);
        log_message('info', '[Attendance][Schema] Created table ' . $table);
    } catch (\Throwable $e) {
        if ($this->attendanceTableExists($table) || $this->isAttendanceSchemaAlreadyExistsError($e)) {
            log_message('info', '[Attendance][Schema] Table already exists ' . $table);

            return;
        }

        log_message('error', '[Attendance][Schema] Failed creating ' . $table . ': ' . $e->getMessage());
        throw $e;
    }
}

private function ensureAttendanceColumns($table, array $columns)
{
    if (!$this->attendanceTableExists($table)) {
        return;
    }

    foreach ($columns as $name => $definition) {
        if ($this->db->field_exists($name, $table)) {
            continue;
        }

        try {
            $this->db->query('ALTER TABLE `' . $table . '` ADD COLUMN `' . $name . '` ' . $definition);
            log_message('info', '[Attendance][Schema] Added column ' . $table . '.' . $name);
        } catch (\Throwable $e) {
            if ($this->db->field_exists($name, $table) || $this->isAttendanceSchemaAlreadyExistsError($e)) {
                continue;
            }

            log_message('error', '[Attendance][Schema] Failed adding ' . $table . '.' . $name . ': ' . $e->getMessage());
        }
    }
}

private function ensureMobileAttendanceSchema()
{
    if ($this->mobileAttendanceSchemaReady) {
        return;
    }

    try {
        $this->provisionMobileAttendanceSchema();
        $this->mobileAttendanceSchemaReady = true;
    } catch (\Throwable $e) {
        log_message('error', '[Attendance][Schema] ' . $e->getMessage());
        if ($this->attendanceTableExists(db_prefix().'tblcheck_in_out')) {
            $this->mobileAttendanceSchemaReady = true;
        }
    }
}

private function provisionMobileAttendanceSchema()
{
    $charset = $this->db->char_set ?: 'utf8mb4';
    $collate = $this->db->dbcollat ?: 'utf8mb4_general_ci';

    $checkInOut = db_prefix() . 'tblcheck_in_out';
    $this->ensureAttendanceTable($checkInOut, "CREATE TABLE `{$checkInOut}` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `staff_id` int(11) DEFAULT NULL,
        `date` datetime DEFAULT NULL,
        `type_check` int(11) DEFAULT NULL,
        `type` varchar(5) NOT NULL DEFAULT 'W',
        `route_point_id` int(11) DEFAULT NULL,
        `workplace_id` int(11) NOT NULL DEFAULT 0,
        `lat` decimal(10,8) DEFAULT NULL,
        `long` decimal(11,8) DEFAULT NULL,
        `address` varchar(500) DEFAULT NULL,
        `ip` varchar(100) DEFAULT NULL,
        `device_type` varchar(255) DEFAULT NULL,
        `device_fingerprint` varchar(255) DEFAULT NULL,
        `distance` decimal(10,2) DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collate}");
    $this->ensureAttendanceColumns($checkInOut, [
        'staff_id'           => 'int(11) DEFAULT NULL',
        'date'               => 'datetime DEFAULT NULL',
        'type_check'         => 'int(11) DEFAULT NULL',
        'type'               => "varchar(5) NOT NULL DEFAULT 'W'",
        'route_point_id'     => 'int(11) DEFAULT NULL',
        'workplace_id'       => 'int(11) NOT NULL DEFAULT 0',
        'lat'                => 'decimal(10,8) DEFAULT NULL',
        'long'               => 'decimal(11,8) DEFAULT NULL',
        'address'            => 'varchar(500) DEFAULT NULL',
        'ip'                 => 'varchar(100) DEFAULT NULL',
        'device_type'        => 'varchar(255) DEFAULT NULL',
        'device_fingerprint' => 'varchar(255) DEFAULT NULL',
        'distance'           => 'decimal(10,2) DEFAULT NULL',
    ]);

    $checkoutHistory = db_prefix() . 'tblcheckout_history';
    $this->ensureAttendanceTable($checkoutHistory, "CREATE TABLE `{$checkoutHistory}` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `staff_id` int(11) NOT NULL,
        `latitude` decimal(10,8) DEFAULT NULL,
        `longitude` decimal(11,8) DEFAULT NULL,
        `distance` decimal(10,3) NOT NULL DEFAULT 0.000,
        `address` varchar(255) DEFAULT NULL,
        `recorded_at` datetime NOT NULL,
        `accuracy_m` decimal(10,2) DEFAULT NULL,
        `type_check` tinyint(1) DEFAULT NULL,
        `type` varchar(20) DEFAULT NULL,
        `created_by` int(11) DEFAULT NULL,
        `device_id` varchar(100) DEFAULT NULL,
        `visit_id` int(11) DEFAULT NULL,
        `entry_id` int(11) DEFAULT NULL,
        `entity_type` varchar(50) DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collate}");
    $this->ensureAttendanceColumns($checkoutHistory, [
        'staff_id'    => 'int(11) NOT NULL',
        'latitude'    => 'decimal(10,8) DEFAULT NULL',
        'longitude'   => 'decimal(11,8) DEFAULT NULL',
        'distance'    => 'decimal(10,3) NOT NULL DEFAULT 0.000',
        'address'     => 'varchar(255) DEFAULT NULL',
        'recorded_at' => 'datetime NOT NULL',
        'accuracy_m'  => 'decimal(10,2) DEFAULT NULL',
        'type_check'  => 'tinyint(1) DEFAULT NULL',
        'type'        => 'varchar(20) DEFAULT NULL',
        'created_by'  => 'int(11) DEFAULT NULL',
        'device_id'   => 'varchar(100) DEFAULT NULL',
        'visit_id'    => 'int(11) DEFAULT NULL',
        'entry_id'    => 'int(11) DEFAULT NULL',
        'entity_type' => 'varchar(50) DEFAULT NULL',
    ]);

    $dailySummary = db_prefix() . 'tblstaff_daily_summary';
    $this->ensureAttendanceTable($dailySummary, "CREATE TABLE `{$dailySummary}` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `staff_id` int(11) NOT NULL,
        `date` date NOT NULL,
        `final_check_in_at` datetime DEFAULT NULL,
        `checkin_json` longtext DEFAULT NULL,
        `final_check_out_at` datetime DEFAULT NULL,
        `checkout_json` longtext DEFAULT NULL,
        `distance` decimal(10,2) DEFAULT 0.00,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `last_lat` decimal(10,8) DEFAULT NULL,
        `last_lng` decimal(11,8) DEFAULT NULL,
        `last_ping` datetime DEFAULT NULL,
        `last_accuracy` decimal(10,2) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_staff_date` (`staff_id`,`date`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collate}");
    $this->ensureAttendanceColumns($dailySummary, [
        'staff_id'           => 'int(11) NOT NULL',
        'date'               => 'date NOT NULL',
        'final_check_in_at'  => 'datetime DEFAULT NULL',
        'checkin_json'       => 'longtext DEFAULT NULL',
        'final_check_out_at' => 'datetime DEFAULT NULL',
        'checkout_json'      => 'longtext DEFAULT NULL',
        'distance'           => 'decimal(10,2) DEFAULT 0.00',
        'created_at'         => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'last_lat'           => 'decimal(10,8) DEFAULT NULL',
        'last_lng'           => 'decimal(11,8) DEFAULT NULL',
        'last_ping'          => 'datetime DEFAULT NULL',
        'last_accuracy'      => 'decimal(10,2) DEFAULT NULL',
    ]);

    if ($this->hrmsUsesLegacyTables()) {
        return;
    }

    $hrmsEmployees = db_prefix() . 'hrms_employees';
    $this->ensureAttendanceTable($hrmsEmployees, "CREATE TABLE `{$hrmsEmployees}` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `staff_id` int(10) unsigned DEFAULT NULL,
        `employee_code` varchar(50) DEFAULT NULL,
        `branch_id` int(10) unsigned DEFAULT NULL,
        `department_id` int(10) unsigned DEFAULT NULL,
        `designation` varchar(150) DEFAULT NULL,
        `role_id` int(10) unsigned DEFAULT NULL,
        `reporting_manager_id` int(10) unsigned DEFAULT NULL,
        `cost_center` varchar(100) DEFAULT NULL,
        `status` varchar(20) DEFAULT 'active',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `employee_code` (`employee_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collate}");
    $this->ensureAttendanceColumns($hrmsEmployees, [
        'staff_id'             => 'int(10) unsigned DEFAULT NULL',
        'employee_code'        => 'varchar(50) DEFAULT NULL',
        'branch_id'            => 'int(10) unsigned DEFAULT NULL',
        'department_id'        => 'int(10) unsigned DEFAULT NULL',
        'designation'          => 'varchar(150) DEFAULT NULL',
        'role_id'              => 'int(10) unsigned DEFAULT NULL',
        'reporting_manager_id' => 'int(10) unsigned DEFAULT NULL',
        'cost_center'          => 'varchar(100) DEFAULT NULL',
        'status'               => "varchar(20) DEFAULT 'active'",
        'created_at'           => 'timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at'           => 'timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP',
    ]);

    $employeePersonal = db_prefix() . 'hrms_employee_personal';
    $this->ensureAttendanceTable($employeePersonal, "CREATE TABLE `{$employeePersonal}` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `employee_id` int(10) unsigned NOT NULL,
        `first_name` varchar(100) DEFAULT NULL,
        `last_name` varchar(100) DEFAULT NULL,
        `personal_email` varchar(150) DEFAULT NULL,
        `mobile_number` varchar(30) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `employee_id` (`employee_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collate}");
    $this->ensureAttendanceColumns($employeePersonal, [
        'employee_id'    => 'int(10) unsigned NOT NULL',
        'first_name'     => 'varchar(100) DEFAULT NULL',
        'last_name'      => 'varchar(100) DEFAULT NULL',
        'personal_email' => 'varchar(150) DEFAULT NULL',
        'mobile_number'  => 'varchar(30) DEFAULT NULL',
    ]);

    $employeeEmployment = db_prefix() . 'hrms_employee_employment';
    $this->ensureAttendanceTable($employeeEmployment, "CREATE TABLE `{$employeeEmployment}` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `employee_id` int(10) unsigned NOT NULL,
        `employment_type` varchar(30) DEFAULT NULL,
        `work_type` varchar(30) DEFAULT NULL,
        `joining_date` date DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `employee_id` (`employee_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collate}");
    $this->ensureAttendanceColumns($employeeEmployment, [
        'employee_id'     => 'int(10) unsigned NOT NULL',
        'employment_type' => 'varchar(30) DEFAULT NULL',
        'work_type'       => 'varchar(30) DEFAULT NULL',
        'joining_date'    => 'date DEFAULT NULL',
    ]);

    $salaryStructures = db_prefix() . 'hrms_salary_structures';
    $this->ensureAttendanceTable($salaryStructures, "CREATE TABLE `{$salaryStructures}` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `employee_id` int(10) unsigned NOT NULL,
        `salary_type` varchar(30) DEFAULT NULL,
        `basic` decimal(15,2) DEFAULT 0,
        `ctc` decimal(15,2) DEFAULT 0,
        `is_active` tinyint(1) DEFAULT 1,
        `effective_from` date DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `employee_id` (`employee_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collate}");
    $this->ensureAttendanceColumns($salaryStructures, [
        'employee_id'    => 'int(10) unsigned NOT NULL',
        'salary_type'    => 'varchar(30) DEFAULT NULL',
        'basic'          => 'decimal(15,2) DEFAULT 0',
        'ctc'            => 'decimal(15,2) DEFAULT 0',
        'is_active'      => 'tinyint(1) DEFAULT 1',
        'effective_from' => 'date DEFAULT NULL',
    ]);

    $attendanceLogs = db_prefix() . 'hrms_attendance_logs';
    $this->ensureAttendanceTable($attendanceLogs, "CREATE TABLE `{$attendanceLogs}` (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `employee_id` int(10) unsigned NOT NULL,
        `punch_in_time` datetime DEFAULT NULL,
        `punch_out_time` datetime DEFAULT NULL,
        `punch_time` datetime DEFAULT NULL,
        `punch_type` varchar(10) DEFAULT NULL,
        `source` varchar(20) DEFAULT 'mobile',
        `device_id` varchar(100) DEFAULT NULL,
        `latitude` decimal(10,8) DEFAULT NULL,
        `longitude` decimal(11,8) DEFAULT NULL,
        `ip_address` varchar(100) DEFAULT NULL,
        `face_verified` tinyint(1) DEFAULT 0,
        `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `employee_id` (`employee_id`),
        KEY `punch_in_time` (`punch_in_time`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collate}");
    $this->ensureAttendanceColumns($attendanceLogs, [
        'employee_id'    => 'int(10) unsigned NOT NULL',
        'punch_in_time'  => 'datetime DEFAULT NULL',
        'punch_out_time' => 'datetime DEFAULT NULL',
        'punch_time'     => 'datetime DEFAULT NULL',
        'punch_type'     => 'varchar(10) DEFAULT NULL',
        'source'         => "varchar(20) DEFAULT 'mobile'",
        'device_id'      => 'varchar(100) DEFAULT NULL',
        'latitude'       => 'decimal(10,8) DEFAULT NULL',
        'longitude'      => 'decimal(11,8) DEFAULT NULL',
        'ip_address'     => 'varchar(100) DEFAULT NULL',
        'face_verified'  => 'tinyint(1) DEFAULT 0',
        'created_at'     => 'datetime DEFAULT CURRENT_TIMESTAMP',
    ]);

    $attendanceSummary = db_prefix() . 'hrms_attendance_summary';
    $this->ensureAttendanceTable($attendanceSummary, "CREATE TABLE `{$attendanceSummary}` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `employee_id` int(11) NOT NULL,
        `attendance_date` date NOT NULL,
        `shift_id` int(11) DEFAULT NULL,
        `first_in` datetime DEFAULT NULL,
        `last_out` datetime DEFAULT NULL,
        `total_work_hours` decimal(5,2) DEFAULT 0,
        `overtime_hours` decimal(5,2) DEFAULT 0,
        `late_minutes` int(11) DEFAULT 0,
        `early_exit_minutes` int(11) DEFAULT 0,
        `status` varchar(20) DEFAULT 'present',
        `payable_days` decimal(4,2) DEFAULT 0,
        `is_lop` tinyint(1) DEFAULT 0,
        `is_locked` tinyint(1) DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uniq_employee_date` (`employee_id`, `attendance_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collate}");
    $this->ensureAttendanceColumns($attendanceSummary, [
        'employee_id'        => 'int(11) NOT NULL',
        'attendance_date'    => 'date NOT NULL',
        'shift_id'           => 'int(11) DEFAULT NULL',
        'first_in'           => 'datetime DEFAULT NULL',
        'last_out'           => 'datetime DEFAULT NULL',
        'total_work_hours'   => 'decimal(5,2) DEFAULT 0',
        'overtime_hours'     => 'decimal(5,2) DEFAULT 0',
        'late_minutes'       => 'int(11) DEFAULT 0',
        'early_exit_minutes' => 'int(11) DEFAULT 0',
        'status'             => "varchar(20) DEFAULT 'present'",
        'payable_days'       => 'decimal(4,2) DEFAULT 0',
        'is_lop'             => 'tinyint(1) DEFAULT 0',
        'is_locked'          => 'tinyint(1) DEFAULT 0',
    ]);
}

private function resolveHrmsEmployeeId($staffOrEmployeeId)
{
    $staffOrEmployeeId = (int) $staffOrEmployeeId;
    if ($staffOrEmployeeId <= 0) {
        return null;
    }

    $empTable = $this->hrmsTable('employees');

    $employee = $this->db
        ->where('staff_id', $staffOrEmployeeId)
        ->get($empTable)
        ->row();

    if ($employee) {
        return (int) $employee->id;
    }

    $employee = $this->db
        ->where('id', $staffOrEmployeeId)
        ->get($empTable)
        ->row();

    if ($employee) {
        return (int) $employee->id;
    }

    return $this->ensureHrmsEmployeeForStaff($staffOrEmployeeId);
}

private function ensureHrmsEmployeeForStaff($staffId)
{
    $staffId  = (int) $staffId;
    $empTable = $this->hrmsTable('employees');

    $employee = $this->db->where('staff_id', $staffId)->get($empTable)->row();
    if ($employee) {
        return (int) $employee->id;
    }

    if ($this->hrmsUsesLegacyTables()) {
        $staff = $this->db->where('staffid', $staffId)->get(db_prefix().'staff')->row();
        if (!$staff) {
            return null;
        }

        $insert = [
            'staff_id' => $staffId,
            'status'   => 'active',
        ];
        if ($this->db->field_exists('employee_code', $empTable)) {
            $insert['employee_code'] = 'EMP'.str_pad((string) $staffId, 4, '0', STR_PAD_LEFT);
        }
        if ($this->db->field_exists('role_id', $empTable) && isset($staff->role)) {
            $insert['role_id'] = (int) $staff->role;
        }

        $this->db->insert($empTable, $insert);

        return (int) $this->db->insert_id();
    }

    $this->load->model('hrms/Employee_model', 'hrms_employee_model');
    $this->hrms_employee_model->bulk_create_from_staff([$staffId]);

    $employee = $this->db->where('staff_id', $staffId)->get($empTable)->row();

    return $employee ? (int) $employee->id : null;
}

private function syncHrmsCheckout($user_id, $today, $now, $lat, $lng, $device_id, $officeCheckin = null)
{
    $hrmsEmployeeId = $this->resolveHrmsEmployeeId($user_id);
    if (!$hrmsEmployeeId) {
        log_message('error', '[Attendance][Checkout] Skipping HRMS summary; no employee for staff '.$user_id);

        return;
    }

    $logsTable       = $this->hrmsTable('attendance_logs');
    $attendanceTable = $this->hrmsTable('attendance_summary');

    $this->db->where('employee_id', $hrmsEmployeeId);
    $this->db->where('DATE(created_at)', $today);
    $this->db->order_by('id', 'DESC');
    $logRecord = $this->db->get($logsTable)->row();

    if ((!$logRecord || empty($logRecord->punch_in_time)) && $officeCheckin && !empty($officeCheckin->date)) {
        $this->insertAttendanceLog([
            'employee_id'   => $user_id,
            'punch_type'    => 'in',
            'punch_time'    => $officeCheckin->date,
            'latitude'      => $officeCheckin->lat ?? $lat,
            'longitude'     => $officeCheckin->long ?? $lng,
            'device_id'     => $device_id,
            'face_verified' => 1,
            'source'        => 'mobile',
        ]);

        $this->db->where('employee_id', $hrmsEmployeeId);
        $this->db->where('DATE(created_at)', $today);
        $this->db->order_by('id', 'DESC');
        $logRecord = $this->db->get($logsTable)->row();
    }

    $logResult = $this->insertAttendanceLog([
        'employee_id'   => $user_id,
        'punch_type'    => 'out',
        'punch_time'    => $now,
        'latitude'      => $lat,
        'longitude'     => $lng,
        'device_id'     => $device_id,
        'face_verified' => 1,
        'source'        => 'mobile',
    ]);

    $summary = $this->db
        ->where('employee_id', $hrmsEmployeeId)
        ->where('attendance_date', $today)
        ->get($attendanceTable)
        ->row();

    $firstIn = null;
    if ($summary && !empty($summary->first_in)) {
        $firstIn = $summary->first_in;
    } elseif ($officeCheckin && !empty($officeCheckin->date)) {
        $firstIn = $officeCheckin->date;
    } elseif ($logRecord && !empty($logRecord->punch_in_time)) {
        $firstIn = $logRecord->punch_in_time;
    }

    $work_hours = 0;
    if ($firstIn) {
        $work_hours = round(max(0, strtotime($now) - strtotime($firstIn)) / 3600, 2);
    }

    if ($summary) {
        $this->db->where('employee_id', $hrmsEmployeeId);
        $this->db->where('attendance_date', $today);
        $this->db->update($attendanceTable, [
            'last_out'         => $now,
            'total_work_hours' => $work_hours,
        ]);

        return;
    }

    if (empty($logResult['status']) && !$firstIn) {
        log_message('error', '[Attendance][Checkout] Skipping HRMS summary insert; no HRMS check-in for employee '.$hrmsEmployeeId);

        return;
    }

    $this->db->insert($attendanceTable, [
        'employee_id'      => $hrmsEmployeeId,
        'attendance_date'  => $today,
        'first_in'         => $firstIn ?: $now,
        'last_out'         => $now,
        'total_work_hours' => $work_hours,
        'status'           => 'present',
        'payable_days'     => 1,
    ]);
}

private function insertAttendanceLog($data)
{
    log_message('info', '[Attendance][insertAttendanceLog] Start: ' . json_encode($data));

    $this->ensureMobileAttendanceSchema();

    $employee_id = $this->resolveHrmsEmployeeId($data['employee_id'] ?? 0);
    if (!$employee_id) {
        log_message('error', '[Attendance][insertAttendanceLog] No HRMS employee for staff/employee ' . ($data['employee_id'] ?? 'NULL'));

        return [
            'status'  => false,
            'message' => 'HRMS employee profile not found',
        ];
    }

    $today = date('Y-m-d');

    // 🔍 Get today's record
    $this->db->where('employee_id', $employee_id);
    $this->db->where('DATE(created_at)', $today);
    $this->db->order_by('id', 'DESC');
    $logsTable = $this->hrmsTable('attendance_logs');
    $record = $this->db->get($logsTable)->row();

    log_message('info', '[Attendance][insertAttendanceLog] Today\'s record found: ' . ($record ? json_encode($record) : 'None'));

    // =====================
    // ✅ CHECK-IN
    // =====================
    if ($data['punch_type'] == 'in') {
        log_message('info', '[Attendance][insertAttendanceLog] Processing CHECK-IN');

        if ($record && $record->punch_in_time != null) {
            log_message('error', '[Attendance][insertAttendanceLog] Already checked in today for employee ' . $employee_id);
            return [
                'status' => false,
                'message' => 'Already checked in today'
            ];
        }

        $insert_data = [
            'employee_id'   => $employee_id,
            'punch_in_time' => $data['punch_time'],
            'source'        => $data['source'] ?? 'mobile',
            'device_id'     => $data['device_id'] ?? null,
            'latitude'      => $data['latitude'] ?? null,
            'longitude'     => $data['longitude'] ?? null,
            'ip_address'    => $data['ip_address'] ?? $this->input->ip_address(),
            'face_verified' => $data['face_verified'] ?? 0,
            'created_at'    => date('Y-m-d H:i:s'),
        ];

        $this->db->insert($logsTable, $insert_data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_message('info', '[Attendance][insertAttendanceLog] Check-in log inserted successfully. ID: ' . $insert_id);
            return [
                'status' => true,
                'message' => 'Check-in success'
            ];
        } else {
            log_message('error', '[Attendance][insertAttendanceLog] Failed to insert Check-in log for employee ' . $employee_id);
            return [
                'status' => false,
                'message' => 'Failed to insert log'
            ];
        }
    }

    // =====================
    // ✅ CHECK-OUT
    // =====================
    if ($data['punch_type'] == 'out') {
        log_message('info', '[Attendance][insertAttendanceLog] Processing CHECK-OUT');

        if (!$record || $record->punch_in_time == null) {
            log_message('error', '[Attendance][insertAttendanceLog] Check-in not found for employee ' . $employee_id);
            return [
                'status' => false,
                'message' => 'Check-in not found'
            ];
        }

        if ($record->punch_out_time != null) {
            log_message('error', '[Attendance][insertAttendanceLog] Already checked out today for employee ' . $employee_id);
            return [
                'status' => false,
                'message' => 'Already checked out'
            ];
        }

        $update_data = [
            'punch_out_time' => $data['punch_time'],
            'latitude'       => $data['latitude'] ?? null,
            'longitude'      => $data['longitude'] ?? null,
            'ip_address'     => $data['ip_address'] ?? $this->input->ip_address(),
            'device_id'      => $data['device_id'] ?? null,
            'face_verified'  => $data['face_verified'] ?? 0,
            'source'         => $data['source'] ?? 'mobile',
        ];

        $this->db->where('id', $record->id);
        $this->db->update($logsTable, $update_data);

        if ($this->db->affected_rows() > 0) {
            log_message('info', '[Attendance][insertAttendanceLog] Check-out log updated successfully for log ID: ' . $record->id);
            return [
                'status' => true,
                'message' => 'Check-out success'
            ];
        } else {
            log_message('error', '[Attendance][insertAttendanceLog] Failed to update Check-out log for log ID: ' . $record->id);
            return [
                'status' => false,
                'message' => 'Failed to update log'
            ];
        }
    }

    log_message('error', '[Attendance][insertAttendanceLog] Invalid punch_type: ' . ($data['punch_type'] ?? 'NULL'));
    return [
        'status' => false,
        'message' => 'Invalid type'
    ];
}

 
   

   
   
    public function getAddressFromLatLong($lat, $long)
    {
        if (empty($lat) || empty($long)) {
            return null;
        }

        // call cache function
        $address = $this->timesheets_model->get_address_cached($lat, $long);

        if ($address) {
            return $address;
        }

        return null;
    }

    /**
     * ---------------------------------------------------------
     * CHECK OUT
     * ---------------------------------------------------------
     */
    public function permissions_get()
{
    $staff_id = $this->staffInfo['data']->staff_id;

    log_activity('Permissions API called by Staff ID: ' . $staff_id);

    // 👑 Check admin (Perfex helper)
    $is_admin = is_admin($staff_id);

    log_activity('Is Admin: ' . ($is_admin ? 'YES' : 'NO'));

    $permissions = [];

    // Admin can see everything (optional: skip DB)
    if (!$is_admin) {

        log_activity('Fetching permissions from database for Staff ID: ' . $staff_id);

        $rows = $this->db
            ->where('staff_id', $staff_id)
            ->get(db_prefix() . 'staff_permissions')
            ->result_array();

        log_activity('Permissions Rows: ' . json_encode($rows));

        foreach ($rows as $row) {

            log_activity(
                'Feature: ' . $row['feature'] .
                ' | Capability: ' . $row['capability']
            );

            $permissions[$row['feature']][] = $row['capability'];
        }
    } else {

        log_activity('Admin user detected. Skipping permission DB fetch.');
    }

    $response = [
        'success'     => true,
        'is_admin'    => $is_admin,
        'permissions' => $permissions,
    ];

    log_activity('Permissions API Response: ' . json_encode($response));

    return $this->response($response, 200);
}


   
    public function checkout_post()
    {
        log_message('info', '[Attendance][Checkout] Request received');

        try {
        $user_id = $this->staffInfo['data']->staff_id ?? null;
        if (!$user_id) {
            return $this->response(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $lat = $this->post('lat');
        $lng = $this->post('lng');
        $accuracy  = $this->post('accuracy');
        $device_id = $this->post('device_id');

        if (!$lat || !$lng) {
            return $this->response([
                'success' => false,
                'message' => 'Latitude and longitude required'
            ], 400);
        }

        $now   = date('Y-m-d H:i:s');
        $today = date('Y-m-d');

        $this->ensureMobileAttendanceSchema();

        $table        = db_prefix() . 'tblcheck_in_out';
        $historyTable = db_prefix() . 'tblcheckout_history';
        $summaryTable = db_prefix() . 'tblstaff_daily_summary';

        /* ----------------------------------------------------
       1️⃣ CHECK ACTIVE CHECKIN
    ---------------------------------------------------- */
        $checkin = $this->db->where('staff_id', $user_id)
            ->where('date >=', $today . ' 00:00:00')
            ->where('date <=', $today . ' 23:59:59')
            ->where('type_check', 1)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get($table)
            ->row();

        if (!$checkin) {
            return $this->response([
                'success' => false,
                'message' => 'No active check-in found'
            ], 400);
        }

        /* ----------------------------------------------------
       2️⃣ PREVENT DOUBLE CHECKOUT (disabled — allow re-checkout flow)
    ---------------------------------------------------- */

        /* ----------------------------------------------------
       3️⃣ GET FINAL DISTANCE (already calculated earlier)
    ---------------------------------------------------- */
        $last = $this->db
            ->select('distance')
            ->where('staff_id', $user_id)
            ->where('recorded_at >=', $today . ' 00:00:00')
            ->where('recorded_at <=', $today . ' 23:59:59')
            ->order_by('recorded_at', 'DESC')
            ->limit(1)
            ->get($historyTable)
            ->row();

        $distance_km = $last ? (float)$last->distance : 0;

        log_message('info', "[Checkout] Final distance: {$distance_km}");

        /* ----------------------------------------------------
       4️⃣ INSERT CHECKOUT ROW
    ---------------------------------------------------- */
        $address = $this->getAddressFromLatLong($lat, $lng);

        $checkout_data = [
            'staff_id'   => $user_id,
            'lat'        => $lat,
            'long'       => $lng,
            'address'    => $address,
            'date'       => $now,
            'type_check' => 2,
            'distance'   => $distance_km
        ];

        $this->db->insert($table, $checkout_data);
        $checkout_id = $this->db->insert_id();

        /* ----------------------------------------------------
       5️⃣ SAVE FINAL GPS POINT
    ---------------------------------------------------- */
        $this->db->insert($historyTable, [
            'staff_id'    => $user_id,
            'latitude'    => $lat,
            'longitude'   => $lng,
            'distance'    => $distance_km,
            'accuracy_m'  => $accuracy,
            'address'     => $address,
            'device_id'   => $device_id,
            'type_check'  => 2,
            'type'        => 'OO',
            'recorded_at' => $now
        ]);

        /* ----------------------------------------------------
       6️⃣ UPDATE SUMMARY
    ---------------------------------------------------- */
        $this->db->where('staff_id', $user_id)
            ->where('date', $today)
            ->update($summaryTable, [
                'final_check_out_at' => $now,
                'checkout_json' => json_encode([
                    'lat' => $lat,
                    'lng' => $lng,
                    'address' => $address
                ]),
                'distance' => $distance_km
            ]);

        log_message('info', '[Attendance][Checkout] Success');

        /* ----------------------------------------------------
           7️⃣ UPDATE HRMS ATTENDANCE SUMMARY (non-blocking)
        ---------------------------------------------------- */
        try {
            $this->syncHrmsCheckout($user_id, $today, $now, $lat, $lng, $device_id, $checkin);
        } catch (\Throwable $hrmsError) {
            log_message('error', '[Attendance][Checkout] HRMS sync failed: '.$hrmsError->getMessage());
        }
        return $this->response([
            'success' => true,
            'message' => 'Checked out successfully',
            'data' => [
                'checkout_id' => $checkout_id,
                'distance_km' => $distance_km
            ]
        ], 200);
        } catch (\Throwable $e) {
            log_message('error', '[Attendance][Checkout] ' . $e->getMessage().' @ '.$e->getFile().':'.$e->getLine());

            return $this->response([
                'success' => false,
                'message' => 'Something went wrong',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }
    public function build_points_for_day($staff_id, $day)
    {
        // ---------------------------------------------------------------------
        // 1️⃣ DELETE EXACT DUPLICATES FROM DATABASE FIRST
        // ---------------------------------------------------------------------
        $this->db->query("
        DELETE a FROM " . db_prefix() . "tblcheckout_history a
        JOIN " . db_prefix() . "tblcheckout_history b
            ON a.staff_id = b.staff_id
           AND a.latitude = b.latitude
           AND a.longitude = b.longitude
           AND DATE_FORMAT(a.recorded_at,'%Y-%m-%d %H:%i:%s')
               = DATE_FORMAT(b.recorded_at,'%Y-%m-%d %H:%i:%s')
           AND a.id > b.id
        WHERE a.staff_id = ?
          AND DATE(a.recorded_at) = ?
    ", [$staff_id, $day]);


        // ---------------------------------------------------------------------
        // 2️⃣ FETCH CHECK-IN / CHECK-OUT LOGS
        // ---------------------------------------------------------------------
        $logs = $this->db->query("
        SELECT id, type_check, lat, `long`, address, date
        FROM " . db_prefix() . "tblcheck_in_out
        WHERE staff_id = ?
          AND DATE(date) = ?
        ORDER BY date ASC
    ", [$staff_id, $day])->result();

        if (!$logs) return [];

        $first_in = null;
        $last_out = null;

        foreach ($logs as $l) {
            if ($l->type_check == 1 && !$first_in) {
                $first_in = $l;
            }
            if ($l->type_check == 2) {
                $last_out = $l;
            }
        }

        if (!$first_in) return [];

        // If no checkout → assume end of day
        if (!$last_out) {
            $last_out = (object)[
                "lat"     => $first_in->lat,
                "long"    => $first_in->long,
                "address" => $first_in->address,
                "date"    => $day . " 23:59:59"
            ];
        }

        // ---------------------------------------------------------------------
        // 3️⃣ FETCH GPS BETWEEN CHECK-IN → CHECK-OUT
        // ---------------------------------------------------------------------
        $gps = $this->db->query("
        SELECT id, latitude, longitude, recorded_at, accuracy_m, address
        FROM " . db_prefix() . "tblcheckout_history
        WHERE staff_id = ?
          AND recorded_at >= ?
          AND recorded_at <= ?
          AND latitude IS NOT NULL
          AND longitude IS NOT NULL
          AND latitude != 0 AND longitude != 0
        ORDER BY recorded_at ASC
    ", [$staff_id, $first_in->date, $last_out->date])->result_array();


        // ---------------------------------------------------------------------
        // 4️⃣ CLEAN POINTS (REMOVE DUPLICATES, SAME TIME, TELEPORTS)
        // ---------------------------------------------------------------------
        $clean = [];
        $prevKey   = null;
        $prevTime  = null;
        $lastValid = null;

        foreach ($gps as $g) {

            $lat = (float)$g['latitude'];
            $lng = (float)$g['longitude'];
            $timestamp = $g['recorded_at'];

            $key = $lat . "|" . $lng;

            // ❌ Duplicate coordinate
            if ($key === $prevKey) continue;

            // ❌ Duplicate timestamp
            if ($timestamp === $prevTime) continue;

            // ❌ Teleport detection (junk points)
            if ($lastValid) {
                $jump = $this->haversine_distance(
                    $lastValid['lat'],
                    $lastValid['lng'],
                    $lat,
                    $lng
                );

                $timeDiff = abs(strtotime($timestamp) - strtotime($lastValid['recorded_at']));

                if ($jump > 800 && $timeDiff < 6) {
                    continue;
                }
            }

            $point = [
                'lat'         => $lat,
                'lng'         => $lng,
                'recorded_at' => $timestamp,
                'address'     => $g['address'] ?? null
            ];

            $clean[]   = $point;
            $lastValid = $point;
            $prevKey   = $key;
            $prevTime  = $timestamp;
        }

        // ---------------------------------------------------------------------
        // 5️⃣ INSERT CHECK-IN POINT FIRST
        // ---------------------------------------------------------------------
        array_unshift($clean, [
            'lat'         => (float)$first_in->lat,
            'lng'         => (float)$first_in->long,
            'recorded_at' => $first_in->date,
            'address'     => $first_in->address ?? 'Check-in'
        ]);


        // ---------------------------------------------------------------------
        // 6️⃣ INSERT CHECK-OUT POINT LAST
        // ---------------------------------------------------------------------
        $clean[] = [
            'lat'         => (float)$last_out->lat,
            'lng'         => (float)$last_out->long,
            'recorded_at' => $last_out->date,
            'address'     => $last_out->address ?? 'Check-out'
        ];

        return $clean;
    }
    private function calculate_distance_like_js($points, $apiKey)
    {
        /*
    if (count($points) < 2) return 0;

    $chunkSize = 25;
    $totalMeters = 0;

    for ($i = 0; $i < count($points) - 1; $i += ($chunkSize - 1)) {

        $chunk = array_slice($points, $i, $chunkSize);
        if (count($chunk) < 2) continue;

        $origin = $chunk[0]['lat'] . ',' . $chunk[0]['lng'];
        $destination = end($chunk)['lat'] . ',' . end($chunk)['lng'];

        $waypoints = [];
        for ($j = 1; $j < count($chunk) - 1; $j++) {
            $waypoints[] = $chunk[$j]['lat'] . ',' . $chunk[$j]['lng'];
        }

        $url = "https://maps.gomaps.pro/maps/api/directions/json?"
             . "origin=$origin&destination=$destination"
             . "&mode=driving&key=$apiKey";

        if (!empty($waypoints)) {
            $url .= "&waypoints=" . implode('|', $waypoints);
        }

        $response = json_decode(file_get_contents($url), true);

        if (!isset($response['routes'][0]['legs'])) {
            continue;
        }

        foreach ($response['routes'][0]['legs'] as $leg) {
            $totalMeters += $leg['distance']['value']; // ✔ Same as JS
        }
    }

 if (count($points) < 2) return 0;

    $total = 0;

    for ($i = 1; $i < count($points); $i++) {
        $p1 = $points[$i - 1];
        $p2 = $points[$i];

        $total += $this->haversine_distance(
            $p1['lat'], $p1['lng'],
            $p2['lat'], $p2['lng']
        );
    }

    return round($totalMeters / 1000, 2); // return KM
    
}
private function calculate_distance_like_js($points)
{
   */
        if (count($points) < 2) return 0;

        $totalMeters = 0;

        for ($i = 1; $i < count($points); $i++) {
        }

        return round($totalMeters / 1000, 2); // KM

    }
    private function haversine_distance($lat1, $lng1, $lat2, $lng2)
    {
        // Earth radius in meters
        $R = 6371000;
        // Convert degrees to radians
        $lat1 = deg2rad($lat1);
        $lng1 = deg2rad($lng1);
        $lat2 = deg2rad($lat2);
        $lng2 = deg2rad($lng2);
        // Differences
        $dLat = $lat2 - $lat1;
        $dLng = $lng2 - $lng1;
        // Haversine formula
        $a = sin($dLat / 2) ** 2 +
            cos($lat1) * cos($lat2) *
            sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        // Distance in meters
        return $R * $c;
    }


    private function visitTable()
    {
        if ($this->visitTableName !== null) {
            return $this->visitTableName;
        }

        $preferred = db_prefix() . 'sales_visits';
        $legacy    = db_prefix() . 'tblsales_visits';

        if ($this->db->table_exists($preferred)) {
            $this->visitTableName = $preferred;
        } elseif ($this->db->table_exists($legacy)) {
            $this->visitTableName = $legacy;
        } else {
            $this->visitTableName = $preferred;
        }

        return $this->visitTableName;
    }

    private function ensureSalesVisitsSchema()
    {
        if ($this->salesVisitsSchemaReady) {
            return;
        }

        try {
            $this->provisionSalesVisitsSchema();
            $this->salesVisitsSchemaReady = true;
        } catch (\Throwable $e) {
            log_message('error', '[Attendance][VisitsSchema] ' . $e->getMessage());
            if ($this->attendanceTableExists($this->visitTable())) {
                $this->salesVisitsSchemaReady = true;
            }
        }
    }

    private function provisionSalesVisitsSchema()
    {
        $table   = $this->visitTable();
        $charset = $this->db->char_set ?: 'utf8mb4';
        $collate = $this->db->dbcollat ?: 'utf8mb4_unicode_ci';

        $this->ensureAttendanceTable($table, "CREATE TABLE `{$table}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `staff_id` int(11) NOT NULL,
                `client_id` int(11) NOT NULL,
                `partner_id` int(11) DEFAULT NULL,
                `visit_date` date NOT NULL,
                `planned_time` time DEFAULT NULL,
                `notes` text DEFAULT NULL,
                `status` varchar(50) DEFAULT 'planned',
                `checkin_time` datetime DEFAULT NULL,
                `checkout_time` datetime DEFAULT NULL,
                `visit_outcome` varchar(255) DEFAULT NULL,
                `order_value` decimal(15,2) DEFAULT NULL,
                `products` text DEFAULT NULL,
                `collection_amount` decimal(15,2) DEFAULT NULL,
                `competitor` varchar(255) DEFAULT NULL,
                `samples_given` text DEFAULT NULL,
                `signature_note` text DEFAULT NULL,
                `next_followup_date` date DEFAULT NULL,
                `entity_type` varchar(20) DEFAULT 'client',
                `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
                `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_staff_id` (`staff_id`),
                KEY `idx_client_id` (`client_id`),
                KEY `idx_visit_date` (`visit_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collate}");

        $this->ensureAttendanceColumns($table, [
            'partner_id'         => 'int(11) DEFAULT NULL',
            'visit_outcome'      => 'varchar(255) DEFAULT NULL',
            'order_value'        => 'decimal(15,2) DEFAULT NULL',
            'products'           => 'text DEFAULT NULL',
            'collection_amount'  => 'decimal(15,2) DEFAULT NULL',
            'competitor'         => 'varchar(255) DEFAULT NULL',
            'samples_given'      => 'text DEFAULT NULL',
            'signature_note'     => 'text DEFAULT NULL',
            'next_followup_date' => 'date DEFAULT NULL',
            'updated_at'         => 'datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP',
        ]);

        if ($this->db->field_exists('status', $table)) {
            try {
                $this->db->query("ALTER TABLE `{$table}` MODIFY `status` varchar(50) DEFAULT 'planned'");
            } catch (\Throwable $e) {
                if (!$this->isAttendanceSchemaAlreadyExistsError($e)) {
                    log_message('error', '[Attendance][VisitsSchema] status column modify failed: ' . $e->getMessage());
                }
            }
        }
    }

    private function resolveVisitStaffId($requestedStaffId = null)
    {
        $authStaffId = (int) ($this->staffInfo['data']->staff_id ?? 0);
        $requestedStaffId = (int) ($requestedStaffId ?: $authStaffId);

        return $requestedStaffId > 0 ? $requestedStaffId : $authStaffId;
    }

    private function postCoordinates()
    {
        $lat = $this->getInputValue('lat');
        $lng = $this->getInputValue('lang');
        if ($lng === null || $lng === '') {
            $lng = $this->getInputValue('lng');
        }

        return [$lat, $lng];
    }

    private function loadDsrOrderHelper()
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $helper = module_dir_path('distribution_channel', 'helpers/dsr_order_helper.php');
        if (is_file($helper)) {
            require_once $helper;
        }
        $loaded = true;
    }

    private function enrichVisitsWithOrders(array $visits)
    {
        $this->loadDsrOrderHelper();
        if (!function_exists('dc_enrich_visit_row_with_orders')) {
            return $visits;
        }

        foreach ($visits as $index => $visit) {
            $visits[$index] = dc_enrich_visit_row_with_orders($visit);
        }

        return $visits;
    }

    private function applyDsrOrderFieldsToUpdate($visit, array $manual, array &$update)
    {
        $this->loadDsrOrderHelper();
        if (!function_exists('dc_resolve_dsr_order_fields')) {
            return null;
        }

        $resolved = dc_resolve_dsr_order_fields($visit, $manual);

        if ($resolved['order_value'] !== null) {
            $update['order_value'] = $resolved['order_value'];
        }
        if ($resolved['products'] !== null) {
            $update['products'] = $resolved['products'];
        }
        if (!empty($resolved['save_dsr_lines']) && !empty($resolved['order_lines'])) {
            dc_save_dsr_order_lines((int) $visit->id, $resolved['order_lines']);
            $resolved['lines_saved'] = true;
        }

        return $resolved;
    }

    private function resolveClientDsrContext($client_id)
    {
        $client_id = (int) $client_id;
        if ($client_id <= 0) {
            return null;
        }

        $clientTable = db_prefix().'clients';
        if (!$this->db->table_exists($clientTable)) {
            return null;
        }

        $select = ['c.userid AS client_id'];
        if ($this->db->field_exists('parent_partner_id', $clientTable)) {
            $select[] = 'c.parent_partner_id';
        }
        if ($this->db->field_exists('territory_id', $clientTable)) {
            $select[] = 'c.territory_id';
        }
        if ($this->db->field_exists('sales_area_id', $clientTable)) {
            $select[] = 'c.sales_area_id';
        }

        $this->db->select(implode(', ', $select), false);
        $this->db->from($clientTable.' c');
        $this->db->where('c.userid', $client_id);

        if ($this->db->field_exists('territory_id', $clientTable) && $this->db->table_exists(db_prefix().'territories')) {
            $this->db->select('t.name AS territory_name', false);
            $this->db->join(db_prefix().'territories t', 't.id = c.territory_id', 'left');
        }
        if ($this->db->field_exists('sales_area_id', $clientTable) && $this->db->table_exists(db_prefix().'sales_area')) {
            $this->db->select('a.name AS sales_area_name', false);
            $this->db->join(db_prefix().'sales_area a', 'a.id = c.sales_area_id', 'left');
        }
        if ($this->db->field_exists('parent_partner_id', $clientTable)) {
            $this->db->select('pp.company AS partner_name', false);
            $this->db->join($clientTable.' pp', 'pp.userid = c.parent_partner_id', 'left');
        }

        $row = $this->db->get()->row_array();
        if (!$row) {
            return null;
        }

        return [
            'partner_id'      => !empty($row['parent_partner_id']) ? (int) $row['parent_partner_id'] : null,
            'partner_name'    => $row['partner_name'] ?? null,
            'territory_id'    => !empty($row['territory_id']) ? (int) $row['territory_id'] : null,
            'territory_name'  => $row['territory_name'] ?? null,
            'sales_area_id'   => !empty($row['sales_area_id']) ? (int) $row['sales_area_id'] : null,
            'sales_area_name' => $row['sales_area_name'] ?? null,
        ];
    }

    private function applyClientContextToPartner(array $item, $client_id, array &$update, &$clientContext = null)
    {
        $clientContext = $this->resolveClientDsrContext($client_id);
        if (!$clientContext) {
            return;
        }

        if (empty($item['partner_id']) && !empty($clientContext['partner_id'])) {
            $update['partner_id'] = (int) $clientContext['partner_id'];
        }
    }

    private function applyDsrOnlyCompletion($visit, array $item, array &$update)
    {
        if (!empty($visit->checkout_time) || !empty($visit->checkin_time)) {
            return;
        }

        $update['checkout_time'] = $item['checkout_at'] ?? $item['checkout_time'] ?? date('Y-m-d H:i:s');
        $update['status']        = 'dsr_only';
    }

    /**
     * Resolve an existing visit or create a DSR-only row when no physical visit happened.
     *
     * @return array{ok:bool,visit?:object,visit_id?:int,created?:bool,error?:array}
     */
    private function resolveOrCreateDsrVisit($staff_id, array $item, $index)
    {
        $visitTable = $this->visitTable();
        $visit_id   = (int) ($item['visit_id'] ?? 0);

        if ($visit_id > 0) {
            $visit = $this->db
                ->where('id', $visit_id)
                ->where('staff_id', $staff_id)
                ->get($visitTable)
                ->row();

            if (!$visit) {
                return [
                    'ok'    => false,
                    'error' => ['index' => $index, 'visit_id' => $visit_id, 'message' => 'Visit not found'],
                ];
            }

            return [
                'ok'       => true,
                'visit'    => $visit,
                'visit_id' => $visit_id,
                'created'  => false,
            ];
        }

        $client_id = (int) ($item['client_id'] ?? 0);
        if ($client_id <= 0) {
            return [
                'ok'    => false,
                'error' => ['index' => $index, 'message' => 'client_id is required when visit_id is omitted'],
            ];
        }

        $entity_type    = $item['entity_type'] ?? 'client';
        $visit_date     = $item['visit_date'] ?? date('Y-m-d');
        $clientContext  = $this->resolveClientDsrContext($client_id);
        $partner_id     = !empty($item['partner_id'])
            ? (int) $item['partner_id']
            : ($clientContext['partner_id'] ?? null);

        if (!$this->sales_assignment_model->client_in_scope($staff_id, $client_id, $entity_type)) {
            return [
                'ok'    => false,
                'error' => ['index' => $index, 'client_id' => $client_id, 'message' => 'Customer outside assignment'],
            ];
        }

        $visit = $this->db->where([
            'staff_id'    => $staff_id,
            'client_id'   => $client_id,
            'visit_date'  => $visit_date,
            'entity_type' => $entity_type,
        ])->get($visitTable)->row();

        if ($visit) {
            return [
                'ok'            => true,
                'visit'         => $visit,
                'visit_id'      => (int) $visit->id,
                'created'       => false,
                'client_context'=> $clientContext,
            ];
        }

        $insert = [
            'staff_id'      => $staff_id,
            'client_id'     => $client_id,
            'entity_type'   => $entity_type,
            'visit_date'    => $visit_date,
            'status'        => 'dsr_only',
            'checkout_time' => $item['checkout_at'] ?? $item['checkout_time'] ?? date('Y-m-d H:i:s'),
            'created_at'    => date('Y-m-d H:i:s'),
        ];

        if ($partner_id) {
            $insert['partner_id'] = $partner_id;
        }

        $this->db->insert($visitTable, $insert);
        $visit_id = (int) $this->db->insert_id();
        $visit    = $this->db->where('id', $visit_id)->get($visitTable)->row();

        return [
            'ok'             => true,
            'visit'          => $visit,
            'visit_id'       => $visit_id,
            'created'        => true,
            'client_context' => $clientContext,
        ];
    }

    /**
     * Save DSR fields for one visit. Checkout is optional and only when $allowCheckout is true.
     *
     * @return array{ok:bool,visit_id?:int,updated?:array,saved?:array,error?:array}
     */
    private function processDsrVisitItem($staff_id, array $item, $index, $allowCheckout = false)
    {
        $resolved = $this->resolveOrCreateDsrVisit($staff_id, $item, $index);
        if (empty($resolved['ok'])) {
            return [
                'ok'    => false,
                'error' => $resolved['error'],
            ];
        }

        $visit         = $resolved['visit'];
        $visit_id      = (int) $resolved['visit_id'];
        $created       = !empty($resolved['created']);
        $visitTable    = $this->visitTable();
        $clientContext = $resolved['client_context'] ?? $this->resolveClientDsrContext((int) $visit->client_id);

        if (!$this->sales_assignment_model->client_in_scope($staff_id, (int) $visit->client_id, $visit->entity_type ?? 'client')) {
            return [
                'ok'    => false,
                'error' => ['index' => $index, 'visit_id' => $visit_id, 'message' => 'Customer outside assignment'],
            ];
        }

        $update = [];
        $this->applyClientContextToPartner($item, (int) $visit->client_id, $update, $clientContext);
        $dsrMap = [
            'visit_outcome'      => ['visit_outcome', 'outcome'],
            'collection_amount'  => ['collection_amount'],
            'competitor'         => ['competitor'],
            'samples_given'      => ['samples_given'],
            'signature_note'     => ['signature_note'],
            'next_followup_date' => ['next_followup_date'],
            'notes'              => ['notes'],
        ];

        foreach ($dsrMap as $field => $keys) {
            foreach ($keys as $key) {
                if (!array_key_exists($key, $item)) {
                    continue;
                }
                $value = $item[$key];
                if ($value === null || $value === '') {
                    continue;
                }
                $update[$field] = $value;
                break;
            }
        }

        $orderResolved = $this->applyDsrOrderFieldsToUpdate($visit, [
            'order_value' => $item['order_value'] ?? null,
            'products'    => $item['products'] ?? null,
            'order_lines' => $item['order_lines'] ?? [],
        ], $update);

        if ($allowCheckout) {
            $complete = !empty($item['complete']) || !empty($item['checkout']);
            if ($complete && empty($visit->checkout_time)) {
                if (empty($visit->checkin_time)) {
                    $this->applyDsrOnlyCompletion($visit, $item, $update);
                } else {
                    $update['checkout_time'] = $item['checkout_at'] ?? $item['checkout_time'] ?? date('Y-m-d H:i:s');
                    $update['status']        = 'completed';
                }
            }
        } else {
            $this->applyDsrOnlyCompletion($visit, $item, $update);
        }

        $linesSaved = is_array($orderResolved) && !empty($orderResolved['lines_saved']);
        if (empty($update) && !$linesSaved) {
            return [
                'ok'    => false,
                'error' => ['index' => $index, 'visit_id' => $visit_id, 'message' => 'No DSR fields to save'],
            ];
        }

        if (!empty($update)) {
            $this->db->where('id', $visit_id)->update($visitTable, $update);
        }

        $savedRow = [
            'visit_id'   => $visit_id,
            'client_id'  => (int) $visit->client_id,
            'visit_date' => $visit->visit_date,
            'updated'    => array_keys($update),
            'created'    => $created,
            'dsr_only'   => ($update['status'] ?? $visit->status ?? null) === 'dsr_only',
            'partner_id' => (int) ($update['partner_id'] ?? $visit->partner_id ?? $clientContext['partner_id'] ?? 0) ?: null,
        ];
        if (is_array($clientContext)) {
            $savedRow['partner_name']    = $clientContext['partner_name'] ?? null;
            $savedRow['territory_id']    = $clientContext['territory_id'] ?? null;
            $savedRow['territory_name']  = $clientContext['territory_name'] ?? null;
            $savedRow['sales_area_id']   = $clientContext['sales_area_id'] ?? null;
            $savedRow['sales_area_name'] = $clientContext['sales_area_name'] ?? null;
        }
        if (is_array($orderResolved)) {
            $savedRow['distribution_order_id'] = $orderResolved['distribution_order_id'] ?? null;
            if (!empty($orderResolved['order_lines']) && function_exists('dc_format_order_lines_for_api')) {
                $savedRow['order_lines'] = dc_format_order_lines_for_api($orderResolved['order_lines']);
            }
        }

        return [
            'ok'    => true,
            'saved' => $savedRow,
        ];
    }

    private function visitListSelect()
    {
        $clientTable = db_prefix().'clients';
        $partnerSelect = $this->db->field_exists('parent_partner_id', $clientTable)
            ? 'COALESCE(v.partner_id, c.parent_partner_id) AS partner_id, pp.company AS partner_name,'
            : 'v.partner_id,';
        $territorySelect = $this->db->field_exists('territory_id', $clientTable)
            ? 'c.territory_id, t.name AS territory_name,'
            : '';
        $salesAreaSelect = $this->db->field_exists('sales_area_id', $clientTable)
            ? 'c.sales_area_id, a.name AS sales_area_name,'
            : '';

        return "
            v.id,
            v.client_id,
            {$partnerSelect}
            {$territorySelect}
            {$salesAreaSelect}
            v.entity_type,
            v.staff_id AS sales_user_id,
            TRIM(CONCAT(COALESCE(s.firstname, ''), ' ', COALESCE(s.lastname, ''))) AS sales_user_name,
            CASE
                WHEN v.entity_type = 'client' THEN c.company
                WHEN v.entity_type = 'lead' THEN l.name
                ELSE 'Unknown'
            END AS client_name,
            CASE
                WHEN v.entity_type = 'client' THEN c.billing_street
                WHEN v.entity_type = 'lead' THEN l.address
                ELSE NULL
            END AS billing_street,
            CASE
                WHEN v.entity_type = 'client' THEN c.billing_city
                WHEN v.entity_type = 'lead' THEN l.city
                ELSE NULL
            END AS billing_city,
            v.visit_date,
            v.planned_time,
            v.notes,
            v.status,
            v.checkin_time,
            v.checkout_time,
            v.visit_outcome,
            v.visit_outcome AS outcome,
            v.order_value,
            v.products,
            v.collection_amount,
            v.competitor,
            v.samples_given,
            v.signature_note,
            v.next_followup_date
        ";
    }

    private function applyVisitListJoins()
    {
        $table       = $this->visitTable();
        $clientTable = db_prefix().'clients';
        $this->db->from($table . ' v');
        $this->db->join(db_prefix() . 'staff s', 's.staffid = v.staff_id', 'left');
        $this->db->join($clientTable . ' c', 'c.userid = v.client_id', 'left');
        $this->db->join(db_prefix() . 'leads l', 'l.id = v.client_id', 'left');

        if ($this->db->field_exists('territory_id', $clientTable) && $this->db->table_exists(db_prefix().'territories')) {
            $this->db->join(db_prefix().'territories t', 't.id = c.territory_id', 'left');
        }
        if ($this->db->field_exists('sales_area_id', $clientTable) && $this->db->table_exists(db_prefix().'sales_area')) {
            $this->db->join(db_prefix().'sales_area a', 'a.id = c.sales_area_id', 'left');
        }
        if ($this->db->field_exists('parent_partner_id', $clientTable)) {
            $this->db->join($clientTable.' pp', 'pp.userid = COALESCE(v.partner_id, c.parent_partner_id)', 'left', false);
        }
    }

    private function parsePartnerIdFromNotes($notes)
    {
        if (!is_string($notes) || $notes === '') {
            return null;
        }

        if (preg_match('/Partner:.*\(#(\d+)\)/i', $notes, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    public function planvisit_post()
    {
        log_message('info', '[PlanVisit][Create] Request received');

        $this->ensureSalesVisitsSchema();

        $staff_id = $this->resolveVisitStaffId($this->getInputValue('sales_user_id'));

        $client_id    = $this->getInputValue('client_id');
        $entity_type  = $this->getInputValue('entity_type') ?: 'client';
        $visit_date   = $this->getInputValue('visit_date');
        $planned_time = $this->getInputValue('planned_time');
        $notes        = $this->getInputValue('notes');
        $partner_id = $this->getInputValue('partner_id');
        if (!$partner_id && $notes) {
            $partner_id = $this->parsePartnerIdFromNotes($notes);
        }
        if (!$partner_id && $client_id) {
            $clientContext = $this->resolveClientDsrContext($client_id);
            $partner_id    = $clientContext['partner_id'] ?? null;
        }

        log_message('info', '[PlanVisit][DATA] ' . json_encode([
            'client_id' => $client_id,
            'visit_date' => $visit_date,
            'planned_time' => $planned_time,
            'notes' => $notes,
            'partner_id' => $partner_id,
            'sales_user_id' => $staff_id,
        ]));

        if (empty($client_id) || empty($visit_date)) {
            return $this->response([
                'status'  => false,
                'message' => 'client_id and visit_date are required'
            ], RestController::HTTP_BAD_REQUEST);
        }

        if (!$this->sales_assignment_model->staff_can_fill_dsr($staff_id)) {
            return $this->response([
                'status'  => false,
                'message' => 'No territory or sales area assigned, and mobile settings require an assignment for DSR visits.',
            ], RestController::HTTP_FORBIDDEN);
        }

        if (!$this->sales_assignment_model->client_in_scope($staff_id, $client_id, $entity_type)) {
            return $this->response([
                'status'  => false,
                'message' => 'This customer is outside your assigned territory or sales area.',
            ], RestController::HTTP_FORBIDDEN);
        }

        $visitTable = $this->visitTable();
        $exists = $this->db->where([
            'staff_id'    => $staff_id,
            'entity_type' => $entity_type,
            'client_id'   => $client_id,
            'visit_date'  => $visit_date,
        ])->get($visitTable)->row();

        if ($exists) {
            if (!empty($exists->checkout_time) || $exists->status === 'completed') {
                return $this->response([
                    'status'  => false,
                    'message' => 'Visit already completed for this client and date',
                ], RestController::HTTP_BAD_REQUEST);
            }

            return $this->response([
                'status'  => true,
                'message' => 'Existing visit reused',
                'data'    => [
                    'visit_id' => (int) $exists->id,
                    'reused'   => true,
                ],
            ], RestController::HTTP_OK);
        }

        $insert = [
            'staff_id'     => $staff_id,
            'client_id'    => $client_id,
            'entity_type'  => $entity_type,
            'visit_date'   => $visit_date,
            'planned_time' => $planned_time,
            'notes'        => $notes,
            'status'       => 'planned',
            'created_at'   => date('Y-m-d H:i:s'),
        ];

        if ($partner_id) {
            $insert['partner_id'] = (int) $partner_id;
        }

        $this->db->insert($visitTable, $insert);
        $visit_id = (int) $this->db->insert_id();

        return $this->response([
            'status'  => true,
            'message' => 'Visit planned successfully',
            'data'    => [
                'visit_id' => $visit_id,
                'reused'   => false,
            ],
        ], RestController::HTTP_OK);
    }

    public function visit_report_summary_get()
    {
        $staff_id = $this->staffInfo['data']->staff_id;
        $from = $this->get('from_date');
        $to   = $this->get('to_date');

        $row = $this->db->query("
        SELECT
            COUNT(*) AS total_visits,

            SUM(v.checkout_time IS NOT NULL) AS completed_visits,

            SUM(v.checkout_time IS NULL AND v.checkin_time IS NOT NULL) AS ongoing_visits,

            COUNT(
                DISTINCT DATE(
                    COALESCE(v.checkout_time, v.visit_date)
                )
            ) AS working_days

        FROM " . db_prefix() . "sales_visits v
        WHERE v.staff_id = ?
        AND DATE(COALESCE(v.checkout_time, v.visit_date))
            BETWEEN ? AND ?
    ", [$staff_id, $from, $to])->row_array();

        return $this->response([
            'status' => true,
            'data' => $row
        ], 200);
    }




    // public function visit_daily_logs_get()
    // {
    //     $staff_id = $this->staffInfo['data']->staff_id;
    //     $from = $this->get('from_date');
    //     $to   = $this->get('to_date');

    //     $rows = $this->db->query("
    //         SELECT 
    //             DATE(COALESCE(v.checkout_time, v.visit_date)) AS visit_day,

    //             v.id,
    //             c.company AS client_name,
    //             v.status,
    //             v.checkin_time,
    //             v.checkout_time,

    //             ci.address AS checkin_address,
    //             co.address AS checkout_address,

    //             v.notes

    //         FROM ".db_prefix()."sales_visits v
    //         JOIN ".db_prefix()."clients c 
    //             ON c.userid = v.client_id

    //         LEFT JOIN ".db_prefix()."lead_checkins ci
    //             ON ci.staff_id = v.staff_id
    //             AND ci.lead_id = v.client_id
    //             AND ci.type = 'checkin'
    //             AND ci.created_at BETWEEN 
    //                 v.checkin_time AND IFNULL(v.checkout_time, v.checkin_time)

    //         LEFT JOIN ".db_prefix()."lead_checkins co
    //             ON co.staff_id = v.staff_id
    //             AND co.lead_id = v.client_id
    //             AND co.type = 'checkout'
    //             AND co.created_at = v.checkout_time

    //         WHERE v.staff_id = ?
    //         AND (
    //             -- completed visits
    //             (
    //                 v.checkout_time IS NOT NULL
    //                 AND v.checkout_time BETWEEN
    //                     CONCAT(?, ' 00:00:00')
    //                     AND CONCAT(?, ' 23:59:59')
    //             )
    //             OR
    //             -- planned / ongoing
    //             (
    //                 v.checkout_time IS NULL
    //                 AND v.visit_date BETWEEN ? AND ?
    //             )
    //         )

    //         ORDER BY visit_day DESC, v.checkout_time DESC
    //     ", [$staff_id, $from, $to, $from, $to])->result_array();

    //     $grouped = [];
    //     foreach ($rows as $r) {
    //         $grouped[$r['visit_day']][] = $r;
    //     }

    //     return $this->response([
    //         'status' => true,
    //         'data' => empty($grouped) ? (object)[] : $grouped
    //     ], 200);
    // }
    public function monthly_get()
    {
        try {
            $staff_id = $this->staffInfo['data']->staff_id;
            $month    = $this->get('month'); // yyyy-mm

            if (!$month) {
                return $this->response([
                    'success' => false,
                    'message' => 'Month is required (yyyy-mm)',
                    'data'    => []
                ], RestController::HTTP_BAD_REQUEST);
            }

            $startDate   = $month . '-01';
            $endDate     = date('Y-m-t', strtotime($startDate));
            $daysInMonth = date('t', strtotime($startDate));

            $summaries = $this->db
                ->where('staff_id', $staff_id)
                ->where('date >=', $startDate)
                ->where('date <=', $endDate)
                ->get(db_prefix() . 'tblstaff_daily_summary')
                ->result_array();

            // Index by date
            $summaryMap = [];
            foreach ($summaries as $s) {
                $summaryMap[$s['date']] = $s;
            }

            $data = [];

            // 🔢 COUNTS
            $present = 0;
            $absent  = 0;
            $approvedLeave = 0;
            $today = date('Y-m-d');

            for ($d = 1; $d <= $daysInMonth; $d++) {

                $date = date('Y-m-d', strtotime("$month-$d"));

                // 🚫 FUTURE DATE → DO NOT MARK ABSENT
                if ($date > $today) {
                    $data[] = [
                        'date'   => $date,
                        'status' => 'future'
                    ];
                    continue;
                }

                // ✅ RECORD EXISTS
                if (isset($summaryMap[$date])) {

                    $row = $summaryMap[$date];
                    $status = $row['type'] ?? 'present';

                    if (in_array($status, ['absent', 'leave', 'office_leave'])) {
                        $status = 'absent';
                        $absent++;
                    } elseif ($status === 'approved_leave') {
                        $approvedLeave++;
                    } else {
                        $status = 'present';
                        $present++;
                    }

                    $data[] = [
                        'date'   => $date,
                        'status' => $status,
                        'check_in_time'  => !empty($row['final_check_in_at'])
                            ? date('H:i', strtotime($row['final_check_in_at']))
                            : null,
                        'check_out_time' => !empty($row['final_check_out_at'])
                            ? date('H:i', strtotime($row['final_check_out_at']))
                            : null,
                    ];
                }
                // ❌ PAST DATE WITH NO RECORD → ABSENT
                else {
                    $absent++;

                    $data[] = [
                        'date'   => $date,
                        'status' => 'absent'
                    ];
                }
            }

            return $this->response([
                'success' => true,
                'message' => 'Monthly attendance fetched',
                'summary' => [
                    'total_days'     => $daysInMonth,
                    'present'        => $present,
                    'absent'         => $absent,
                    'approved_leave' => $approvedLeave,
                ],
                'data' => $data
            ], RestController::HTTP_OK);
        } catch (\Throwable $e) {
            log_message('error', '[Attendance][Monthly] ' . $e->getMessage());

            return $this->response([
                'success' => false,
                'message' => 'Something went wrong',
                'data'    => []
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function visit_daily_logs_get()
    {
        $staff_id = $this->staffInfo['data']->staff_id;
        $from     = $this->get('from_date');
        $to       = $this->get('to_date');

        // 🔎 INPUT LOG
        log_message('error', '[VisitLogs][INPUT] staff_id=' . $staff_id
            . ' from=' . ($from ?? 'NULL')
            . ' to=' . ($to ?? 'NULL'));

        // 🔐 Validate input
        if (empty($from) || empty($to)) {
            log_message('error', '[VisitLogs][ABORT] Missing from_date or to_date');

            return $this->response([
                'status' => false,
                'message' => 'from_date and to_date are required',
                'data' => []
            ], 400);
        }

        // 🧪 QUICK COUNT CHECK (bypass joins)
        $rawCount = $this->db->query("
        SELECT COUNT(*) AS cnt
        FROM " . db_prefix() . "sales_visits
        WHERE staff_id = ?
        AND visit_date BETWEEN ? AND ?
    ", [$staff_id, $from, $to])->row()->cnt;

        log_message('error', '[VisitLogs][RAW COUNT] sales_visits rows=' . $rawCount);

        // 🔎 MAIN QUERY
        $sql = "
        SELECT 
            DATE(v.visit_date) AS visit_day,

            v.id,
            v.client_id,
            c.company AS client_name,

            v.status,
            v.checkin_time,
            v.checkout_time,
            v.notes,

            ci.address AS checkin_address,
            co.address AS checkout_address

        FROM " . db_prefix() . "sales_visits v

        LEFT JOIN " . db_prefix() . "clients c
            ON c.userid = v.client_id

        LEFT JOIN " . db_prefix() . "lead_checkins ci
            ON ci.staff_id = v.staff_id
            AND ci.lead_id  = v.client_id
            AND ci.type     = 'checkin'

        LEFT JOIN " . db_prefix() . "lead_checkins co
            ON co.staff_id = v.staff_id
            AND co.lead_id  = v.client_id
            AND co.type     = 'checkout'

        WHERE v.staff_id = ?
          AND v.visit_date BETWEEN ? AND ?

        ORDER BY v.visit_date DESC, v.planned_time ASC
    ";

        // 🔎 LOG SQL & BINDINGS
        log_message('error', '[VisitLogs][SQL] ' . $sql);
        log_message('error', '[VisitLogs][BIND] ' . json_encode([$staff_id, $from, $to]));

        $rows = $this->db->query($sql, [$staff_id, $from, $to])->result_array();

        // 🔎 RESULT LOG
        log_message('error', '[VisitLogs][RESULT] rows=' . count($rows));

        if (empty($rows)) {
            log_message('error', '[VisitLogs][EMPTY] Query returned zero rows');
        } else {
            log_message('error', '[VisitLogs][SAMPLE ROW] ' . json_encode($rows[0]));
        }

        // 📦 GROUP BY DAY
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['visit_day']][] = $row;
        }

        log_message('error', '[VisitLogs][GROUPED DAYS] ' . json_encode(array_keys($grouped)));

        return $this->response([
            'status' => true,
            'data'   => empty($grouped) ? (object)[] : $grouped
        ], 200);
    }
public function planvisitstoday_get()
{
    log_message('info', '[PlanVisit][Today] Request received');

    $this->ensureSalesVisitsSchema();

    $staff_id = $this->staffInfo['data']->staff_id;
    $today    = date('Y-m-d');

    $this->db->select($this->visitListSelect(), false);
    $this->applyVisitListJoins();
    $this->db->where('v.staff_id', $staff_id);
    $this->db->where('v.visit_date', $today);
    $this->db->order_by('v.planned_time', 'ASC');

    $visits = $this->enrichVisitsWithOrders($this->db->get()->result_array());

    return $this->response([
        'status'  => true,
        'success' => true,
        'message' => 'Today visits fetched',
        'data'    => $visits
    ], RestController::HTTP_OK);
}

    public function planvisitstoday111_get()
    {
        log_message('info', '[PlanVisit][Today] Request received');

        $staff_id = $this->staffInfo['data']->staff_id;
        $today    = date('Y-m-d');

        $visits = $this->db
            ->select('
            v.id,
            v.client_id,
            c.company as client_name,
            c.billing_street,
            c.billing_city,
            v.visit_date,
            v.planned_time,
            v.notes,
            v.status,
            v.checkin_time,
            v.checkout_time
        ')
            ->from(db_prefix() . 'sales_visits v')
            ->join(db_prefix() . 'clients c', 'c.userid = v.client_id', 'left')
            ->where('v.staff_id', $staff_id)
            ->where('v.visit_date', $today)
            ->order_by('v.planned_time', 'ASC')
            ->get()
            ->result_array();

        return $this->response([
            'status'  => true,
            'success' => true,
            'message' => 'Today visits fetched',
            'data'    => $visits
        ], RestController::HTTP_OK);
    }



    public function planvisit11s_get()
    {
        log_message('info', '[PlanVisit][All] Request received');

        $staff_id = $this->staffInfo['data']->staff_id;

        $visits = $this->db
            ->select('
            v.id,
            v.client_id,
           
            v.entity_type,
            c.company as client_name,
            c.billing_street,
            c.billing_city,
            v.visit_date,
            v.planned_time,
            v.notes,
            v.status,
            v.checkin_time,
            v.checkout_time
        ')
            ->from($this->visitTable() . ' v')
            ->join(db_prefix() . 'clients c', 'c.userid = v.client_id', 'left')
            ->where('v.staff_id', $staff_id)
            ->order_by('v.visit_date', 'DESC')
            ->order_by('v.planned_time', 'ASC')
            ->get()
            ->result_array();

        return $this->response([
            'status'  => true,
            'success' => true,
            'message' => 'All visits fetched',
            'data'    => $visits
        ], RestController::HTTP_OK);
    }
public function planvisits_get()
{
    log_message('info', '[PlanVisit][All] Request received');

    $this->ensureSalesVisitsSchema();

    $staff_id = $this->staffInfo['data']->staff_id;
    $date     = $this->get('date');
    $from     = $this->get('from');
    $to       = $this->get('to');

    $this->db->select($this->visitListSelect(), false);
    $this->applyVisitListJoins();
    $this->db->where('v.staff_id', $staff_id);

    if ($date) {
        $this->db->where('v.visit_date', $date);
    } elseif ($from && $to) {
        $this->db->where('DATE(v.visit_date) >=', $from);
        $this->db->where('DATE(v.visit_date) <=', $to);
    }

    $this->db->order_by('v.visit_date', 'DESC');
    $this->db->order_by('v.planned_time', 'ASC');

    $visits = $this->enrichVisitsWithOrders($this->db->get()->result_array());

    return $this->response([
        'status'  => true,
        'success' => true,
        'message' => 'All visits fetched',
        'data'    => $visits
    ], RestController::HTTP_OK);
}

    public function dsr_daily_get()
    {
        try {
            $staff_id = (int) ($this->get('staff_id') ?: $this->staffInfo['data']->staff_id);
            $date     = $this->get('date') ?: date('Y-m-d');

            $this->ensureSalesVisitsSchema();
            $this->load->model('distribution_channel/sales_targets_model');

            $this->db->select($this->visitListSelect(), false);
            $this->applyVisitListJoins();
            $this->db->where('v.staff_id', $staff_id);
            $this->db->where('v.visit_date', $date);
            $this->db->order_by('v.planned_time', 'ASC');
            $all_visits = $this->enrichVisitsWithOrders($this->db->get()->result_array());

            $summary = $this->sales_targets_model->daily_dsr_summary($staff_id, $date);

            return $this->response([
                'status'  => true,
                'success' => true,
                'data'    => [
                    'date'       => $date,
                    'staff_id'   => $staff_id,
                    'summary'    => [
                        'completed_visit_count' => $summary['completed_visit_count'],
                        'order_value_lakhs'     => $summary['order_value_lakhs'],
                        'collection_lakhs'      => $summary['collection_lakhs'],
                        'railway_visits_count'  => $summary['railway_visits_count'],
                        'new_accounts_count'    => $summary['new_accounts_count'],
                    ],
                    'visits'     => $all_visits,
                    'completed'  => $summary['completed_visits'],
                ],
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->response([
                'status'  => false,
                'message' => 'Failed to fetch daily DSR',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function dsr_save_post()
    {
        try {
            $staff_id = (int) $this->staffInfo['data']->staff_id;
            $body     = $this->getInputValue();

            if (!is_array($body)) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Invalid request body',
                ], RestController::HTTP_BAD_REQUEST);
            }

            $item = $body;
            if (empty($item['visit_id']) && !empty($body['visits'][0]) && is_array($body['visits'][0])) {
                $item = $body['visits'][0];
            }

            $this->ensureSalesVisitsSchema();
            $this->load->model('distribution_channel/sales_assignment_model');

            $result = $this->processDsrVisitItem($staff_id, $item, 0, false);
            if (empty($result['ok'])) {
                return $this->response([
                    'status'  => false,
                    'success' => false,
                    'message' => $result['error']['message'] ?? 'Failed to save DSR',
                    'data'    => [
                        'errors' => [$result['error']],
                    ],
                ], RestController::HTTP_BAD_REQUEST);
            }

            return $this->response([
                'status'  => true,
                'success' => true,
                'message' => 'DSR saved',
                'data'    => $result['saved'],
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->response([
                'status'  => false,
                'message' => 'Failed to save DSR',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function dsr_bulk_post()
    {
        try {
            $staff_id = (int) $this->staffInfo['data']->staff_id;
            $items    = $this->getInputValue('visits');

            if (!is_array($items) || empty($items)) {
                return $this->response([
                    'status'  => false,
                    'message' => 'visits array is required',
                ], RestController::HTTP_BAD_REQUEST);
            }

            $this->ensureSalesVisitsSchema();
            $this->load->model('distribution_channel/sales_assignment_model');

            $saved  = [];
            $errors = [];

            foreach ($items as $index => $item) {
                if (!is_array($item)) {
                    $errors[] = ['index' => $index, 'message' => 'Invalid visit item'];
                    continue;
                }

                $result = $this->processDsrVisitItem($staff_id, $item, $index, true);
                if (empty($result['ok'])) {
                    $errors[] = $result['error'];
                    continue;
                }
                $saved[] = $result['saved'];
            }

            return $this->response([
                'status'  => true,
                'success' => count($saved) > 0,
                'message' => count($saved).' visit(s) updated',
                'data'    => [
                    'saved'  => $saved,
                    'errors' => $errors,
                ],
            ], RestController::HTTP_OK);
        } catch (\Throwable $th) {
            return $this->response([
                'status'  => false,
                'message' => 'Failed to save bulk DSR',
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }
    public function visitcheckin_post()
    {
        log_message('info', '[PlanVisit][CheckIn] ===== START =====');

        try {

            /* ================= AUTH ================= */
            $staff = $this->staffInfo['data'] ?? null;

            if (!$staff) {
                log_message('error', '[PlanVisit][CheckIn] Unauthorized access attempt');
                return $this->response([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], RestController::HTTP_UNAUTHORIZED);
            }



            $this->ensureSalesVisitsSchema();

            /* ================= INPUT ================= */
            $staff_id = $this->staffInfo['data']->staff_id;
            $visit_id = $this->getInputValue('visit_id');
            [$lat, $lng] = $this->postCoordinates();

            log_message('info', "[PlanVisit][CheckIn] Input => staff_id={$staff_id}, visit_id={$visit_id}, lat={$lat}, lng={$lng}");

            if (!$visit_id || !$lat || $lng === null || $lng === '') {
                log_message('error', '[PlanVisit][CheckIn] Missing required parameters');
                return $this->response([
                    'success' => false,
                    'message' => 'visit_id, lat and lang are required'
                ], RestController::HTTP_BAD_REQUEST);
            }

            if (!is_numeric($lat) || !is_numeric($lng)) {
                log_message('error', '[PlanVisit][CheckIn] Invalid lat/lng format');
                return $this->response([
                    'success' => false,
                    'message' => 'Invalid latitude or longitude'
                ], RestController::HTTP_BAD_REQUEST);
            }

            $lat = (float)$lat;
            $lng = (float)$lng;
            $accuracy = 10;

            /* ================= FETCH VISIT ================= */
            $visitTable = $this->visitTable();
            $visit = $this->db
                ->where('id', $visit_id)
                ->where('staff_id', $staff_id)
                ->get($visitTable)
                ->row();

            if (!$visit) {
                log_message('error', "[PlanVisit][CheckIn] Visit not found for visit_id={$visit_id}");
                return $this->response([
                    'success' => false,
                    'message' => 'Visit not found'
                ], RestController::HTTP_NOT_FOUND);
            }

            if (!empty($visit->checkin_time)) {
                log_message('error', "[PlanVisit][CheckIn] Already checked in visit_id={$visit_id}");
                return $this->response([
                    'success' => false,
                    'message' => 'Visit already checked in'
                ], RestController::HTTP_CONFLICT);
            }

            $now = $this->getInputValue('checkin_at') ?: $this->getInputValue('checkin_time');
            $now = $now ?: date('Y-m-d H:i:s');
            $today   = date('Y-m-d', strtotime($now));
            $address = $this->getAddressFromLatLong($lat, $lng);

            log_message('info', "[PlanVisit][CheckIn] Address resolved => {$address}");

            /* ================= TRANSACTION START ================= */
            $this->db->trans_begin();

            log_message('info', '[PlanVisit][CheckIn] Transaction started');

            /* ================= UPDATE VISIT ================= */
            $this->db->where('id', $visit_id)->update(
                $visitTable,
                [
                    'checkin_time' => $now,
                    'status'       => 'checked_in'
                ]
            );

            log_message('info', "[PlanVisit][CheckIn] sales_visits updated");

            /* ================= INSERT LEAD CHECKIN ================= */
            $this->db->insert(db_prefix() . 'lead_checkins', [
                'visit_id'    => $visit_id,
                'entity_type' => $visit->entity_type,
                'entity_id'   => $visit->client_id,
                'staff_id'    => $staff_id,
                'type'        => 'checkin',
                'latitude'    => $lat,
                'longitude'   => $lng,
                'address'     => $address,
                'ip_address'  => $this->input->ip_address(),
                'user_agent'  => $this->input->user_agent(),
                'created_at'  => $now
            ]);

            log_message('info', '[PlanVisit][CheckIn] lead_checkins inserted');
            $summaryTable = db_prefix() . 'staff_daily_summary';

            $last = $this->db->select('last_lat,last_lng')
                ->where('staff_id', $staff_id)
                ->where('date', $today)
                ->get($summaryTable)
                ->row();

            $distance = 0;

            if ($last && $last->last_lat && $last->last_lng) {

                $distance = $this->calculateDistance(
                    $last->last_lat,
                    $last->last_lng,
                    $lat,
                    $lng
                );

                if ($distance < 0.01) {
                    $distance = 0;
                }
            }

            /* ================= INSERT GPS HISTORY ================= */


            $this->db->insert(db_prefix() . 'checkout_history', [
                'staff_id'    => $staff_id,
                'visit_id'    => $visit_id,
                'entry_id'    => $visit->client_id,
                'entity_type' => $visit->entity_type,
                'latitude'    => $lat,
                'longitude'   => $lng,
                'distance'    => $distance,
                'address'     => $address,
                'accuracy_m'  => $accuracy,
                'type_check'  => 1,
                'type'        => 'V',
                'recorded_at' => $now
            ]);

            log_message('info', '[PlanVisit][CheckIn] checkout_history inserted');

            /* ================= CALCULATE DISTANCE ================= */

            log_message('info', "[PlanVisit][CheckIn] Distance calculated => {$distance} KM");

            /* ================= UPSERT SUMMARY ================= */
            $sql = "
        INSERT INTO {$summaryTable}
            (staff_id,date,last_lat,last_lng,last_ping,last_accuracy,distance,final_check_in_at)
        VALUES (?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            last_lat = VALUES(last_lat),
            last_lng = VALUES(last_lng),
            last_ping = VALUES(last_ping),
            last_accuracy = VALUES(last_accuracy),
            distance = distance + VALUES(distance),
            final_check_in_at = VALUES(final_check_in_at)
        ";

            $this->db->query($sql, [
                $staff_id,
                $today,
                $lat,
                $lng,
                $now,
                $accuracy,
                $distance,
                $now
            ]);

            log_message('info', '[PlanVisit][CheckIn] staff_daily_summary upserted');

            /* ================= CHECK TRANSACTION ================= */
            if ($this->db->trans_status() === false) {

                log_message('error', '[PlanVisit][CheckIn] Transaction FAILED');

                $this->db->trans_rollback();

                return $this->response([
                    'success' => false,
                    'message' => 'Failed to check in visit'
                ], RestController::HTTP_INTERNAL_ERROR);
            }

            $this->db->trans_commit();

            log_message('info', '[PlanVisit][CheckIn] Transaction committed successfully');
            log_message('info', '[PlanVisit][CheckIn] ===== END SUCCESS =====');

            return $this->response([
                'success' => true,
                'message' => 'Visit checked in successfully',
                'data' => [
                    'visit_id' => $visit_id,
                    'checkin_time' => $now,
                    'distance_added_km' => round($distance, 3)
                ]
            ], RestController::HTTP_OK);
        } catch (\Throwable $e) {

            log_message('error', '[PlanVisit][CheckIn] EXCEPTION => ' . $e->getMessage());
            log_message('error', $e->getTraceAsString());

            return $this->response([
                'success' => false,
                'message' => 'Something went wrong'
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    /**
     * Calculate distance between two coordinates (in KM)
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Earth's radius in KM

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) *
            cos(deg2rad($lat2)) *
            sin($dLon / 2) *
            sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c;

        return $distance; // returns distance in KM
    }



    public function visitcheckout_post()
    {
        log_message('info', '[PlanVisit][CheckOut] ===== START =====');

        try {

            /* ================= AUTH ================= */
            $staff = $this->staffInfo['data'] ?? null;

            if (!$staff) {
                return $this->response([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], RestController::HTTP_UNAUTHORIZED);
            }

            $this->ensureSalesVisitsSchema();

            $staff_id = $this->staffInfo['data']->staff_id;
            $visit_id = $this->getInputValue('visit_id');
            [$lat, $lng] = $this->postCoordinates();

            if (!$visit_id || !$lat || $lng === null || $lng === '') {
                return $this->response([
                    'success' => false,
                    'message' => 'visit_id, lat and lang are required'
                ], RestController::HTTP_BAD_REQUEST);
            }

            if (!is_numeric($lat) || !is_numeric($lng)) {
                return $this->response([
                    'success' => false,
                    'message' => 'Invalid latitude or longitude'
                ], RestController::HTTP_BAD_REQUEST);
            }

            $lat = (float)$lat;
            $lng = (float)$lng;
            $accuracy = 10;

            /* ================= FETCH VISIT ================= */
            $visitTable = $this->visitTable();
            $visit = $this->db
                ->where('id', $visit_id)
                ->where('staff_id', $staff_id)
                ->get($visitTable)
                ->row();

            if (!$visit) {
                return $this->response([
                    'success' => false,
                    'message' => 'Visit not found'
                ], RestController::HTTP_NOT_FOUND);
            }

            if (empty($visit->checkin_time)) {
                return $this->response([
                    'success' => false,
                    'message' => 'Visit not checked in yet'
                ], RestController::HTTP_CONFLICT);
            }

            if (!empty($visit->checkout_time)) {
                return $this->response([
                    'success' => false,
                    'message' => 'Visit already checked out'
                ], RestController::HTTP_CONFLICT);
            }

            $now = $this->getInputValue('checkout_at') ?: $this->getInputValue('checkout_time');
            $now = $now ?: date('Y-m-d H:i:s');
            $today   = date('Y-m-d', strtotime($now));
            $address = $this->getAddressFromLatLong($lat, $lng);

            $visitStart  = strtotime($visit->checkin_time);
            $visitEnd    = strtotime($now);
            $durationSec = $visitEnd - $visitStart;

            $visitUpdate = [
                'checkout_time' => $now,
                'status'        => 'completed',
            ];

            $this->db->trans_begin();

            /* ================= UPDATE VISIT ================= */
            $this->db->where('id', $visit_id)->update($visitTable, $visitUpdate);

            /* ================= INSERT STOP ================= */
            if ($durationSec >= 60) {

                $this->db->insert(db_prefix() . 'tblstaff_stops', [
                    'staff_id'     => $staff_id,
                    'date'         => $today,
                    'start_time'   => $visit->checkin_time,
                    'end_time'     => $now,
                    'duration_sec' => $durationSec,
                    'lat'          => $lat,
                    'lng'          => $lng,
                    'address'      => $address,
                    'source'       => 'visit',
                    'type'         => $visit->entity_type,
                    'title'        => ucfirst($visit->entity_type) . ' Visit',
                    'entity_type'  => $visit->entity_type,
                    'entity_id'    => $visit->client_id
                ]);
            }

            /* ================= INSERT CHECKOUT GPS HISTORY ================= */
            $this->db->insert(db_prefix() . 'checkout_history', [
                'staff_id'    => $staff_id,
                'visit_id'    => $visit_id,           // ✅ IMPORTANT
                'entry_id'    => $visit->client_id,   // ✅ IMPORTANT
                'entity_type' => $visit->entity_type, // ✅ IMPORTANT
                'latitude'    => $lat,
                'longitude'   => $lng,
                'distance'    => 0,
                'address'     => $address,
                'accuracy_m'  => $accuracy,
                'type_check'  => 1,
                'type'        => 'VO',   // visit checkout
                'recorded_at' => $now
            ]);


            /* ================= UPDATE DAILY SUMMARY ================= */
            $summaryTable = db_prefix() . 'staff_daily_summary';

            $last = $this->db->select('last_lat,last_lng')
                ->where('staff_id', $staff_id)
                ->where('date', $today)
                ->get($summaryTable)
                ->row();

            $distance = 0;

            if ($last && $last->last_lat && $last->last_lng) {
                $distance = $this->calculateDistance(
                    $last->last_lat,
                    $last->last_lng,
                    $lat,
                    $lng
                );

                if ($distance < 0.01) {
                    $distance = 0;
                }
            }

            $sql = "
        INSERT INTO {$summaryTable}
            (staff_id,date,last_lat,last_lng,last_ping,last_accuracy,distance)
        VALUES (?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            last_lat = VALUES(last_lat),
            last_lng = VALUES(last_lng),
            last_ping = VALUES(last_ping),
            last_accuracy = VALUES(last_accuracy),
            distance = distance + VALUES(distance)
        ";

            $this->db->query($sql, [
                $staff_id,
                $today,
                $lat,
                $lng,
                $now,
                $accuracy,
                $distance
            ]);

            /* ================= TRANSACTION END ================= */
            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();
                return $this->response([
                    'success' => false,
                    'message' => 'Failed to check out visit'
                ], RestController::HTTP_INTERNAL_ERROR);
            }

            $this->db->trans_commit();

            log_message('info', '[PlanVisit][CheckOut] ===== SUCCESS =====');

            return $this->response([
                'success' => true,
                'message' => 'Visit checked out successfully',
                'data' => [
                    'visit_id'       => (int) $visit_id,
                    'checkout_time'  => $now,
                    'duration_sec'   => $durationSec,
                    'status'         => 'completed',
                ],
            ], RestController::HTTP_OK);
        } catch (\Throwable $e) {

            log_message('error', '[PlanVisit][CheckOut] EXCEPTION => ' . $e->getMessage());

            return $this->response([
                'success' => false,
                'message' => 'Something went wrong'
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function mapview_get()
    {
        log_message('info', '[Attendance][MapView] Method hit');

        if (empty($this->staffInfo) || empty($this->staffInfo['data']->staff_id)) {
            log_message('error', '[Attendance][MapView] Unauthorized');
            return $this->response([
                'success' => false,
                'message' => 'Unauthorized',
            ], RestController::HTTP_UNAUTHORIZED);
        }

        $staff_id = (int) ($this->get('staff_id') ?: $this->staffInfo['data']->staff_id);
        $date     = $this->get('date') ?: date('Y-m-d');

        if (!can_view_staff_map((int) $this->staffInfo['data']->staff_id, $staff_id)) {
            return $this->response([
                'success' => false,
                'message' => 'Forbidden',
            ], RestController::HTTP_FORBIDDEN);
        }

        $token = trim((string) ($this->staffInfo['data']->flutex_api_key ?? ''));
        $params = [
            'staff_id' => $staff_id,
            'date'     => $date,
        ];
        if ($token !== '') {
            $params['token'] = $token;
        }

        $webview_url = site_url('flutex_admin_api/attendance/map?' . http_build_query($params));

        $staffName = trim(($this->staffInfo['data']->firstname ?? '').' '.($this->staffInfo['data']->lastname ?? ''));
        if ($staff_id !== (int) ($this->staffInfo['data']->staff_id ?? 0)) {
            $target = $this->db->select('firstname,lastname')
                ->where('staffid', $staff_id)
                ->get(db_prefix().'staff')
                ->row_array();
            if ($target) {
                $staffName = trim(($target['firstname'] ?? '').' '.($target['lastname'] ?? ''));
            }
        }

        return $this->response([
            'success'      => true,
            'message'      => 'Map webview URL generated',
            'webview_url'  => $webview_url,
            'staff_id'     => $staff_id,
            'staff_name'   => $staffName,
            'date'         => $date,
            'layout'       => 'mobile_full_map',
            'ui'           => [
                'sidebar'           => 'bottom_sheet',
                'sidebar_default'   => 'collapsed',
                'recommended_frame' => 'fullscreen',
            ],
        ], RestController::HTTP_OK);
    }

    public function visitreport_get()
    {
        log_message('info', '[PlanVisit][Report] Request received');

        $staff_id = $this->staffInfo['data']->staff_id;
        $from = $this->get('from');
        $to   = $this->get('to');

        if (!$from || !$to) {
            return $this->response([
                'success' => false,
                'message' => 'from and to dates required'
            ], RestController::HTTP_BAD_REQUEST);
        }

        $data = $this->db
            ->where('staff_id', $staff_id)
            ->where('visit_date >=', $from)
            ->where('visit_date <=', $to)
            ->order_by('visit_date', 'DESC')
            ->get(db_prefix() . 'sales_visits')
            ->result_array();

        return $this->response([
            'success' => true,
            'message' => 'Visit report fetched',
            'data'    => $data
        ], RestController::HTTP_OK);
    }

    public function daysummary111_get()
    {
        log_message('info', '[Attendance][DayRoute] Request received');

        try {

            $staff_id = $this->staffInfo['data']->staff_id;
            $date     = $this->get('date') ?? date('Y-m-d');

            if (!$staff_id) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Invalid staff'
                ], 400);
            }

            /* ==========================================
         * GET TIMELINE
         * ========================================== */
            $timeline = $this->timesheets_model->get_full_day_activity($staff_id, $date);

            /* ==========================================
         * GET DISTANCE FROM SUMMARY TABLE
         * ========================================== */
            $summary = $this->db
                ->where('staff_id', $staff_id)
                ->where('date', $date)
                ->get(db_prefix() . 'staff_daily_summary')
                ->row();

            $totalDistanceKm = 0;

            if ($summary && isset($summary->distance)) {
                $totalDistanceKm = round($summary->distance / 1000, 2);
            }

            /* ==========================================
         * CALCULATE WORK TIME
         * ========================================== */

            $firstCheckin = null;
            $lastCheckout = null;

            foreach ($timeline as $item) {

                if ($item['type'] === 'office_checkin') {
                    $firstCheckin = $item['time'];
                }

                if ($item['type'] === 'office_checkout') {
                    $lastCheckout = $item['time'];
                }
            }

            // If no checkout & selected date is today → calculate till now
            if ($firstCheckin && !$lastCheckout && $date == date('Y-m-d')) {
                $lastCheckout = date('Y-m-d H:i:s');
            }

            $workTimeFormatted = "00h 00m";

            if ($firstCheckin && $lastCheckout) {

                $seconds = strtotime($lastCheckout) - strtotime($firstCheckin);

                if ($seconds < 0) {
                    $seconds = 0; // safety
                }

                $hours   = floor($seconds / 3600);
                $minutes = floor(($seconds % 3600) / 60);

                $workTimeFormatted = sprintf("%02dh %02dm", $hours, $minutes);
            }

            /* ==========================================
         * FINAL RESPONSE
         * ========================================== */

            return $this->response([
                'status'            => true,
                'date'              => $date,
                'total_distance_km' => $totalDistanceKm,
                'total_work_time'   => $workTimeFormatted,
                'data'              => $timeline
            ], 200);
        } catch (Exception $e) {

            log_message('error', '[Attendance][DayRoute] Error: ' . $e->getMessage());

            return $this->response([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function daysummary_get()
    {
        log_message('info', '[Attendance][DayRoute] Request received');

        try {

            $staff_id = $this->staffInfo['data']->staff_id ?? null;
            $date     = $this->get('date') ?? date('Y-m-d');

            if (!$staff_id) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Invalid staff'
                ], 400);
            }

            /* =====================================================
         * 1️⃣ GET TIMELINE
         * ===================================================== */
            $timeline = $this->timesheets_model
                ->get_full_day_activity($staff_id, $date);

            // Ensure timeline sorted (extra safety)
            usort($timeline, function ($a, $b) {
                return strtotime($a['time']) - strtotime($b['time']);
            });

            /* =====================================================
         * 2️⃣ GET DISTANCE (SAFE)
         * ===================================================== */
            $summary = $this->db
                ->select('distance')
                ->where('staff_id', $staff_id)
                ->where('date', $date)
                ->limit(1)
                ->get(db_prefix() . 'staff_daily_summary')
                ->row();

            $totalDistanceKm = 0.00;

            if ($summary && is_numeric($summary->distance)) {
                $totalDistanceKm = round((float)$summary->distance, 2);
            }

            /* =====================================================
         * 3️⃣ CALCULATE WORK TIME (ROBUST)
         * ===================================================== */

            $firstCheckin = null;
            $lastCheckout = null;

            foreach ($timeline as $item) {

                if ($item['type'] === 'office_checkin') {

                    if (
                        !$firstCheckin ||
                        strtotime($item['time']) < strtotime($firstCheckin)
                    ) {
                        $firstCheckin = $item['time'];
                    }
                }

                if ($item['type'] === 'office_checkout') {

                    if (
                        !$lastCheckout ||
                        strtotime($item['time']) > strtotime($lastCheckout)
                    ) {
                        $lastCheckout = $item['time'];
                    }
                }
            }

            // If no office_checkout found
            if ($firstCheckin && !$lastCheckout) {

                // If today → till now
                if ($date == date('Y-m-d')) {
                    $lastCheckout = date('Y-m-d H:i:s');
                }
                // If past date → use last activity of day
                elseif (!empty($timeline)) {
                    $lastItem = end($timeline);
                    $lastCheckout = $lastItem['time'];
                }
            }

            $workTimeFormatted = "00h 00m";

            if ($firstCheckin && $lastCheckout) {

                $seconds = strtotime($lastCheckout) - strtotime($firstCheckin);

                if ($seconds < 0) {
                    $seconds = 0;
                }

                $hours   = floor($seconds / 3600);
                $minutes = floor(($seconds % 3600) / 60);

                $workTimeFormatted = sprintf("%02dh %02dm", $hours, $minutes);
            }

            /* =====================================================
         * 4️⃣ FINAL RESPONSE
         * ===================================================== */

            return $this->response([
                'status'            => true,
                'date'              => $date,
                'total_distance_km' => $totalDistanceKm,
                'total_work_time'   => $workTimeFormatted,
                'data'              => $timeline
            ], 200);
        } catch (Exception $e) {

            log_message('error', '[Attendance][DayRoute] Error: ' . $e->getMessage());

            return $this->response([
                'status'  => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    public function daypath_get()
    {
        try {

            $staff_id = $this->staffInfo['data']->staff_id ?? null;
            $date     = $this->get('date') ?? date('Y-m-d');
            $max_accuracy_m = $this->get('max_accuracy') ?? 50;

            if (!$staff_id) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Invalid staff'
                ], 400);
            }

            /* =====================================================
         * FETCH GPS POINTS
         * ===================================================== */
            $this->db->select('latitude, longitude, recorded_at');
            $this->db->from(db_prefix() . 'checkout_history');
            $this->db->where('staff_id', $staff_id);
            $this->db->where('DATE(recorded_at)', $date);
            $this->db->where('latitude !=', 0);
            $this->db->where('longitude !=', 0);
            $this->db->where('accuracy_m <=', (int)$max_accuracy_m);
            $this->db->order_by('recorded_at', 'ASC');

            $rows = $this->db->get()->result_array();

            if (!$rows) {
                return $this->response([
                    'status' => true,
                    'date'   => $date,
                    'points' => []
                ], 200);
            }

            /* =====================================================
         * CLEAN DUPLICATES (distance-based)
         * ===================================================== */
            $points = [];
            $prev = null;

            foreach ($rows as $r) {

                $current = [
                    'lat'  => (float)$r['latitude'],
                    'lng'  => (float)$r['longitude'],
                    'time' => $r['recorded_at']
                ];

                if ($prev) {

                    $dist = $this->haversine_distance(
                        $prev['lat'],
                        $prev['lng'],
                        $current['lat'],
                        $current['lng']
                    );

                    // Ignore jitter < 5 meters
                    if ($dist < 5) {
                        continue;
                    }
                }

                $points[] = $current;
                $prev = $current;
            }

            /* =====================================================
         * RETURN RESPONSE
         * ===================================================== */
            return $this->response([
                'status' => true,
                'date'   => $date,
                'points' => $points
            ], 200);
        } catch (Exception $e) {

            log_message('error', '[Attendance][DayPath] Error: ' . $e->getMessage());

            return $this->response([
                'status'  => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }


    public function dayroute_get()
    {
        log_message('info', '[Attendance][DayRoute] Request received');

        try {
            $staff_id = $this->staffInfo['data']->staff_id;
            $date     = $this->get('date') ?? date('Y-m-d');

            $from = $date . ' 00:00:00';
            $to   = $date . ' 23:59:59';

            // ==================================================
            // 1️⃣ OFFICE CHECK-IN / CHECK-OUT
            // ==================================================
            $officeLogs = $this->db
                ->where('staff_id', $staff_id)
                ->where('date >=', $from)
                ->where('date <=', $to)
                ->order_by('date', 'ASC')
                ->get(db_prefix() . 'tblcheck_in_out')
                ->result();

            if (!$officeLogs) {
                return $this->response([
                    'success' => true,
                    'message' => 'No activity found',
                    'data' => [
                        'date' => $date,
                        'summary' => [
                            'total_distance_km' => 0,
                            'total_work_time'   => '00h 00m'
                        ],
                        'route' => [],
                        'logs'  => []
                    ]
                ], RestController::HTTP_OK);
            }

            // ==================================================
            // 2️⃣ FIRST IN & LAST OUT
            // ==================================================
            $firstIn  = null;
            $lastOut = null;

            foreach ($officeLogs as $l) {
                if ($l->type_check == 1 && !$firstIn) {
                    $firstIn = $l;
                }
                if ($l->type_check == 2) {
                    $lastOut = $l;
                }
            }

            if (!$firstIn) {
                return $this->response([
                    'success' => true,
                    'message' => 'No check-in found',
                    'data' => []
                ], RestController::HTTP_OK);
            }

            // ==================================================
            // 3️⃣ TOTAL WORK TIME (PAIR BASED)
            // ==================================================
            $totalSeconds = 0;
            $lastInTime   = null;

            foreach ($officeLogs as $l) {
                if ($l->type_check == 1) {
                    $lastInTime = strtotime($l->date);
                }

                if ($l->type_check == 2 && $lastInTime) {
                    $out = strtotime($l->date);
                    if ($out > $lastInTime) {
                        $totalSeconds += ($out - $lastInTime);
                    }
                    $lastInTime = null;
                }
            }

            $totalWorkTime = gmdate('H\h i\m', $totalSeconds);

            // ==================================================
            // 4️⃣ TRACKING POINTS (FOR MAP)
            // ==================================================
            $startTime = $firstIn->date;
            $endTime   = $lastOut ? $lastOut->date : $startTime;

            $tracking = $this->db
                ->where('staff_id', $staff_id)
                ->where('recorded_at >=', $startTime)
                ->where('recorded_at <=', $endTime)
                ->where('latitude IS NOT NULL')
                ->where('longitude IS NOT NULL')
                ->order_by('recorded_at', 'ASC')
                ->get(db_prefix() . 'tblcheckout_history')
                ->result_array();

            // ==================================================
            // 5️⃣ ROUTE (MAP)
            // ==================================================
            $route = [];

            $route[] = [
                'type'    => 'start_day',
                'lat'     => (float)$firstIn->lat,
                'lng'     => (float)$firstIn->long,
                'time'    => $firstIn->date,
                'address' => $firstIn->address
            ];

            foreach ($tracking as $t) {
                $route[] = [
                    'type'    => 'tracking',
                    'lat'     => (float)$t['latitude'],
                    'lng'     => (float)$t['longitude'],
                    'time'    => $t['recorded_at'],
                    'address' => $t['address'] ?? null
                ];
            }

            if ($lastOut) {
                $route[] = [
                    'type'    => 'end_day',
                    'lat'     => (float)$lastOut->lat,
                    'lng'     => (float)$lastOut->long,
                    'time'    => $lastOut->date,
                    'address' => $lastOut->address
                ];
            }

            // ==================================================
            // 6️⃣ TOTAL DISTANCE
            // ==================================================
            $meters = 0;
            for ($i = 1; $i < count($route); $i++) {
                $meters += $this->haversine_distance(
                    $route[$i - 1]['lat'],
                    $route[$i - 1]['lng'],
                    $route[$i]['lat'],
                    $route[$i]['lng']
                );
            }

            $totalKm = round($meters / 1000, 2);

            // ==================================================
            // 7️⃣ FULL DAY ACTIVITY LOGS (OFFICE + CLIENT)
            // ==================================================
            $logs = [];

            // 🏢 Office events
            foreach ($officeLogs as $l) {
                $logs[] = [
                    'time'    => $l->date,
                    'type'    => $l->type_check == 1 ? 'office_check_in' : 'office_check_out',
                    'title'   => $l->type_check == 1 ? 'Office Check-in' : 'Office Check-out',
                    'lat'     => (float)$l->lat,
                    'lng'     => (float)$l->long,
                    'address' => $l->address,
                    'device'  => [
                        'ip'    => $l->ip_address ?? null,
                        'agent' => $l->user_agent ?? null
                    ],
                    'source'  => 'office'
                ];
            }




            $visitRows = $this->db
                ->select('client_id')
                ->where('staff_id', $staff_id)
                ->where('visit_date', $date)
                ->get(db_prefix() . 'sales_visits')
                ->result_array();

            $clients = [];
            $leadIds   = [];

            foreach ($visitRows as $r) {
                if ($r['entity_type'] === 'client') {
                    $clientIds[] = $r['client_id'];
                }
                if ($r['entity_type'] === 'lead') {
                    $leadIds[] = $r['client_id']; // same column, different meaning
                }
            }
            $clients = [];

            if (!empty($clientIds)) {
                $clientRows = $this->db
                    ->where_in('userid', array_unique($clientIds))
                    ->get(db_prefix() . 'clients')
                    ->result_array();

                foreach ($clientRows as $c) {
                    $clients[$c['userid']] = $c;
                }
            }
            $leads = [];

            if (!empty($leadIds)) {
                $leadRows = $this->db
                    ->where_in('id', array_unique($leadIds))
                    ->get(db_prefix() . 'leads')
                    ->result_array();

                foreach ($leadRows as $l) {
                    $leads[$l['id']] = $l;
                }
            }


            $visits = $this->db
                ->where('staff_id', $staff_id)
                ->where('created_at >=', $from)
                ->where('created_at <=', $to)
                ->order_by('created_at', 'ASC')
                ->get(db_prefix() . 'lead_checkins')
                ->result_array();

            $openVisit = null;

            foreach ($visits as $v) {

                // ▶ VISIT CHECK-IN
                if ($v['type'] === 'checkin') {
                    $openVisit = $v;
                    continue;
                }

                // ◀ VISIT CHECK-OUT
                if ($v['type'] === 'checkout' && $openVisit) {

                    $entityType = $openVisit['entity_type'];   // client | lead
                    $entityId   = $openVisit['entity_id'];

                    $entity = null;

                    if ($entityType === 'client') {
                        $entity = $clients[$entityId] ?? null;
                    } elseif ($entityType === 'lead') {
                        $entity = $leads[$entityId] ?? null;
                    }

                    $durationSec = strtotime($v['created_at']) - strtotime($openVisit['created_at']);
                    $durationTxt = gmdate('H\h i\m', $durationSec);

                    // ▶ VISIT START
                    $logs[] = [
                        'time'    => $openVisit['created_at'],
                        'type'    => $entityType . '_visit_start',
                        'title'   => ucfirst($entityType) . ' Visit Start – ' . ($entity['company'] ?? $entity['name'] ?? 'Unknown'),
                        'lat'     => (float)($openVisit['latitude'] ?? $entity['latitude'] ?? 0),
                        'lng'     => (float)($openVisit['longitude'] ?? $entity['longitude'] ?? 0),
                        'address' => $openVisit['address'] ?? $entity['address'] ?? null,
                        'source'  => $entityType,

                        'client' => [
                            'type'    => $entityType,
                            'id'      => $entityId,
                            'name'    => $entity['company'] ?? $entity['name'] ?? null,
                            'phone'   => $entity['phonenumber'] ?? $entity['phone'] ?? null,
                            'city'    => $entity['city'] ?? null,
                            'state'   => $entity['state'] ?? null,
                        ]
                    ];

                    // ◀ VISIT END
                    $logs[] = [
                        'time'     => $v['created_at'],
                        'type'     => $entityType . '_visit_end',
                        'title'    => ucfirst($entityType) . ' Visit End – ' . ($entity['company'] ?? $entity['name'] ?? 'Unknown'),
                        'duration' => $durationTxt,
                        'lat'      => (float)($v['latitude'] ?? $entity['latitude'] ?? 0),
                        'lng'      => (float)($v['longitude'] ?? $entity['longitude'] ?? 0),
                        'address'  => $v['address'] ?? $entity['address'] ?? null,
                        'source'   => $entityType,

                        'client' => [
                            'type' => $entityType,
                            'id'   => $entityId,
                            'name'    => $entity['company'] ?? $entity['name'] ?? null,
                            'phone'   => $entity['phonenumber'] ?? $entity['phone'] ?? null,
                            'city'    => $entity['city'] ?? null,
                            'state'   => $entity['state'] ?? null,
                        ]
                    ];

                    $openVisit = null;
                }
            }

            usort($logs, fn($a, $b) => strtotime($a['time']) <=> strtotime($b['time']));

            // ==================================================
            // 8️⃣ RESPONSE
            // ==================================================
            return $this->response([
                'success' => true,
                'message' => 'Full day activity fetched',
                'data' => [
                    'date' => $date,
                    'total_distance_km' => $totalKm,
                    'total_work_time'   => $totalWorkTime,
                    'start_point' => $route[0],
                    'end_point' => end($route),
                    'points' => $route,
                    'logs'  => $logs
                ]
            ], RestController::HTTP_OK);
        } catch (\Throwable $e) {
            log_message('error', '[Attendance][DayRoute] ' . $e->getMessage());
            return $this->response(
                ['message' => _l('something_went_wrong')],
                RestController::HTTP_INTERNAL_ERROR
            );
        }
    }

    private function haversine_km($lat1, $lon1, $lat2, $lon2)
    {
        $earth = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2 +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) ** 2;

        return 2 * $earth * atan2(sqrt($a), sqrt(1 - $a));
    }


    public function storelocation_post()
    {
        log_message('info', '[Tracking] bulk request');

        /* ================= AUTH ================= */
        $user_id = $this->staffInfo['data']->staff_id ?? null;
        if (!$user_id) {
            return $this->response(['success' => false], 401);
        }

        /* ================= INPUT ================= */
        $payload   = json_decode(file_get_contents("php://input"), true);
        $locations = $payload['locations'] ?? $payload;

        if (!is_array($locations) || empty($locations)) {
            return $this->response(['success' => false], 400);
        }

        usort($locations, function ($a, $b) {
            return strtotime($a['recorded_at']) <=> strtotime($b['recorded_at']);
        });

        /* ================= TABLES ================= */
        $historyTable = db_prefix() . 'tblcheckout_history';
        $summaryTable = db_prefix() . 'tblstaff_daily_summary';
        $stopTable    = db_prefix() . 'tblstaff_stops';
        $today        = date('Y-m-d');

        /* ================= CONFIG ================= */
        $MAX_ACCURACY = 40;    // meters
        $MIN_MOVE     = 70;    // meters
        $MAX_JUMP     = 500;   // meters
        $FORCE_TIME   = 300;   // sec

        $STOP_RADIUS  = 50;    // meters
        $STOP_MIN_TIME = 300;  // sec (5 min)

        /* ================= LAST POINT ================= */
        $last = $this->db
            ->where('staff_id', $user_id)
            ->where('DATE(recorded_at)', $today)
            ->order_by('recorded_at', 'DESC')
            ->limit(1)
            ->get($historyTable)
            ->row_array();

        $lastLat  = $last['latitude'] ?? null;
        $lastLng  = $last['longitude'] ?? null;
        $lastTime = $last ? strtotime($last['recorded_at']) : 0;
        $distance = (float)($last['distance'] ?? 0);

        /* ================= STOP STATE ================= */
        $openStopId = $this->db
            ->select('id')
            ->where('staff_id', $user_id)
            ->where('date', $today)
            ->where('end_time IS NULL', null, false)
            ->order_by('start_time', 'DESC')
            ->limit(1)
            ->get($stopTable)
            ->row('id');

        $idleStartTime = null;
        $inserted = 0;

        /* ================= LOOP ================= */
        foreach ($locations as $p) {

            if (!isset($p['lat'], $p['lng'], $p['recorded_at'])) continue;

            $lat  = (float)$p['lat'];
            $lng  = (float)$p['lng'];
            $time = strtotime($p['recorded_at']);
            $acc  = (float)($p['accuracy'] ?? 0);

            if (!$time) continue;

            /* ===== LIVE LOCATION UPDATE ===== */
            $this->db->query("
            INSERT INTO $summaryTable
            (staff_id,date,last_lat,last_lng,last_ping,last_accuracy,distance)
            VALUES (?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
                last_lat = VALUES(last_lat),
                last_lng = VALUES(last_lng),
                last_ping = VALUES(last_ping),
                last_accuracy = VALUES(last_accuracy),
                distance = VALUES(distance)
        ", [
                $user_id,
                $today,
                $lat,
                $lng,
                date('Y-m-d H:i:s', $time),
                $acc,
                $distance
            ]);

            if ($acc > $MAX_ACCURACY) continue;

            /* ===== FIRST POINT ===== */
            if ($lastLat === null) {
                $this->db->insert($historyTable, [
                    'staff_id'    => $user_id,
                    'latitude'    => $lat,
                    'longitude'   => $lng,
                    'distance'    => $distance,
                    'accuracy_m'  => $acc,
                    'recorded_at' => date('Y-m-d H:i:s', $time),
                ]);
 $this->insertAttendanceLog([
        'employee_id'  => $user_id,
        'punch_type'   => 'in',
        'punch_time'   => date('Y-m-d H:i:s', $time),
        'latitude'     => $lat,
        'longitude'    => $lng,
        'device_id'    => $this->input->user_agent(),
        'face_verified'=> 0, // GPS based
        'source'       => 'gps'
    ]);
                $lastLat  = $lat;
                $lastLng  = $lng;
                $lastTime = $time;
                $inserted++;
                continue;
            }

            if ($time <= $lastTime) continue;

            $timeDiff = $time - $lastTime;

            /* ===== FORCE SAVE ===== */
            if ($timeDiff >= $FORCE_TIME) {
                $this->db->insert($historyTable, [
                    'staff_id'    => $user_id,
                    'latitude'    => $lat,
                    'longitude'   => $lng,
                    'distance'    => $distance,
                    'accuracy_m'  => $acc,
                    'recorded_at' => date('Y-m-d H:i:s', $time),
                ]);

                $lastLat  = $lat;
                $lastLng  = $lng;
                $lastTime = $time;
                $inserted++;
                continue;
            }

            /* ===== DISTANCE ===== */
            $meters = $this->haversine_km($lastLat, $lastLng, $lat, $lng) * 1000;

            /* ===== IDLE / STOP ===== */
            if ($meters < $STOP_RADIUS) {

                if ($idleStartTime === null) {
                    $idleStartTime = $lastTime;
                }

                if (($time - $idleStartTime) >= $STOP_MIN_TIME && !$openStopId) {
                    $this->db->insert($stopTable, [
                        'staff_id'   => $user_id,
                        'date'       => $today,
                        'start_time' => date('Y-m-d H:i:s', $idleStartTime),
                        'lat'        => $lastLat,
                        'lng'        => $lastLng,
                        'source'     => 'gps',
                        'type'       => 'gps',
                        'title'      => 'Idle Stop'
                    ]);

                    $openStopId = $this->db->insert_id();
                }

                continue;
            }

            /* ===== MOVED ===== */
            $idleStartTime = null;

            if ($openStopId) {
                $stop = $this->db->where('id', $openStopId)->get($stopTable)->row_array();
                if ($stop) {
                    $dur = $time - strtotime($stop['start_time']);
                    if ($dur >= $STOP_MIN_TIME) {
                        $this->db->where('id', $openStopId)->update($stopTable, [
                            'end_time'     => date('Y-m-d H:i:s', $time),
                            'duration_sec' => $dur
                        ]);
                    } else {
                        $this->db->where('id', $openStopId)->delete($stopTable);
                    }
                }
                $openStopId = null;
            }

            if ($meters < $MIN_MOVE) continue;
            if ($meters > $MAX_JUMP && $timeDiff < 120) continue;

            /* ===== VALID MOVE ===== */
            $distance += $meters / 1000;

            $this->db->insert($historyTable, [
                'staff_id'    => $user_id,
                'latitude'    => $lat,
                'longitude'   => $lng,
                'distance'    => round($distance, 3),
                'accuracy_m'  => $acc,
                'type_check' => 3,
                'type'       => 'T',
                'recorded_at' => date('Y-m-d H:i:s', $time),
            ]);

            $lastLat  = $lat;
            $lastLng  = $lng;
            $lastTime = $time;
            $inserted++;
        }

        /* ================= FINAL SUMMARY ================= */
        $this->db->where('staff_id', $user_id)
            ->where('date', $today)
            ->update($summaryTable, [
                'distance' => round($distance, 2)
            ]);

        /* ================= CLOSE OPEN STOP ================= */
        if ($openStopId) {
            $stop = $this->db->where('id', $openStopId)->get($stopTable)->row_array();
            if ($stop) {
                $this->db->where('id', $openStopId)->update($stopTable, [
                    'end_time'     => date('Y-m-d H:i:s'),
                    'duration_sec' => time() - strtotime($stop['start_time'])
                ]);
            }
        }

        return $this->response([
            'success'  => true,
            'inserted' => $inserted,
            'distance' => round($distance, 2)
        ], 200);
    }




    public function fix_distance_get($staff_id)
    {
        $history_table = db_prefix() . 'tblcheckout_history';
        $summary_table = db_prefix() . 'tblstaff_daily_summary';

        $rows = $this->db->where('staff_id', $staff_id)
            ->order_by('recorded_at', 'ASC')
            ->get($history_table)
            ->result_array();

        if (!$rows) {
            echo "No data";
            return;
        }

        $prev = null;
        $current_day = null;
        $total_distance = 0;

        foreach ($rows as $r) {

            $row_day = date('Y-m-d', strtotime($r['recorded_at']));

            // 🔁 NEW DAY RESET
            if ($current_day !== $row_day) {
                $total_distance = 0;
                $prev = null;
                $current_day = $row_day;
            }

            if ($prev) {
                $segment = $this->haversine_km(
                    $prev['latitude'],
                    $prev['longitude'],
                    $r['latitude'],
                    $r['longitude']
                );

                $segment = round($segment, 3);

                // ignore abnormal jump
                if ($segment > 2) {
                    $segment = 0;
                }

                $total_distance += $segment;
            }

            $total_distance = round($total_distance, 3);

            // update history table
            $this->db->where('id', $r['id'])->update($history_table, [
                'distance' => $total_distance
            ]);

            // update summary table
            $this->db->where('staff_id', $staff_id)
                ->where('date', $current_day)
                ->update($summary_table, [
                    'distance' => $total_distance
                ]);

            $prev = $r;
        }

        echo "Distance fixed + summary updated ✔";
    }
    public function dayactivity_get()
    {
        try {

            $staff = $this->staffInfo['data'] ?? null;

            if (!$staff) {
                return $this->response([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], RestController::HTTP_UNAUTHORIZED);
            }

            $staff_id = $staff->staff_id;
            $date     = $this->get('date') ?? date('Y-m-d');

            $timeline = [];

            /* ======================================================
           1️⃣ FETCH VISITS WITH COMPANY NAME (OPTIMIZED JOIN)
        ====================================================== */

            $visits = $this->db
                ->select('v.*, 
                      c.company as client_company, 
                      l.name as lead_name')
                ->from(db_prefix() . 'sales_visits v')
                ->join(
                    db_prefix() . 'clients c',
                    'c.userid = v.client_id AND v.entity_type="client"',
                    'left'
                )
                ->join(
                    db_prefix() . 'leads l',
                    'l.id = v.client_id AND v.entity_type="lead"',
                    'left'
                )
                ->where('v.staff_id', $staff_id)
                ->where('DATE(v.checkin_time)', $date)
                ->order_by('v.checkin_time', 'ASC')
                ->get()
                ->result();

            foreach ($visits as $visit) {

                $companyName = $visit->client_company
                    ?: $visit->lead_name
                    ?: 'Unknown';

                /* ===== VISIT CHECKIN ENTRY ===== */
                $timeline[] = [
                    'time'      => $visit->checkin_time,
                    'type'      => 'visit_checkin',
                    'title'     => 'Visited ' . $companyName,
                    'entity_id' => $visit->client_id
                ];

                /* ===== VISIT CHECKOUT ENTRY ===== */
                if (!empty($visit->checkout_time)) {

                    $durationSec = strtotime($visit->checkout_time)
                        - strtotime($visit->checkin_time);

                    $timeline[] = [
                        'time'      => $visit->checkout_time,
                        'type'      => 'visit_checkout',
                        'title'     => 'Left ' . $companyName,
                        'subtitle'  => gmdate("H:i:s", $durationSec),
                        'entity_id' => $visit->client_id
                    ];
                }
            }

            /* ======================================================
           2️⃣ FETCH BREAKS (NON-VISIT STOPS)
        ====================================================== */

            $breaks = $this->db
                ->where('staff_id', $staff_id)
                ->where('date', $date)
                ->where('source !=', 'visit')
                ->order_by('start_time', 'ASC')
                ->get(db_prefix() . 'tblstaff_stops')
                ->result();

            foreach ($breaks as $stop) {

                $timeline[] = [
                    'time'  => $stop->start_time,
                    'type'  => 'break_start',
                    'title' => 'Break Started',
                    'subtitle' => $stop->address
                ];

                $timeline[] = [
                    'time'  => $stop->end_time,
                    'type'  => 'break_end',
                    'title' => 'Break Ended',
                    'subtitle' => gmdate("H:i:s", $stop->duration_sec)
                ];
            }

            /* ======================================================
           3️⃣ SORT COMPLETE TIMELINE BY TIME
        ====================================================== */

            usort($timeline, function ($a, $b) {
                return strtotime($a['time']) - strtotime($b['time']);
            });

            /* ======================================================
           4️⃣ FETCH DAILY SUMMARY
        ====================================================== */

            $summary = $this->db
                ->where('staff_id', $staff_id)
                ->where('date', $date)
                ->get(db_prefix() . 'staff_daily_summary')
                ->row();

            $summaryData = [
                'total_distance_km' => $summary->distance ?? 0,
                'last_ping'         => $summary->last_ping ?? null,
                'last_lat'          => $summary->last_lat ?? null,
                'last_lng'          => $summary->last_lng ?? null
            ];

            /* ======================================================
           5️⃣ FINAL RESPONSE
        ====================================================== */

            return $this->response([
                'success' => true,
                'date'    => $date,
                'summary' => $summaryData,
                'timeline' => $timeline
            ], RestController::HTTP_OK);
        } catch (\Throwable $e) {

            log_message('error', '[DayActivity] ' . $e->getMessage());

            return $this->response([
                'success' => false,
                'message' => 'Something went wrong'
            ], RestController::HTTP_INTERNAL_ERROR);
        }
    }
    public function monthly_analytics_get()
    {
        try {

            $staff_id = $this->staffInfo['data']->staff_id;
            $month    = $this->get('month') ?? date('Y-m');

            if (!$staff_id) {
                return $this->response([
                    'status' => false,
                    'message' => 'Invalid staff'
                ], 400);
            }

            /* ==========================================
         * TOTAL DISTANCE
         * ========================================== */

            $startDate = $month . '-01';
            $endDate   = date('Y-m-t', strtotime($startDate));
            $prevMonth = date('Y-m', strtotime($startDate . ' -1 month'));
            $prevStartDate = $prevMonth . '-01';
            $prevEndDate   = date('Y-m-t', strtotime($prevStartDate));

            $this->db->select('COALESCE(SUM(distance),0) as total_distance', false);
            $this->db->from(db_prefix() . 'staff_daily_summary');
            $this->db->where('staff_id', $staff_id);
            $this->db->where('date >=', $startDate);
            $this->db->where('date <=', $endDate);

            $row = $this->db->get()->row();

            $totalDistanceKm = round($row->total_distance, 2);

            //  $totalDistanceKm = round(($distanceRow->distance ?? 0) / 1000, 2);
            $this->db->select('COALESCE(SUM(distance),0) as total_distance', false);
            $this->db->from(db_prefix() .'tblstaff_daily_summary'); // use correct table
            $this->db->where('staff_id', $staff_id);
            $this->db->where('date >=', $prevStartDate);
            $this->db->where('date <=', $prevEndDate);

            $prevRow = $this->db->get()->row();
            $prevTotalDistance = round($prevRow->total_distance, 2);
            $this->db->where('staff_id', $staff_id);
            $this->db->where('date >=', $prevStartDate);
            $this->db->where('date <=', $prevEndDate);
            $this->db->where('final_check_in_at IS NOT NULL');

            $prevWorkedDays = $this->db
                ->count_all_results(db_prefix() .'staff_daily_summary');

            $prevTotalWorkingDays = 0;
            $prevStart = strtotime($prevStartDate);
            $prevEnd   = strtotime($prevEndDate);

            for ($d = $prevStart; $d <= $prevEnd; $d += 86400) {
                if (date('N', $d) < 7) {
                    $prevTotalWorkingDays++;
                }
            }

            $prevAttendance = $prevTotalWorkingDays > 0
                ? round(($prevWorkedDays / $prevTotalWorkingDays) * 100, 1)
                : 0;
            // Completed Visits
            $this->db->where('staff_id', $staff_id);
            $this->db->where('type', 'VO');
            $this->db->where('recorded_at >=', $prevStartDate . ' 00:00:00');
            $this->db->where('recorded_at <=', $prevEndDate . ' 23:59:59');

            $prevCompletedVisits = $this->db
                ->count_all_results(db_prefix() . 'checkout_history');

            // Planned Visits
            $this->db->where('staff_id', $staff_id);
            $this->db->where('visit_date >=', $prevStartDate);
            $this->db->where('visit_date <=', $prevEndDate);

            $prevPlannedVisits = $this->db
                ->count_all_results(db_prefix() . 'sales_visits');

            $prevVisitCompletion = $prevPlannedVisits > 0
                ? round(($prevCompletedVisits / $prevPlannedVisits) * 100, 1)
                : 0;

            /* ==========================================
         * WORKED DAYS
         * ========================================== */
            $this->db->where('staff_id', $staff_id);
            $this->db->where("DATE_FORMAT(date,'%Y-%m') =", $month);
            $this->db->where('final_check_in_at IS NOT NULL');
            $workedDays = $this->db
                ->count_all_results(db_prefix() . 'staff_daily_summary');

            /* ==========================================
         * TOTAL WORKING DAYS (Mon–Sat example)
         * ========================================== */
            $start = strtotime($month . '-01');
            $end   = strtotime(date('Y-m-t', $start));

            $totalWorkingDays = 0;
            for ($d = $start; $d <= $end; $d += 86400) {
                if (date('N', $d) < 7) { // exclude Sunday
                    $totalWorkingDays++;
                }
            }

            $attendanceRate = $totalWorkingDays > 0
                ? round(($workedDays / $totalWorkingDays) * 100, 1)
                : 0;

            /* ==========================================
         * VISITS
         * ========================================== */
            $this->db->where('staff_id', $staff_id);
            $this->db->where('type', 'VO');
            $this->db->where("DATE_FORMAT(recorded_at,'%Y-%m') =", $month);
            $completedVisits = $this->db
                ->count_all_results(db_prefix() . 'checkout_history');

            // Example planned visits table
            $this->db->where('staff_id', $staff_id);
            $this->db->where("DATE_FORMAT(visit_date,'%Y-%m') =", $month);
            $plannedVisits = $this->db
                ->count_all_results(db_prefix() . 'sales_visits');

            $visitCompletion = $plannedVisits > 0
                ? round(($completedVisits / $plannedVisits) * 100, 1)
                : 0;

            /* ==========================================
         * TOP CLIENT
         * ========================================== */
            $topClient = '';

            $query = $this->db->query("
            SELECT c.company, COUNT(*) as total
            FROM " . db_prefix() . "checkout_history h
            JOIN " . db_prefix() . "clients c ON c.userid = h.entry_id
            WHERE h.staff_id = ?
            AND h.type = 'VO'
            AND DATE_FORMAT(h.recorded_at,'%Y-%m') = ?
            GROUP BY h.entry_id
            ORDER BY total DESC
            LIMIT 1
        ", [$staff_id, $month]);

            if ($query->num_rows() > 0) {
                $topClient = $query->row()->company;
            }

            /* ==========================================
         * BEST PERFORMANCE DAY
         * ========================================== */
            $bestDay = '';
            $bestQuery = $this->db->query("
            SELECT date
            FROM " . db_prefix() . "staff_daily_summary
            WHERE staff_id = ?
            AND DATE_FORMAT(date,'%Y-%m') = ?
            ORDER BY distance DESC
            LIMIT 1
        ", [$staff_id, $month]);

            if ($bestQuery->num_rows() > 0) {
                $bestDay = $bestQuery->row()->date;
            }

            /* ==========================================
         * DISTANCE TREND
         * ========================================== */
            $trendQuery = $this->db->query("
            SELECT date, ROUND(distance/1000,2) as distance
            FROM " . db_prefix() . "staff_daily_summary
            WHERE staff_id = ?
            AND DATE_FORMAT(date,'%Y-%m') = ?
            ORDER BY date ASC
        ", [$staff_id, $month]);

            $trend = $trendQuery->result_array();
            $this->db->select("DATE(recorded_at) as date, COUNT(*) as visits");
            $this->db->from(db_prefix() . 'checkout_history');
            $this->db->where('staff_id', $staff_id);
            $this->db->where('type', 'V'); // visit checkin
            $this->db->where("DATE_FORMAT(recorded_at, '%Y-%m') =", $month);
            $this->db->group_by("DATE(recorded_at)");

            $visitTrend = $this->db->get()->result_array();

            /* ==========================================
 * TODAY ANALYTICS
 * ========================================== */

            $today = date('Y-m-d');

            /* Today's Total Distance */
            $this->db->select('COALESCE(distance,0) as distance', false);
            $this->db->from(db_prefix() . 'staff_daily_summary'); // use correct table
            $this->db->where('staff_id', $staff_id);
            $this->db->where('date', $today);

            $todayRow = $this->db->get()->row();
            $todayTotalDistance = $todayRow ? round($todayRow->distance, 2) : 0;

            /* Today's Completed Visits */
            $this->db->where('staff_id', $staff_id);
            $this->db->where('type', 'VO');
            $this->db->where('DATE(recorded_at)', $today);

            $todayCompletedVisits = $this->db
                ->count_all_results(db_prefix() . 'checkout_history');

            /* Today's Planned Visits */
            $this->db->where('staff_id', $staff_id);
            $this->db->where('visit_date', $today);

            $todayPlannedVisits = $this->db
                ->count_all_results(db_prefix() . 'sales_visits');

            $todayVisitCompletion = $todayPlannedVisits > 0
                ? round(($todayCompletedVisits / $todayPlannedVisits) * 100, 1)
                : 0;


            /* ==========================================
         * RESPONSE
         * ========================================== */
            return $this->response([
                'status' => true,
                'month'  => $month,
                'total_distance_km' => $totalDistanceKm,
                'average_daily_distance' =>
                $workedDays > 0 ? round($totalDistanceKm / $workedDays, 2) : 0,
                'attendance_rate' => $attendanceRate,
                'visit_completion_rate' => $visitCompletion,
                'worked_days' => $workedDays,
                'total_working_days' => $totalWorkingDays,
                'top_client' => $topClient,
                'best_day' => $bestDay,
                "visit_trend" => $visitTrend,
                'distance_trend' => $trend,
                /* Today */
                'today_total_distance_km' => $todayTotalDistance,
                'today_completed_visits'  => $todayCompletedVisits,
                'today_planned_visits'    => $todayPlannedVisits,
                'today_visit_completion_rate' => $todayVisitCompletion,
                'previous_total_distance_km' => $prevTotalDistance,
                'previous_attendance_rate' => $prevAttendance,
                'previous_visit_completion_rate' => $prevVisitCompletion,
                'previous_worked_days' => $prevWorkedDays

            ], 200);
        } catch (Exception $e) {

            return $this->response([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
///smart plannig 
private function geocode_address_osm($address)
{
    if (empty($address)) return null;

    $url = "https://nominatim.openstreetmap.org/search?"
         . "q=" . urlencode($address)
         . "&format=json&limit=1";

    $opts = [
        "http" => [
            "header" => "User-Agent: DotERP/1.0\r\n"
        ]
    ];

    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);

    if (!$response) return null;

    $data = json_decode($response, true);

    if (empty($data)) return null;

    return [
        'lat' => (float)$data[0]['lat'],
        'lng' => (float)$data[0]['lon']
    ];
}

public function cron_prepare_locations()
{
    $limit = 20; // Convert 20 per run

    // Convert Clients
    $clients = $this->db
        ->where('latitude IS NULL', null, false)
        ->limit($limit)
        ->get(db_prefix() .'tblclients')
        ->result_array();

    foreach ($clients as $client) {

        $address = trim(
            $client['address'] . ', ' .
            $client['city'] . ', ' .
            $client['state'] . ', ' .
            $client['zip']
        );

        $geo = $this->geocode_address_osm($address);

        if ($geo) {
            $this->db->where('userid', $client['userid'])
                     ->update(db_prefix().'tblclients', [
                         'latitude' => $geo['lat'],
                         'longitude' => $geo['lng']
                     ]);
        }

        sleep(1); // OSM rate limit protection
    }

    // Convert Leads
    $leads = $this->db
        ->where('latitude IS NULL', null, false)
        ->limit($limit)
        ->get(db_prefix() .'tblleads')
        ->result_array();

    foreach ($leads as $lead) {

        $address = trim(
            $lead['address'] . ', ' .
            $lead['city'] . ', ' .
            $lead['state'] . ', ' .
            $lead['zip']
        );

        $geo = $this->geocode_address_osm($address);

        if ($geo) {
            $this->db->where('id', $lead['id'])
                     ->update(db_prefix().'tblleads', [
                         'latitude' => $geo['lat'],
                         'longitude' => $geo['lng']
                     ]);
        }

        sleep(1);
    }

    echo "Location preparation completed.";
}

// =======================================================
// PREPARE MISSING LOCATIONS (CLIENTS + LEADS)
// Converts address → lat/lng using OpenStreetMap
// =======================================================
private function prepare_missing_locations_in_radius($originLat, $originLng, $radiusKm, $limit = 5)
{
    // ------------------------------------------
    // PROCESS CLIENTS
    // ------------------------------------------
    $clients = $this->db
        ->where('latitude IS NULL', null, false)
        ->where('longitude IS NULL', null, false)
        ->limit($limit)
        ->get('tblclients')
        ->result_array();

    foreach ($clients as $client) {

        $fullAddress = trim(
            ($client['address'] ?? '') . ' ' .
            ($client['city'] ?? '') . ' ' .
            ($client['state'] ?? '') . ' ' .
            ($client['zip'] ?? '')
        );

        if (!$fullAddress) continue;

        $coords = $this->geocode_address_osm($fullAddress);

        if ($coords) {
            $this->db->where('userid', $client['userid'])
                ->update('tblclients', [
                    'latitude'  => $coords['lat'],
                    'longitude' => $coords['lng']
                ]);
        }
    }

    // ------------------------------------------
    // PROCESS LEADS
    // ------------------------------------------
    $leads = $this->db
        ->where('latitude IS NULL', null, false)
        ->where('longitude IS NULL', null, false)
        ->limit($limit)
        ->get('tblleads')
        ->result_array();

    foreach ($leads as $lead) {

        $fullAddress = trim(
            ($lead['address'] ?? '') . ' ' .
            ($lead['city'] ?? '') . ' ' .
            ($lead['state'] ?? '') . ' ' .
            ($lead['zip'] ?? '')
        );

        if (!$fullAddress) continue;

        $coords = $this->geocode_address_osm($fullAddress);

        if ($coords) {
            $this->db->where('id', $lead['id'])
                ->update('tblleads', [
                    'latitude'  => $coords['lat'],
                    'longitude' => $coords['lng']
                ]);
        }
    }
}
private function cluster_points($points, $clusterRadius = 1) // 1 KM cluster
{
    $clusters = [];
    $used = [];

    foreach ($points as $i => $point) {

        if (isset($used[$i])) continue;

        $cluster = [];
        $cluster[] = $point;
        $used[$i] = true;

        foreach ($points as $j => $other) {

            if ($i == $j || isset($used[$j])) continue;

            $distance = $this->calculate_distance(
                $point['latitude'],
                $point['longitude'],
                $other['latitude'],
                $other['longitude']
            );

            if ($distance <= $clusterRadius) {
                $cluster[] = $other;
                $used[$j] = true;
            }
        }

        $clusters[] = [
            'cluster_center' => [
                'lat' => $point['latitude'],
                'lng' => $point['longitude']
            ],
            'total_points' => count($cluster),
            'points' => $cluster
        ];
    }

    return $clusters;
}
/*public function smart_planning_get()
{
    $lat    = (float)$this->get('lat');
    $lng    = (float)$this->get('lng');
    $radius = (int)($this->get('radius') ?? 10);

    if (!$lat || !$lng) {
        return $this->response([
            'status' => false,
            'message' => 'Latitude & Longitude required'
        ], 400);
    }

    // FIXED CALL
    $this->prepare_missing_locations_in_radius($lat, $lng, $radius, 5);

    // ----------------------------------------------------
    // FETCH CLIENTS + LAST VISIT FROM sales_visits
    // ----------------------------------------------------
    $clients = $this->db->query("
        SELECT 
            c.userid as id,
            c.company as name,
            c.latitude,
            c.longitude,
            MAX(sv.visit_date) as last_visit_date,

            ( 6371 * acos(
                cos(radians(?)) *
                cos(radians(c.latitude)) *
                cos(radians(c.longitude) - radians(?)) +
                sin(radians(?)) *
                sin(radians(c.latitude))
            )) AS distance

        FROM tblclients c

        LEFT JOIN ".db_prefix()."sales_visits sv
            ON sv.client_id = c.userid
            AND sv.entity_type = 'client'
            AND sv.status = 'completed'

        WHERE c.latitude IS NOT NULL

        GROUP BY c.userid

        HAVING distance < ?
    ", [$lat, $lng, $lat, $radius])->result_array();


    foreach ($clients as &$c) {

        if (!empty($c['last_visit_date'])) {
            $days = (new DateTime($c['last_visit_date']))
                ->diff(new DateTime())->days;

            $c['visit_status'] = "Visited {$days} days ago";
        } else {
            $c['visit_status'] = "Never visited";
        }

        // Simple priority logic
        $score = 0;

        if (empty($c['last_visit_date'])) {
            $score += 20;
        } else {
            $days = (new DateTime($c['last_visit_date']))
                ->diff(new DateTime())->days;

            if ($days > 7) $score += 10;
        }

        $c['priority_score'] = $score;
        $c['type'] = 'client';
    }

    // ----------------------------------------------------
    // FETCH LEADS
    // ----------------------------------------------------
    $leads = $this->db->query("
        SELECT 
            id,
            name,
            latitude,
            longitude,

            ( 6371 * acos(
                cos(radians(?)) *
                cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) *
                sin(radians(latitude))
            )) AS distance

        FROM tblleads
        WHERE latitude IS NOT NULL
        HAVING distance < ?
    ", [$lat, $lng, $lat, $radius])->result_array();

    foreach ($leads as &$l) {
        $l['priority_score'] = 5;
        $l['type'] = 'lead';
    }

    // Route optimize
    $optimizedClients = $this->optimize_route($lat, $lng, $clients);

    // OSM suggestions
    $suggested = $this->get_osm_suggestions($lat, $lng, $radius);

    return $this->response([
        'status' => true,
        'origin' => ['lat'=>$lat,'lng'=>$lng],
        'clients' => $optimizedClients,
        'leads' => $leads,
        'suggested' => $suggested
    ], 200);
}*/

public function smart_planning_get()
{
    $lat    = (float)$this->get('lat');
    $lng    = (float)$this->get('lng');
    $radius = (int)($this->get('radius') ?? 10);
   $category=$this->get('category') ?? null;
    if (!$lat || !$lng) {
        return $this->response([
            'status' => false,
            'message' => 'Latitude & Longitude required'
        ], 400);
    }

    $this->prepare_missing_locations_in_radius($lat, $lng, $radius, 5);

    // ----------------------------------------------------
    // FETCH CLIENTS + LAST VISIT + CONVERSION SCORE
    // ----------------------------------------------------
    $clients = $this->db->query("
        SELECT 
            c.userid as id,
            c.company as name,
            c.latitude,
            c.longitude,
            MAX(sv.visit_date) as last_visit_date,

            ( 6371 * acos(
                cos(radians(?)) *
                cos(radians(c.latitude)) *
                cos(radians(c.longitude) - radians(?)) +
                sin(radians(?)) *
                sin(radians(c.latitude))
            )) AS distance

        FROM tblclients c

        LEFT JOIN ".db_prefix()."sales_visits sv
            ON sv.client_id = c.userid
            AND sv.entity_type = 'client'
            AND sv.status = 'completed'

        WHERE c.latitude IS NOT NULL
        GROUP BY c.userid
        HAVING distance < ?
    ", [$lat, $lng, $lat, $radius])->result_array();


    foreach ($clients as &$c) {

        // Visit Recency
        $days = null;
        if (!empty($c['last_visit_date'])) {
            $days = (new DateTime($c['last_visit_date']))
                ->diff(new DateTime())->days;

            $c['visit_status'] = "Visited {$days} days ago";
        } else {
            $c['visit_status'] = "Never visited";
        }

        // 🔥 Conversion Intelligence
        $conversionScore = $this->get_conversion_score($c['id']);
        $c['conversion_score'] = $conversionScore;

        // 🔥 Smart Priority Scoring
        $score = 0;

        if (!$days) $score += 20;
        if ($days && $days > 7) $score += 10;
        $score += $conversionScore; // boost by conversion %

        $c['priority_score'] = $score;
        $c['type'] = 'client';
    }

    // ----------------------------------------------------
    // FETCH LEADS
    // ----------------------------------------------------
    $leads = $this->db->query("
        SELECT 
            id,
            name,
            latitude,
            longitude,

            ( 6371 * acos(
                cos(radians(?)) *
                cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) *
                sin(radians(latitude))
            )) AS distance

        FROM tblleads
        WHERE latitude IS NOT NULL
        HAVING distance < ?
    ", [$lat, $lng, $lat, $radius])->result_array();

    foreach ($leads as &$l) {
        $l['priority_score'] = 5;
        $l['type'] = 'lead';
    }

    // ----------------------------------------------------
    // SMART CLUSTERING (Clients + Leads)
    // ----------------------------------------------------
    $allPoints = array_merge($clients, $leads);
    $clusters = $this->cluster_points($allPoints, 1); // 1km cluster

    // Route optimize only clients
    $optimizedClients = $this->optimize_route($lat, $lng, $clients);

    // OSM suggestions (LIMITED)
    $suggested = $this->get_osm_suggestions($lat, $lng, $radius,$category);

    return $this->response([
        'status' => true,
        'origin' => ['lat'=>$lat,'lng'=>$lng],
        'clients' => $optimizedClients,
        'leads' => $leads,
        'clusters' => $clusters,
        'suggested' => $suggested
    ], 200);
}
private function get_conversion_score($clientId)
{
    $visits = $this->db
        ->where('client_id', $clientId)
        ->count_all_results(db_prefix().'sales_visits');

    if ($visits == 0) return 0;

    $orders = $this->db
        ->where('clientid', $clientId)
        ->count_all_results('tblinvoices');

    return round(($orders / $visits) * 20); 
    // scale 0-20 weight
}
private function optimize_route($originLat, $originLng, $points)
{
    if (empty($points)) return [];

    $ordered = [];
    $remaining = $points;

    $currentLat = $originLat;
    $currentLng = $originLng;

    while (!empty($remaining)) {

        $nearestIndex = null;
        $nearestDistance = PHP_INT_MAX;

        foreach ($remaining as $index => $point) {

            if (empty($point['latitude']) || empty($point['longitude']))
                continue;

            $distance = $this->calculate_distance(
                $currentLat,
                $currentLng,
                $point['latitude'],
                $point['longitude']
            );

            if ($distance < $nearestDistance) {
                $nearestDistance = $distance;
                $nearestIndex = $index;
            }
        }

        if ($nearestIndex === null) break;

        $next = $remaining[$nearestIndex];
        $next['route_order'] = count($ordered) + 1;

        $ordered[] = $next;

        $currentLat = $next['latitude'];
        $currentLng = $next['longitude'];

        unset($remaining[$nearestIndex]);
    }

    return array_values($ordered);
}
private function get_osm_suggestions($lat, $lng, $radius, $category = 'all')
{
    $radiusMeters = $radius * 1000;

    $categoryMap = [
        'medical'     => 'amenity=pharmacy',
        'grocery'     => 'shop=supermarket',
        'hardware'    => 'shop=hardware',
        'garments'    => 'shop=clothes',
        'electronics' => 'shop=electronics',
        'factory'     => 'industrial=*'
    ];

    $results = [];

    // If category = all → show generic shop
    if ($category == 'all') {
        $tag = 'shop';
    } else {
        if (!isset($categoryMap[$category])) {
            return []; // invalid category
        }
        $tag = $categoryMap[$category];
    }

    $query = "
        [out:json];
        node
        (around:$radiusMeters,$lat,$lng)
        [$tag];
        out;
    ";

    $url = "https://overpass-api.de/api/interpreter?data=" . urlencode($query);
    $response = @file_get_contents($url);

    if (!$response) return [];

    $data = json_decode($response, true);
    if (!isset($data['elements'])) return [];

    foreach ($data['elements'] as $element) {

    if (!isset($element['lat']) || !isset($element['lon'])) {
        continue;
    }

    $tags = $element['tags'] ?? [];

    $name = $tags['name'] ?? null;
    if (!$name) {
        continue;
    }

    // -------------------------------
    // DUPLICATE CHECK (case insensitive)
    // -------------------------------
    $this->db->where('LOWER(company)', strtolower($name));
    $existsClient = $this->db->count_all_results('tblclients');

    $this->db->where('LOWER(name)', strtolower($name));
    $existsLead = $this->db->count_all_results('tblleads');

    if ($existsClient > 0 || $existsLead > 0) {
        continue;
    }

    // -------------------------------
    // EXTRACT PHONE
    // -------------------------------
    $phone =
        $tags['phone'] ??
        $tags['contact:phone'] ??
        $tags['contact:mobile'] ??
        null;

    // -------------------------------
    // BUILD ADDRESS
    // -------------------------------
    $addressParts = [];

    if (!empty($tags['addr:housenumber'])) {
        $addressParts[] = $tags['addr:housenumber'];
    }

    if (!empty($tags['addr:street'])) {
        $addressParts[] = $tags['addr:street'];
    }

    $address = implode(' ', $addressParts);

    $city    = $tags['addr:city']    ?? '';
    $state   = $tags['addr:state']   ?? '';
    $country = $tags['addr:country'] ?? '';

    // -------------------------------
    // DISTANCE CALCULATION
    // -------------------------------
    $distance = $this->calculate_distance(
        $lat,
        $lng,
        $element['lat'],
        $element['lon']
    );

    // -------------------------------
    // FINAL RESULT
    // -------------------------------
    $results[] = [
        'name'      => $name,
        'latitude'  => (float)$element['lat'],
        'longitude' => (float)$element['lon'],
        'distance'  => round($distance, 2),
        'type'      => 'suggested',
        'category'  => $category,

        // 🔥 NEW FIELDS
        'phone'     => $phone,
        'address'   => $address,
        'city'      => $city,
        'state'     => $state,
        'country'   => $country,
    ];
}

    usort($results, fn($a,$b) => $a['distance'] <=> $b['distance']);

    return array_slice($results, 0, 50);
}

private function calculate_distance($lat1, $lon1, $lat2, $lon2)
{
    // Validate inputs
    if (!$lat1 || !$lon1 || !$lat2 || !$lon2) {
        return 0;
    }

    $earthRadius = 6371; // Earth radius in KM

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) *
         cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    $distance = $earthRadius * $c;

    return round($distance, 2); // Distance in KM
}
}