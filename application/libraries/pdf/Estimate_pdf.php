<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(__DIR__ . '/App_pdf.php');

class Estimate_pdf extends App_pdf
{
    protected $estimate;

    private $estimate_number;

    public function __construct($estimate, $tag = '')
    {
        $this->load_language($estimate->clientid);

        $estimate                = hooks()->apply_filters('estimate_html_pdf_data', $estimate);
        $GLOBALS['estimate_pdf'] = $estimate;

        parent::__construct();

        $this->tag             = $tag;
        $this->estimate        = $estimate;
        $this->estimate_number = format_estimate_number($this->estimate->id);

        $this->SetTitle($this->estimate_number);
    }

    public function prepare()
    {
        $this->with_number_to_word($this->estimate->clientid);

        $this->set_view_vars([
            'status'          => $this->estimate->status,
            'estimate_number' => $this->estimate_number,
            'estimate'        => $this->estimate,
        ]);

        return $this->build();
    }

    protected function type()
    {
        return 'estimate';
    }

    // protected function file_path()
    // {
    //     $customPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/my_estimatepdf.php';
    //     $actualPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/estimatepdf.php';

    //     if (file_exists($customPath)) {
    //         $actualPath = $customPath;
    //     }

    //     return $actualPath;
    // }
      protected function file_path()
{
    // Get selected PDF format
    $pdf_format = get_option('estimate_pdf_format');

    // Decide file based on format
    if ($pdf_format === 'standard') {
        $file_name = 'estimatepdf_V2.php';
    } else {
        // default OR null
        $file_name = 'estimatepdf.php';
    }

    // Build paths
    $customPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/' . $file_name;
    $actualPath = APPPATH . 'views/themes/' . active_clients_theme() . '/views/' . $file_name;

    // Use custom theme file if exists
    if (file_exists($customPath)) {
        return $customPath;
    }

    return $actualPath;
}
}
