<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Promo_codes_service
{
    /**
     * @var CI_Controller
     */
    protected $CI;

    /**
     * Load necessary models upon initialization.
     */
    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model(PROMO_CODES_MODULE_NAME . '/promo_codes_model');
        $this->CI->load->model('invoices_model');
        $this->CI->load->model('clients_model');
    }

    /**
     * Retrieves the list of supported sales object types with optional filtering
     *
     * Applies a filter hook to allow modification of supported sales objects.
     *
     * @return array Array of supported sales object type identifiers
     */
    public function getSupportedSalesObjects()
    {
        $supported = ['proposal', 'estimate', 'invoice', 'subscription'];
        return hooks()->apply_filters('promo_codes_supported_sales_objects', $supported);
    }

    /**
     * Generates a dropdown options array for applicable sales objects
     * 
     * @param bool $include_all If to include all or should filtered for only enabled option in settings.
     * @return array[] Array of option arrays with 'id' and 'name' keys, formatted for dropdowns
     */
    public function getSalesObjectsDropdown($include_all = false)
    {
        $options = [];

        // Add options from supported sales objects
        foreach ($this->getSupportedSalesObjects() as $sales_object_type) {

            if ($include_all == false && !$this->canApplyCodeToSalesObject($sales_object_type)) continue;

            $options[] = ['id' => $sales_object_type, 'name' => _l('promo_codes_applicable_' . $sales_object_type)];
        }
        return $options;
    }

    
    public function canApplyCodeToSalesObject($sales_object_type)
    {
        // Get disabled types from settings and safely decode JSON
        $disabled = get_option('promo_codes_settings_disabled_sales_objects');
        $disabled = (array)json_decode($disabled ?? '');

        // Check if requested type is in disabled list
        if (in_array($sales_object_type, $disabled)) return false;

        return true;
    }

    
    public function getSalesObjectTable($sales_object_type)
    {
        $table = hooks()->apply_filters('promo_codes_sales_object_table', db_prefix() . $sales_object_type . 's');
        return $table;
    }

    
    public function getSalesObjectModel($sales_object_type)
    {
        $model = hooks()->apply_filters('promo_codes_sales_object_model', $sales_object_type);
        if (is_object($model)) {
            return $model;
        }

        // Load the correct model dynamically
        $sales_object_model = $sales_object_type . 's_model';
        $this->CI->load->model($sales_object_model);
        return $this->CI->{$sales_object_model};
    }

    
    public function getSalesObject($sales_object_type, $sales_object_id = '', $where = [])
    {
        if (isset($where['clientid']) && $sales_object_type == 'proposal') {
            $client_id = $where['clientid'];
            unset($where['clientid']);
            $where['rel_type'] = 'customer';
            $where['rel_id'] = $client_id;
        }


        // Get the model
        $model = $this->getSalesObjectModel($sales_object_type);

        // Deduce the model method for getting the object
        $model_method = $sales_object_type == 'subscription' ? 'get_by_id' : 'get';
        $model_method = hooks()->apply_filters('promo_codes_get_sales_object_model_method', $model_method);

        // Fetch object
        $sales_object = $model->{$model_method}($sales_object_id, $where);

        // Proposal custom case: attach rel_id as clientid for type customer
        if (isset($sales_object->rel_type) && isset($sales_object->rel_id)) {
            if ($sales_object->rel_type !== 'customer' || empty($sales_object->rel_id)) {
                throw new Exception(_l('promo_codes_sales_object_not_found'));
            }

            if (empty($sales_object->clientid)) {
                $sales_object->clientid = $sales_object->rel_id;
            }
        } // End proposal custom case.

        return $sales_object;
    }

    
    public function syncPromoCodeWithStripe($promo_code, $action = '')
    {
        $deleted = false;
        $new_stripe_coupon_id = '';

        $stripe_coupon_id = $promo_code->metadata['stripe_coupon_id'] ?? '';

        $this->CI->load->library('stripe_subscriptions');

        if (
            empty($stripe_coupon_id) &&
            !in_array('subscription', $promo_code->metadata['condition']['applicable_to'] ?? [])
        ) {
            // Promo code not supporting subscription sales type
            return true;
        }

        // Delete old coupon if existing
        if (!empty($stripe_coupon_id)) {
            try {
                $coupon = \Stripe\Coupon::retrieve($stripe_coupon_id);
                if ($coupon)
                    $coupon->delete();
            } catch (\Throwable $th) {
                $deleted = true;
            }
        }

        switch ($action) {
            case 'inactive':
                break;

            case 'delete':
                return $deleted;
                break;


            default:
                // Create or update
                $params = [
                    'name'            => $promo_code->code,
                    'duration'        => $promo_code->metadata['stripe_coupon']['duration'] ?? 'once', // 'forever' or 'once'
                    'max_redemptions' => $promo_code->usage_limit ?: null,
                    'redeem_by'       => !empty($promo_code->end_date) ? strtotime($promo_code->end_date) : null,
                ];

                if ($promo_code->type == 'percentage') {
                    $params['percent_off'] = (float) $promo_code->amount;
                } else {
                    $params['amount_off']   = (int) ($promo_code->amount * 100); // in cents
                    $params['currency']     = strtolower(get_base_currency()->name); // or your desired default currency
                }
                $params['metadata'] = ['local_promo_code_id' => $promo_code->id];

                $coupon = \Stripe\Coupon::create($params);
                $new_stripe_coupon_id = $coupon->id;
                break;
        }

        if (!$deleted && empty($new_stripe_coupon_id)) return true;

        // Save new Stripe coupon ID back to promo code
        $metadata = $promo_code->metadata;
        $metadata['stripe_coupon_id'] = $new_stripe_coupon_id;
        return $this->CI->promo_codes_model->update($promo_code->id, ['metadata' => $metadata]);
    }

    
    public function validateAndApply(string $code, int $sales_object_id, string $sales_object_type): bool
    {
        $sales_object       = $this->getSalesObject($sales_object_type, $sales_object_id);
        $promo = $this->CI->promo_codes_model->get_by_code($code);

        $this->validate($promo, $sales_object, $sales_object_type);
        return $this->apply($promo, $sales_object, $sales_object_type);
    }

    
    public function validateAndRemove(string $code, int $sales_object_id, string $sales_object_type): bool
    {
        $sales_object = $this->getSalesObject($sales_object_type, $sales_object_id);

        if (!$sales_object) {
            throw new Exception(_l('promo_codes_sales_object_not_found'));
        }

        $promo = $this->CI->promo_codes_model->get_by_code($code, []);
        if (!$promo) {
            throw new \Exception(_l('promo_codes_promo_code_not_found'), 1);
        }

        // Validate code has not already been used for this object
        $logs = $this->CI->promo_codes_model->get_usage($promo->id, ['rel_type' => $sales_object_type, 'rel_id' => $sales_object->id]);
        if (empty($logs)) {
            throw new Exception(_l('promo_codes_no_usage'));
        }

        return $this->remove($promo, $sales_object, $sales_object_type);
    }

   
    public function validate($promo, $sales_object, string $sales_object_type): void
    {
        if (!$promo || $this->isExpired($promo)) {
            throw new Exception(_l('promo_codes_expired_or_invalid'));
        }

        if (!$sales_object) {
            throw new Exception(_l('promo_codes_sales_object_not_found'));
        }

        $condition = $promo->metadata['condition'] ?? [];

        $applicable_sales_objects = array_filter((array)($condition['applicable_to'] ?? []));
        if (
            !$this->canApplyCodeToSalesObject($sales_object_type) ||
            !in_array($sales_object_type, $applicable_sales_objects)
        ) {
            throw new Exception(_l('promo_codes_sales_not_applicable'), 1);
        }

        // Extract relevant total and client id based on sales type
        $total     = $sales_object->total ?? 0;
        $client_id = $sales_object->clientid ?? null;

        if (!empty($condition['min_total']) && $total < (float)$condition['min_total']) {
            throw new Exception(_l('promo_codes_min_total_not_met', ['min_total' => $condition['min_total']]));
        }

        if (!empty($condition['max_total']) && $total > (float)$condition['max_total']) {
            throw new Exception(_l('promo_codes_max_total_exceeded', ['max_total' => $condition['max_total']]));
        }

        // Validate new customer condition (works for invoices only)
        if (!empty($condition['new_customers_only']) && $client_id) {
            foreach ($this->getSupportedSalesObjects() as $sales_object_type) {

                $first_sales_object_type = $this->getSalesObject($sales_object_type, '', ['clientid' => $client_id]);
                if ($first_sales_object_type && $first_sales_object_type->id != $sales_object->id) {
                    throw new Exception(_l('promo_codes_only_for_new_customers'));
                }
            }
        }

        if (!empty($condition['sales_without_discount_only']) && !empty((float)$sales_object->discount_total)) {
            throw new Exception(_l('promo_codes_sales_has_discount'), 1);
        }

        $should_check_usage = !empty($condition['sales_without_applied_code_only'])
            || get_option('promo_codes_disallow_multiple_code') == '1';
        if ($should_check_usage) {
            $usages = $this->CI->promo_codes_model->get_usage_by_relationship($sales_object->id, $sales_object_type);
            if (!empty($usages)) {
                throw new Exception(_l('promo_codes_sales_has_applied_code'), 1);
            }
        }

        // Validate client group
        if (!empty($promo->client_groups) && $client_id) {
            $client       = $this->CI->clients_model->get($client_id);
            $allowed_groups = explode(',', $promo->client_groups);
            if (!in_array($client->groupid, $allowed_groups)) {
                throw new Exception(_l('promo_codes_not_valid_for_your_group'));
            }
        }

        // Validate code has not already been used for this object
        $logs = $this->CI->promo_codes_model->get_usage($promo->id, ['rel_type' => $sales_object_type, 'rel_id' => $sales_object->id]);
        if (!empty($logs)) {
            throw new Exception(_l('promo_codes_promo_code_already_in_use'));
        }

        if (!empty($promo->usage_limit)) {
            $logs = $this->CI->promo_codes_model->get_usage($promo->id);
            if (count($logs) >= $promo->usage_limit) {
                throw new Exception(_l('promo_codes_usage_limit_reached'));
            }
        }
    }

    
    
    protected function apply($promo, $sales_object, string $sales_object_type): bool
    {
        $this->CI->db->trans_start();

        $subtotal      = (float)$sales_object->subtotal;
        $adjustment    = (float)$sales_object->adjustment;
        $total_tax     = (float)$sales_object->total_tax;
        $tax_relation  = $promo->metadata['tax_relation'] ?? 'after_tax';

        // Take the existing discount already applied to the sales object
        $total_discount = (float)$sales_object->discount_total;

        // Calculate new promo discount
        $new_discount_amount = $promo->type === 'percentage'
            ? round($subtotal * ((float)$promo->amount / 100), 2)
            : (float)$promo->amount;

        // Add new promo discount to total discount
        $total_discount += $new_discount_amount;

        // Cap discount to not exceed subtotal
        $total_discount = min($total_discount, $subtotal);

        // Calculate cumulative discount percent
        $total_discount_percent = ($subtotal > 0) ? round(($total_discount / $subtotal) * 100, 2) : 0;

        // Calculate new total based on tax relation
        if ($tax_relation === 'before_tax') {
            $total = $subtotal - $total_discount + $total_tax + $adjustment;
        } else {
            $total = $subtotal + $total_tax - $total_discount + $adjustment;
        }

        // Update sales object totals and discounts
        $updated = $this->CI->promo_codes_model->update_sales_object_discount(
            $sales_object,
            $this->getSalesObjectTable($sales_object_type),
            $total,
            $total_discount,
            $total_discount_percent,
            $tax_relation
        );

        if (!$updated) {
            $this->CI->db->trans_rollback();
            return false;
        }

        // Log new usage
        $logged = $this->CI->promo_codes_model->log_usage(
            $promo->id,
            $sales_object->id,
            $sales_object_type,
            $sales_object->clientid,
            $new_discount_amount,
            $sales_object->currency
        );

        if (!$logged) {
            $this->CI->db->trans_rollback();
            return false;
        }

        $this->CI->db->trans_complete();

        $status = $this->CI->db->trans_status();

        if ($sales_object_type == 'invoice') {
            update_invoice_status($sales_object->id, false, true);
        }

        return $status;
    }


   
    protected function remove($promo, $sales_object, string $sales_object_type): bool
    {
        $this->CI->db->trans_start();

        $subtotal     = (float)$sales_object->subtotal;
        $adjustment   = (float)$sales_object->adjustment;
        $total_tax    = (float)$sales_object->total_tax;
        $tax_relation = $promo->metadata['tax_relation'] ?? 'after_tax';

        // Calculate the discount amount from this promo
        $promo_discount_amount = $promo->type === 'percentage'
            ? round($subtotal * ((float)$promo->amount / 100), 2)
            : (float)$promo->amount;

        // Reduce from total discount
        $new_total_discount = max(0, (float)$sales_object->discount_total - $promo_discount_amount);

        // Recalculate discount percent
        $new_discount_percent = ($subtotal > 0) ? round(($new_total_discount / $subtotal) * 100, 2) : 0;

        // Recalculate total based on tax relation
        if ($tax_relation === 'before_tax') {
            $new_total = $subtotal - $new_total_discount + $total_tax + $adjustment;
        } else {
            $new_total = $subtotal + $total_tax - $new_total_discount + $adjustment;
        }

        // Update sales object with new totals
        $updated = $this->CI->promo_codes_model->update_sales_object_discount(
            $sales_object,
            $this->getSalesObjectTable($sales_object_type),
            $new_total,
            $new_total_discount,
            $new_discount_percent,
            $tax_relation
        );

        if (!$updated) {
            $this->CI->db->trans_rollback();
            return false;
        }

        // Remove promo code usage log
        $deleted = $this->CI->promo_codes_model->delete_usage(
            $promo->id,
            $sales_object->id,
            $sales_object_type,
            $sales_object->clientid,
        );

        if (!$deleted) {
            $this->CI->db->trans_rollback();
            return false;
        }

        $this->CI->db->trans_complete();
        $status = $this->CI->db->trans_status();

        if ($sales_object_type == 'invoice') {
            update_invoice_status($sales_object->id, false, true);
        }

        return $status;
    }


    
    protected function isExpired($promo): bool
    {
        $now = date('Y-m-d');
        return strtotime($promo->end_date) < strtotime($now);
    }
}