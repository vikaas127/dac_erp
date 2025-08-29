<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Fleximage_model extends App_Model
{
    protected $table = 'fleximages';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param array $$conditions
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
     * @param $id
     * @return array
     * get model by id
     */
    public function get($id)
    {
        $this->db->select('*');
        $this->db->from(db_prefix() . $this->table);
        $this->db->where('id', $id);
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
            log_activity('New Event Category Added [ID:' . $insert_id . ', ' . $data['name'] . ']');
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
            log_activity('Event Category Updated [ID:' . $id . ', ' . $data['name'] . ']');
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
            log_activity('Event Category Deleted');
            return true;
        }
        return false;
    }

    public function remove_file($id)
    {
        $this->db->where('id', $id);
        $file = $this->db->get(db_prefix() . $this->table)->row();
        if ($file) {
            if (empty($file->external)) {
                $path = get_file_path() . $file->event_id . '/';
                $fullPath = $path . $file->file_name;
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

            $this->db->where('id', $id);
            $this->db->delete(db_prefix() . $this->table);

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
