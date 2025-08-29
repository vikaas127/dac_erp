<div class="modal fade" id="scrapModal">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title"><?php echo new_html_entity_decode(_l('add_scrap_details')); ?></h4>
            </div>
            
            <?php 
                $title = isset($bill_of_material_detail) ? _l('update_bill_of_material_detail') : _l('add_bill_of_material_detail');
                $id = isset($bill_of_material_detail) ? $bill_of_material_detail->id : '';
                $product_id = isset($bill_of_material_detail) ? $bill_of_material_detail->product_id : '';
                $product_qty = isset($bill_of_material_detail) ? $bill_of_material_detail->product_qty : 1.0;
                $unit_id = isset($bill_of_material_detail) ? $bill_of_material_detail->unit_id : '';
                 $routing_id = isset($bill_of_material_detail) ? $bill_of_material_detail->$routing_id : '';
                $operation_id = isset($bill_of_material_detail) ? $bill_of_material_detail->operation_id : '';
                $display_order = isset($bill_of_material_detail) ? $bill_of_material_detail->display_order : 1;
                $bill_of_material_id = isset($bill_of_material_id) ? $bill_of_material_id : '';
            ?>

            <?php echo form_open(admin_url('manufacturing/save_scrap_modal'), array('id' => 'add_scrap_form', 'autocomplete' => 'on')); ?>
            <div class="modal-body">
                <div class="tab-content">
                    <div class="row">
                        <div class="col-md-12">
	<div class="col-md-12"> 
								<?php echo render_select('product_id',$products,array('id','description'),'component', $product_id); ?>
							</div>

							<div class="col-md-6">
								<?php echo render_input('estimated_quantity','product_qty', $product_qty,'number'); ?> 
							</div>
                            <!-- Unit -->
                            <div class="col-md-6">
                                <label for="unit_id">Unit of Measure</label>
                                <?php echo render_select('unit_id', $units, array('unit_type_id', 'unit_name'), '', '', [], [], '', 'unit_id', false); ?>
                            </div>

                           

                            <!-- Consumed in Operation -->
                            <div class="col-md-12">
                                <label for="operation_id">Consumed in Operation</label>
                                <?php echo render_select('operation_id', $arr_operations, array('id', 'operation'), '', $operation_id, [], [], '', 'operation_id', true); ?>
                            </div>

                         <div class="col-md-6">
    <label for="scrap_type">Scrap Type</label>
    <?php 
    $scrap_types = array(
        array('id' => 'reuse', 'name' => 'Reuse'),
        array('id' => 'waste', 'name' => 'Waste')
    );
    echo render_select('scrap_type', $scrap_types, array('id', 'name'), '', '', [], [], '', 'scrap_type', false); 
    ?>
</div>

                          
                    

                         
                            <!-- Reason -->
                            <div class="col-md-12">
                                <label for="reason">Comment</label>
                                <?php echo render_textarea('reason', '', '', ['id' => 'reason', 'name' => 'reason']); ?>
                            </div>
                        </div>
                    </div>
                </div>
              <input type="hidden" id="bill_of_material_id" name="bill_of_material_id" value="<?php echo isset($bill_of_material_id) ? $bill_of_material_id : ''; ?>">

<input type="hidden" id="bill_of_material_product_id" name="bill_of_material_product_id" value="<?php echo isset($bill_of_material_product_id) ? $bill_of_material_product_id : ''; ?>">
<input type="hidden" id="routing_id" name="routing_id" value="<?php echo isset($routing_id) ? $routing_id : '6'; ?>">


            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default close_btn" data-dismiss="modal"><?php echo _l('hr_close'); ?></button>
                <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>

<?php require('modules/manufacturing/assets/js/bill_of_materials/add_edit_scrap_modal_js.php'); ?>
