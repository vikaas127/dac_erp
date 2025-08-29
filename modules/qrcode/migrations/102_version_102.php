<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_102 extends App_module_migration
{
    public function up()
    {
        add_option('qr_code_invoice_base64_encryption', '0');
    }
}
