<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			<?php
			$id = '';
			$title = '';
			if (isset($manufacturing_order)) {
				$title .= _l('update_manufacturing_order_lable');
				$id    = $manufacturing_order->id;
			} else {
				$title .= _l('add_manufacturing_order_lable');
			}

			?>

			<?php echo form_open_multipart(admin_url('manufacturing/add_edit_manufacturing_order/' . $id), array('id' => 'add_update_manufacturing_order', 'autocomplete' => 'off')); ?>
			<input type="hidden" name="manufacturing_order_id" value="<?php echo $id; ?>">
			<div class="col-md-12">
				<div class="panel_s">

					<div class="panel-body">
						<div class="row mb-5">
							<div class="col-md-5">
								<h4 class="no-margin"><?php echo new_html_entity_decode($title); ?>
							</div>
						</div>
						<hr class="hr-color no-margin">

						<!-- start tab -->
						<div class="modal-body">
							<div class="tab-content">
								<!-- start general infor -->
								<?php
                                 $CI =& get_instance();
								$product_id = isset($manufacturing_order) ? $manufacturing_order->product_id : '';
								$estimate_id = isset($manufacturing_order) ? $manufacturing_order->estimate_id : '';
								

								
								$product_qty = isset($manufacturing_order) ? $manufacturing_order->product_qty : 1;
								$unit_id = isset($manufacturing_order) ? $manufacturing_order->unit_id : '';
								$manufacturing_order_code = isset($manufacturing_order) ? $manufacturing_order->manufacturing_order_code : $mo_code;
								$staff_id = isset($manufacturing_order) ? $manufacturing_order->staff_id : '';
								$bom_id = isset($manufacturing_order) ? $manufacturing_order->bom_id : '';
								$routing_id = isset($manufacturing_order) ? $manufacturing_order->routing_id : '';
								$components_warehouse_id = isset($manufacturing_order) ? $manufacturing_order->components_warehouse_id : '';
								$finished_products_warehouse_id = isset($manufacturing_order) ? $manufacturing_order->finished_products_warehouse_id : '';
								$date_deadline = isset($manufacturing_order) ? _dt($manufacturing_order->date_deadline) : '';
								$date_plan_from = isset($manufacturing_order) ? _dt($manufacturing_order->date_plan_from) : '';
								$routing_id_view = isset($manufacturing_order) ? mrp_get_routing_name($manufacturing_order->routing_id) : '';
								$routing_id = isset($manufacturing_order) ? ($manufacturing_order->routing_id) : '';
								

								foreach ($estimate as &$est) {
									$est['formatted'] = format_estimate_number($est['id']);
								}
								unset($est);

								$disabled_edit = [];
								if (isset($manufacturing_order) && $manufacturing_order->status != 'draft') {
									$disabled_edit = ['disabled' => true];
								}
	                            

								?>
									<div class="row">
									    	<div class="col-md-6">
										<div class="form-group">
											<label for="estimate_id"><?php echo _l('estimates'); ?></label>
											<select name="estimate_id" id="estimate_id" class="selectpicker form-control <?php if(isset($manufacturing_order)){ echo 'disabled'; } ?>" 
										
											data-live-search="true" 
											data-width="100%" 
											data-none-selected-text="<?php echo _l('ticket_settings_none_assigned'); ?>">
											
											<option value=""><?php echo _l('dropdown_non_selected_tex'); ?></option>

											<?php foreach($estimate as $e): ?>
												<option value="<?php echo pur_html_entity_decode($e['id']); ?>" 
												<?php 
													if (
													(isset($manufacturing_order) && $manufacturing_order->estimate_id == $e['id']) || 
													(isset($estimate_id) && $estimate_id == $e['id'])
													) { echo 'selected'; } 
												?>>
												<?php echo format_estimate_number($e['id']); ?>
												</option>
											<?php endforeach; ?>
											</select>
										</div>
										</div>
											<div class="col-md-6">
												<?php
												
											
echo render_select(
    'product_id',
    $products,
    ['id', 'description'],
    'product_label',
    $product_id,
    $disabled_edit
);?>

										</div>
									
										

							


										</div>
								<div class="row">
									<div class="row">
										<div class="col-md-6">
											      <label for="clientid"
                                        class="control-label"><?php echo _l('customer_label'); ?></label>
										 <select id="clientid" name="clientid" data-live-search="true" data-width="100%"
                                        class="ajax-search"
                                        data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                        <?php $selected = (isset($manufacturing_order) ? $manufacturing_order->contact_id : '');
if ($selected == '' && isset($customer_id)) {
    $selected = $customer_id;
}?>
<option value=""><?php echo _l('dropdown_non_selected_tex'); ?></option>

<?php
                             if ($selected != '') {
                                 $rel_data = get_relation_data('customer', $selected);
                                 $rel_val  = get_relation_values($rel_data, 'customer');
                                 echo '<option value="' . $rel_val['id'] . '" selected>' . $rel_val['name'] . '</option>';
                             } ?>
                                    </select>
										</div>
										<div class="col-md-6">
											<?php echo render_datetime_input('date_deadline', 'date_deadline', $date_deadline); ?>
										</div>
									</div>

									<div class="row">
										<div class="col-md-6">
											<?php echo render_input('product_qty', 'product_qty', $product_qty, 'number', $disabled_edit); ?>
										</div>
										<div class="col-md-6">
											<?php echo render_datetime_input('date_plan_from', 'date_plan_from', $date_plan_from); ?>
										</div>
									</div>

									<div class="row">
										<div class="col-md-6">
											<?php echo render_select('unit_id', $units, array('unit_type_id', 'unit_name'), 'unit_of_measure', $unit_id, $disabled_edit, [], '', '', false); ?>
										</div>
										<div class="col-md-6">
											<?php echo render_select('staff_id', $staffs, array('staffid', array('firstname', 'lastname')), 'responsible', $staff_id, [], [], '', '', false); ?>
										</div>
									</div>

									<div class="row">
										<div class="col-md-6">
											<?php echo render_select('bom_id', $bill_of_materials, array('id', 'description'), 'bill_of_material_label', $bom_id, $disabled_edit, [], '', '', false); ?>
										</div>
										<div class="col-md-6">
											<?php echo render_input('manufacturing_order_code', 'reference_code', $manufacturing_order_code, '', $disabled_edit); ?>
										</div>
									</div>

									<div class="row">
										<div class="col-md-6">
											<?php echo render_input('routing_id_view', 'routing_label', $routing_id_view, '', ['disabled' => true]); ?>
											<input type="hidden" name="routing_id" value="<?php echo new_html_entity_decode($routing_id) ?>">
										</div>
									
										

									</div>
									
								</div>


								<div class="row">
									<h5 class="h5-color"><?php echo _l('work_center_info'); ?></h5>
									<hr class="hr-color">
								</div>

								<div class="row">
									<div class="horizontal-scrollable-tabs preview-tabs-top">
										<div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
										<div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
										<div class="horizontal-tabs">
											<ul class="nav nav-tabs nav-tabs-horizontal mbot15" role="tablist">
												<li role="presentation" class="active">
													<a href="#component_tab" aria-controls="component_tab" role="tab" data-toggle="tab">
														<span class="glyphicon glyphicon-align-justify"></span>&nbsp;<?php echo _l('tab_component_tab'); ?>
													</a>
												</li>
												<li role="presentation" class="">
													<a href="#finished_product_tab" aria-controls="finished_product_tab" role="tab" data-toggle="tab">
														<span class="fa fa-cogs menu-icon"></span>&nbsp;<?php echo _l('finished_product_tab'); ?>
													</a>
												</li>
												<li role="presentation" class="">
													<a href="#scarp_tab" aria-controls="scarp_tab" role="tab" data-toggle="tab">
														<span class="fa fa-cogs menu-icon"></span>&nbsp;<?php echo _l('scarp_tab'); ?>
													</a>
												</li>
												<li role="presentation" class="">
													<a href="#miscellaneous_tab" aria-controls="miscellaneous_tab" role="tab" data-toggle="tab">
														<span class="fa fa-balance-scale menu-icon"></span>&nbsp;<?php echo _l('miscellaneous_tab'); ?>
													</a>
												</li>

												<li role="presentation" class="">
													<a href="#costing_tab" aria-controls="costing_tab" role="tab" data-toggle="tab">
														<span class="fa fa-balance-scale menu-icon"></span>&nbsp; Costing
													</a>
												</li>

											</ul>
										</div>
									</div>
									<br>


									<div class="tab-content active">
										<div role="tabpanel" class="tab-pane active" id="component_tab">
											<div class="form">
												<div id="product_tab_hs" class="product_tab handsontable htColumnHeaders">
												</div>
												<?php echo form_hidden('product_tab_hs'); ?>
											</div>

										</div>
										<div role="tabpanel" class="tab-pane " id="finished_product_tab">
											<?php echo _l('Use_the_Produce_button_or_process_the_work_orders_to_create_some_finished_products'); ?>
										</div>
										<div role="tabpanel" class="tab-pane " id="scarp_tab">
											<div class="row">
											   
												<?php if (has_permission('manufacturing', '', 'create')&&has_permission('manufacturing_orders', '', 'create')) { ?>
													<div class="col-md-3">
														<div class="_buttons">
															<?php
																/*
																<a href="#" onclick="add_scrap(<?php echo $id; ?>); return false;" class="btn btn-info mbot10"><?php echo _l('add'); ?></a>
																*/
															?>
															<a href="<?php echo admin_url('manufacturing/import_xlsx_contract'); ?>" class=" btn mright5 btn-default pull-left hide">
																<?php echo _l('work_center_import'); ?>
															</a>
														</div>
													</div>
													<br>
												<?php } ?>

											</div>
											<div class="form">
												<div id="scrab_tab_hs" class="scrab_tab handsontable htColumnHeaders">
												</div>
												<?php echo form_hidden('scrab_tab_hs'); ?>
											</div>

										</div>
										<div role="tabpanel" class="tab-pane " id="miscellaneous_tab">
											<div class="row">
												<div class="col-md-12">
													<?php echo render_select('components_warehouse_id', $warehouses, array('warehouse_id', 'warehouse_name'), 'components_warehouse', $components_warehouse_id, ['data-none-selected-text' => _l('mrp_all')], [], '', '', true); ?>
												</div>
												<div class="col-md-12">
													<?php echo render_select('finished_products_warehouse_id', $warehouses, array('warehouse_id', 'warehouse_name'), 'finished_products_warehouse', $finished_products_warehouse_id, [], [], '', '', false); ?>
												</div>

											</div>
										</div>

										<div role="tabpanel" class="tab-pane " id="costing_tab">
										<div class="row">




					</div>
										</div>

									</div>
								</div>

							</div>

							<div class="modal-footer">
								<a href="<?php echo admin_url('manufacturing/manufacturing_order_manage'); ?>" class="btn btn-default mr-2 "><?php echo _l('close'); ?></a>
								<?php if (has_permission('manufacturing', '', 'create')&&has_permission('manufacturing_orders', '', 'create') || has_permission('manufacturing', '', 'edit')&&has_permission('manufacturing_orders', '', 'edit')) { ?>
									<button type="button" class="btn btn-info pull-right add_manufacturing_order"><?php echo _l('submit'); ?></button>

								<?php } ?>
							</div>

						</div>
					</div>
				</div>

				<?php echo form_close(); ?>
			</div>
		</div>
		<div id="modal_wrapper"></div>
		<?php init_tail(); ?>
		<?php
		require('modules/manufacturing/assets/js/manufacturing_orders/add_edit_manufacturing_order_js.php');
		?>
		</body>

		</html>