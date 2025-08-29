<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper" class="customer_profile">
    <div class="content">
        <div class="row">
            <div class="col-md-3">
                <h4 class="tw-text-lg tw-font-semibold tw-text-neutral-800 tw-mt-0">
                    <div class="tw-space-x-3 tw-flex tw-items-center">
                        <span class="tw-truncate">
                            #
                            <?php echo $event['id'] ?> -
                            <?php echo $event['name'] ?>
                        </span>
                        <?php if (has_permission('flexstage', '', 'delete') || is_admin()) { ?>
                            <!-- <div class="btn-group">
                                    <a href="#" class="dropdown-toggle btn-link" data-toggle="dropdown" aria-haspopup="true"
                                       aria-expanded="false">
                                        <span class="caret"></span>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-right">
                                        <?php if (has_permission('customers', '', 'delete')) { ?>
                                            <li>
                                                <a href="<?php echo admin_url('clients/delete/' . $key); ?>"
                                                   class="text-danger delete-text _delete"><i class="fa fa-remove"></i>
                                                    <?php echo _l('delete'); ?>
                                                </a>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                </div> -->
                        <?php } ?>
                    </div>
                </h4>
            </div>
            <div class="col-md-9">
                <a href="<?php echo admin_url('flexstage') ?>" class="btn btn-link">
                    <i class="fa fa-circle-left fa-lg"></i>
                    <?php echo strtoupper(_l('flexstage_back')); ?>
                </a>
            </div>
            <div class="clearfix"></div>
            <div class="col-md-3">
                <?php $this->load->view('admin/details/tabs', ['key' => $key]); ?>
            </div>
            <div class="tw-mt-12 sm:tw-mt-0 col-md-9">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php echo $content; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>

</html>