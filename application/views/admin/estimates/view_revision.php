<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700 tw-flex tw-items-center tw-space-x-2">
                    
                    <?php echo isset($revision) ? $revision->version : ''; ?>
                </h4>
                <?php $this->load->view('admin/estimates/estimate_template_readonly', ['revision' => $revision]); ?>
            </div>
            <?php $this->load->view('admin/invoice_items/item'); ?>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>
</html>
