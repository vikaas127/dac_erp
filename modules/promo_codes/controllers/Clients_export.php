<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Clients Export Controller
 *
 * Manage clients segment exporting.
 */
class Clients_export extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('clients_model');
        $this->load->model(PROMO_CODES_MODULE_NAME . '/promo_codes_model');
        $this->load->model(PROMO_CODES_MODULE_NAME . '/clients_export_model');
        $this->load->library(PROMO_CODES_MODULE_NAME . '/promo_codes_service');
    }

    /**
     * Show the export form
     *
     * @return void
     */
    public function index()
    {
        $data['title'] = _l('customer_filter_title');
        $this->load->view('clients_export', $data);
    }

    /**
     * Export customer based on the given filter from input
     *
     * @return void
     */
    public function export()
    {
        $redirt_url = admin_url('promo_codes/clients_export');

        if ($this->input->post()) {
            $filters = $this->input->post(null, true);

            // Store filters in session for reuse
            $this->session->set_userdata('customer_export_filters', $filters);

            // Sanitize inputs
            $filters = array_map(function ($value) {
                return is_string($value) ? $this->security->xss_clean($value) : $value;
            }, $filters);

            // Convert checkbox values to boolean
            $checkboxes = [
                'never_logged_in', 'logged_in_once', 'incomplete_profiles', 'no_payment',
                'without_documents', 'overdue_invoices', 'unpaid_invoices',
                'overdue_or_cancelled_subscriptions', 'active_subscriptions_only',
                'multiple_active_subscriptions', 'free_trial_subscriptions',
                'has_partial_payments', 'last_estimate_rejected',
                'expired_estimates', 'accepted_estimates_no_invoices', 'no_tickets'
            ];
            foreach ($checkboxes as $checkbox) {
                $filters[$checkbox] = isset($filters[$checkbox]) && $filters[$checkbox] === 'on';
            }

            // Validate numeric inputs
            $numeric_fields = [
                'new_customers_days', 'not_logged_in_since',
                'payments_over', 'payments_under', 'outstanding_balance_over'
            ];
            foreach ($numeric_fields as $field) {
                if (!empty($filters[$field]) && !is_numeric($filters[$field])) {
                    set_alert('danger', _l('customer_filter_invalid_input', $field));
                    redirect($redirt_url);
                }
                $filters[$field] = !empty($filters[$field]) ? (float)$filters[$field] : '';
            }

            // Validate date inputs
            $date_fields = ['registered_after', 'registered_before'];
            foreach ($date_fields as $field) {
                if (!empty($filters[$field]) && !strtotime($filters[$field])) {
                    set_alert('danger', _l('customer_filter_invalid_date', $field));
                    redirect($redirt_url);
                }
            }

            // Validate tickets_in_status
            if (!empty($filters['tickets_in_status'])) {
                $filters['tickets_in_status'] = $this->security->xss_clean($filters['tickets_in_status']);
            }

            // Get customers
            $customers = $this->clients_export_model->get_customers_with_conditions($filters);

            if (empty($customers)) {
                set_alert('warning', _l('customer_filter_no_results'));
                redirect($redirt_url);
            }

            // Generate CSV
            $filename = 'customers_export_' . date('Y-m-d_H-i-s') . '.csv';
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache');

            $fp = fopen('php://output', 'w');

            // CSV headers
            $headers = array_keys($customers[0]);
            foreach ($headers as $key => $value) {
                $trans = _l('customer_filter_' . $value);
                if ($trans  != 'customer_filter_' . $value) {
                    $headers[$key] = $trans;
                }
            }

            fputcsv($fp, $headers);

            // CSV data
            foreach ($customers as $customer) {
                fputcsv($fp, $customer);
            }

            fclose($fp);
            exit;
        }

        // If not POST, show the form
        redirect($redirt_url);
    }

    /**
     * Clears session filters for reset action.
     */
    public function reset_filters()
    {
        $this->session->unset_userdata('customer_export_filters');
        echo json_encode(['success' => true]);
    }
}