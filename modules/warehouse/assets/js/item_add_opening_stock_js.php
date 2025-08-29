<script>
	var working_hours;

	$(document).ready(function () {
		setTimeout(function () {
			"use strict";
			$('.work_instruction').click();
		}, 1);
	});

	(function ($) {
		"use strict";

		// Form validation
		appValidateForm($("#add_update_working_hour"), {
			'working_hour_name': 'required',
			'hours_per_day': 'required',
		});

		// Opening stock data
		var dataObject_pu = <?php echo isset($opening_stock_data) ? json_encode($opening_stock_data) : '[]'; ?>;
		console.log('Opening Stock Data:', dataObject_pu); 
		setTimeout(function () {
			var hotElement1 = document.getElementById('item_add_opening_stock_hs');

			working_hours = new Handsontable(hotElement1, {
				licenseKey: 'non-commercial-and-evaluation',
				data: dataObject_pu,
				contextMenu: true,
				manualRowMove: true,
				manualColumnMove: true,
				stretchH: 'none',
				autoWrapRow: true,
				rowHeights: 30,
				defaultRowHeight: 100,
				minRows: <?php echo new_html_entity_decode($min_row); ?>,
				width: '100%',
				height: '350px',
				rowHeaders: true,
				filters: true,
				allowInsertRow: true,
				allowRemoveRow: true,
				columnHeaderHeight: 40,
				minSpareRows: 1,
				colWidths: [40, 300, 200, 200, 200, 220, 150, 160, 170],
				rowHeaderWidth: [44],
				hiddenColumns: {
					columns: [0],
					indicators: true
				},

				columns: [
					{ type: 'text', data: 'id' },

					{
						type: 'text',
						data: 'commodity_id',
						renderer: customDropdownRenderer,
						editor: "chosen",
						chosenOptions: {
							data: <?php echo json_encode($commodity_code_name); ?>
						}
					},

					{
						type: 'text',
						data: 'warehouse_id',
						renderer: customDropdownRenderer,
						editor: "chosen",
						chosenOptions: {
							data: <?php echo json_encode($units_warehouse_name); ?>
						}
					},

					{
	type: 'text',
	data: 'lot_id',
	renderer: customDropdownRenderer,
	editor: "chosen",
	chosenOptions: { data: <?php echo json_encode($lot_list); ?> }
},
{
	type: 'text',
	data: 'rack_id',
	renderer: customDropdownRenderer,
	editor: "chosen",
	chosenOptions: { data: <?php echo json_encode($rack_list); ?> }
},
{
	type: 'text',
	data: 'shelf_id',
	renderer: customDropdownRenderer,
	editor: "chosen",
	chosenOptions: { data: <?php echo json_encode($shelf_list); ?> }
},


					{ type: 'text', data: 'lot_number' },
					{
						type: 'date',
						data: 'expiry_date',
						dateFormat: 'YYYY-MM-DD',
						correctFormat: true,
						defaultDate: "<?php echo _d(date('Y-m-d')) ?>"
					},
					{
						data: 'inventory_number',
						type: 'numeric',
						numericFormat: {
							pattern: '0,0.00',
						},
					}
				],

				colHeaders: [
					"<?php echo _l('id'); ?>",
					"<?php echo _l('commodity_name'); ?>",
					"<?php echo _l('warehouse_name'); ?>",
					"<?php echo _l('lot_name'); ?>",
					"<?php echo _l('rack_name'); ?>",
					"<?php echo _l('shelf_name'); ?>",
					"<?php echo _l('lot_number'); ?>",
					"<?php echo _l('expiry_date'); ?>",
					"<?php echo _l('inventory_number'); ?>"
				],

				afterChange: function (changes, source) {
					if (!changes || source === 'loadData') return;

					changes.forEach(function ([row, prop, oldValue, newValue]) {
						if (prop === 'warehouse_id' && oldValue !== newValue) {
							updateRackLotOptions(row, newValue);
						}
						if (prop === 'rack_id' && oldValue !== newValue) {
							updateShelfOptions(row, newValue);
						}
					});
				}
			});
		}, 300);
	})(jQuery);

	// Custom dropdown renderer to show label, save ID
	function customDropdownRenderer(instance, td, row, col, prop, value, cellProperties) {
		"use strict";
		let options = cellProperties.chosenOptions && cellProperties.chosenOptions.data;
		let label = value;

		if (options && Array.isArray(options)) {
			let match = options.find(opt => opt.id == value);
			label = match ? match.label : value;
		}

		Handsontable.renderers.TextRenderer.apply(this, [instance, td, row, col, prop, label, cellProperties]);
		return td;
	}

	// Submit button
	$('.btn_add_opening_stock').on('click', function () {
		'use strict';
		var hasInvalid = $('#item_add_opening_stock_hs').find('.htInvalid').length > 0;

		if (hasInvalid) {
			alert_float('danger', "<?php echo _l('data_must_number'); ?>");
		} else {
			$(this).attr("disabled", "disabled");
			$('input[name="item_add_opening_stock_hs"]').val(JSON.stringify(working_hours.getData()));
			$('#add_opening_stock').submit();
		}
	});

	// Load Rack + Lot from selected Warehouse
	function updateRackLotOptions(row, warehouse_id) {
		$.ajax({
			url: admin_url + 'warehouse/get_rack_lot_by_warehouse',
			type: 'POST',
			data: { warehouse_id: warehouse_id },
			dataType: 'json',
			success: function (response) {
				console.log('Rack/Lot response:', response);
				working_hours.setCellMeta(row, 3, 'chosenOptions', { data: response.lots });   // lot_id
				working_hours.setCellMeta(row, 4, 'chosenOptions', { data: response.racks });  // rack_id
				working_hours.render();
			}
		});
	}

	// Load Shelves from selected Rack
	function updateShelfOptions(row, rack_id) {
		$.ajax({
			url: admin_url + 'warehouse/get_shelf_by_rack',
			type: 'POST',
			data: { rack_id: rack_id },
			dataType: 'json',
			success: function (response) {
				console.log('Shelf response:', response);
				working_hours.setCellMeta(row, 5, 'chosenOptions', { data: response.shelves }); // shelf_id
				working_hours.render();
			}
		});
	}
</script>
