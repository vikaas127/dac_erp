<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">

			<div class="col-md-12">
				<div class="row">
					<div class="panel_s">
						<?php 

						$routing_id = isset($routing) ? $routing->id : '';
						$routing_code = isset($routing) ? $routing->routing_code : '';
						$routing_name = isset($routing) ? $routing->routing_name : '';
						$description = isset($routing) ? $routing->description : '';
						?>

						<div class="panel-body">
							<div class="col-md-12">
								<h4 class="no-margin col-md-8">
									<?php echo new_html_entity_decode($routing_code); ?>	
								</h4>
								<div style="margin-bottom: 15px;" class="col-md-4">
									<a  href="<?php echo admin_url('manufacturing/routing_manage'); ?>"  class="btn btn-default pull-right mright5 "><?php echo _l('hr_close'); ?></a>
								</div>
							
							</div>
							

							<hr class="hr-panel-heading" />

							<div class="row">
								<div class="col-md-6">
									<?php echo render_input('routing_code','routing_code', $routing_code,'text', ['readonly' => 'readonly']); ?>   
								</div>
								<div class="col-md-6">
									<?php echo render_input('routing_name','routing_name', $routing_name,'text', ['readonly' => 'readonly']); ?>   
								</div>

								<div class="col-md-12">

									<p class="bold"><?php echo _l('routing_description'); ?></p>
									<?php
               					// onclick and onfocus used for convert ticket to task too
									echo render_textarea('description','',($description), ['readonly' => 'readonly']); ?>
								</div>	
							</div>
							<hr />
							
						</div>
						
					</div>

				</div>
			</div>

			<div class="col-md-12">
				<div class="row">

					<div class="panel_s"> 
						<div class="panel-body">

							<div class="row">
								<div class="col-md-12">
									<h4 class="h4-color no-margin"><i class="fa fa-shirtsinbulk" aria-hidden="true"></i> <?php echo _l('operations'); ?></h4>
								</div>
							</div>
							<hr class="hr-color">
							<?php render_datatable(array(
								_l('id'),
								_l('display_order'),
								_l('operation'),
								_l('work_center_name'),
								_l('duration_computation'),
							),'operation_table_view'); ?>
						</div>

					</div>
				</div>
				<div id="modal_wrapper"></div>
			</div>


		</div>
	</div>
</div>
<div id="contract_file_data"></div>

<?php echo form_hidden('routing_id',$routing_id); ?>
<?php init_tail(); ?>
<?php 
require('modules/manufacturing/assets/js/routings/add_edit_routing_js.php');
require('modules/manufacturing/assets/js/routings/routing_details/operation_manage_js.php');

?>
</body>
</html>
