<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Line_discounts extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get discount rate for a specific item
     */
    public function get_item_discount()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $item_id = $this->input->post('item_id');

        if (!$item_id) {
            echo json_encode(['discount_rate' => 0]);
            return;
        }

        $this->db->select('line_discount_rate');
        $this->db->where('id', $item_id);
        $item = $this->db->get(db_prefix() . 'itemable')->row();

        $discount_rate = 0;
        if ($item && isset($item->line_discount_rate)) {
            $discount_rate = $item->line_discount_rate;
        }

        echo json_encode(['discount_rate' => $discount_rate]);
    }


    /**
     * Get discount rate from order id for convert page
     */
    public function get_item_discount_from_order()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $proposal_id = $this->input->post('proposal_id');
        $order_id = $this->input->post('order_id');

        if (!$proposal_id || !$order_id)
        {
            echo json_encode(['discount_rate' => 0]);
            return;
        }

        $this->db->select('line_discount_rate');
        $this->db->where('item_order', $order_id);
        $this->db->where('rel_id', $proposal_id);
        $this->db->where('rel_type', 'proposal');
        $item = $this->db->get(db_prefix() . 'itemable')->row();

        $discount_rate = 0;
        if ($item && isset($item->line_discount_rate)) {
            $discount_rate = $item->line_discount_rate;
        }

        echo json_encode(['discount_rate' => $discount_rate]);


    }

}