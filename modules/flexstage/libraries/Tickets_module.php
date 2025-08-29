<?php

class Tickets_module
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();
        $this->ci->load->model('flexstage/flexstage_model');
        $this->ci->load->model('flexstage/flexticket_model');
        $this->ci->load->model('flexstage/flexticketorder_model');
        $this->ci->load->model('flexstage/flexticketsale_model');
    }

    /**
     * Send tickets to attendees email
     *
     * @param int $invoice_id
     * @return bool
     */
    protected function send($ticket_order_conditions)
    {
        $this->ci->email->initialize();
        $this->ci->load->library('email');

        $ticket_order = $this->ci->flexticketorder_model->get($ticket_order_conditions);

        if($ticket_order){
            $event = $this->ci->flexstage_model->get_event($ticket_order['eventid']);
    
            $ticket_sales_conditions = [
                'ticketorderid' => $ticket_order['id']
            ];
            $ticket_sales = $this->ci->flexticketsale_model->all($ticket_sales_conditions);
    
            $venue = flexstage_format_venue($event['type'], $event['event_link'], $event['location']);
    
            $date_time = flexstage_format_date($event['start_date']);
    
            $ticket_details = '';
            $ticket_pdf_files = [];
    
            foreach ($ticket_sales as $ticket_sale) {
                $ticket_conditions = [
                    'id' => $ticket_sale['ticketid']
                ];
    
                $ticket = $this->ci->flexticket_model->get($ticket_conditions);
    
                $ticket_details .= "$ticket[name] ($ticket_sale[reference_code]) - Admits $ticket_sale[quantity] <br/>";
    
    
                $pdf = ticketsale_pdf($ticket_sale['id']);
    
                for ($i=0; $i < $ticket_sale['quantity']; $i++) { 
                    $file_path = FLEXSTAGE_TICKETS_FOLDER . $ticket_sale['reference_code'] . '_' . ($i + 1) . '.pdf';
                    $pdf->Output($file_path, 'F');
                    //get file extension
                    $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
        
                    //get file name without extension
                    $file_name = pathinfo($file_path, PATHINFO_FILENAME);
                    $ticket_pdf_files[] = [
                        'attachment' => $file_path,
                        'filename' => $file_name,
                        'type' => $file_extension,
                        'read' => true,
                    ];
                }
    
            }
    
            $template_name = 'Flexstage_tickets_success';
            $template = mail_template($template_name, "flexstage", $ticket_order['attendee_email'], $event['name'], $date_time, $venue, $ticket_details, $ticket_order['id'], $ticket_pdf_files);
    
            if ($template->send()) {
                $data = [
                    'tickets_sent' => 1
                ];
    
                $this->ci->flexticketorder_model->update($ticket_order['id'], $data);
    
                return true;
            }
        }

        return false;
    }

    public function create_email_template()
    {
        $templateMessage = "Hi there! <br/><br/>Find the tickets you ordered for the {event_name} event below.<br/><br/> Venue: {venue} <br/><br/> Date/Time: {date_time} <br/><br/> <strong>Tickets</strong><br/> {ticket_details}<br/>Regards.";
        create_email_template('Tickets - {event_name}', $templateMessage, 'staff', 'Flexstage Tickets Success', 'flexstage-tickets-success');
    }

    public function send_by_invoice($invoice_id){
        return $this->send([
            'invoiceid' => $invoice_id
        ]);
    }

    public function send_by_ticketorder($ticketorder_id){
        return $this->send([
            'id' => $ticketorder_id
        ]);
    }
}
