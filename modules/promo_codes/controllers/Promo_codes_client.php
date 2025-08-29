<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Promo Codes Client Controller
 *
 * Handles promo code application for the client panel.
 */
class Promo_codes_client extends App_Controller
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

        if (!$this->input->is_ajax_request()) {
            show_404();
        }
    }

    /**
     * Apply a promo code to a sales object (invoice, estimate, subscription, etc.)
     *
     * This method validates input, attempts to apply the promo code via the service layer,
     * and returns a JSON response indicating success or failure.
     *
     * @return void Outputs a JSON response and exits.
     */
    public function apply()
    {
        $this->handle_promo_code_action(function ($data) {
            $this->promo_codes_service->validateAndApply($data['code'], $data['sales_object_id'], $data['sales_object_type']);

            return [
                'success' => true
            ];
        });
    }

    /**
     * Remove an applied promo code from a sales object.
     *
     * This method validates input, attempts to remove the promo code via the service layer,
     * and returns a JSON response indicating success or failure.
     *
     * @return void Outputs a JSON response and exits.
     */
    public function remove()
    {
        $this->handle_promo_code_action(function ($data) {
            $removed = $this->promo_codes_service->validateAndRemove($data['code'], $data['sales_object_id'], $data['sales_object_type']);

            if (!$removed) {
                throw new \Exception(_l('promo_codes_failed_to_remove_code'), 1);
            }

            return [
                'success' => true,
                'message' => _l('promo_codes_code_removed_successfully')
            ];
        });
    }

    /**
     * Validate promo code (AJAX)
     * @return void Outputs a JSON response and exits.
     */
    public function validate()
    {
        $this->handle_promo_code_action(function ($data) {
            $promo_code = $this->promo_codes_model->get_by_code($data['code']);
            $sales_object       = $this->promo_codes_service->getSalesObject($data['sales_object_type'], $data['sales_object_id']);
            $this->promo_codes_service->validate($promo_code, $sales_object, $data['sales_object_type']);
            return [
                'success' => true,
                'html' => '<span class="badge badge-info">' . html_escape($promo_code->code) . '</span>' .
                    '<span> - </span><span>' .
                    ($promo_code->type === 'percentage' ? $promo_code->amount . '%' : app_format_money($promo_code->amount, get_base_currency())) .
                    '</span>',
                'promo_code' => $promo_code
            ];
        });
    }

    /**
     * Get the subscription discounts (AJAX)
     */
    public function get_subscription_discounts($subscription_id)
    {
        try {
            if (!is_numeric($subscription_id)) {
                throw new \Exception(_l('promo_codes_invalid_subscription_id'), 1);
            }

            $this->load->model('subscriptions_model');
            $this->load->model('promo_codes_model');
            $this->load->library('stripe_subscriptions');
            $this->load->library(PROMO_CODES_MODULE_NAME . '/promo_codes_stripe_subscriptions');

            $subscription = $this->subscriptions_model->get_by_id($subscription_id);

            if (!$subscription) {
                throw new \Exception(_l('promo_codes_subscription_not_found'), 1);
            }

            $logs = $this->promo_codes_model->get_usage_by_relationship($subscription_id, 'subscription');

            $discounts = [];

            $stripe_coupon_id = null;

            if (!empty($subscription->stripe_subscription_id)) {
                $stripeSub = $this->promo_codes_stripe_subscriptions->get_cached_stripe_subscription($subscription->stripe_subscription_id);

                if (!empty($stripeSub->discount) && !empty($stripeSub->discount->coupon)) {
                    $stripe_coupon_id = $stripeSub->discount->coupon->id;
                }
            }

            if (isset($stripeSub) && $stripeSub->discount) {
                foreach ($logs as $log) {
                    $promo = $this->promo_codes_model->get($log['promo_code_id']);
                    if ($promo) {
                        if ($stripe_coupon_id && $promo->metadata['stripe_coupon_id'] != $stripe_coupon_id) {
                            continue;
                        }

                        $discounts[] = [
                            'code'   => $promo->code,
                            'amount' => ($promo->type == 'percentage' ? $promo->amount . '%' : app_format_money($promo->amount, $subscription->currency_name)),
                            'duration' => $stripeSub->discount->coupon->duration
                        ];
                    }
                }
            }

            return $this->json_response(['success' => true, 'discounts' => $discounts]);
        } catch (\Throwable $th) {
            return $this->json_response(['success' => false, 'message' => $th->getMessage()]);
        }
    }



    /**
     * Handle a promo code action request (apply or remove).
     *
     * This method validates input, processes the request via a given callback, and handles
     * JSON response generation and output. It also checks for user authentication and
     * manages exceptions.
     *
     * @param callable $action The action-specific callback to execute after validation.
     * @return void Outputs a JSON response and exits.
     */
    private function handle_promo_code_action(callable $action)
    {
        try {

            if (!is_logged_in() && get_option('promo_codes_allow_guest') != '1') {
                throw new \Exception(_l('promo_codes_access_denied_login'), 1);
            }

            $this->set_promo_code_validation_rules();

            if ($this->form_validation->run() == false) {
                throw new \Exception(validation_errors('<p>', '</p>'), 1);
            }

            $data = [
                'code'               => trim($this->input->post('code')),
                'sales_object_id'    => (int) $this->input->post('sales_object_id'),
                'sales_object_type'  => $this->input->post('sales_object_type') ?? 'invoice'
            ];

            $response = call_user_func($action, $data);
        } catch (\Throwable $th) {
            $response = [
                'success' => false,
                'message' => $th->getMessage(),
                'redirect' => !is_logged_in() ? base_url('login') : ''
            ];
        }

        return $this->json_response($response);
    }

    /**
     * Set validation rules for promo code actions.
     *
     * This method defines the form validation rules for applying or removing a promo code.
     *
     * @return void
     */
    private function set_promo_code_validation_rules()
    {
        $this->form_validation->set_rules('code', lang('promo_codes_code'), 'required|min_length[3]');
        $this->form_validation->set_rules('sales_object_id', lang('promo_codes_sales_object_id'), 'required');
        $this->form_validation->set_rules('sales_object_type', lang('promo_codes_sales_object_type'), 'required');
    }

    /**
     * Output json
     *
     * @param array $response
     * @return void
     */
    private function json_response(array $response)
    {
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response);
        exit;
    }
}