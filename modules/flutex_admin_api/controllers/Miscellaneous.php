<?php

defined('BASEPATH') || exit('No direct script access allowed');

require_once __DIR__.'/RestController.php';

use FlutexAdminApi\RestController;

class Miscellaneous extends RestController
{
    protected $staffInfo;

    public function __construct()
    {
        parent::__construct();
        register_language_files('flutex_admin_api');
        load_admin_language();
        
        $this->load->helper('flutex_admin_api');
        if (!isset(isAuthorized()['status'])) {
            $this->response(isAuthorized()['response'], isAuthorized()['response_code']);
        }

        $this->staffInfo = isAuthorized();
    }
    
    public function client_groups_get()
    {
        $this->load->model('client_groups_model');
		$client_groups = $this->client_groups_model->get_groups();
        
        if (!empty($client_groups)) {
            $this->response(['message' => _l('data_retrieved_successfully'), 'data' => $client_groups], RestController::HTTP_OK);
        } else {
            $this->response(['message' => _l('data_not_found')], RestController::HTTP_NOT_FOUND);
        }
    }
    
    public function payment_modes_get()
    {
        $this->load->model('payment_modes_model');
		$payment_modes = $this->payment_modes_model->get('', ['invoices_only !=' => 1]);
        
        if (!empty($payment_modes)) {
            $this->response(['message' => _l('data_retrieved_successfully'), 'data' => $payment_modes], RestController::HTTP_OK);
        } else {
            $this->response(['message' => _l('data_not_found')], RestController::HTTP_NOT_FOUND);
        }
    }
 public function staff_get()
    {
        $this->db->select('staffid, firstname, lastname, email');
        $this->db->from(db_prefix() . 'staff');
        $this->db->where('active', 1);
        $this->db->order_by('firstname', 'ASC');

        $staff = $this->db->get()->result_array();

        $response = [
            'status' => true,
            'message' => 'Staff list fetched successfully',
            'data' => $staff
        ];

        return $this->response($response, 200);
    }

    public function expense_categories_get()
    {
    	$this->load->model('expenses_model');
		$expense_categories = $this->expenses_model->get_category();
        
        if (!empty($expense_categories)) {
            $this->response(['message' => _l('data_retrieved_successfully'), 'data' => $expense_categories], RestController::HTTP_OK);
        } else {
            $this->response(['message' => _l('data_not_found')], RestController::HTTP_NOT_FOUND);
        }
    }
public function privacy_policy_get()
{
    $url = "https://techdotbit.com/privacy-policy/";

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);

    curl_close($ch);

    return $this->response([
        'status' => true,
        'message' => 'Privacy policy fetched successfully',
        'data' => $response
    ], 200);
}
    public function tax_data_get()
    {
    	$this->load->model('taxes_model');
		$tax_data = $this->taxes_model->get();
        
        if (!empty($tax_data)) {
            $this->response(['message' => _l('data_retrieved_successfully'), 'data' => $tax_data], RestController::HTTP_OK);
        } else {
            $this->response(['message' => _l('data_not_found')], RestController::HTTP_NOT_FOUND);
        }
    }

    public function leads_sources_get()
    {
    	$this->load->model('leads_model');
		$leads_sources = $this->leads_model->get_source();
        
        if (!empty($leads_sources)) {
            $this->response(['message' => _l('data_retrieved_successfully'), 'data' => $leads_sources], RestController::HTTP_OK);
        } else {
            $this->response(['message' => _l('data_not_found')], RestController::HTTP_NOT_FOUND);
        }
    }

    public function leads_statuses_get()
    {
    	$this->load->model('leads_model');
		$leads_statuses = $this->leads_model->get_status();
        
        if (!empty($leads_statuses)) {
            $this->response(['message' => _l('data_retrieved_successfully'), 'data' => $leads_statuses], RestController::HTTP_OK);
        } else {
            $this->response(['message' => _l('data_not_found')], RestController::HTTP_NOT_FOUND);
        }
    }

    public function proposal_statuses_get()
    {
    	$this->load->model('proposals_model');
		$proposal_statuses = $this->proposals_model->get_statuses();
        
        if (!empty($proposal_statuses)) {
            $this->response(['message' => _l('data_retrieved_successfully'), 'data' => $proposal_statuses], RestController::HTTP_OK);
        } else {
            $this->response(['message' => _l('data_not_found')], RestController::HTTP_NOT_FOUND);
        }
    }

    public function ticket_departments_get()
    {
    	$this->load->model('departments_model');
		$ticket_departments = $this->departments_model->get();
        
        if (!empty($ticket_departments)) {
            $this->response(['message' => _l('data_retrieved_successfully'), 'data' => $ticket_departments], RestController::HTTP_OK);
        } else {
            $this->response(['message' => _l('data_not_found')], RestController::HTTP_NOT_FOUND);
        }
    }

    public function ticket_priorities_get()
    {
    	$this->load->model('tickets_model');
		$ticket_priorities = $this->tickets_model->get_priority();
        
        if (!empty($ticket_priorities)) {
            $this->response(['message' => _l('data_retrieved_successfully'), 'data' => $ticket_priorities], RestController::HTTP_OK);
        } else {
            $this->response(['message' => _l('data_not_found')], RestController::HTTP_NOT_FOUND);
        }
    }

    public function ticket_services_get()
    {
        $this->load->model('tickets_model');
		$ticket_services = $this->tickets_model->get_service();
        
        if (!empty($ticket_services)) {
            $this->response(['message' => _l('data_retrieved_successfully'), 'data' => $ticket_services], RestController::HTTP_OK);
        } else {
            $this->response(['message' => _l('data_not_found')], RestController::HTTP_NOT_FOUND);
        }
    }

    public function currencies_get()
    {
        $this->load->model('currencies_model');
		$currencies = $this->currencies_model->get();
        
        if (!empty($currencies)) {
            $this->response(['message' => _l('data_retrieved_successfully'), 'data' => $currencies], RestController::HTTP_OK);
        } else {
            $this->response(['message' => _l('data_not_found')], RestController::HTTP_NOT_FOUND);
        }
    }

    public function countries_get()
    {
		$countries = get_all_countries();
        
        if (!empty($countries)) {
            $this->response(['message' => _l('data_retrieved_successfully'), 'data' => $countries], RestController::HTTP_OK);
        } else {
            $this->response(['message' => _l('data_not_found')], RestController::HTTP_NOT_FOUND);
        }
    }
}