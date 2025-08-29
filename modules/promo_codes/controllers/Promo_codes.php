<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Promo Codes Controller
 *
 * Handles promo code management for the admin panel.
 */
class Promo_codes extends AdminController
{
    /**
     * Constructor
     *
     * Loads the necessary model and libraries.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->library('form_validation');
        $this->load->model(PROMO_CODES_MODULE_NAME . '/promo_codes_model');
        $this->load->library(PROMO_CODES_MODULE_NAME . '/promo_codes_service');
    }

    /**
     * Promo Codes Index
     *
     * Displays the list of all promo codes.
     */
    public function index()
    {
        if (!staff_can('view', PROMO_CODES_MODULE_NAME)) {

            return access_denied(PROMO_CODES_MODULE_NAME);
        }

        // Load all promo codes
        $data['promo_codes'] = $this->promo_codes_model->get_all_codes();

        // Set the page title
        $data['title'] = lang('promo_codes_table_heading');

        // Load the view with data
        $this->load->view('manage', $data);
    }

    /**
     * Table View
     *
     * Loads table data using Perfex CRM's helper.
     */
    public function table()
    {
        if (!staff_can('view', PROMO_CODES_MODULE_NAME)) {

            return access_denied(PROMO_CODES_MODULE_NAME);
        }

        $this->app->get_table_data(module_views_path(PROMO_CODES_MODULE_NAME, 'table'));
    }

    /**
     * View Promo Code
     *
     * Displays detailed usage information for a single promo code.
     *
     * @param int $id Promo code ID
     */
    public function view($id)
    {
        if (!staff_can('view', PROMO_CODES_MODULE_NAME)) {

            return access_denied(PROMO_CODES_MODULE_NAME);
        }

        // Ensure only admin can view details
        if (!is_admin()) {
            access_denied('Promo Codes');
        }

        // Fetch promo code by ID
        $data['code'] = $this->promo_codes_model->get($id);

        // Show warning if not found
        if (!$data['code']) {
            set_alert('warning', _l('not_found'));
            redirect(admin_url('promo_codes'));
        }

        // Get usage statistics for the promo code
        $data['usage'] = $this->promo_codes_model->get_usage($id);

        // Set page title and load view
        $data['title'] = _l('promo_codes_view');
        $this->load->view('view', $data);
    }

    /**
     * Genearate and redirect to a sale view url as customer
     *
     * @param string $sales_object_type
     * @param string|int $sales_object_id
     * @return void
     */
    public function view_sales($sales_object_type, $sales_object_id)
    {
        try {
            $sales_object = $this->promo_codes_service->getSalesObject($sales_object_type, $sales_object_id);
            if (!$sales_object) {
                throw new \Exception(_l('promo_codes_sales_object_not_found'), 1);
            }

            $view_url_segment = [$sales_object_type, $sales_object_id, $sales_object->hash];
            if ($sales_object_type == 'subscription')
                unset($view_url_segment[1]);

            $view_url =  base_url(implode('/', $view_url_segment));
            redirect($view_url);
        } catch (\Throwable $th) {
            set_alert('warning', $th->getMessage());
            redirect(admin_url('promo_codes'));
        }
    }

    /**
     * Create Promo Code
     *
     * Handles form display, validation, and processing for creating a new promo code.
     *
     * @return void
     */
    public function create()
    {
        if (!staff_can('create', PROMO_CODES_MODULE_NAME)) {

            return access_denied(PROMO_CODES_MODULE_NAME);
        }

        $this->set_promo_code_form_validation_rules();

        if ($this->form_validation->run() == false) {
            $data['title'] = lang('promo_codes_create_heading');
            $data['sales_object_dropdown_options'] =  $this->promo_codes_service->getSalesObjectsDropdown();
            $this->load->view('form', $data);
        } else {
            $promo_code_data = $this->get_promo_code_post_data();

            try {
                if ($id = $this->promo_codes_model->create($promo_code_data)) {

                    // Get promo code
                    $this->sync_with_subscription($id);

                    set_alert('success', lang('promo_codes_successfully_created'));
                    redirect('admin/promo_codes');
                } else {
                    set_alert('danger', lang('promo_codes_error_creating'));
                    redirect('admin/promo_codes/create');
                }
            } catch (\Throwable $th) {
                set_alert('danger', $th->getMessage());
                redirect('admin/promo_codes');
            }
        }
    }

    /**
     * Edit Promo Code
     *
     * Loads existing promo code by ID, handles form display, validation,
     * and updates the record upon successful validation.
     *
     * @param int $id Promo code ID
     * @return void
     */
    public function edit($id)
    {
        if (!staff_can('edit', PROMO_CODES_MODULE_NAME)) {

            return access_denied(PROMO_CODES_MODULE_NAME);
        }

        $this->set_promo_code_form_validation_rules();

        $promo_code = $this->promo_codes_model->get($id);

        if ($this->form_validation->run() == false) {
            $data['promo_code'] = $promo_code;
            $data['title']      = lang('promo_codes_edit_heading');
            $data['sales_object_dropdown_options'] =  $this->promo_codes_service->getSalesObjectsDropdown();
            $this->load->view('form', $data);
        } else {

            $promo_code_data = $this->get_promo_code_post_data();
            $promo_code_data['metadata'] = array_merge($promo_code->metadata, $promo_code_data['metadata']);

            try {
                if ($this->promo_codes_model->update($id, $promo_code_data)) {

                    $this->sync_with_subscription($id);

                    set_alert('success', lang('promo_codes_successfully_updated'));
                    redirect('admin/promo_codes');
                } else {
                    set_alert('danger', lang('promo_codes_error_updating'));
                    redirect('admin/promo_codes/edit/' . $id);
                }
            } catch (\Throwable $th) {
                set_alert('danger', $th->getMessage());
                redirect('admin/promo_codes/edit/' . $id);
            }
        }
    }


    /**
     * Delete Promo Code
     *
     * Deletes a promo code from the database.
     *
     * @param int $id Promo code ID
     */
    public function delete($id)
    {
        if (!staff_can('delete', PROMO_CODES_MODULE_NAME)) {

            return access_denied(PROMO_CODES_MODULE_NAME);
        }

        // Get promo code
        $promo_code = $this->promo_codes_model->get($id);

        try {
            // Attempt to delete promo code
            if ($promo_code && $this->promo_codes_model->delete($id)) {
                $this->sync_with_subscription($promo_code, 'delete');
                set_alert('success', lang('promo_codes_successfully_deleted'));
            } else {
                set_alert('danger', lang('promo_codes_error_deleting'));
            }
        } catch (\Throwable $th) {
            set_alert('danger', $th->getMessage());
        }

        // Redirect to promo codes list
        redirect('admin/promo_codes');
    }

    /**
     * Toggle Status
     *
     * Toggles a promo code's status between active and inactive.
     *
     * @param int $id Promo code ID
     */
    public function toggle_status($id)
    {
        if (!staff_can('edit', PROMO_CODES_MODULE_NAME)) {

            return access_denied(PROMO_CODES_MODULE_NAME);
        }

        // Get promo code
        $promo_code = $this->promo_codes_model->get($id);

        // If found, toggle status
        if ($promo_code) {
            $new_status = ($promo_code->status == 'active') ? 'inactive' : 'active';

            try {
                // Update status
                $this->promo_codes_model->update($id, ['status' => $new_status]);

                $this->sync_with_subscription($id, $new_status);

                set_alert('success', lang('promo_codes_status_updated'));
            } catch (\Throwable $th) {

                set_alert('danger', $th->getMessage());
            }
        } else {
            // Promo code not found
            set_alert('danger', lang('promo_codes_promo_code_not_found'));
        }

        // Redirect back to promo codes list
        redirect('admin/promo_codes');
    }

    /**
     * Set validation rules for promo code create/edit forms.
     *
     * Defines required fields and validation constraints for form submission.
     *
     * @return void
     */
    private function set_promo_code_form_validation_rules()
    {
        $this->form_validation->set_rules('code', lang('promo_codes_code'), 'required|min_length[3]');
        $this->form_validation->set_rules('type', lang('promo_codes_type'), 'required');
        $this->form_validation->set_rules('amount', lang('promo_codes_value'), 'required|numeric');
        $this->form_validation->set_rules('usage_limit', lang('promo_codes_usage_limit'), 'numeric');
        $this->form_validation->set_rules('start_date', lang('promo_codes_start_date'), 'required');
        $this->form_validation->set_rules('end_date', lang('promo_codes_end_date'), 'required');
    }

    /**
     * Retrieve and sanitize promo code form POST data.
     *
     * Gathers POST input, applies type casting and trimming where appropriate.
     *
     * @return array
     */
    private function get_promo_code_post_data(): array
    {
        return [
            'code'         => trim($this->input->post('code')),
            'type'         => $this->input->post('type'),
            'amount'       => (float)$this->input->post('amount'),
            'usage_limit'  => (int)$this->input->post('usage_limit'),
            'start_date'   => $this->input->post('start_date'),
            'end_date'     => $this->input->post('end_date'),
            'metadata'     => $this->input->post('metadata') ?? []
        ];
    }

    /**
     * Sync local promo code with stripe.
     *
     * @param mixed $promo_code
     * @param string $action
     * @return bool
     */
    private function sync_with_subscription($promo_code, $action = 'updateOrCreate')
    {
        try {

            if (!is_object($promo_code))
                $promo_code = $this->promo_codes_model->get((int)$promo_code);

            return $this->promo_codes_service->syncPromoCodeWithStripe($promo_code, $action);
        } catch (\Throwable $th) {

            log_activity('Stripe Coupon Sync Error: ' . $th->getMessage());
            throw new \Exception($th->getMessage(), 1);
        }
    }
}