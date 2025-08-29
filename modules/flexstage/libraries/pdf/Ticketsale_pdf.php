<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(LIBSPATH . 'pdf/App_pdf.php');

class Ticketsale_pdf extends App_pdf
{
    protected $ticketsale;
    protected $ticket;
    protected $ticketorder;
    protected $event;

    public function __construct($ticketsale_id, $tag = '')
    {
        parent::__construct();

        if (!class_exists('flexstage/flexticketsale_model', false)) {
            $this->ci->load->model('flexstage/flexticketsale_model');
        }
        if (!class_exists('flexstage/flexticket_model', false)) {
            $this->ci->load->model('flexstage/flexticket_model');
        }
        if (!class_exists('flexstage/flexticketorder_model', false)) {
            $this->ci->load->model('flexstage/flexticketorder_model');
        }
        if (!class_exists('flexstage/flexstage_model', false)) {
            $this->ci->load->model('flexstage/flexstage_model');
        }

        $this->tag = $tag;

        $ticketsale_conditions = [
            'id' => $ticketsale_id
        ];

        $this->ticketsale = $this->ci->flexticketsale_model->get($ticketsale_conditions);

        $ticket_conditions = [
            'id' => $this->ticketsale['ticketid']
        ];
        
        $ticketorder_conditions = [
            'id' => $this->ticketsale['ticketorderid']
        ];

        $this->ticket = $this->ci->flexticket_model->get($ticket_conditions);
        $this->ticketorder = $this->ci->flexticketorder_model->get($ticketorder_conditions);
        $this->event = $this->ci->flexstage_model->get_event($this->ticketsale['eventid']);

        $title = $this->ticket['name'] . ' ticket for ' . $this->event['name'];

        $this->SetTitle($title);
    }

    public function prepare()
    {
        $this->set_view_vars([
            'ticketsale' => $this->ticketsale,
            'ticket' => $this->ticket,
            'ticketorder' => $this->ticketorder,
            'event' => $this->event
        ]);

        return $this->build();
    }

    protected function type()
    {
        return 'ticket';
    }

    protected function file_path()
    {
        $customPath = APP_MODULES_PATH . flexstage_MODULE_NAME . '/views/pdf/ticketsalepdf.php';

        if (file_exists($customPath)) {
            $actualPath = $customPath;
        }

        return $actualPath;
    }
}
