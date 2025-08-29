<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Promo Codes Model
 */
class Promo_codes_model extends App_Model
{
    const STATUS_ACTIVE = 'active';

    /**
     * Database table for discount codes
     * @var string
     */
    protected $table_promo_codes;

    /**
     * Database table for tracking usage
     * @var string
     */
    protected $table_promo_code_usage;

    /**
     * Constructor to initialize table names with db prefix
     */
    public function __construct()
    {
        parent::__construct();
        // Initialize table names with db prefix
        $this->table_promo_codes = db_prefix() . 'promo_codes';
        $this->table_promo_code_usage = db_prefix() . 'promo_codes_usage';
    }

    /**
     * Retrieve all discount codes
     *
     * @return array List of all promo code records
     */
    public function get_all_codes()
    {
        return $this->db->get($this->table_promo_codes)->result();
    }

    /**
     * Retrieve a specific discount code by ID
     *
     * @param int $id Promo code ID
     * @return object|null Promo code record or null if not found
     */
    public function get($id)
    {
        $code = $this->db->get_where($this->table_promo_codes, ['id' => $id])->row();
        return $this->parse($code);
    }

    /**
     * Format a promo code.
     *
     * @param mixed $code
     * @return mixed
     */
    public function parse($code)
    {
        if ($code) {
            $code->metadata = empty($code->metadata) ? [] : json_decode($code->metadata, true) ?? [];
        }

        return $code;
    }

    /**
     * Create a new discount code
     *
     * @param array $data Promo code data
     * @return bool True on success, false on failure
     */
    public function create($data)
    {
        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $data['metadata'] = json_encode($data['metadata']);
        }
        return $this->db->insert($this->table_promo_codes, $data);
    }

    /**
     * Update an existing discount code by ID
     *
     * @param int $id Promo code ID
     * @param array $data Data to update
     * @return bool True on success, false on failure
     */
    public function update($id, $data)
    {
        if (isset($data['metadata']) && is_array($data['metadata'])) {
            $data['metadata'] = json_encode($data['metadata']);
        }
        return $this->db->update($this->table_promo_codes, $data, ['id' => $id]);
    }

    /**
     * Delete a discount code by ID
     *
     * @param int $id Promo code ID
     * @return bool True on success, false on failure
     */
    public function delete($id)
    {
        return $this->db->delete($this->table_promo_codes, ['id' => $id]);
    }

    /**
     * Get a promo code record by its code string if active and within date range.
     *
     * @param string $code Promo code string
     * @param array|null $where Extra where clause
     * @return object|null Promo code object or null if not found
     */
    public function get_by_code(string $code, $where = null)
    {
        if ($where === null) {
            $where = [
                'status' => self::STATUS_ACTIVE,
                'start_date <=' => date('Y-m-d'),
                'end_date >='   => date('Y-m-d'),
            ];
        }

        if (!empty($where)) {
            $this->db->where($where);
        }

        $code = $this->db->where('code', $code)->get($this->table_promo_codes)
            ->row();

        return $this->parse($code);
    }

    /**
     * Log the usage of a promo code against a sales object and client.
     *
     * @param int $promo_id Promo code ID
     * @param int $rel_id Sales object ID (invoice, estimate, etc.)
     * @param string $rel_type The type of relation (e.g., 'invoices', 'estimates', 'subscriptions')
     * @param int $client_id Client ID
     * @param float $value The discount value amount
     * @param $currency The value amount currency
     * @return bool True if successfully logged
     */
    public function log_usage(
        int $promo_id,
        int $rel_id,
        string $rel_type,
        int $client_id,
        float $value,
        $currency
    ) {
        return $this->db->insert($this->table_promo_code_usage, [
            'promo_code_id'    => $promo_id,
            'rel_type'         => $rel_type,      // Type of sales object (invoice, estimate, etc.)
            'rel_id'           => $rel_id,        // Sales object ID (invoice, estimate, etc.)
            'user_id'          => $client_id,               // Client ID
            'value'            => $value,
            'currency'         => $currency,
            'used_at'          => date('Y-m-d H:i:s'),     // Timestamp of when the promo code was used
        ]);
    }

    /**
     * Delete the usage of a promo code against a sales object and client.
     *
     * @param int $promo_id Promo code ID
     * @param int $rel_id Sales object ID (invoice, estimate, etc.)
     * @param string $rel_type The type of relation (e.g., 'invoices', 'estimates', 'subscriptions')
     * @param int $client_id Client ID
     * @return bool True if successfully logged
     */
    public function delete_usage(int $promo_id, int $rel_id, string $rel_type, int $client_id)
    {
        return $this->db->delete($this->table_promo_code_usage, [
            'promo_code_id'    => $promo_id,
            'rel_type'         => $rel_type,      // Type of sales object (invoice, estimate, etc.)
            'rel_id'           => $rel_id,        // Sales object ID (invoice, estimate, etc.)
            'user_id'          => $client_id,               // Client ID
        ]);
    }

    /**
     * Retrieve usage history of a promo code.
     *
     * @param int $promo_code_id Promo code ID
     * @param array $where Additional conditions for filtering usage records (optional)
     * @return array List of usage records with joined client info
     */
    public function get_usage(int $promo_code_id, array $where = [])
    {
        $db_prefix = db_prefix();

        // Apply any additional filters if provided
        $this->db->where('promo_code_id', $promo_code_id);

        // Apply custom filters
        if (!empty($where)) {
            $this->db->where($where);
        }

        $this->db->select('*, ' . $db_prefix . 'currencies.id as currencyid, ' . $db_prefix . 'currencies.name as currency_name');
        $this->db->join($db_prefix . 'currencies', '' . $db_prefix . 'currencies.id = ' . $this->table_promo_code_usage . '.currency', 'left');

        // Join with clients table to get client information
        return $this->db->join($db_prefix . 'clients', $db_prefix . 'clients.userid=' . $this->table_promo_code_usage . '.user_id', 'inner')
            ->order_by('used_at', 'DESC')  // Sort by the most recent usage
            ->get($this->table_promo_code_usage)
            ->result();  // Retrieve all matching records
    }

    /**
     * Retrieve all promo codes that have been applied to a specific object (e.g., invoice, estimate, subscription).
     *
     * @param int $rel_id The ID of the object (invoice, estimate, subscription, etc.) to fetch applied promo codes for.
     * @param string $rel_type The type of the object (e.g., 'invoice', 'estimate', 'subscription').
     * @return array List of applied promo codes with fields: code, description, and used_at timestamp.
     */
    public function get_usage_by_relationship(int $rel_id, string $rel_type)
    {
        // Select the promo code information
        $this->db->select('pc.id as promo_code_id, pc.code, u.used_at, pc.type, pc.amount');
        $this->db->from($this->table_promo_code_usage . ' u');

        // Join with promo codes table to get promo details
        $this->db->join($this->table_promo_codes . ' pc', 'u.promo_code_id = pc.id');

        // Filter by object type and ID
        $this->db->where('u.rel_type', $rel_type);
        $this->db->where('u.rel_id', $rel_id);

        // Get the result as an array
        return $this->db->get()->result_array();
    }

    /**
     * Update the sales object record with the discount details.
     *
     * @param object $sales_object Sales object (e.g., invoice, estimate, etc.)
     * @param string $sales_object_table Sales object table (e.g., db_prefix() . 'invoices')
     * @param float $total The final total after applying the discount(s)
     * @param float $discount_total The total discount to apply (after all promo codes)
     * @param float $discount_percent The cumulative discount percentage (if applicable)
     * @param string $discount_type The type of tax relation (i.e., 'before_tax' or 'after_tax')
     * @return bool True if the update was successful
     */
    public function update_sales_object_discount(
        object $sales_object,
        string $sales_object_table,
        float $total,
        float $discount_total,
        float $discount_percent = 0,
        string $discount_type = 'before_tax'
    ) {
        // Update the sales object (invoice/estimate) with the new discount and total
        return $this->db->update($sales_object_table, [
            'discount_total'   => $discount_total,
            'discount_percent' => $discount_percent,
            'discount_type'    => $discount_type,
            'total'            => $total  // Update the final total after discounts are applied
        ], ['id' => $sales_object->id]);
    }

    /**
     * Retrieve all promo codes that have been applied to a specific sales object (e.g., invoice, estimate, subscription).
     *
     * @param int $sales_object_id The ID of the sales object (invoice, estimate, subscription, etc.) to fetch applied promo codes for.
     * @param string $sales_object_type The type of the sales object (e.g., 'invoice', 'estimate', 'subscription').
     * @return array List of applied promo codes with fields: code, description, and used_at timestamp.
     */
    public function get_sales_object_applied_codes(int $sales_object_id, string $sales_object_type)
    {
        return $this->get_usage_by_relationship($sales_object_id, $sales_object_type);
    }
}