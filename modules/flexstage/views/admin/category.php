<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="flexstage_category_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <?php echo form_open(admin_url('flexstage/category'), ['id' => 'flexstage_category_form']); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">
                    <span class="edit-title"><?php echo _l('flexstage_edit_category'); ?></span>
                    <span class="add-title"><?php echo _l('flexstage_new_category'); ?></span>
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div id="additional"></div>
                        <?php echo render_input('name', 'flexstage_name_label'); ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('flexstage_close'); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo _l('flexstage_submit'); ?></button>
            </div>
        </div>
        <!-- /.modal-content -->
        <?php echo form_close(); ?>
    </div>
    <!-- /.modal-dialog -->
</div>
<script>
    window.addEventListener('load',function(){

    // Validating the knowledge group form
    appValidateForm($('#flexstage_category_form'), {
        name: 'required'
    }, manage_flexstage_categories);

    // On hidden modal reset the values
    $('#flexstage_category_modal').on("hidden.bs.modal", function(event) {
        $('#flexstage_category_slug').addClass('hide');
        $('#flexstage_category_slug input').rules('remove', 'required');
        $('#additional').html('');
        $('#flexstage_category_modal input').not('[type="hidden"]').val('');
        $('.add-title').removeClass('hide');
        $('.edit-title').removeClass('hide');
    });
});
// Form handler function for knowledgebase group
function manage_flexstage_categories(form) {
    var data = $(form).serialize();
    var url = form.action;
    $.post(url, data).done(function(response) {
        window.location.reload();
    });
    return false;
}

// New knowledgebase group, opens modal
function new_flexstage_category() {
    $('#flexstage_category_modal').modal('show');
    $('.edit-title').addClass('hide');
}

// Edit KB group, 2 places groups view or articles view directly click on kanban
function edit_flexstage_category(invoker, id) {
    $('#additional').append(hidden_input('id', id));
    $('#flexstage_category_slug').removeClass('hide');
    $('#flexstage_category_slug input').rules('add', {required:true});
    $('#flexstage_category_slug input').val($(invoker).data('slug'));
    $('#flexstage_category_modal input[name="name"]').val($(invoker).data('name'));
    $('#flexstage_category_modal').modal('show');
    $('.add-title').addClass('hide');
}

</script>
