<div class="row">
    <div class="col-md-12">
        <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
            <?php echo $title; ?>
        </h4>
        <h6>
            <?php echo _l('flexstage_social_subheader') ?>
        </h6>
        <?php echo validation_errors('<div class="alert alert-danger alert-dismissible" role="alert">
  <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="right:0px"><span aria-hidden="true">&times;</span></button>', '</div>'); ?>
        <?php echo form_open(current_url() . '?key=' . $key, ['id' => 'flex_social_pages_form']); ?>

        <div class="panel_s">
            <div class="panel-body">
                <?php $channels = flexstage_social_channels(); ?>
                <?php foreach ($channels as $channel): ?>
                    <?php $value = (isset($event) ? get_event_social_channel($event['id'], $channel['id']) : ''); ?>
                    <?php echo render_input('channels[' . $channel['id'] . ']', 'flexstage_' . $channel['id'], $value, 'url', ['placeholder' => $channel['placeholder']]); ?>

                <?php endforeach; ?>

                <div class="panel-footer text-right">
                    <button type="submit" class="btn btn-primary">
                        <?php echo strtoupper(_l('flexstage_save')); ?>
                    </button>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>