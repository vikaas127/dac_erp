<?php

class Invitations_module
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->model('flexstage/flexemaillist_model');
        $this->ci->load->model('flexstage/flexstage_model');
    }

    public function send($cronManuallyInvoked = false)
    {
        $last_flexstage_send_cron = get_option('last_flexstage_send_cron');
        if ($last_flexstage_send_cron == '' || (time() > ($last_flexstage_send_cron + 3600)) || $cronManuallyInvoked === true) {


            $invitation_send_cron_table = $this->ci->flexemaillist_model->get_invitation_send_cron_table();
            $invitation_send_log_table = $this->ci->flexemaillist_model->get_invitation_send_log_table();

            $found_emails = $this->ci->db->count_all_results(db_prefix() . $invitation_send_cron_table);
            if ($found_emails > 0) {
                $total_emails_per_cron = get_option('flexstage_send_emails_per_cron_run');
                // Initialize mail library
                $this->ci->email->initialize();
                $this->ci->load->library('email');

                // Get all invications send log where sending emails is not finished
                $this->ci->db->where('iscronfinished', 0);
                $unfinished_invitations_send_log = $this->ci->db->get(db_prefix() . $invitation_send_log_table)->result_array();

                foreach ($unfinished_invitations_send_log as $_invitation) {
                    $eventid = $_invitation['eventid'];
                    // Get event emails that has been not sent yet.
                    $this->ci->db->where('eventid', $eventid);
                    $this->ci->db->limit($total_emails_per_cron);
                    $emails = $this->ci->db->get(db_prefix() . $invitation_send_cron_table)->result_array();

                    $event = $this->ci->flexstage_model->get_event($eventid);

                    $total = $_invitation['total'];
                    foreach ($emails as $data) {
                        $template_name = 'Flexstage_event_invitation';
                        $date_time = flexstage_format_date($event['start_date']);
                        $venue = flexstage_format_venue($event['type'], $event['event_link'], $event['location']);
                        $event_link = flexstage_get_client_event_url($event['slug']);

                        $template = mail_template($template_name, "flexstage", $data['email'], $event['name'], $date_time, $venue, $event_link, $event['id']);

                        if ($template->send()) {
                            $total++;
                        }

                        $this->ci->db->where('id', $data['id']);
                        $this->ci->db->delete(db_prefix() . $invitation_send_cron_table);
                    }
                    // Update survey send log
                    $this->ci->db->where('id', $_invitation['id']);
                    $this->ci->db->update(db_prefix() . $invitation_send_log_table, [
                        'total' => $total,
                    ]);
                    // Check if all emails send
                    $this->ci->db->where('eventid', $eventid);
                    $found_emails = $this->ci->db->count_all_results(db_prefix() . $invitation_send_cron_table);
                    if ($found_emails == 0) {
                        // Update that survey send is finished
                        $this->ci->db->where('id', $_invitation['id']);
                        $this->ci->db->update(db_prefix() . $invitation_send_log_table, [
                            'iscronfinished' => 1,
                        ]);
                    }
                }
                update_option('last_flexstage_send_cron', time());
            }
        }
    }

    public function create_email_template()
    {
        $templateMessage = "Hi there! <br/><br/>You are hereby invited for {event_name}.<br/><br/> Venue: {venue} <br/><br/> Date/Time: {date_time} <br/><br/> You can register for the event here: {event_link}<br><br/>Regards.";
        create_email_template('Invitation - {event_name}', $templateMessage, 'staff', 'Flexstage Event Invitation', 'flexstage-event-invitation');
    }
}
