<?php


defined('BASEPATH') or exit('No direct script access allowed');

class Flexstage_tickets_success extends App_mail_template
{
    protected $for = 'flexstage';

    protected $email;
    protected $event_name;
    protected $event_date_time;
    protected $event_venue;
    protected $ticket_details;
    protected $ticket_order_id;
    protected $files;

    public $slug = 'flexstage-tickets-success';

    public $rel_type = 'flexstage';

    public function __construct($email, $event_name, $event_date_time, $event_venue, $ticket_details, $ticket_order_id, $files)
    {
        parent::__construct();

        $this->email = $email;
        $this->event_name = $event_name;
        $this->event_date_time = $event_date_time;
        $this->event_venue = $event_venue;
        $this->ticket_details = $ticket_details;
        $this->ticket_order_id = $ticket_order_id;
        $this->files = $files;
    }

    public function build()
    {
        $data = [
            'event_name' => $this->event_name,
            'event_date_time' => $this->event_date_time,
            'event_venue' => $this->event_venue,
            'ticket_details' => $this->ticket_details,
        ];
        foreach($this->files as $file){
            $this->add_attachment($file);
        }
        $this->set_merge_fields('flexstage_merge_fields', $data);
        $this->to($this->email)
            ->set_rel_id($this->ticket_order_id);
    }
}