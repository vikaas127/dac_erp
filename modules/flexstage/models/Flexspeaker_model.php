<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Flexspeaker_model extends App_Model
{
    protected $table = 'flexspeakers';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param array $conditions
     * @return array|array[]
     * get all models
     */
    public function all($conditions = [])
    {
        $this->db->select('*');
        $this->db->from(db_prefix() . $this->table);
        if(!empty($conditions)){
            $this->db->where($conditions);
        }
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
            log_activity('New Event Speaker Added [ID:' . $insert_id . ', ' . $data['name'] . ']');
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
            log_activity('Event Speaker Updated [ID:' . $id . ', ' . $data['name'] . ']');
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
            log_activity('Event Speaker Deleted');
            return true;
        }
        return false;
    }

    public function remove_image($speaker_id)
    {
        $this->db->where('id', $speaker_id);
        $speaker = $this->db->get(db_prefix() . $this->table)->row();
        if ($speaker) {
            if (!empty($speaker->image)) {
                $path = get_file_path('speakers') . $speaker->event_id . '/';
                $fullPath = $path . $speaker->image;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                    $fname     = pathinfo($fullPath, PATHINFO_FILENAME);
                    $fext      = pathinfo($fullPath, PATHINFO_EXTENSION);
                    $thumbPath = $path . $fname . '_thumb.' . $fext;

                    if (file_exists($thumbPath)) {
                        unlink($thumbPath);
                    }
                }
            }

            if (is_dir($path)) {
                // Check if no files left, so we can delete the folder also
                $image_files = list_files($path);
                if (count($image_files) == 0) {
                    delete_dir($path);
                }
            }

            return true;
        }

        return false;
    }
}
