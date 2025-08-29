<div class="row">
    <div class="col-md-12">
        <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
            <?php echo $title; ?>
        </h4>
        <h6>
            <?php echo _l('flexstage_speakers_subheader') ?>
        </h6>
        <?php echo validation_errors('<div class="alert alert-danger alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="right:0px"><span aria-hidden="true">&times;</span></button>', '</div>'); ?>

        <?php $hidden = isset($speaker) ? ['id' => $speaker['id']] : [] ?>
        <?php echo form_open_multipart(current_url() . '?key=' . $key, ['id' => 'flex_speakers_form'], $hidden); ?>
        <div class="panel_s">
            <div class="panel-body">
                <?php $value = (isset($speaker) ? $speaker['name'] : set_value('name')); ?>
                <?php $attrs = ['placeholder' => _l('flexstage_speaker_name_placeholder')]; ?>
                <?php echo render_input('name', 'flexstage_name_label', $value, 'text', $attrs); ?>

                <?php $value = (isset($speaker) ? $speaker['email'] : set_value('email')); ?>
                <?php $attrs = ['placeholder' => _l('flexstage_speaker_email_placeholder')]; ?>
                <?php echo render_input('email', 'flexstage_email_label', $value, 'email', $attrs); ?>

                <?php $value = (isset($speaker) ? $speaker['image'] : set_value('image')); ?>
                <?php $attrs = ['placeholder' => _l('flexstage_speaker_image_placeholder')]; ?>
                <?php echo render_input('image', 'flexstage_image_label', $value, 'file', $attrs); ?>

                <?php $value = (isset($speaker) ? $speaker['show'] : set_value('show', 1)); ?>
                <div class="form-group">
                    <div class="btn btn-default">
                        <input class="form-check-input" type="checkbox" name="show" value="1" <?php echo set_checkbox('show', $value) ?> />
                        <?php echo _l('flexstage_speaker_show'); ?>
                    </div>
                </div>


                <?php $value = (isset($speaker) ? $speaker['bio'] : set_value('bio')); ?>
                <?php echo render_textarea('bio', 'flexstage_bio_label', $value); ?>

                <div class="panel-footer text-right">
                    <button type="submit" class="btn btn-primary">
                        <?php echo strtoupper(_l('flexstage_save')); ?>
                    </button>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>

        <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
            <?php echo _l('flexstage_speakers'); ?>
        </h4>
        <div class="panel_s panel-table-full">
            <div class="panel-body">
                <?php if (count($speakers) > 0) { ?>
                    <table class="table dt-table">
                        <thead>
                            <tr>
                                <th>
                                    <?php echo _l('flexstage_name_label'); ?>
                                </th>
                                <th>
                                    <?php echo _l('flexstage_email_label'); ?>
                                </th>
                                <th>
                                    <?php echo _l('flexstage_speaker_show'); ?>
                                </th>
                                <th>
                                    <?php echo _l('flexstage_bio_label'); ?>
                                </th>
                                <th>
                                    <?php echo _l('flexstage_options_heading'); ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($speakers as $speaker) { ?>
                                <tr>
                                    <td>
                                        <?php $path = get_file_path('speakers') . $speaker['event_id'] . '/' . $speaker['image']; ?>
                                        <?php if (is_image(FLEXSTAGE_SPEAKERS_FOLDER . $speaker['event_id'] . '/' . $speaker['image']) || (!empty($speaker['external']) && !empty($speaker['thumbnail_link']))) {
                                            echo '<div class="text-left"><i class="fa fa-spinner fa-spin mtop30"></i></div>';
                                            echo '<img class="project-file-image img-table-loading" src="#" data-orig="' . fs_image_file_url($speaker['event_id'], $speaker['image'], 'speakers') . '" width="100">';
                                            echo '</div>';
                                        } ?>
                                        <div>
                                            <?php echo $speaker['name']; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo $speaker['email']; ?>
                                    </td>
                                    <td>
                                        <?php echo $speaker['show'] ? _l('flexstage_yes') : _l('flexstage_no'); ?>
                                    </td>
                                    <td>
                                        <?php echo $speaker['bio']; ?>
                                    </td>
                                    <td>
                                        <div class="tw-flex tw-items-center tw-space-x-3">
                                            <?php if (has_permission('flexstage', '', 'edit')) { ?>
                                                <a href="<?php echo admin_url('flexstage/event_details/' . $event['id'] . '?key=' . $key . '&speaker-id=' . $speaker['id']); ?>"
                                                    class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700"
                                                    title="<?php echo _l('flexstage_edit') ?>">
                                                    <i class="fa-regular fa-pen-to-square fa-lg"></i>
                                                </a>
                                            <?php } ?>
                                            <?php if (has_permission('flexstage', '', 'delete')) { ?>
                                                <a href="<?php echo admin_url('flexstage/remove_speaker/' . $event['id'] . '/' . $speaker['id'] . '?key=' . $key); ?>"
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
                        <?php echo _l('flexstage_no_speakers_found'); ?>
                    </p>
                <?php } ?>
            </div>
        </div>
    </div>
</div>