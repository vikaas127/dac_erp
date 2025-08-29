
<script>

	"use strict";

	var InvoiceServerParams={
		"bill_of_material_id": "[name='bill_of_material_id']",
		"bill_of_material_product_id": "[name='bill_of_material_product_id']",
		"bill_of_material_routing_id": "[name='bill_of_material_routing_id']",
	};



  var bill_of_material_detail_table = $('#bill_of_material_detail_table');
  var bill_of_material_scrap_table = $('#bill_of_material_scrap_table');

    // Initialize DataTable
    

 function loadBillOfMaterialDetails(bomId) {
    requestGetJSON('manufacturing/view_bill_of_material_detail_json/' + bomId)
        .done(function(response) {
            if (response.status === 'success') {
                // Clear existing table rows
                var tableBody = $('#bill_of_material_detail_table tbody');
                tableBody.empty();

                // Variable to hold the total price
                var totalPrice = 0;
                const unitMapping = {};
                response.bill_of_material.units.forEach(unit => {
                    unitMapping[unit.unit_type_id] = unit.unit_name;
                });

                // Get the total quantity for the product
                const totalProductQuantity = response.bill_of_material.quality || 1;
                console.log(`product Quantity ${JSON.stringify(response.bill_of_material)}`);

                response.bill_of_material.components.forEach(function(component, index) {
                    // Adjust product quantity for one unit
                    const adjustedProductQty = (component.product_qty || 0) / totalProductQuantity;

                    // Calculate the total for this component (price * quantity)
                    var componentTotal = (component.price || 0) * adjustedProductQty;
                    totalPrice += componentTotal; // Add to the total price

                    const unitName = unitMapping[component.product_unit] || 'Unknown Unit';

                    var row = `<tr>
                        <td>${component.product_id}</td>
                        <td>${index + 1}</td>
                        <td>${component.product_name}</td>
                        <td class="text-right">${adjustedProductQty.toFixed(2)}</td>
                        <td>${unitName}</td>
                        <td class="text-right">${component.price || ''}</td>
                        <td class="text-right">${componentTotal.toFixed(2)}</td>
                    </tr>`;
                    tableBody.append(row);
                });

                // Add a row for the total price at the end
                var totalRow = `<tr>
                    <td colspan="6" class="text-right"><strong>Total Price (Per Unit):</strong></td>
                    <td class="text-right"><strong>${totalPrice.toFixed(2)}</strong></td>
                </tr>`;
                tableBody.append(totalRow);
                //total for all products 
                var ttotalRow = `<tr>
                    <td colspan="6" class="text-right"><strong>Total Price:</strong></td>
                    <td class="text-right"><strong>${totalPrice.toFixed(2)*totalProductQuantity}</strong></td>
                </tr>`;
                tableBody.append(ttotalRow);
            } else {
                alert(response.message);
            }
        })
        .fail(function(xhr, status, error) {
            console.error("Error fetching Bill of Material details:", error);
        });
}




$(document).ready(function() {
    var bomId = $('input[name="bill_of_material_id"]').val();
    if (bomId) {
       // loadBillOfMaterialDetails(bomId);
    }
});

	var bill_of_material_detail_table = $('.table-bill_of_material_detail_table');
	initDataTable(bill_of_material_detail_table, admin_url+'manufacturing/bill_of_material_detail_table',[0],[0], InvoiceServerParams, [1 ,'asc']);
	var bill_of_material_scrap_table = $('.table-bill_of_material_scrap_table');
	initDataTable(bill_of_material_scrap_table, admin_url+'manufacturing/bill_of_material_scrap_table',[0],[0], InvoiceServerParams, [1 ,'asc']);

	$('#date_add').on('change', function() {
		bill_of_material_detail_table.DataTable().ajax.reload().columns.adjust().responsive.recalc();
	});

	var hidden_columns = [1];
	$('.table-bill_of_material_detail_table').DataTable().columns(hidden_columns).visible(false, false);

function add_component(bill_of_material_id, component_id, product_id, routing_id, type) {
	"use strict";

	  $("#modal_wrapper").load("<?php echo admin_url('manufacturing/manufacturing/bill_of_material_detail_modal'); ?>", {
	       bill_of_material_id: bill_of_material_id,
	       component_id: component_id,
	       bill_of_material_product_id: product_id,
	       routing_id: routing_id,
	       type: type
	  }, function() {

	       $("body").find('#appointmentModal').modal({ show: true, backdrop: 'static' });
	  });

	  init_selectpicker();
	  $(".selectpicker").selectpicker('refresh');

}





function open_scrap_modal(bill_of_material_id, component_id, product_id, routing_id, type) {
  	"use strict";

	  $("#modal_wrapper").load("<?php echo admin_url('manufacturing/manufacturing/add_scrap_modal'); ?>", {
	       bill_of_material_id: bill_of_material_id,
	       component_id: component_id,
	       bill_of_material_product_id: product_id,
	       routing_id: routing_id,
	       type: type
	  }, function() {

	       $("body").find('#scrapModal').modal({ show: true, backdrop: 'static' });
	  });

	  init_selectpicker();
	  $(".selectpicker").selectpicker('refresh');

}

	$('input[name="bom_type"]').on('click', function() {
	"use strict";
		
		var bom_type =$(this).val();

		if(bom_type == 'manufacture_this_product'){
			$('.kit_hide').addClass('hide');
		}else if(bom_type == 'kit'){
			$('.kit_hide').removeClass('hide');

		}
	});   

	function staff_bulk_actions(){
		"use strict";
		$('#bill_of_material_detail_table_bulk_actions').modal('show');
	}


	// Leads bulk action
	function staff_delete_bulk_action(event) {
		"use strict";

		if (confirm_delete()) {
			var mass_delete = $('#mass_delete').prop('checked');

			if(mass_delete == true){
				var ids = [];
				var data = {};

				data.mass_delete = true;
				data.rel_type = 'component_bill_of_material';

				var rows = $('#table-bill_of_material_detail_table').find('tbody tr');
				$.each(rows, function() {
					var checkbox = $($(this).find('td').eq(0)).find('input');
					if (checkbox.prop('checked') === true) {
						ids.push(checkbox.val());
					}
				});

				data.ids = ids;
				$(event).addClass('disabled');
				setTimeout(function() {
					$.post(admin_url + 'manufacturing/mrp_product_delete_bulk_action', data).done(function() {
						window.location.reload();
					}).fail(function(data) {
						$('#bill_of_material_detail_table_bulk_actions').modal('hide');
						alert_float('danger', data.responseText);
					});
				}, 200);
			}else{
				window.location.reload();
			}

		}
	}


</script>

<script>
$(document).ready(function() {
    $("#save_costing").click(function() {
        var formData = {
            bill_of_material_id: "<?php echo $bill_of_material->id; ?>",
            labour_charges: $("#labour_charges").val(),
            electricity_charges: $("#electricity_charges").val(),
            machinery_charges: $("#machinery_charges").val(),
            other_charges: $("#other_charges").val(),
            labour_charges_description: $("#labour_charges_description").val(),
            electricity_charges_description: $("#electricity_charges_description").val(),
            machinery_charges_description: $("#machinery_charges_description").val(),
            other_charges_description: $("#other_charges_description").val()
        };

        $.ajax({
            url: "<?php echo admin_url('manufacturing/bom_costing'); ?>",
            type: "POST",
            data: formData,
            success: function(response) {
                console.log("AJAX response:", response); // Debugging
                try {
                    var result = JSON.parse(response);
                    if (result.status == "success") {
                        alert("BOM Updated Successfully!");
                    } else {
                        alert("Error: " + result.message);
                    }
                } catch (e) {
                    alert("Invalid server response! Check console for details.");
                    console.error("Error parsing JSON:", e, response);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX request failed:", status, error);
                alert("Error updating Work Order. Check console for details.");
            }
        });
    });
});

</script>

