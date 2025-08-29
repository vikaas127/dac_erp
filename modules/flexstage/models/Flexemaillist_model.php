<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Flexemaillist_model extends App_Model
{
    protected $table = 'flexemaillists';
    protected $custom_fields_table = 'fmlcustomfields';
    protected $custom_field_values_table = 'fmlcustomfieldvalues';
    protected $emails_table = 'flexlistemails';
    protected $invitations_send_log_table = 'flexinvitationsendlog';
    protected $invitations_email_send_cron_table = 'flexinvitationsemailsendcron';

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
        if (!empty($conditions)) {
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
            log_activity('New Event Ticket Added [ID:' . $insert_id . ', ' . $data['name'] . ']');
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
            log_activity('Event Ticket Updated [ID:' . $id . ', ' . $data['name'] . ']');
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
            log_activity('Event Ticket Deleted');
            return true;
        }
        return false;
    }

    /**
     * Get list custom fields added by staff
     *
     * @param mixed $list_id list id
     * @return array
     */
    public function get_list_custom_fields($list_id)
    {
        $this->db->where('listid', $list_id);

        return $this->db->get(db_prefix() . $this->custom_fields_table)->result_array();
    }

    /**
     * Get custom field values
     * @param array $conditions
     * @return mixed
     */
    public function get_email_custom_field_value($conditions = [])
    {
        $this->db->where($conditions);
        $row = $this->db->get(db_prefix() . $this->custom_field_values_table)->row();
        if ($row) {
            return $row->value;
        }

        return '';
    }

    /**
     * List data used in view
     * @param  mixed $id list id
     * @return mixed object
     */
    public function get_data_for_view_list($id)
    {
        $list = $this->get_mail_lists($id);
        $list_emails = $this->db->select('email,dateadded,emailid')->from(db_prefix() . $this->emails_table)->where('listid', $id)->get()->result_array();
        $list->emails = $list_emails;

        return $list;
    }

    /**
     * Get mail list/s
     * @param  mixed $id Optional
     * @return mixed     object if id is passed else array
     */
    public function get_mail_lists($id = '')
    {
        $this->db->select();
        $this->db->from(db_prefix() . $this->table);
        if (is_numeric($id)) {
            $this->db->where('listid', $id);

            return $this->db->get()->row();
        }
        $lists = $this->db->get()->result_array();

        return $lists;
    }

    /**
     * Add new mail list
     * @param array $data mail list data
     */
    public function add_mail_list($data)
    {
        $data['creator'] = get_staff_full_name(get_staff_user_id());
        $data['datecreated'] = date('Y-m-d H:i:s');
        if (isset($data['list_custom_fields_add'])) {
            $custom_fields = $data['list_custom_fields_add'];
            unset($data['list_custom_fields_add']);
        }
        $this->db->insert(db_prefix() . $this->table, $data);
        $listid = $this->db->insert_id();
        if (isset($custom_fields)) {
            foreach ($custom_fields as $field) {
                if (!empty($field)) {
                    $this->db->insert(db_prefix() . $this->custom_fields_table, [
                        'listid' => $listid,
                        'fieldname' => $field,
                        'fieldslug' => slug_it($data['name'] . '-' . $field),
                    ]);
                }
            }
        }
        log_activity('New Email List Added [ID: ' . $listid . ', ' . $data['name'] . ']');

        return $listid;
    }

    /**
     * Update mail list
     * @param  mixed $data mail list data
     * @param  mixed $id   list id
     * @return boolean
     */
    public function update_mail_list($data, $id)
    {
        if (isset($data['list_custom_fields_add'])) {
            foreach ($data['list_custom_fields_add'] as $field) {
                if (!empty($field)) {
                    $this->db->insert(db_prefix() . $this->custom_fields_table, [
                        'listid' => $id,
                        'fieldname' => $field,
                        'fieldslug' => slug_it($field),
                    ]);
                }
            }
            unset($data['list_custom_fields_add']);
        }
        if (isset($data['list_custom_fields_update'])) {
            foreach ($data['list_custom_fields_update'] as $key => $update_field) {
                $this->db->where('customfieldid', $key);
                $this->db->update(db_prefix() . $this->custom_fields_table, [
                    'fieldname' => $update_field,
                    'fieldslug' => slug_it($data['name'] . '-' . $update_field),
                ]);
            }
            unset($data['list_custom_fields_update']);
        }
        $this->db->where('listid', $id);
        $this->db->update(db_prefix() . $this->table, $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Mail List Updated [ID: ' . $id . ', ' . $data['name'] . ']');

            return true;
        }

        return false;
    }

    /**
     * Delete mail list and all connections
     * @param  mixed $id list id
     * @return boolean
     */
    public function delete_mail_list($id)
    {
        $affectedRows = 0;
        $this->db->where('listid', $id);
        $this->db->delete(db_prefix() . $this->custom_field_values_table);
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }
        $this->db->where('listid', $id);
        $this->db->delete(db_prefix() . $this->custom_fields_table);
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }
        $this->db->where('listid', $id);
        $this->db->delete(db_prefix() . $this->emails_table);
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }
        $this->db->where('listid', $id);
        $this->db->delete(db_prefix() . $this->table);
        if ($this->db->affected_rows() > 0) {
            $affectedRows++;
        }
        if ($affectedRows > 0) {
            log_activity('Mail List Deleted [ID: ' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * Add new email to mail list
     * @param array $data
     * @return mixed
     */
    public function add_email_to_list($data)
    {
        $exists = total_rows(db_prefix() . $this->emails_table, [
            'email' => $data['email'],
            'listid' => $data['listid'],
        ]);
        if ($exists > 0) {
            return [
                'success' => false,
                'duplicate' => true,
                'error_message' => _l('email_is_duplicate_mail_list'),
            ];
        }
        $dateadded = date('Y-m-d H:i:s');
        $this->db->insert(db_prefix() . $this->emails_table, [
            'listid' => $data['listid'],
            'email' => $data['email'],
            'dateadded' => $dateadded,
        ]);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            if (isset($data['customfields'])) {
                foreach ($data['customfields'] as $key => $val) {
                    $this->db->insert(db_prefix() . $this->custom_field_values_table, [
                        'listid' => $data['listid'],
                        'customfieldid' => $key,
                        'emailid' => $insert_id,
                        'value' => $val,
                    ]);
                }
            }
            log_activity('Email Added To Mail List [ID:' . $data['listid'] . ' - Email:' . $data['email'] . ']');

            return [
                'success' => true,
                'dateadded' => $dateadded,
                'email' => $data['email'],
                'emailid' => $insert_id,
                'message' => _l('email_added_to_mail_list_successfully'),
            ];
        }

        return [
            'success' => false,
        ];
    }

    /**
     * Remove email from mail list
     * @param  mixed $emailid email id (is unique)
     * @return mixed          array
     */
    public function remove_email_from_mail_list($emailid)
    {
        $this->db->where('emailid', $emailid);
        $this->db->delete(db_prefix() . $this->emails_table);
        if ($this->db->affected_rows() > 0) {
            $this->db->where('emailid', $emailid);
            $this->db->delete(db_prefix() . $this->custom_field_values_table);

            return [
                'success' => true,
                'message' => _l('flexstage_email_removed_from_list'),
            ];
        }

        return [
            'success' => false,
            'message' => _l('flexstage_email_remove_fail'),
        ];
    }

    /**
     * Get event send log
     * @param  mixed $event_id
     * @return array
     */
    public function get_event_send_log($event_id)
    {
        $this->db->where('eventid', $event_id);

        return $this->db->get(db_prefix() . $this->invitations_send_log_table)->result_array();
    }

    function get_send_log_count($event_id)
    {
        return total_rows(db_prefix() . $this->invitations_send_log_table, ['iscronfinished' => 0, 'eventid' => $event_id]);
    }

    /**
     * Add new event send log
     * @param mixed $event_id event_id
     * @param integer @iscronfinished always to 0
     * @param integer $lists array of lists which event has been send
     */
    public function init_invitation_send_log($event_id, $iscronfinished = 0, $lists = [])
    {
        $this->db->insert(db_prefix() . $this->invitations_send_log_table, [
            'date' => date('Y-m-d H:i:s'),
            'eventid' => $event_id,
            'total' => 0,
            'iscronfinished' => $iscronfinished,
            'send_to_mail_lists' => serialize($lists),
        ]);
        $log_id = $this->db->insert_id();
        log_activity('Invitation Email Lists Send Setup [ID: ' . $event_id . ', Lists: ' . implode(' ', $lists) . ']');

        return $log_id;
    }

    public function init_invitation_send_cron($data)
    {
        $this->db->insert(db_prefix() . $this->invitations_email_send_cron_table, $data);
    }

    public function get_invitation_send_cron_table()
    {
        return $this->invitations_email_send_cron_table;
    }

    public function get_invitation_send_log_table()
    {
        return $this->invitations_send_log_table;
    }

    /**
     * Get all emails from mail list
     * @param  mixed $id list id
     * @return array
     */
    public function get_mail_list_emails($id)
    {
        $this->db->select('email,emailid')->from(db_prefix() . $this->emails_table)->where('listid', $id);

        return $this->db->get()->result_array();
    }
}
