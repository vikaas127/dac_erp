<?php

defined('BASEPATH') || exit('No direct script access allowed');

require_once __DIR__.'/RestController.php';
require_once __DIR__.'/../vendor/autoload.php';
use FlutexAdminApi\RestController;

class Authentication extends RestController
{
    
    public function __construct()
    {
        parent::__construct();
        register_language_files('flutex_admin_api');
        load_admin_language();

        $this->load->helper('flutex_admin_api');
        if (checkModuleStatus()) {
            $this->response(checkModuleStatus()['response'], checkModuleStatus()['response_code']);
        }

    }

    public function domain_check_post()
{
    $post = json_decode($this->input->raw_input_stream, true);
    $input = trim($post['domain'] ?? '');

    if ($input === '') {
        return $this->response([
            'success' => false,
            'message' => 'Domain is required'
        ], RestController::HTTP_BAD_REQUEST);
    }

    // Normalize domain
    $domain = strtolower($input);
    $domain = preg_replace('#^https?://#', '', $domain);
    $domain = rtrim($domain, '/');

    $baseDomain = 'techdotbit.in';
    $slug = null;

    // Case 1: subdomain.techdotbit.in
    if (preg_match('/^([a-z0-9\-]+)\.' . preg_quote($baseDomain, '/') . '$/', $domain, $m)) {
        $slug = $m[1];
    }
    // Case 2: only slug
    elseif (!str_contains($domain, '.')) {
        $slug   = $domain;
        $domain = $slug . '.' . $baseDomain;
    }

    $table = perfex_saas_table('companies');

    $this->db->group_start();

    if ($slug !== null) {
        $this->db->where('slug', $slug);
    }

    $this->db->or_where('custom_domain', $domain);

    $this->db->group_end();

    $company = $this->db->get($table)->row();

    if (!$company) {
        return $this->response([
            'success' => false,
            'message' => 'Company not found'
        ], RestController::HTTP_NOT_FOUND);
    }

    if (in_array($company->status, ['disabled', 'banned', 'pending-delete'])) {
        return $this->response([
            'success' => false,
            'message' => 'Company is not active'
        ], RestController::HTTP_FORBIDDEN);
    }

    $finalDomain = $company->custom_domain ?: ($company->slug . '.' . $baseDomain);

    return $this->response([
        'success' => true,
        'message' => 'Company verified',
        'data'    => [
            'id'       => $company->id,
            'name'     => $company->name,
            'slug'     => $company->slug,
            'status'   => $company->status,
            'domain'   => $finalDomain,
            'base_url' => 'https://' . $finalDomain,
        ]
    ], RestController::HTTP_OK);
}
public function app_version_get()
{
    $response = [
        "android_version" => "1.2.35",
        "ios_version"     => "1.2.29",
        "force_android"   => false,
        "force_ios"       => false,
        "android_store_url"=> "https://play.google.com/store/apps/details?id=com.techdotbit.dotone",
     "ios_store_url"=> "https://apps.apple.com/in/app/dotone/id6759178649",
        "message"         => "We've released an improved version of the app with performance enhancements and bug fixes. Update now for the best experience."
    ];

    echo json_encode($response);
}

public function domain_check111_get()
{
    $post = json_decode($this->input->raw_input_stream, true);
    $input = trim($post['domain'] ?? '');

    if ($input === '') {
        return $this->_res(false, 'Domain is required');
    }

    // Normalize domain
    $domain = strtolower($input);
    $domain = preg_replace('#^https?://#', '', $domain);
    $domain = rtrim($domain, '/');

    $baseDomain = 'techdotbit.in';
    $slug = null;

    // Case 1: subdomain.techdotbit.in
    if (preg_match('/^([a-z0-9\-]+)\.' . preg_quote($baseDomain, '/') . '$/', $domain, $m)) {
        $slug = $m[1];
    }
    // Case 2: user typed only slug
    elseif (!str_contains($domain, '.')) {
        $slug = $domain;
        $domain = $slug . '.' . $baseDomain;
    }

    $table = perfex_saas_table('companies');

    $this->db->group_start();

    if ($slug !== null) {
        $this->db->where('slug', $slug);
    }

    $this->db->or_where('custom_domain', $domain);

    $this->db->group_end();

    $company = $this->db->get($table)->row();

    if (!$company) {
        return $this->_res(false, 'Company not found');
    }

    if (in_array($company->status, ['disabled', 'banned', 'pending-delete'])) {
        return $this->_res(false, 'Company is not active');
    }

    $finalDomain = $company->custom_domain ?: ($company->slug . '.' . $baseDomain);

    return $this->_res(true, 'Company verified', [
        'id'       => $company->id,
        'name'     => $company->name,
        'slug'     => $company->slug,
        'status'   => $company->status,
        'domain'   => $finalDomain,
        'base_url' => 'https://' . $finalDomain,
    ]);
}
    public function domain_check1()
    {
        $post = json_decode($this->input->raw_input_stream, true);
        $input = trim($post['domain'] ?? '');

        if (empty($input)) {
            return $this->_res(false, 'Domain is required');
        }

        // Remove protocol and trailing slashes
        $domain = preg_replace('/^https?:\/\//', '', strtolower($input));
        $domain = preg_replace('/\/+$/', '', $domain); // remove trailing /
        $domainParts = explode('.', $domain);
        $baseDomain = 'techdotbit.in';

        // If it's a full domain like savit.techdotbit.in
        // Extract slug (savit) if possible
        $slug = '';
        if (count($domainParts) > 2 && implode('.', array_slice($domainParts, -2)) === $baseDomain) {
            $slug = $domainParts[count($domainParts) - 3];
        } elseif (count($domainParts) == 2 && end($domainParts) == $baseDomain) {
            // e.g. techdotbit.in
            $slug = '';
        } else {
            // e.g. user typed only "savit"
            $slug = $domainParts[0];
        }

        $fullDomain = $slug ? ($slug . '.' . $baseDomain) : $domain;

        $table = perfex_saas_table('companies');

        $this->db->group_start()
            ->where('slug', $slug)
            ->or_where('custom_domain', $domain)
            ->or_where('custom_domain', $fullDomain)
            ->group_end();

        $company = $this->db->get($table)->row();

        if (!$company) {
            return $this->_res(false, 'Company not found');
        }

        if (in_array($company->status, ['disabled', 'banned', 'pending-delete'])) {
            return $this->_res(false, 'Company is not active. Status: ' . $company->status);
        }

        $domainName = $company->custom_domain ?: ($company->slug . '.' . $baseDomain);

        return $this->_res(true, 'Company verified', [
            'id' => $company->id,
            'name' => $company->name,
            'slug' => $company->slug,
            'status' => $company->status,
            'domain' => $domainName,
            'base_url' => 'https://' . $domainName,
        ]);
    }
private function _res($success, $message, $data = [], $code = 200)
{
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data
    ]);
    exit;
}

    // ==================== LOGIN ====================
public function login_post()
{
    log_message('error', 'LOGIN API HIT');

    if (1 != get_option('allow_flutex_admin_login')) {
        log_message('error', 'Login API disabled');
        $this->response(['message' => _l('login_not_enabled_using_api')], RestController::HTTP_OK);
    }

    $requiredData = [
        'email'    => '',
        'password' => '',
        'type'     => 'staff',
    ];

    $postData = array_merge($requiredData, $this->post());

    log_message('error', 'Login Request: ' . json_encode($postData));

    $type = strtolower($postData['type']);

    $this->load->library('form_validation');
    $this->form_validation->set_data($postData);

    $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
    $this->form_validation->set_rules('password', 'Password', 'required');

    if (!$this->form_validation->run()) {
        log_message('error', 'Validation Failed: ' . validation_errors());
        return $this->response([
            'message' => strip_tags(validation_errors())
        ], 400);
    }

    try {

        log_message('error', 'Login Type: ' . $type);

        // =========================
        // 🔵 STAFF LOGIN
        // =========================
        if ($type === 'staff') {

            log_message('error', 'Staff Login Attempt: ' . $postData['email']);

            $this->load->model('Authentication_model');
            log_message('error', 'Before Staff Authentication');
            $success = $this->Authentication_model->login(
                $postData['email'],
                $postData['password'],
                true,
                true
            );
            log_message('error', 'After Staff Authentication: ' . json_encode($success));
            if (is_array($success) && isset($success['memberinactive'])) {
                log_message('error', 'Staff inactive: ' . $postData['email']);
                return $this->response(['message' => _l('admin_auth_inactive_account')], 403);
            } elseif ($success === false) {
                log_message('error', 'Staff login failed: ' . $postData['email']);
                return $this->response(['message' => _l('admin_auth_invalid_email_or_password')], 401);
            }

            $staff = $this->db
                ->where('email', $postData['email'])
                ->get(db_prefix().'staff')
                ->row();

            log_message('error', 'Staff Found ID: ' . $staff->staffid);

            $data = [
                'staff_id' => $staff->staffid,
                'staff_email' => $staff->email,
                'type' => 'staff',
                'API_TIME' => time(),
            ];
            log_message('error', 'Before Token Generation');

            $token = $this->authorization_token->generateToken($data);

            log_message('error', 'Staff Token Generated: ' . $token);

            $this->db->update(db_prefix().'staff', [
                'flutex_api_key' => $token
            ], [
                'staffid' => $staff->staffid
            ]);

            log_message('error', 'Before Success Response');

            return $this->response([
                'message' => 'Staff login successful',
                'data' => [
                    'id' => $staff->staffid,
                    'type' => 'staff',
                    'token' => $token
                ]
            ], 200);
        }

        // =========================
        // 🟢 CLIENT LOGIN
        // =========================
            // =========================
        // 🟢 CLIENT LOGIN (CORRECT)
        // =========================
    if ($type === 'client') {

        log_message('error', 'Client Login Attempt: ' . $postData['email']);

        $this->load->model('Authentication_model');

        $success = $this->Authentication_model->login(
            $postData['email'],
            $postData['password'],
            false, // remember
            false  // staff = false → client login
        );

        if (is_array($success) && isset($success['memberinactive'])) {
            log_message('error', 'Client inactive');
            return $this->response(['message' => 'Inactive account'], 403);
        } elseif ($success === false) {
            log_message('error', 'Client login failed');
            return $this->response(['message' => 'Invalid email or password'], 401);
        }

        // ✅ Now get logged-in contact
        $contact_id = get_contact_user_id();

        $contact = $this->db
            ->where('id', $contact_id)
            ->get(db_prefix().'contacts')
            ->row();

        $client = $this->db
            ->where('userid', $contact->userid)
            ->get(db_prefix().'clients')
            ->row();

        log_message('error', 'Client Login Success ID: ' . $client->userid);

        // Token
        $data = [
            'client_id'  => $client->userid,
            'contact_id' => $contact->id,
            'type'       => 'client',
            'API_TIME'   => time(),
        ];

        $token = $this->authorization_token->generateToken($data);

        $this->db->update(db_prefix().'clients', [
            'flutex_api_key' => $token
        ], [
            'userid' => $client->userid
        ]);

                return $this->response([
                    'message' => 'Client login successful',
                    'data' => [
                        'client_id'  => $client->userid,
                        'contact_id' => $contact->id,
                        'type'       => 'client',
                        'token'      => $token
                    ]
                ], 200);
            }

            log_message('error', 'Invalid login type: ' . $type);

            return $this->response([
                'message' => 'Invalid login type'
            ], 400);

        } catch (\Throwable $th) {

    log_message('error', 'Login Exception: ' . $th->getMessage());

    log_message('error', 'File: ' . $th->getFile());

    log_message('error', 'Line: ' . $th->getLine());

    return $this->response([
        'message' => $th->getMessage()
    ], 500);
}
    }
    public function login1_post()
    {
        if (1 != get_option('allow_flutex_admin_login')) {
            $this->response(['message' => _l('login_not_enabled_using_api')], RestController::HTTP_OK);
        }

        $requiredData = [
            'email'    => '',
            'password' => '',
        ];

        $postData = $this->post();
        $postData = array_merge($requiredData, $postData);

        $this->load->library('form_validation');

        $this->form_validation->set_data($postData);

        $this->form_validation->set_rules('email', _l('admin_auth_login_email'), 'trim|required|valid_email');
        $this->form_validation->set_rules('password', _l('admin_auth_login_password'), 'required');

        if (!$this->form_validation->run()) {
            $this->response(['message' => strip_tags(validation_errors())], RestController::HTTP_BAD_REQUEST);
        }

        try {
            $this->load->model('Authentication_model');

            $success = $this->Authentication_model->login($postData['email'], $postData['password'], true, true);
    
            if (is_array($success) && isset($success['memberinactive'])) {
                $this->response(['message' => _l('admin_auth_inactive_account')], RestController::HTTP_FORBIDDEN);
            } elseif (false == $success) {
                $this->response(['message' => _l('admin_auth_invalid_email_or_password')], RestController::HTTP_UNAUTHORIZED);
            }
    
            $table = db_prefix().'staff';
    
            $this->db->where('email', $postData['email']);
            $staff = $this->db->get($table)->row();
    
            $data = [
                'staff_id'         => $staff->staffid, // Staff ID
                'staff_email'      => $staff->email,   // Staff Email
                'staff_logged_in'  => true,
                'API_TIME'         => time(),
            ];
            
            $token         = $this->authorization_token->generateToken($data);
            $data['token'] = $token;
    
            $this->db->update(db_prefix() . 'staff', ['flutex_api_key' => $token], ['staffid' => $staff->staffid]);
    
            $this->response(['message' => _l('logged_in_successfully'), 'data' => $data], RestController::HTTP_OK);
            
        } catch (\Throwable $th) {
            $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
        }
    }
    
    public function forgotPassword_post()
    {
        try {
            $this->form_validation->set_rules('email', _l('admin_auth_login_email'), 'required|valid_email|callback_email_exists');
            
            if (!$this->form_validation->run()) {
                $this->response(['message' => strip_tags(validation_errors()),'error' => $this->form_validation->error_array()], RestController::HTTP_BAD_REQUEST);
            } else {
                $email = $this->input->post('email');
                
                $this->load->model('Authentication_model');
                $success = $this->Authentication_model->forgot_password($email, true);
                
                if (is_array($success) && isset($success['memberinactive'])) {
                    $this->response(['message' => _l('inactive_account')], RestController::HTTP_FORBIDDEN);
                } elseif ($success) {
                    $this->response(['message' => _l('check_email_for_resetting_password')], RestController::HTTP_OK);
                } else {
                    $this->response(['message' => _l('error_setting_new_password_key')], RestController::HTTP_INTERNAL_ERROR);
                }
            }
        } catch (\Throwable $th) {
            $this->response(['message' => _l('something_went_wrong')], RestController::HTTP_INTERNAL_ERROR);
        }
    }

    public function email_exists($email)
    {
        $total_rows = total_rows(db_prefix() . 'staff', [
            'email' => $email,
        ]);
        if ($total_rows == 0) {
            $this->form_validation->set_message('email_exists', _l('auth_reset_pass_email_not_found'));

            return false;
        }

        return true;
    }
}
