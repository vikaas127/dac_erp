<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <?php if (has_permission('flexstage', '', 'create')) { ?>
                    <div class="_buttons tw-mb-2 sm:tw-mb-4">
                        <a href="<?php echo admin_url('flexstage/mail_list'); ?>"
                            class="btn btn-primary pull-left display-block">
                            <i class="fa-regular fa-plus tw-mr-1"></i>
                            <?php echo _l('flexstage_new_mail_list'); ?>
                        </a>

                        <?php echo init_flexstage_home_link() ?>
                        <div class="clearfix"></div>
                    </div>
                <?php } ?>
                <div class="panel_s">
                    <div class="panel-body">
                        <?php if (count($email_lists) > 0) { ?>
                            <div class="panel-table-full">
                                <table class="table dt-table">
                                    <thead>
                                        <th>
                                            <?php echo _l('flexstage_id_label');
                                            ?>
                                        </th>
                                        <th>
                                            <?php echo _l('flexstage_name_label');
                                            ?>
                                        </th>
                                        <th>
                                            <?php echo _l('flexstage_date_created_label');
                                            ?>
                                        </th>
                                        <th>
                                            <?php echo _l('flexstage_creator_label');
                                            ?>
                                        </th>
                                        <th>
                                            <?php echo _l('flexstage_options_heading'); ?>
                                        </th>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($email_lists as $email_list) { ?>
                                            <?php $special = $email_list['name'] == 'leads' || $email_list['name'] == 'clients' || $email_list['name'] == 'staff'; ?>
                                            <?php $view_uri = 'admin/flexstage/mail_list_view/' ?>
                                            <tr>
                                                <td>
                                                    <?php echo ($special) ? '--' : $email_list['listid']; ?>
                                                </td>
                                                <td>
                                                    <a href="<?php echo $special ? site_url($view_uri . $email_list['name']) : site_url($view_uri . $email_list['listid']) ?>"
                                                        data-toggle="tooltip"
                                                        title="<?php echo $special ? _l('flexstage_cant_edit_mail_list') : '' ?>">
                                                        <?php echo $special ? _l('flexstage_' . $email_list['name'] . '_label') : $email_list['name'] ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <?php echo $special ? '--' : $email_list['datecreated']; ?>
                                                </td>
                                                <td>
                                                    <?php echo $special ? '--' : $email_list['creator']; ?>
                                                </td>
                                                <td>
                                                    <div class="tw-flex tw-items-center tw-space-x-3">
                                                        <a href="<?php echo $special ? site_url($view_uri . $email_list['name']) : site_url($view_uri . $email_list['listid']) ?>"
                                                            class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700"><i
                                                                class="fa fa-eye fa-lg"></i>
                                                            <?php if (!$special) { ?>
                                                                <?php if (has_permission('flexstage', '', 'edit')) { ?>
                                                                    <a href="<?php echo admin_url('flexstage/mail_list/' . $email_list['listid']); ?>"
                                                                        class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700"
                                                                        title="<?php echo _l('flexstage_edit') ?>">
                                                                        <i class="fa-regular fa-pen-to-square fa-lg"></i>
                                                                    </a>
                                                                <?php } ?>
                                                                <?php if (has_permission('flexstage', '', 'delete')) { ?>
                                                                    <a href="<?php echo admin_url('flexstage/delete_mail_list/' . $email_list['listid']); ?>"
                                                                        class="tw-mt-px tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete"
                                                                        title="<?php echo _l('flexstage_delete') ?>">
                                                                        <i class="fa-regular fa-trash-can fa-lg"></i>
                                                                    </a>
                                                                <?php } ?>
                                                            <?php } ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } else { ?>
                            <p class="no-margin">
                                <?php echo _l('flexstage_no_mail_lists_found'); ?>
                            </p>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
</body>

</html>