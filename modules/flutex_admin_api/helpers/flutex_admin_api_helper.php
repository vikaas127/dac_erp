<?php

defined('BASEPATH') || exit('No direct script access allowed');

if (!function_exists('convertSerializeDataToObject')) {
    function convertSerializeDataToObject($data)
    {
        return json_decode(json_encode(unserialize($data)));
    }
}

if (!function_exists('isAuthorized')) {
    function isAuthorized()
    {
        $CI = &get_instance();

        // ❌ Module check
        if (checkModuleStatus()) {
            return [
                'response' => [
                    'message' => checkModuleStatus()['response']['message'],
                ],
                'response_code' => 404,
            ];
        }

        // 🔐 Decode token
        $loggedInUser = $CI->authorization_token->validateToken();

        if (!$loggedInUser['status']) {
            return [
                'response' => [
                    'message' => $loggedInUser['message'],
                ],
                'response_code' => 401,
            ];
        }

        $user = $loggedInUser['data'];

        // ===============================
        // 🔥 STAFF AUTH
        // ===============================
        if ($user->type == 'staff') {

            $CI->db->where('staffid', $user->staff_id);
            $staff = $CI->db->get(db_prefix() . 'staff')->row();

            if (!$staff) {
                return unauthorized();
            }

            $token = $staff->flutex_api_key;

            $authHeader = $CI->input->request_headers()['Authorization'] ?? '';

            if (empty($token) || trim($token) !== trim($authHeader)) {
                return unauthorized();
            }

            if ($staff->active == 0) {
                return [
                    'response' => [
                        'message' => _l('admin_auth_inactive_account'),
                    ],
                    'response_code' => 401,
                ];
            }

        if (isset($staff->staffid)) {
    $staff->staff_id = $staff->staffid;
}

return [
    'status' => true,
    'data'   => $staff
];
        }

        // ===============================
        // 🔥 CLIENT AUTH (NEW)
        // ===============================
        if ($user->type == 'client') {

            $CI->db->where('userid', $user->client_id);
            $client = $CI->db->get(db_prefix() . 'clients')->row();

            if (!$client) {
                return unauthorized();
            }

            // 🔥 OPTIONAL: you can store token in contacts table if needed
            // or skip token match for client

            return [
                'status' => true,
                'data' => (object)[
                    'client_id' => $client->userid,
                    'contact_id' => $user->contact_id,
                    'type' => 'client'
                ]
            ];
        }

        // fallback
        return unauthorized();
    }
}
function unauthorized()
{
    return [
        'response' => [
            'message' => "You've been logged out. Login in to continue.",
        ],
        'response_code' => 401,
    ];
}

if (!function_exists('checkModuleStatus')) {
    function checkModuleStatus()
    {
        get_instance()->load->library('app_modules');
       //  get_instance()->flutex_admin_api->activate(FLUTEX_ADMIN_API); 
      //  if (get_instance()->app_modules->is_inactive('flutex_admin_api')) {
        //    return [
        //        'response' => [
             //       'message' => 'DotOne/Staff API module is deactivated. Please reactivate or contact support',
            //    ],
            //    'response_code' => 404,
        //    ];
      //  }
    }
}

function get_invoices_percent_by_status_api($status, $staff_id)
{
    $has_permission_view = staff_can('view',  'invoices', $staff_id);
    $total_invoices      = total_rows(db_prefix() . 'invoices', 'status NOT IN(5)' . (!$has_permission_view ? ' AND (' . get_invoices_where_sql_for_staff($staff_id) . ')' : ''));

    $data            = [];
    $total_by_status = 0;
    if (!is_numeric($status)) {
        if ($status == 'not_sent') {
            $total_by_status = total_rows(db_prefix() . 'invoices', 'sent=0 AND status NOT IN(2,5)' . (!$has_permission_view ? ' AND (' . get_invoices_where_sql_for_staff($staff_id) . ')' : ''));
        }
    } else {
        $total_by_status = total_rows(db_prefix() . 'invoices', 'status = ' . $status . ' AND status NOT IN(5)' . (!$has_permission_view ? ' AND (' . get_invoices_where_sql_for_staff($staff_id) . ')' : ''));
    }
    $percent                 = ($total_invoices > 0 ? number_format(($total_by_status * 100) / $total_invoices, 2) : 0);
    $data['total_by_status'] = $total_by_status;
    $data['percent']         = $percent;
    $data['total']           = $total_invoices;

    return $data;
}

function get_proposals_percent_by_status_api($status,$staff_id, $total_proposals = '')
{
    $has_permission_view                 = staff_can('view',  'proposals',$staff_id);
    $has_permission_view_own             = staff_can('view_own',  'proposals',$staff_id);
    $allow_staff_view_proposals_assigned = get_option('allow_staff_view_proposals_assigned');
    $staffId                             = $staff_id;

    $whereUser = '';
    if (!$has_permission_view) {
        if ($has_permission_view_own) {
            $whereUser = '(addedfrom=' . $staffId;
            if ($allow_staff_view_proposals_assigned == 1) {
                $whereUser .= ' OR assigned=' . $staffId;
            }
            $whereUser .= ')';
        } else {
            $whereUser .= 'assigned=' . $staffId;
        }
    }

    if (!is_numeric($total_proposals)) {
        $total_proposals = total_rows(db_prefix() . 'proposals', $whereUser);
    }

    $data            = [];
    $total_by_status = 0;
    $where           = 'status=' . get_instance()->db->escape_str($status);
    if (!$has_permission_view) {
        $where .= ' AND (' . $whereUser . ')';
    }

    $total_by_status = total_rows(db_prefix() . 'proposals', $where);
    $percent         = ($total_proposals > 0 ? number_format(($total_by_status * 100) / $total_proposals, 2) : 0);

    $data['total_by_status'] = $total_by_status;
    $data['percent']         = $percent;
    $data['total']           = $total_proposals;

    return $data;
}

function get_estimates_percent_by_status_api($status, $staff_id, $project_id = null)
{
    $has_permission_view = staff_can('view',  'estimates',$staff_id);
    $where               = '';

    if (isset($project_id)) {
        $where .= 'project_id=' . get_instance()->db->escape_str($project_id) . ' AND ';
    }
    if (!$has_permission_view) {
        $where .= get_estimates_where_sql_for_staff($staff_id);
    }

    $where = trim($where);

    if (endsWith($where, ' AND')) {
        $where = substr_replace($where, '', -3);
    }

    $total_estimates = total_rows(db_prefix() . 'estimates', $where);

    $data            = [];
    $total_by_status = 0;

    if (!is_numeric($status)) {
        if ($status == 'not_sent') {
            $total_by_status = total_rows(db_prefix() . 'estimates', 'sent=0 AND status NOT IN(2,3,4)' . ($where != '' ? ' AND (' . $where . ')' : ''));
        }
    } else {
        $whereByStatus = 'status=' . $status;
        if ($where != '') {
            $whereByStatus .= ' AND (' . $where . ')';
        }
        $total_by_status = total_rows(db_prefix() . 'estimates', $whereByStatus);
    }

    $percent                 = ($total_estimates > 0 ? number_format(($total_by_status * 100) / $total_estimates, 2) : 0);
    $data['total_by_status'] = $total_by_status;
    $data['percent']         = $percent;
    $data['total']           = $total_estimates;

    return $data;
}

if (!function_exists('resolveFlutexWebAuth')) {
    function resolveFlutexWebAuth()
    {
        $auth = isAuthorized();
        if (isset($auth['status'])) {
            return $auth;
        }

        $CI    = &get_instance();
        $token = trim((string) $CI->input->get('token'));
        if ($token === '') {
            return unauthorized();
        }

        $CI->load->config('jwt');
        require_once APP_MODULES_PATH . 'flutex_admin_api/vendor/autoload.php';

        try {
            $decoded = \Firebase\JWT\JWT::decode(
                $token,
                new \Firebase\JWT\Key(base_url(), $CI->config->item('jwt_algorithm'))
            );
        } catch (\Throwable $e) {
            return unauthorized();
        }

        if (empty($decoded->staff_id)) {
            return unauthorized();
        }

        $staff = $CI->db->where('staffid', (int) $decoded->staff_id)->get(db_prefix().'staff')->row();
        if (!$staff || trim((string) $staff->flutex_api_key) !== trim($token) || (int) $staff->active !== 1) {
            return unauthorized();
        }

        $staff->staff_id = $staff->staffid;

        return [
            'status' => true,
            'data'   => $staff,
        ];
    }
}

if (!function_exists('can_view_staff_map')) {
    function can_view_staff_map($viewer_id, $target_staff_id)
    {
        $viewer_id       = (int) $viewer_id;
        $target_staff_id = (int) $target_staff_id;

        if ($viewer_id === $target_staff_id) {
            return true;
        }

        if (function_exists('is_admin') && is_admin($viewer_id)) {
            return true;
        }

        return staff_can('view', 'timesheets_dashboard', $viewer_id);
    }
}

if (!function_exists('build_staff_map_webview_url')) {
    function build_staff_map_webview_url($staff_id, $date, $token = null)
    {
        $params = [
            'staff_id' => (int) $staff_id,
            'date'     => $date,
        ];

        if ($token) {
            $params['token'] = $token;
        }

        return site_url('flutex_admin_api/attendance/map?' . http_build_query($params));
    }
}