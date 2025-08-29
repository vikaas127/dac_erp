<?php

defined('BASEPATH') or exit('No direct script access allowed');
include_once(APPPATH . 'libraries/pdf/App_pdf.php');

class Bom_pdf extends App_pdf
{
    protected $bill_of_material;

    public function __construct($bill_of_material)
    {
        $this->bill_of_material = $bill_of_material;
        parent::__construct();

        // Set document metadata
        $this->SetTitle('Bill of Material Export - ' . $this->bill_of_material->bom_code);
    }

    public function prepare()
    {
        $this->SetHeaderData('', 0, 'Bill of Material', 'BOM No: ' . $this->bill_of_material->bom_code);
        $this->SetFont('helvetica', '', 12);
    
        $bill_of_material = $this->bill_of_material;
        $components = $this->bill_of_material->components;
    
        $html = $this->ci->load->view($this->file_path(), [
            'bill_of_material' => $bill_of_material,
            'components' => $components,
            'scrap_details' => $bill_of_material->scrap_details ?? [],
            'labour_charges' => $bill_of_material->labour_charges ?? 0,
            'electricity_charges' => $bill_of_material->electricity_charges ?? 0,
            'machinery_charges' => $bill_of_material->machinery_charges ?? 0,
            'other_charges' => $bill_of_material->other_charges ?? 0,
            'labour_charges_description' => $bill_of_material->labour_charges_description ?? '',
            'electricity_charges_description' => $bill_of_material->electricity_charges_description ?? '',
            'machinery_charges_description' => $bill_of_material->machinery_charges_description ?? '',
            'other_charges_description' => $bill_of_material->other_charges_description ?? '',
            'pdf' => $this,
            'font_size' => 10,
            'font_name' => 'helvetica',
        ], true);
        
    
        $this->writeHTML($html, true, false, true, false, '');
    }
    

    protected function file_path()
    {
    return 'bill_of_materials/bill_of_material_details/bom_pdf_template';
    }

    protected function type()
    {
        return 'bom';
    }
}
