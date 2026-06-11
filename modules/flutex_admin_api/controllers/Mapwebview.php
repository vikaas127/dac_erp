<?php

defined('BASEPATH') || exit('No direct script access allowed');

class Mapwebview extends CI_Controller
{
    protected $staffInfo;

    public function __construct()
    {
        parent::__construct();

        $this->load->helper('flutex_admin_api');
        $this->load->helper('admin');

        $auth = resolveFlutexWebAuth();
        if (!isset($auth['status'])) {
            show_error($auth['response']['message'] ?? 'Unauthorized', $auth['response_code'] ?? 401);
        }

        $this->staffInfo = $auth;
    }

    public function map()
    {
        $viewer_id = (int) $this->staffInfo['data']->staff_id;
        $staff_id  = (int) $this->input->get('staff_id');
        $date      = $this->input->get('date');
        $from      = $this->input->get('from');
        $to        = $this->input->get('to');

        if (!$staff_id) {
            $staff_id = $viewer_id;
        }

        if (!can_view_staff_map($viewer_id, $staff_id)) {
            show_error('Forbidden', 403);
        }

        if (!$date && (!$from || !$to)) {
            $date = date('Y-m-d');
        }

        $this->load->helper('modules');
        $this->load->helper('timesheets/timesheets');

        $data = [
            'staff_id'            => $staff_id,
            'date'                => $date,
            'from'                => $from,
            'to'                  => $to,
            'max_accuracy_m'      => $this->input->get('max_accuracy_m'),
            'googlemap_api_key'   => get_timesheets_option('googlemap_api_key'),
            'json_endpoint'       => site_url('flutex_admin_api/attendance/map_data'),
            'activity_endpoint'   => site_url('flutex_admin_api/attendance/map_activity'),
            'map_token'           => trim((string) $this->input->get('token')),
            'mobile_embed'        => true,
            'staff_list'          => [[
                'id'        => $staff_id,
                'full_name' => trim(($this->staffInfo['data']->firstname ?? '').' '.($this->staffInfo['data']->lastname ?? '')),
            ]],
        ];

        if ($staff_id !== $viewer_id) {
            $target = $this->db->select('firstname,lastname')
                ->where('staffid', $staff_id)
                ->get(db_prefix().'staff')
                ->row_array();
            if ($target) {
                $data['staff_list'] = [[
                    'id'        => $staff_id,
                    'full_name' => trim(($target['firstname'] ?? '').' '.($target['lastname'] ?? '')),
                ]];
            }
        }

        extract($data);
        include module_views_path('timesheets', 'timekeeping/staff_map.php');
    }

    public function map_data()
    {
        $viewer_id = (int) $this->staffInfo['data']->staff_id;
        $staff_id  = (int) $this->input->get('staff_id');
        $date      = $this->input->get('date');
        $from      = $this->input->get('from');
        $to        = $this->input->get('to');
        $maxAcc    = (float) $this->input->get('max_accuracy_m');

        if ($maxAcc <= 0) {
            $maxAcc = 200;
        }

        if (!$staff_id || !can_view_staff_map($viewer_id, $staff_id)) {
            return $this->jsonError('Forbidden', 403);
        }

        $this->load->model('timesheets/timesheets_model');

        if ($date) {
            $points = $this->timesheets_model->get_path_for_date($staff_id, $date, $maxAcc);
            $summaryRow = $this->db->get_where(db_prefix().'tblstaff_daily_summary', [
                'staff_id' => $staff_id,
                'date'     => $date,
            ])->row_array();

            $summary = null;
            if ($summaryRow) {
                $summary = [
                    'check_in'  => $summaryRow['final_check_in_at'],
                    'check_out' => $summaryRow['final_check_out_at'],
                    'distance'  => (float) $summaryRow['distance'],
                    'last_lat'  => $summaryRow['last_lat'],
                    'last_lng'  => $summaryRow['last_lng'],
                    'last_ping' => $summaryRow['last_ping'],
                    'is_live'   => empty($summaryRow['final_check_out_at']),
                ];
            }

            $timeline = [];
            if (method_exists($this->timesheets_model, 'get_full_day1_activity')) {
                $timeline = $this->timesheets_model->get_full_day1_activity($staff_id, $date) ?: [];
            }

            return $this->jsonOk([
                'mode'     => 'single_day',
                'date'     => $date,
                'points'   => $points,
                'summary'  => $summary,
                'timeline' => $timeline,
            ]);
        }

        if ($from && $to) {
            $paths = $this->timesheets_model->get_paths_between($staff_id, $from, $to, $maxAcc);

            return $this->jsonOk([
                'mode' => 'range_by_day',
                'from' => $from,
                'to'   => $to,
                'days' => $paths,
            ]);
        }

        return $this->jsonError('Provide either date=YYYY-MM-DD OR from=YYYY-MM-DD&to=YYYY-MM-DD');
    }

    public function map_activity()
    {
        $viewer_id = (int) $this->staffInfo['data']->staff_id;
        $staff_id  = (int) $this->input->get('staff_id');
        $date      = $this->input->get('date');

        if (!$staff_id || !$date || !can_view_staff_map($viewer_id, $staff_id)) {
            return $this->jsonError('Forbidden', 403);
        }

        $this->load->model('timesheets/timesheets_model');
        $data = $this->timesheets_model->get_full_day_activity($staff_id, $date);

        return $this->jsonOk([
            'data' => $data,
        ]);
    }

    private function jsonOk(array $payload, $code = 200)
    {
        return $this->output
            ->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode(['status' => true] + $payload));
    }

    private function jsonError($message, $code = 400)
    {
        return $this->output
            ->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode(['status' => false, 'error' => $message]));
    }
}
