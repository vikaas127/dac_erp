<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Flexstage_model extends App_Model
{
    protected $table = 'flexevents';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return array|array[]
     * get all events
     */
    public function get_events()
    {
        $this->db->select('*');
        $this->db->from(db_prefix() . $this->table);
        $query = $this->db->get();
        return $query->result_array();
    }

    /**
     * @param $id
     * @return array
     * get event by id
     */
    public function get_event($id)
    {
        $this->db->select('*');
        $this->db->from(db_prefix() . $this->table);
        $this->db->where('id', $id);
        $this->db->or_where('slug', $id);
        $query = $this->db->get();
        return $query->row_array();
    }

    /**
     * @param $data
     * @return bool
     * add event
     */
    public function add_event($data)
    {
        $data['start_date'] = to_sql_date($data['start_date'],true);
        $data['end_date'] = to_sql_date($data['end_date'],true);
        $this->db->insert(db_prefix() . $this->table, $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New Event Added [ID:' . $insert_id . ', ' . $data['name'] . ']');
            return $insert_id;
        }
        return false;
    }

    /**
     * @param $id
     * @param $data
     * @return bool
     * update event
     */
    public function update_event($id, $data)
    {
        $data['start_date'] = to_sql_date($data['start_date'],true);
        $data['end_date'] = to_sql_date($data['end_date'],true);
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . $this->table, $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Event Updated [ID:' . $id . ', ' . $data['name'] . ']');
            return true;
        }
        return false;
    }

    /**
     * @param $id
     * @return bool
     * delete event
     */
    public function delete_event($id)
    {
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . $this->table);
        if ($this->db->affected_rows() > 0) {
            log_activity('Event Deleted [ID:' . $id . ']');
            return true;
        }
        return false;
    }

    public function delete_by_category($category_id)
    {
        $this->db->where('category_id', $category_id);
        $this->db->delete(db_prefix() . $this->table);
        if ($this->db->affected_rows() > 0) {
            log_activity('Event Deleted [ID:' . $category_id . ']');
            return true;
        }
        return false;
    }

    public function change_status($id, $status)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . $this->table, [
            'status' => $status,
        ]);

        log_activity('Event Status Changed [ID: ' . $id . ' - Publish: ' . $status . ']');
    }
    
    public function change_auto_sync_attendees($id, $status)
    {
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . $this->table, [
            'auto_sync_attendees' => $status,
        ]);

        log_activity('Event Auto Sync Attendee Changed [ID: ' . $id . ' - Auto Sync Attendee: ' . $status . ']');
    }
}
