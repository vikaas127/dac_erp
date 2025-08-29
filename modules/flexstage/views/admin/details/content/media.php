<div class="row">
    <div class="col-md-12">
        <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
            <?php echo $title; ?>
        </h4>
        <h6>
            <?php echo _l('flexstage_media_subheader') ?>
        </h6>

        <div class="panel_s">
            <div class="panel-body">
                <?php if (count($images) < FLEXSTAGE_MAX_IMAGES): ?>
                    <label class="">
                        <?php echo _l('flexstage_upload_images') ?>
                    </label>
                    <?php echo form_open_multipart(admin_url('flexstage/upload_image/' . $event['id'] . '?key=' . $key), ['class' => 'dropzone', 'id' => 'flex-images-upload']); ?>

                    <div class="fallback">
                        <input type="file" name="file" multiple />
                    </div>
                    <?php echo form_close(); ?>
                <?php else: ?>
                    <!-- <h4 class="text-danger">
                    </h4> -->
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="right:0px">
                            <span aria-hidden="true">&times;</span>
                        </button>

                        <?php echo _l('flexstage_image_limit_reach') ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php echo form_open(current_url() . '?key=' . $key, ['id' => 'flex_media_form']); ?>
            <div class="panel-body">
                <?php $value = (isset($video) ? $video['url'] : ''); ?>
                <?php echo render_input('url', 'flexstage_video_url', $value, 'url', ['placeholder' => _l('flexstage_video_url_placeholder')]); ?>

                <div class="panel-footer text-right">
                    <button type="submit" class="btn btn-primary">
                        <?php echo strtoupper(_l('flexstage_save')); ?>
                    </button>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>

        <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
            <?php echo _l('flexstage_images'); ?>
        </h4>
        <div class="panel_s panel-table-full">
            <div class="panel-body">
                <?php if (count($images) > 0) { ?>
                    <table class="table dt-table">
                        <thead>
                            <tr>
                                <th>
                                    <?php echo _l('flexstage_imagename'); ?>
                                </th>
                                <th>
                                    <?php echo _l('flexstage_uploaded_by'); ?>
                                </th>
                                <th>
                                    <?php echo _l('flexstage_dateadded'); ?>
                                </th>
                                <th>
                                    <?php echo _l('flexstage_options_heading'); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($images as $image) {
                                $path = get_file_path() . $event['id'] . '/' . $image['file_name']; ?>
                                <tr>
                                    <td data-order="<?php echo $image['file_name']; ?>">
                                        <?php if (is_image(FLEXSTAGE_IMAGES_FOLDER . $event['id'] . '/' . $image['file_name']) || (!empty($image['external']) && !empty($image['thumbnail_link']))) {
                                            echo '<div class="text-left"><i class="fa fa-spinner fa-spin mtop30"></i></div>';
                                            echo '<img class="project-file-image img-table-loading" src="#" data-orig="' . fs_image_file_url($image['event_id'], $image['file_name']) . '" width="100">';
                                            echo '</div>';
                                        }
                                        echo $image['subject']; ?>
                                    </td>

                                    <td>
                                        <?php if ($image['uploaded_by'] != 0) {
                                            $_data = '<a href="' . admin_url('staff/profile/' . $image['uploaded_by']) . '">' . staff_profile_image($image['uploaded_by'], [
                                                'staff-profile-image-small',
                                            ]) . '</a>';
                                            $_data .= ' <a href="' . admin_url('staff/member/' . $image['uploaded_by']) . '">' . get_staff_full_name($image['uploaded_by']) . '</a>';
                                            echo $_data;
                                        } ?>
                                    </td>
                                    <td>
                                        <?php echo _dt($image['dateadded']); ?>
                                    </td>
                                    <td>
                                        <div class="tw-flex tw-items-center tw-space-x-3">
                                            <?php if ($image['uploaded_by'] == get_staff_user_id() || has_permission('flexstage', '', 'delete')) { ?>
                                                <a href="<?php echo admin_url('flexstage/remove_image/' . $event['id'] . '/' . $image['id'] . '?key=' . $key); ?>"
                                                    class="tw-mt-px tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete">
                                                    <i class="fa-regular fa-trash-can fa-lg"></i>
                                                </a>
                                            <?php } ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p class="no-margin">
                        <?php echo _l('flexstage_no_images_found'); ?>
                    </p>
                <?php } ?>
            </div>
        </div>
    </div>
</div>