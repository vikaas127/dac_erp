<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Flexticketsale_model extends App_Model
{
    protected $table = 'flexticketsales';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param array $conditions
     * @return array|array[]
     * get all models
     */
    public function all($conditions = [], $sortings = [])
    {
        $this->db->select('*');
        $this->db->from(db_prefix() . $this->table);
        if (!empty($conditions)) {
            $this->db->where($conditions);
        }

        if (!empty($sortings)) {
            for ($i = 0; $i < count($sortings); $i++) {
                $this->db->order_by($sortings[$i]['field'], $sortings[$i]['order']);
            }
        }
        
        $query = $this->db->get();
        return $query->result_array();
    }

    public function all_paid(array $where, array $in)
    {
        $this->db->select('*');
        $this->db->from(db_prefix() . $this->table);
        $this->db->where($where);
        $this->db->where_in('ticketorderid', $in);
        
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * @param $conditions
     * @return array
     * get model by conditions
     */
    public function get($conditions)
    {
        $this->db->select('*');
        $this->db->from(db_prefix() . $this->table);
        $this->db->where($conditions);
        $query = $this->db->get();
        return $query->row_array();
    }

    /**
     * @param $data
     * @return bool
     * add model
     */
    public function add($data)
    {
        $this->db->insert(db_prefix() . $this->table, $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New Event Ticket Order Added [ID: ' . $insert_id . ']');
            return $insert_id;
        }
        return false;
    }

    /**
     * @param $id
     * @param $data
     * @return bool
     * update model
     */
    public function update($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . $this->table, $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Event Ticket Order Updated [ID: ' . $id . ']');
            return true;
        }
        return false;
    }

    /**
     * @param $conditions
     * @return bool
     * delete model
     */
    public function delete($conditions)
    {
        $this->db->where($conditions);
        $this->db->delete(db_prefix() . $this->table);
        if ($this->db->affected_rows() > 0) {
            log_activity('Event Ticket Order Deleted');
            return true;
        }
        return false;
    }

    /**
     * @param $conditions
     * @return array
     * get total quantity by conditions
     */
    public function get_total_quantity($conditions)
    {
        $this->db->select_sum('quantity');
        $this->db->where($conditions);
        $query = $this->db->get(db_prefix() . $this->table);
        return $query->row_array();
    }
}
