<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="_buttons tw-mb-2 sm:tw-mb-4">
                    <?php if (has_permission('flexstage', '', 'create')) { ?>
                        <a href="#" onclick="new_flexstage_category(); return false;"
                            class="btn btn-primary pull-left display-block">
                            <i class="fa-regular fa-plus tw-mr-1"></i>
                            <?php echo _l('flexstage_new_category'); ?>
                        </a>
                    <?php } ?>
                    <?php echo init_flexstage_home_link() ?>
                    <div class="clearfix"></div>
                </div>

                <div class="panel_s">
                    <div class="panel-body ">
                        <?php if (count($categories) > 0) { ?>
                            <div class="panel-table-full">
                                <table class="table dt-table">
                                    <thead>
                                        <th>
                                            <?php echo _l('flexstage_name_heading'); ?>
                                        </th>
                                        <th>
                                            <?php echo _l('flexstage_slug_heading'); ?>
                                        </th>
                                        <th>
                                            <?php echo _l('flexstage_options_heading'); ?>
                                        </th>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $category) { ?>
                                            <tr>
                                                <td>
                                                    <?php echo $category['name']; ?>

                                                    <span class="badge mleft5"><?php echo total_rows(db_prefix() . 'flexevents', 'category_id=' . $category['id']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo $category['slug']; ?>
                                                </td>
                                                <td>
                                                    <div class="tw-flex tw-items-center tw-space-x-3">
                                                        <?php if (has_permission('flexstage', '', 'edit')) { ?>
                                                            <a href="#"
                                                                onclick="edit_flexstage_category(this,<?php echo $category['id']; ?>); return false"
                                                                data-name="<?php echo $category['name']; ?>"
                                                                data-slug="<?php echo $category['slug']; ?>"
                                                                class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">
                                                                <i class="fa-regular fa-pen-to-square fa-lg"></i>
                                                            </a>
                                                        <?php } ?>
                                                        <?php if (has_permission('flexstage', '', 'delete')) { ?>
                                                            <a href="<?php echo admin_url('flexstage/delete_category/' . $category['id']); ?>"
                                                                class="tw-mt-px tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete">
                                                                <i class="fa-regular fa-trash-can fa-lg"></i>
                                                            </a>
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
                                <?php echo _l('flexstage_no_categories_found'); ?>
                            </p>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<?php $this->load->view('admin/category'); ?>
<?php init_tail(); ?>
</body>

</html>