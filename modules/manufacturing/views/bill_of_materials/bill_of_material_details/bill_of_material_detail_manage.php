<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">

           
			<div class="col-md-12">
				<div class="row">
					<div class="panel_s">
						<?php 

						$bill_of_material_id = isset($bill_of_material) ? $bill_of_material->id : '';
						$product_id = isset($bill_of_material) ? $bill_of_material->product_id : '';
						$product_variant_id = isset($bill_of_material) ? $bill_of_material->product_variant_id : '';
						$product_qty = isset($bill_of_material) ? $bill_of_material->product_qty : '';
						$unit_id = isset($bill_of_material) ? $bill_of_material->unit_id : '';
						$routing_id = isset($bill_of_material) ? $bill_of_material->routing_id : '';
						$bom_code = isset($bill_of_material) ? $bill_of_material->bom_code : '';

						$bom_type = isset($bill_of_material) ? $bill_of_material->bom_type : '';
						$bill_of_material_id = isset($bill_of_material) ? $bill_of_material->id : '';
						$labour_charges = isset($bill_of_material) ? $bill_of_material->labour_charges : 0;
						$electricity_charges = isset($bill_of_material) ? $bill_of_material->electricity_charges : 0;
						$machinery_charges = isset($bill_of_material) ? $bill_of_material->machinery_charges : 0;
						$other_charges = isset($bill_of_material) ? $bill_of_material->other_charges : 0;
						$labour_charges_description = isset($bill_of_material) ? $bill_of_material->labour_charges_description : '';
						$electricity_charges_description = isset($bill_of_material) ? $bill_of_material->electricity_charges_description : '';
						$machinery_charges_description = isset($bill_of_material) ? $bill_of_material->machinery_charges_description : '';
						$other_charges_description = isset($bill_of_material) ? $bill_of_material->other_charges_description : '';

						$manufacture_this_product_checked='';
						$kit_checked='';
						$kit_hide ='hide';

						if($bom_type == 'manufacture_this_product'){
							$manufacture_this_product_checked = 'checked';
							$kit_hide ='hide';

						}else{
							$kit_checked = 'checked';
							$kit_hide ='';

						}

						$ready_to_produce = isset($bill_of_material) ? $bill_of_material->ready_to_produce : '';
						$consumption = isset($bill_of_material) ? $bill_of_material->consumption : '';

						$product_variant_name='';
						if($product_variant_id != '' && $product_variant_id != 0){
							$product_variant_name = '( '.mrp_get_product_name($product_variant_id).' )';
						}
						?>
						<?php echo form_open(admin_url('manufacturing/add_bill_of_material_modal/'.$bill_of_material_id), array('id' => 'add_bill_of_material', 'autocomplete'=>'off')); ?>

						<div class="panel-body">
							<div class="col-md-12">
								

									<h4 class="no-margin col-md-8">
										<?php echo new_html_entity_decode(mrp_get_product_name($product_id) .$product_variant_name); ?>
									</h4>
							<div class="col-md-4" style="margin-bottom: 15px;">
    <div style="display: flex; justify-content: flex-end; align-items: center; flex-wrap: wrap;">
        <a href="<?php echo admin_url('manufacturing/view_bill_of_material_detail/' . $bill_of_material_id); ?>" class="btn btn-info" style="margin-left: 8px;">
            <?php echo _l('View'); ?>
        </a>
             <a href="<?php echo admin_url('manufacturing/bill_of_material_manage'); ?>" class="btn btn-default" style="margin-left: 8px;">
            <?php echo _l('close'); ?>
        </a>
        <button type="submit" class="btn btn-info" style="margin-left: 8px;">
            <?php echo _l('submit'); ?>
        </button>

    
    </div>
</div>


							</div>
							<hr class="hr-panel-heading" />

							

							<div class="row">
								<div class="col-md-6">
									<?php echo render_input('bom_code','BOM_code', $bom_code,'text'); ?> 
								</div>

								<div class="col-md-6">
									<?php echo render_select('product_id',$parent_product,array('id',array('description')),'product_label', $product_id); ?>
								</div>
								<div class="col-md-6">
									<?php echo render_select('product_variant_id',$product_variant,array('id','description'),'product_variant', $product_variant_id); ?>

								</div>

								<div class="col-md-6">
									<?php echo render_input('product_qty','product_qty', $product_qty ,'number'); ?> 
								</div>

								<div class="col-md-6">
									<?php echo render_select('unit_id',$units,array('unit_type_id', 'unit_name'), 'unit_of_measure', $unit_id,[], [], '', '' , false); ?>
								</div>

								<div class="col-md-6">
									<?php echo render_select('routing_id',$routings,array('id', 'routing_name'), 'routing_label', $routing_id,[], [], '', '' , true); ?>
								</div>

								<div class="col-md-12">
									<div class="form-group">
										<label for="profit_rate" class="control-label clearfix"><?php echo _l('bom_type'); ?></label>
										<div class="radio radio-primary radio-inline" >
											<input type="radio" id="manufacture_this_product" name="bom_type" value="manufacture_this_product" <?php echo new_html_entity_decode($manufacture_this_product_checked ) ?>>
											<label for="manufacture_this_product"><?php echo _l('manufacture_this_product'); ?></label>

										</div>
										<br>
										<div class="radio radio-primary radio-inline" >
											<input type="radio" id="kit" name="bom_type" value="kit" <?php echo new_html_entity_decode($kit_checked ) ?>>
											<label for="kit"><?php echo _l('kit'); ?></label>

										</div>
										<div class="kit_hide <?php echo new_html_entity_decode($kit_hide); ?>">
											<?php echo _l('A_BoM_of_type_kit_is_used_to_split_the_product_into_its_components'); ?><br>
											<?php echo _l('At_the_creation_of_a_Manufacturing_Order'); ?><br>
											<?php echo _l('At_the_creation_of_a_Stock_Transfer'); ?><br>
										</div>
									</div>
								</div>

								 <div class="col-md-12"><h4><?php echo _l('miscellaneous') ?></h4></div>

								<div class="col-md-6">
									<?php echo render_select('ready_to_produce',$ready_to_produce_type,array('name', 'label'), 'ready_to_produce', $ready_to_produce,[], [], '', '' , false); ?>
								</div>
								<div class="col-md-6">
									<?php echo render_select('consumption',$consumption_type,array('name', 'label'), 'consumption', $consumption,[], [], '', '' , false); ?>
								</div>

							</div>

							
						</div>
						
	
				<?php echo form_close(); ?>
						  <div class="col-md-12">
                <div class="row">
                    <div class="panel_s">
                        <div class="panel-body">
                            
                            <!-- TAB NAVIGATION -->
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="active">
                                    <a href="#components_tab" aria-controls="components_tab" role="tab" data-toggle="tab">
                                        <i class="fa fa-cogs"></i> <?php echo _l('component'); ?>
                                    </a>
                                </li>
                                <li>
                                    <a href="#scrap_tab" aria-controls="scrap_tab" role="tab" data-toggle="tab">
                                        <i class="fa fa-trash"></i> <?php echo _l('scrap'); ?>
                                    </a>
                                </li>
								<li>
                                    <a href="#costing_tab" aria-controls="costing_tab" role="tab" data-toggle="tab">
                                        <i class="fa fa-calculater"></i> Costing
                                    </a>
                                </li>
                            </ul>

                            <!-- TAB CONTENT -->
                            <div class="tab-content">
                                <!-- COMPONENTS TAB -->
                                <div role="tabpanel" class="tab-pane active" id="components_tab">
                                    

                                    <?php if(has_permission('manufacturing', '', 'create') && has_permission('bill_of_material', '', 'create')) { ?>
                                        <div class="_buttons">
                                            <a href="#" onclick="add_component(<?php echo new_html_entity_decode($bill_of_material_id) ?>, 0, <?php echo new_html_entity_decode($product_id) ?>, <?php echo new_html_entity_decode($routing_id) ?>, 'add'); return false;" 
                                               class="btn btn-info mbot10">
                                                <?php echo _l('add_component'); ?>
                                            </a>
                                        </div>
                                    <?php } ?>
                                    
                                    <?php render_datatable(array(
                                        	'<span class="hide"> - </span><div class="checkbox mass_select_all_wrap"><input type="checkbox" id="mass_select_all" data-to-table="bill_of_material_table"><label></label></div>',
                                        _l('id'),
                                        _l('display_order'),
                                        _l('component'),
                                        _l('product_qty'),
                                        _l('unit_id'),
                                        _l('apply_on_variants'),
                                        _l('consumed_in_operation'),
                                    ), 'bill_of_material_detail_table', array('customizable-table')); ?>
                                </div>

                                <!-- SCRAP TAB -->
                                <div role="tabpanel" class="tab-pane" id="scrap_tab">
                                 

                                    <?php if(has_permission('manufacturing', '', 'create') && has_permission('bill_of_material', '', 'create')) { ?>
                                        <div class="_buttons">
                                            <a href="#" onclick="open_scrap_modal(<?php echo new_html_entity_decode($bill_of_material_id) ?>, <?php echo new_html_entity_decode($product_id) ?>, <?php echo new_html_entity_decode($routing_id) ?>); return false;" 
                                               class="btn btn-warning mbot10">
                                                <?php echo _l('add_scrap'); ?>
                                            </a>
                                        </div>
                                    <?php } ?>
 <?php 
render_datatable(array(
    
     _('component') ,          // ScrapID
        // product_id (name shown via helper)
    _l('unit_id'),              // item_type
                    // unit_id (name via helper)
    _l('scrap_type'),              // scrap_type
    _l('product_qty'),               // estimated_quantity
           // scrap_location_id (optional, name if joined)
    _l('reason'),                  // reason
     // bill_of_material_id
                // routing_id
    _l('consumed_in_operation'),            // operation_id
    // bill_of_material_product_id
), 'bill_of_material_scrap_table', array('customizable-table')); 
?>

                                </div>

								<div role="tabpanel" class="tab-pane" id="costing_tab">
							
								<div class="col-md-12">
			<div class="row">
			<table class="table table-bordered" >
				<thead class="table-dark">
					<tr>
						<th>#</th>
						<th>Classification</th>
						<th>Amount</th>
						<th>Comment</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>1</td>
						<td>Labour Charges</td>
						<td>
						<input type="hidden" id="bill_of_material_id" value="<?php echo $bill_of_material_id; ?>">

							<input type="number" name="labour_charges" id="labour_charges" value="<?php echo $labour_charges; ?>">
						</td>
						<td>
							<input type="text" name="labour_charges_description" id="labour_charges_description" 
								value="<?php echo $labour_charges_description; ?>" >
						</td>
						<td></td>
					</tr>
					<tr>
						<td>2</td>
						<td>Electricity Charges</td>
						<td>
							<input type="number" name="electricity_charges" id="electricity_charges" value="<?php echo $electricity_charges; ?>">
						</td>
						<td> <input type="text" name="electricity_charges_description" id="electricity_charges_description" 
						value="<?php echo $electricity_charges_description; ?>" ></td>
					</tr>
					<tr>
						<td>3</td>
						<td>Machinery Charges</td>
						<td>
							<input type="number" name="machinery_charges" id="machinery_charges" value="<?php echo $machinery_charges; ?>">
						</td>
						<td>
							<input type="text" name="machinery_charges_description" id="machinery_charges_description" 
								value="<?php echo $machinery_charges_description; ?>" >
						</td>
					</tr>
					<tr>
						<td>4</td>
						<td>Other Charges</td>
						<td>
							<input type="number" name="other_charges" id="other_charges" value="<?php echo $other_charges; ?>">
						</td>
						<td> <input type="text" name="other_charges_description" id="other_charges_description" 
						value="<?php echo $other_charges_description; ?>" ></td>
					</tr>
				</tbody>
			</table>
			<button type="button" class="btn btn-primary" id="save_costing">Save</button>

					</div>
			</div>

								</div>
                            </div>

                        </div>
                    </div>
                </div>
                <div id="modal_wrapper"></div>
            </div>
					
					</div>

				</div>
			</div>

          

        </div>
    </div>
</div>

<div id="contract_file_data"></div>


<?php echo form_hidden('bill_of_material_id', $bill_of_material_id); ?>
<?php echo form_hidden('bill_of_material_product_id', $product_id); ?>
<?php echo form_hidden('bill_of_material_routing_id', $routing_id); ?>

<?php init_tail(); ?>
<?php 
require('modules/manufacturing/assets/js/bill_of_materials/add_edit_bill_of_material_js.php');

require('modules/manufacturing/assets/js/bill_of_materials/bill_of_material_details/bill_of_material_detail_manage_js.php');
?>
</body>
</html>
