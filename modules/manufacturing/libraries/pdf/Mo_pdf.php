<?php

defined('BASEPATH') or exit('No direct script access allowed');
include_once(APPPATH . 'libraries/pdf/App_pdf.php');

class Mo_pdf extends App_pdf
{
    protected $manufacturing_order_data;

    public function __construct($manufacturing_order_data)
    {
        $this->manufacturing_order_data = $manufacturing_order_data;
        parent::__construct();

        // Set document metadata
        $mo = $this->manufacturing_order_data['manufacturing_order'];
        $this->SetTitle('Manufacturing Order Export - ' . $mo->mo_code);
    }

    public function prepare()
    {
        $mo = $this->manufacturing_order_data['manufacturing_order'];
        $this->SetHeaderData('', 0, 'Manufacturing Order', 'MO No: ' . $mo->mo_code);
        $this->SetFont('helvetica', '', 12);

        $html = $this->ci->load->view($this->file_path(), [
            'manufacturing_order_data' => $this->manufacturing_order_data,
            
            'pdf' => $this,
            'font_size' => 10,
            'font_name' => 'helvetica',
        ], true);

        $this->writeHTML($html, true, false, true, false, '');
    }

    protected function file_path()
    {
        return 'manufacturing/manufacturing_orders/mo_pdf_template';
    }

    protected function type()
    {
        return 'mo';
    }
}
