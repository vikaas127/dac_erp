<script>
    
   $(document).ready(function() {
    "use strict";

    // Initialize select pickers
    init_selectpicker();
    $(".selectpicker").selectpicker('refresh');

    // Form validation
    appValidateForm($("#add_scrap_form"), {
        'product_id': 'required',
        'product_qty': 'required',
        'unit_id': 'required',
        'display_order': 'required',
    });

    // Fetch product variants on product change
    $('select[name="product_id"]').on('change', function() {
        var product_id = $(this).val();

        if (product_id) {
            $.get(admin_url + 'manufacturing/get_product_variants/' + product_id, function(response) {
                if (response.unit_id) {
                    $("select[name='unit_id']").val(response.unit_id).selectpicker('refresh');
                }
            }, 'json');
        }
    });

    // Handle modal show event and set dynamic values
    $('#scrapModal').on('show.bs.modal', function(event) {
        var button = $(event.relatedTarget); // Button that triggered the modal

        // Get data attributes from the button or set defaults
        var billOfMaterialId = button.data('bill_of_material_id') || '';
       
        var billOfMaterialProductId = button.data('bill_of_material_product_id') || '';
        var routingId = button.data('routing_id') || '';
        

        // Set values in hidden inputs
        $('#bill_of_material_id').val(billOfMaterialId);
       
        $('#bill_of_material_product_id').val(billOfMaterialProductId);
        $('#routing_id').val(routingId);
       
    });

});

	
     </script>