<script>
	appValidateForm($("body").find('#add_update_manufacturing_order'), {
		'product_id': 'required',
		'product_qty': 'required',
		'unit_id': 'required',
		'bom_id': 'required',
		'manufacturing_order_code': 'required',
		'finished_products_warehouse_id': 'required',
		'date_plan_from': 'required',
		'date_deadline': 'required',

		// Add validation for Scrap Tab fields
		'scrap_type': 'required',
		'item_type': 'required',
		'estimated_quantity': 'required',
		'reason': 'required',
	});

	var product_tabs, scrap_tabs;

	(function($) {
		"use strict";


		<?php if (isset($product_tab_details)) { ?>
			var dataObject_pu = <?php echo json_encode($product_tab_details); ?>;
			console.log("Initializing Product data Tab Data:", dataObject_pu);
		<?php } else { ?>
			var dataObject_pu = [];
		<?php } ?>

		var hotElement1 = document.getElementById('product_tab_hs');

		product_tabs = new Handsontable(hotElement1, {
			licenseKey: 'non-commercial-and-evaluation',

			contextMenu: true,
			manualRowMove: true,
			manualColumnMove: true,
			stretchH: 'all',
			autoWrapRow: true,
			rowHeights: 30,
			defaultRowHeight: 100,
			minRows: 5,
			maxRows: 40,
			width: '100%',

			rowHeaders: true,
			colHeaders: true,
			autoColumnSize: {
				samplingRatio: 23
			},

			filters: true,
			manualRowResize: true,
			manualColumnResize: true,
			allowInsertRow: true,
			allowRemoveRow: true,
			columnHeaderHeight: 40,

			rowHeights: 30,
			rowHeaderWidth: [44],
			minSpareRows: 1,
			hiddenColumns: {
				columns: [0],
				indicators: true
			},

			columns: [{
					type: 'text',
					data: 'id',
				},
				{
					type: 'text',
					data: 'product_id',
					renderer: customDropdownRenderer,
					editor: "chosen",
					chosenOptions: {
						data: <?php echo json_encode($product_for_hansometable); ?>
					},
				},
				{
					type: 'text',
					data: 'unit_id',
					renderer: customDropdownRenderer,
					editor: "chosen",
					chosenOptions: {
						data: <?php echo json_encode($unit_for_hansometable); ?>
					},
				},
{
  data: 'warehouse_id',
  type: 'dropdown',
  source: function (query, process) {
    const row = this.instance.getSourceDataAtRow(this.row);
    const pid = row.product_id;

    fetch('/manufacturing/get_inventory_dropdowns/' + pid)
      .then(response => response.json())
      .then(data => {
        console.log('Fetched inventory:', data);
        row._invData = data;
        process(data.map(w => w.label));
      });
  },
  strict: true
},
{
  data: 'lot_id',
  type: 'dropdown',
  source: function (query, process) {
    const row = this.instance.getSourceDataAtRow(this.row);
    const lots = row._selectedWarehouse?.lots || [];
    console.log('Lots for selected warehouse:', lots);
    process(lots.map(l => l.label));
  },
  strict: true
},
{
  data: 'rack_id',
  type: 'dropdown',
  source: function (query, process) {
    const row = this.instance.getSourceDataAtRow(this.row);
    const racks = row._selectedWarehouse?.racks || [];
    console.log('Racks for selected warehouse:', racks);
    process(racks.map(r => r.label));
  },
  strict: true
},
{
  data: 'shelf_id',
  type: 'dropdown',
  source: function (query, process) {
    const row = this.instance.getSourceDataAtRow(this.row);
    const shelves = row._selectedWarehouse?.shelves || [];
    console.log('Shelves for selected warehouse:', shelves);
    process(shelves.map(s => s.label));
  },
  strict: true
},


				{
					data: 'qty_to_consume',
					type: 'numeric',
					numericFormat: {
						pattern: '0,0.00',
					},
				},
				{
					data: 'qty_reserved',
					type: 'numeric',
					numericFormat: {
						pattern: '0,0.00',
					},
				},

				{
					data: 'qty_done',
					type: 'numeric',
					numericFormat: {
						pattern: '0,0.00',
					},
				},


			],

			colHeaders: [

				'<?php echo _l('id'); ?>',
				'<?php echo _l('product_label'); ?>',
				'<?php echo _l('unit_id'); ?>',
				'<?php echo _l('warehouse_id'); ?>',
				'<?php echo _l('lot'); ?>',
				'<?php echo _l('rack'); ?>',
				'<?php echo _l('shelf'); ?>',
				'<?php echo _l('qty_to_consume'); ?>',
				'<?php echo _l('qty_reserved'); ?>',
				'<?php echo _l('qty_done'); ?>',

			],
			afterChange: function (changes, source) {
  if (source === 'edit') {
    changes.forEach(([rowIndex, prop, oldVal, newVal]) => {
      if (prop === 'warehouse_id') {
        const row = this.getSourceDataAtRow(rowIndex);
        const warehouses = row._invData || [];

        const selected = warehouses.find(w => w.label === newVal);
        console.log('Selected warehouse:', selected);
        row._selectedWarehouse = selected;

        // Optionally reset dependent dropdowns
        row.lot_id = null;
        row.rack_id = null;
        row.shelf_id = null;

        this.render(); // refresh the table
      }
    });
  }
},


			data: dataObject_pu,
		});
		
		// Initialize Product Tab Details


		// Initialize Scrap Tab Details

		var scrapData = <?php echo isset($product_for_scrap) ? json_encode($product_for_scrap) : '[]'; ?>;
		console.log("Initializing Scrap Tab Data:", scrapData);

		var hotElement2 = document.getElementById('scrab_tab_hs');

		scrap_tabs = new Handsontable(hotElement2, {
			licenseKey: 'non-commercial-and-evaluation',
			contextMenu: true,
			manualRowMove: true,
			manualColumnMove: true,
			stretchH: 'all',
			autoWrapRow: true,
			defaultRowHeight: 100,
			minRows: 5,
			maxRows: 40,
			width: '100%',

			rowHeaders: true,
			autoColumnSize: {
				samplingRatio: 23
			},

			filters: true,
			manualRowResize: true,
			manualColumnResize: true,
			allowInsertRow: true,
			allowRemoveRow: true,
			columnHeaderHeight: 40,

			rowHeights: 30,
			rowHeaderWidth: [44],
			minSpareRows: 1,
			hiddenColumns: {
				columns: [0],
				indicators: true
			},

			columns: [{
					type: 'text',
					data: 'id',
				},
				{
					type: 'text',
					data: 'product_id',
					renderer: customDropdownRenderer,
					editor: "chosen",
					chosenOptions: {
						data: <?php echo json_encode($product_for_hansometable); ?>
					},
				},
				{
					type: 'text',
					data: 'unit_id',
					renderer: customDropdownRenderer,
					editor: "chosen",
					chosenOptions: {
						data: <?php echo json_encode($unit_for_hansometable); ?>
					},
				},

				{
					data: 'scrap_type',
					type: 'dropdown',
					source: ['Reuse', 'Waste'],
					strict: true,
				},
				

				{
					data: 'estimated_quantity',
					type: 'numeric',
					numericFormat: {
						pattern: '0,0.00'
					}
				},
				// { 
				// 	data: 'actual_quantity', 
				// 	type: 'numeric', 
				// 	numericFormat: { pattern: '0,0.00' }, 
				// 	readOnly: true 
				// },
				// { 
				// 	data: 'cost_allocation', 
				// 	type: 'numeric', 
				// 	numericFormat: { pattern: '0,0.00' }, 
				// 	readOnly: true 
				// },
				{
					data: 'reason',
					type: 'text',
					readOnly: true
				},



			],

			colHeaders: [

				'<?php echo _l('id'); ?>',
				'<?php echo _l('product_label'); ?>',
				'<?php echo _l('unit_id'); ?>',
				'<?php echo _l('scrap_type'); ?>',
			
				'<?php echo _l('estimated_Quantity'); ?>',
			
				'<?php echo _l('comment'); ?>',

			],

			data: scrapData,

		});

	})(jQuery);

	function customDropdownRenderer(instance, td, row, col, prop, value, cellProperties) {
		"use strict";
		var selectedId;
		var optionsList = cellProperties.chosenOptions.data;

		if (typeof optionsList === "undefined" || typeof optionsList.length === "undefined" || !optionsList.length) {
			Handsontable.cellTypes.text.renderer(instance, td, row, col, prop, value, cellProperties);
			return td;
		}

		var values = (value + "").split("|");
		value = [];
		for (var index = 0; index < optionsList.length; index++) {

			if (values.indexOf(optionsList[index].id + "") > -1) {
				selectedId = optionsList[index].id;
				value.push(optionsList[index].label);
			}
		}
		value = value.join(", ");

		Handsontable.cellTypes.text.renderer(instance, td, row, col, prop, value, cellProperties);
		return td;
	}
	
	$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
    var target = $(e.target).attr("href"); // example: #component_tab or #scarp_tab

    setTimeout(function () {
        if (target === '#component_tab' && typeof product_tabs !== 'undefined') {
            product_tabs.render();
            product_tabs.refreshDimensions();
        }

        if (target === '#scarp_tab' && typeof scrap_tabs !== 'undefined') {
            scrap_tabs.render();
            scrap_tabs.refreshDimensions();
        }
    }, 100); // short delay to let layout settle
});





	//get related data for manufacturing order
	$('select[name="product_id"]').on('change', function() {
		"use strict";

		var product_id = $(this).val();

		$.get(admin_url + 'manufacturing/get_data_create_manufacturing_order/' + product_id, function(response) {
			$("select[name='bom_id']").html('');
			$("select[name='bom_id']").append(response.bill_of_material_option);

			$("input[name='routing_id_view']").val(response.routing_name);
			$("input[name='routing_id']").val(response.routing_id);
			$("select[name='unit_id']").val(response.unit_id).selectpicker('refresh');
			$("#expected_labour_charges").text(response.expected_labour_charges);
			$("#expected_machinery_charges").text(response.expected_machinery_charges);
			$("#expected_electricity_charges").text(response.expected_electricity_charges);
			$("#expected_other_charges").text(response.expected_other_charges);

			console.log('Expected Charges:', {
			labour: response.expected_labour_charges,
			machinery: response.expected_machinery_charges,
			electricity: response.expected_electricity_charges,
			other: response.expected_other_charges
			});

			product_tabs.updateSettings({
				data: response.component_arr,
				// maxRows: response.component_row,
			});
          if (response.scrap_arr) {
            scrap_tabs.updateSettings({
                data: response.scrap_arr,
                // maxRows: response.scrap_row,
            });
        } else {
            console.log("No Scrap Data Found!");
        }


			init_selectpicker();
			$(".selectpicker").selectpicker('refresh');


		}, 'json');

	});






	$('select[name="bom_id"]').on('change', function() {
		"use strict";

		var bill_of_material_id = $(this).val();
		var product_id = $('select[name="product_id"]').val();
		var product_qty = $('input[name="product_qty"]').val();

		$.get(admin_url + 'manufacturing/get_bill_of_material_detail/' + bill_of_material_id + '/' + product_id + '/' + product_qty, function(response) {

			product_tabs.updateSettings({
				data: response.component_arr,
				// maxRows: response.component_row,
			});
			 // Update scrap table (ensure `scrap_tabs` exists)
        if (typeof scrap_tabs !== 'undefined') {
            scrap_tabs.updateSettings({
                data: response.scrap_arr,
                // maxRows: response.scrap_row,
            });
        }

			$("input[name='routing_id_view']").val(response.routing_name);
			$("input[name='routing_id']").val(response.routing_id);


			init_selectpicker();
			$(".selectpicker").selectpicker('refresh');

		}, 'json');
	});

	

	$('input[name="product_qty"]').on('change input', function () {
    "use strict";

    var product_qty = parseFloat($(this).val()) || 0;
    var product_id = $('select[name="product_id"]').val();
    var bill_of_material_id = $('select[name="bom_id"]').val();

    $.get(admin_url + 'manufacturing/get_bill_of_material_detail/' + bill_of_material_id + '/' + product_id + '/' + product_qty, function (response) {

        product_tabs.updateSettings({
            data: response.component_arr,
        });

        if (typeof scrap_tabs !== 'undefined' && scrap_tabs) {
            let updatedScrapArr = response.scrap_arr.map(scrap => {
                scrap.estimated_quantity = (scrap.estimated_quantity * product_qty); // Adjust scrap quantity
                return scrap;
            });

            scrap_tabs.updateSettings({
                data: updatedScrapArr,
            });
        }

        $("input[name='routing_id_view']").val(response.routing_name);
        $("input[name='routing_id']").val(response.routing_id);

        // Save unit charges in data attribute for later reuse
        $("#expected_labour_charges").data('unit', response.unit_labour_charges || response.expected_labour_charges).text(response.expected_labour_charges);
        $("#expected_machinery_charges").data('unit', response.unit_machinery_charges || response.expected_machinery_charges).text(response.expected_machinery_charges);
        $("#expected_electricity_charges").data('unit', response.unit_electricity_charges || response.expected_electricity_charges).text(response.expected_electricity_charges);
        $("#expected_other_charges").data('unit', response.unit_other_charges || response.expected_other_charges).text(response.expected_other_charges);

        // Now update real-time based on input qty
        updateExpectedCharge('#expected_labour_charges', product_qty);
        updateExpectedCharge('#expected_machinery_charges', product_qty);
        updateExpectedCharge('#expected_electricity_charges', product_qty);
        updateExpectedCharge('#expected_other_charges', product_qty);

        init_selectpicker();
        $(".selectpicker").selectpicker('refresh');
    }, 'json');
});



	

	$('.add_manufacturing_order').on('click', function() {
    "use strict";

		// Get manually entered actual charges and descriptions
		var actualCharges = {
			labour: $('#labour_charges').val(),
			machinery: $('#machinery_charges').val(),
			electricity: $('#electricity_charges').val(),
			other: $('#other_charges').val(),
		};

		var chargeDescriptions = {
			labour: $('#labour_charges_description').val(),
			machinery: $('#machinery_charges_description').val(),
			electricity: $('#electricity_charges_description').val(),
			other: $('#other_charges_description').val(),
		};

		// Get Handsontable data (scrap)
		var scrapData = scrap_tabs.getData().filter(row =>
			row.some(cell => cell !== "" && cell !== null && cell !== 0)
		);

		scrapData = scrapData.map(row => row.map((cell, index) =>
			index === 5 && (cell === "" || cell === null || isNaN(cell)) ? 0 : cell ?? ''
		));

		// Get Handsontable data (product)
		var productTabsData = product_tabs.getData().filter(row =>
			row.some(cell => cell !== "" && cell !== null && cell !== 0)
		);

		// Assign cleaned data to hidden fields
		$('input[name="scrab_tab_hs"]').val(JSON.stringify(scrapData));
		$('input[name="product_tab_hs"]').val(JSON.stringify(productTabsData));
		$('input[name="labour_charges"]').val(actualCharges.labour);
		$('input[name="machinery_charges"]').val(actualCharges.machinery);
		$('input[name="electricity_charges"]').val(actualCharges.electricity);
		$('input[name="other_charges"]').val(actualCharges.other);

    $('input[name="labour_charges_description"]').val(chargeDescriptions.labour);
    $('input[name="machinery_charges_description"]').val(chargeDescriptions.machinery);
    $('input[name="electricity_charges_description"]').val(chargeDescriptions.electricity);
    $('input[name="other_charges_description"]').val(chargeDescriptions.other);

    // Submit the form
    $('#add_update_manufacturing_order').submit();
});




	function add_scrap(id) {
		$("#modal_wrapper").load("<?php echo admin_url('manufacturing/manufacturing/add_scrap_modal/'); ?>" + id, {
			slug: 'add',
			moi: $('body input[name="moi"]').val(),
		}, function() {
			$("body").find('#scrapModal').modal({
				show: true,
				backdrop: 'static'
			});
		});
		init_selectpicker();
		$(".selectpicker").selectpicker('refresh');
	}
	
	"use strict";
$(function () {
  const estimateToClientMap = <?php
    $map = [];
    foreach ($estimate as $e) {
      $client = get_client($e['clientid']); // optional if you want names
      $map[$e['id']] = [
        'id' => $e['clientid'],
        'name' => $client->company ?? ('Customer ' . $e['clientid']),
      ];
    }
    echo json_encode($map);
  ?>;

  const $estimateSelect = $('#estimate_id');
  const $clientSelect = $('#clientid');

function updateProductDropdown(estimateItems) {
  const $productSelect = $('select[name="product_id"]');
  $productSelect.prop('disabled', true).empty(); // Clear + disable during update

  // Add default option
  $productSelect.append(new Option('-- Select Product --', ''));

  estimateItems.forEach(item => {
    const label = item.description || 'Unnamed Product';
    const value = item.item_id;

    if (value) {
      $productSelect.append(new Option(label, value));
    }
  });

  $productSelect.prop('disabled', false).selectpicker('refresh');

  if (estimateItems.length === 1) {
    $productSelect.val(estimateItems[0].item_id);
    $productSelect.trigger('change');
  }

}


function filterProductsByEstimate(estimateId) {
   
  $.get(admin_url + 'manufacturing/get_products_by_estimate/' + estimateId, function (response) {
    const estimateItems = Array.isArray(response) ? response : (response.items || []);
     console.log('estimateItems fetching estimate items:', estimateItems);
    updateProductDropdown(estimateItems);
  }).fail(function (err) {
    console.error('Error fetching estimate items:', err);
  });
}



 // When an estimate is selected
 $estimateSelect.on('change', function () {
  const estimateId = $(this).val();

  if (estimateId) {
    filterProductsByEstimate(estimateId);
  }

  if (estimateId && estimateToClientMap[estimateId]) {
    const clientData = estimateToClientMap[estimateId];

    // Update customer dropdown
    $clientSelect.html('');
    $clientSelect.append(new Option(clientData.name, clientData.id));
    $clientSelect.val(clientData.id).attr('disabled', true).selectpicker('refresh');
  } else {
    // If no estimate selected, reset customer dropdown
    $clientSelect.html('');
    $clientSelect.append(new Option('<?php echo _l('dropdown_non_selected_tex'); ?>', ''));
    $clientSelect.prop('disabled', false).selectpicker('refresh');
  }
});



  // If user changes client manually, clear the estimate
  $clientSelect.on('change', function () {
    if (!$clientSelect.prop('disabled')) {
      $estimateSelect.val('').selectpicker('refresh');
    }
  });


});



</script>
