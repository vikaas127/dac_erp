<?php


defined('BASEPATH') or exit('No direct script access allowed');

class Flexstage_event_invitation extends App_mail_template
{
    protected $for = 'flexstage';

    protected $email;
    protected $event_name;
    protected $event_date_time;
    protected $event_venue;
    protected $event_link;
    protected $event_id;

    public $slug = 'flexstage-event-invitation';

    public $rel_type = 'flexstage';

    public function __construct($email, $event_name, $event_date_time, $event_venue, $event_link, $event_id)
    {
        parent::__construct();

        $this->email = $email;
        $this->event_name = $event_name;
        $this->event_date_time = $event_date_time;
        $this->event_venue = $event_venue;
        $this->event_link = $event_link;
        $this->event_id = $event_id;
    }

    public function build()
    {
        $data = [
            'event_name' => $this->event_name,
            'event_date_time' => $this->event_date_time,
            'event_venue' => $this->event_venue,
            'event_link' => $this->event_link,
        ];
        $this->set_merge_fields('flexstage_invitation_merge_fields', $data);
        $this->to($this->email)
            ->set_rel_id($this->event_id);
    }
}