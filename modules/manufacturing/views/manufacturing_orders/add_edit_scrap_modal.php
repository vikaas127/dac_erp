<div class="modal fade" id="scrapModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo new_html_entity_decode(_l('add_scrap_details')); ?></h4>
            </div>
            <?php echo form_open(admin_url('manufacturing/save_scrap_modal'), array('id' => 'add_scrap_form', 'autocomplete' => 'off')); ?>
            <div class="modal-body">
                <div class="tab-content">
                    <div class="row">
                        <div class="col-md-12">
                            <!-- Scrap Code -->
                           
                            <!-- Product -->
                           	<div class="col-md-12">
								<?php echo render_select('product_id',$parent_product,array('id','description'),'product_label',''); ?>
							</div>
                            <!-- Unit -->
                            <div class="col-md-6">
                                <?php echo render_select('unit_id', $units, array('unit_type_id', 'unit_name'), 'unit_of_measure', '', [], [], '', '', false); ?>
                            </div>
                            <!-- Estimated Quantity -->
                            <div class="col-md-6">
                                <?php echo render_input('estimated_quantity', 'estimated_Quantity', '', 'number'); ?>
                            </div>
                            <!-- Actual Quantity -->
                            <div class="col-md-6">
                                <?php echo render_input('actual_quantity', 'actual_Quantity', '', 'number'); ?>
                            </div>
                             <!-- Unit -->
                            <div class="col-md-6">
                                <?php echo render_select('scrap_type', $scrap_type, array('scrap_type', 'scrap_type'), 'scrap_type', '', [], [], '', '', false); ?>
                            </div>
                            <!-- Cost Allocation -->
                            <div class="col-md-6">
                                <?php echo render_input('cost_allocation', 'cost_Allocation(%)', '', 'number'); ?>
                            </div>
                            <!-- Reason -->
                            <div class="col-md-12">
                                <?php echo render_textarea('reason', 'comment', ''); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <input type="hidden" name="manufacturing_order_id" value="<?php echo $manufacturing_order_id;?>">
            <div class="modal-footer">
                <button type="button" class="btn btn-default close_btn" data-dismiss="modal"><?php echo _l('hr_close'); ?></button>
                <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
<?php require('modules/manufacturing/assets/js/manufacturing_orders/add_edit_scrap_modal_js.php'); ?>