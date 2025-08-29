<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">
        <?php $bill_of_material_id = isset($bill_of_material) ? $bill_of_material->id : '';
?>
			<div class="col-md-12">
				<div class="row">
				      <h2 class="text-center">Bill Of Material</h2>
                        <p class="text-center">
                            Date: <?= date('d M Y') ?><br>

                        <a href="<?php echo admin_url('manufacturing/bill_of_material_detail_manage/' . $bill_of_material_id); ?>" class="btn btn-info"><?php echo _l('Edit'); ?></a>

                        

                         
					<div class="panel_s">
						<?php 

						$bill_of_material_id = isset($bill_of_material) ? $bill_of_material->id : '';
						$product_id = isset($bill_of_material) ? $bill_of_material->product_id : '';
						$product_variant_id = isset($bill_of_material) ? $bill_of_material->product_variant_id : '';
						$product_qty = isset($bill_of_material) ? $bill_of_material->product_qty : '';
						$unit_id = isset($bill_of_material) ? $bill_of_material->unit_id : '';
						$routing_id = isset($bill_of_material) ? $bill_of_material->routing_name : '';
						$bom_code = isset($bill_of_material) ? $bill_of_material->bom_code : '';
                        

						$bom_type = isset($bill_of_material) ? $bill_of_material->bom_type : '';

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
					
					<div class="panel-body">
    
  

    <div class="table-responsive">
        <table class="table table-bordered">
            <tbody>
                 <tr>
                    <th><?php echo _l('Product Name'); ?></th>
                 
                 <td>
                    <h4 class="no-margin">
        <?php echo new_html_entity_decode(mrp_get_product_name($product_id) . $product_variant_name); ?>
        </h4> 
                 </td>   
                </tr>
                <tr>
                    <th><?php echo _l('BOM Code'); ?></th>
                    <td><?php echo htmlspecialchars($bom_code); ?></td>
                </tr>
                <tr>
                    <th><?php echo _l('Product ID'); ?></th>
                    <td><?php echo htmlspecialchars($product_id); ?></td>
                </tr>
                <tr>
                    <th><?php echo _l('Product Variant Name'); ?></th>
                    <td><?php echo htmlspecialchars($product_variant_name); ?></td>
                </tr>
                <tr>
                    <th><?php echo _l('Product Variant ID'); ?></th>
                    <td><?php echo htmlspecialchars($product_variant_id); ?></td>
                </tr>
                <tr>
                    <th><?php echo _l('Product Quantity'); ?></th>
                    <td><?php echo htmlspecialchars($product_qty); ?></td>
                </tr>
              
                <!--<tr>
                    <th><?php echo _l('Unit ID'); ?></th>
                    <td><?php echo htmlspecialchars($unit_id); ?></td>
                </tr>
                <tr>
                    <th><?php echo _l('Routing ID'); ?></th>
                    <td><?php echo htmlspecialchars($routing_id); ?></td>
                </tr>-->
                <tr>
                    <th><?php echo _l('BOM Type'); ?></th>
                    <td><?php echo htmlspecialchars($bom_type == 'manufacture_this_product' ? 'Manufacture This Product' : 'Kit'); ?></td>
                </tr>
                <tr>
                    <th><?php echo _l('Consumption Type'); ?></th>
                    <td><?php echo htmlspecialchars($consumption_type == 'Flexible' ? ' Flexible' : 'Strict'); ?></td>
                </tr>
                 <tr>
                  <td class="bold"><?php echo _l('print'); ?></td>
                  <!-- <td>
                       <button id="exportToPDF" onclick="exportBillOfMaterialToPDF()">Export to PDF</button>
                  </td> -->
                 <td>
                    <div class="btn-group">
                      <a href="#" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-file-pdf"></i><?php if(is_mobile()){echo ' PDF';} ?> <span class="caret"></span></a>
                      <ul class="dropdown-menu dropdown-menu-right">
                       <li class="hidden-xs"><a href="<?php echo admin_url('manufacturing/bom_export_pdf/'.$bill_of_material_id.'?output_type=I'); ?>"><?php echo _l('view_pdf'); ?></a></li>
                       <li class="hidden-xs"><a href="<?php echo admin_url('manufacturing/bom_export_pdf/'.$bill_of_material_id.'?output_type=I'); ?>" target="_blank"><?php echo _l('view_pdf_in_new_window'); ?></a></li>
                       <li><a href="<?php echo admin_url('manufacturing/bom_export_pdf/'.$bill_of_material_id); ?>"><?php echo _l('download'); ?></a></li>
                       <li>
                        <a href="<?php echo admin_url('manufacturing/bom_export_pdf/'.$bill_of_material_id.'?print=true'); ?>" target="_blank">
                          <?php echo _l('print'); ?>
                        </a>
                      </li>
                    </ul>
                  </div>

                </td>
              </tr>
            </tbody>
        </table>
    </div>

 




				
					<div class="row">

					<div class="panel_s"> 
						<div class="panel-body">

                       <div class="row">
                        <h4><?php echo _l('components'); ?></h4>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th><?php echo _l('component'); ?></th>
                                    <th><?php echo _l('unit_id'); ?></th>

                                    <th><?php echo _l('product_qty'); ?></th>
                                    <th><?php echo _l('price'); ?></th>
                                    <th> Sub-Total</th>

                                </tr>
                            </thead>
                            <tbody>
                            <?php $i = 1; ?>
                                <?php foreach ($components as $component) { ?>
                                    <tr>
                                        <td><?php echo $i++; ?></td>
                                        <td><?php echo html_escape($component['product_name']); ?></td>
                                        <td><?php echo html_escape($component['product_unit']); ?></td>
                                        <td><?php echo html_escape($component['product_qty']); ?></td>

                                        <td><?php echo html_escape($component['price']); ?></td>
                                        <td><?php echo html_escape($component['subtotal_cost']); ?></td>

                                    </tr>
                                <?php } ?>

                            </tbody>
                        </table>
                        </div>

                        <div class="row">
                        <h4>Scraps</h4>

                        <?php if (!empty($scrap_details)) : ?>
    <table id="bill_of_material_scrap_table" class="table  table-bordered ">
        <thead>
            <tr>
                <th>#</th>
                <th><?php echo _l('component'); ?></th>
                <th><?php echo _l('unit_id'); ?></th>
                <th><?php echo _l('product_qty'); ?></th>
                <th><?php echo _l('price'); ?></th>
                <th> Sub-Total</th>
                <th><?php echo _l('scrap_type'); ?></th>

                <th><?php echo _l('reason'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php $i = 1; ?>
                              
            <?php foreach ($scrap_details as $scrap): ?>
                <tr>
                <td><?php echo $i++; ?></td>

                    <td><?php echo htmlspecialchars($scrap['product_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($scrap['unit_name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($scrap['estimated_quantity'] ?? ''); ?></td>
                    <td><?php echo  htmlspecialchars($scrap['price']); ?></td>
                    <td><?php echo  htmlspecialchars($scrap['scrap_subtotal_cost']); ?></td>
                    <td><?php echo htmlspecialchars($scrap['scrap_type'] ?? ''); ?></td>

                    <td><?php echo htmlspecialchars($scrap['reason'] ?? ''); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No scrap data found.</p>
<?php endif; ?>

                        </div>

                        <div class="row">
                        <h4>Costing</h4>

                        <table class="table table-bordered">
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
                <td><span><?php echo $labour_charges; ?></span></td>
                <td><span><?php echo $labour_charges_description; ?></span></td>
            </tr>
            <tr>
                <td>2</td>
                <td>Electricity Charges</td>
                <td><span><?php echo $electricity_charges; ?></span></td>
                <td><span><?php echo $electricity_charges_description; ?></span></td>
            </tr>
            <tr>
                <td>3</td>
                <td>Machinery Charges</td>
                <td><span><?php echo $machinery_charges; ?></span></td>
                <td><span><?php echo $machinery_charges_description; ?></span></td>
            </tr>
            <tr>
                <td>4</td>
                <td>Other Charges</td>
                <td><span><?php echo $other_charges; ?></span></td>
                <td><span><?php echo $other_charges_description; ?></span></td>
            </tr>
        </tbody>
    </table>
                        </div>


						
	    <!-- <div class="table-responsive">
            <div class="table-responsive">
                <table class="table table-bordered" id="bill_of_material_detail_table">
                    <thead>
                    <tr>
                            <th><?php echo _l('id'); ?></th>
                            <th><?php echo _l('display_order'); ?></th>
                            <th><?php echo _l('component'); ?></th>
                            <th><?php echo _l('Quantity (Per Unit)'); ?></th>  
                            <th><?php echo _l('unit'); ?></th>  
                            <th><?php echo _l('Price (Per Unit)'); ?></th> 
                            <th><?php echo _l('Subtotal (Per Unit)'); ?></th> 
                            <th><?php echo _l('apply_on_variants'); ?></th>
                            <th><?php echo _l('consumed_in_operation'); ?></th> 
                        </tr>

                    </thead>
                    <tbody>
                    
                    </tbody>
                </table>
            </div>





       </div> -->
						
    </div>

					
</div>
				
</div>
			
</div>

			<div class="col-md-7">
			
				<div id="modal_wrapper"></div>
			</div>


		</div>
	</div>
</div>
<div id="contract_file_data"></div>

<?php echo form_hidden('bill_of_material_id',$bill_of_material_id); ?>
<?php echo form_hidden('bill_of_material_product_id',$product_id); ?>
<?php echo form_hidden('bill_of_material_routing_id',$routing_id); ?>
<?php init_tail(); ?>
<?php 
require('modules/manufacturing/assets/js/bill_of_materials/add_edit_bill_of_material_js.php');
require('modules/manufacturing/assets/js/bill_of_materials/bill_of_material_details/bill_of_material_detail_manage_js.php');

?>




<script>
   

    function stripHTML(html) {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        return tempDiv.textContent || tempDiv.innerText || '';
    }
</script>

<script>

    const logoUrl = "<?= get_company_logo_url(); ?>"; // Fetch the logo URL dynamically
        const companyInfo = `<?= addslashes(format_organization_info()); ?>`; // Fetch company info
    async function getBase64ImageFromURL(url) {
        if (!url) {
            console.error("Logo URL is empty or invalid.");
            return null;
        }
        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error(`Failed to fetch logo. Status: ${response.status}`);
            const blob = await response.blob();
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onloadend = () => resolve(reader.result);
                reader.onerror = (error) => reject(`Error reading blob: ${error}`);
                reader.readAsDataURL(blob);
            });
        } catch (error) {
            console.error("Error fetching or converting logo:", error);
            return null;
        }
    }

    async function exportBillOfMaterialToPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        let yOffset = 20;
        const logoWidth = 50; // Desired width of the logo
        const logoHeight = 20; // Desired height of the logo
        const logoX = 14; // X position of the logo
        const logoY = 20; 
        // Fetch and add the logo
        const base64Logo = await getBase64ImageFromURL(logoUrl);

        // Add a white background rectangle behind the logo
        if (base64Logo) {
            doc.setFillColor(255, 255, 255); // Set white color for the rectangle
            doc.rect(logoX, logoY, logoWidth, logoHeight, 'F'); // Draw filled rectangle
            doc.addImage(base64Logo, 'PNG', logoX, logoY, logoWidth, logoHeight); // Add the logo on top of the rectangle
        } else {
            console.warn("Logo not added to PDF due to fetch/conversion issues.");
        }
        const pageWidth = doc.internal.pageSize.getWidth();
            // Fetch and Add Organization Information
        //const companyInfo = `<?= addslashes(format_organization_info()); ?>`; 
        const plainCompanyInfo = stripHTML(companyInfo); // Ensure `stripHTML` function is defined
            const companyLines = doc.splitTextToSize(plainCompanyInfo, 90); // Wrap text for a 90px wide column

            // Calculate position for right-aligned company info
        const rightAlignX = pageWidth - 80; // Adjust as per desired right margin

        // Add company info on the right
        doc.setFontSize(10);
        companyLines.forEach((line) => {
            doc.text(line, rightAlignX, yOffset);
            yOffset += 6; // Line spacing
        });
        // "Bill of Material" Title with background
        doc.setFillColor(169, 169, 169);
        doc.rect(14, yOffset, 182, 10, 'F');
        doc.setFontSize(16);
        doc.setTextColor(255, 255, 255);
        doc.text("Bill of Material", 105, yOffset + 8, null, null, 'center');
        doc.setTextColor(0, 0, 0); // Reset to black
        yOffset += 20;

        // BOM Details in Two-Column Layout
    const bomInfo = [
            ["Bill of Material No.", "<?= isset($bill_of_material) ? htmlspecialchars($bill_of_material->bom_code) : 'N/A'; ?>"],
            ["Date", "<?= isset($bill_of_material) && $bill_of_material->creation_date ? date('d M Y', strtotime($bill_of_material->creation_date)) : date('d M Y'); ?>"],
            ["Product ID", "<?= isset($bill_of_material) ? htmlspecialchars($bill_of_material->product_id) : 'N/A'; ?>"],
            ["Product Name", "<?= isset($bill_of_material) ? htmlspecialchars(mrp_get_product_name($bill_of_material->product_id)) : 'N/A'; ?>"],
            ["Quantity", "<?= isset($bill_of_material) ? htmlspecialchars($bill_of_material->product_qty) : 'N/A'; ?>"],
            ["BOM Type", "<?= isset($bill_of_material) ? ($bill_of_material->bom_type == 'manufacture_this_product' ? 'Manufacture This Product' : 'Kit') : 'N/A'; ?>"],
            ["Consumption Type", "<?= isset($bill_of_material) ? ($bill_of_material->consumption_type == 'Flexible' ? 'Flexible' : 'Strict') : 'N/A'; ?>"]
        ];

        doc.setFontSize(10);
        const leftColumnX = 14;
        const rightColumnX = 105;
        let rowYOffset = yOffset;

        // Loop through BOM info to position alternately in left and right columns
        for (let i = 0; i < bomInfo.length; i++) {
            const [label, value] = bomInfo[i];
            const text = `${label}: ${value}`;

            // Position text in left or right column based on index
            if (i % 2 === 0) {
                doc.text(text, leftColumnX, rowYOffset);
            } else {
                doc.text(text, rightColumnX, rowYOffset);
                rowYOffset += 8; // Move to the next row after right column
            }
        }

        rowYOffset += 10; // Add some space before the components table

        // Component Table Headers
        const columns = [
            { header: "Sl. No.", dataKey: "slNo" },
            { header: "Item Name", dataKey: "component" },
            { header: "Product ID", dataKey: "productId" },
            { header: "Quantity", dataKey: "quantity" },
            { header: "Unit", dataKey: "unit" },
            { header: "Price", dataKey: "price" },
            { header: "Amount", dataKey: "amount" }
        ];

        // Fetch component rows
        const rows = [];
        let totalPrice = 0;

    $('#bill_of_material_detail_table tbody tr').each(function(index) {
        const component = $(this).find("td:eq(2)").text();
        const productId = $(this).find("td:eq(0)").text().trim(); // Trim whitespace
        const quantity = $(this).find("td:eq(3)").text();
        const unit = $(this).find("td:eq(4)").text();
        const price = $(this).find("td:eq(5)").text();
        const amount = $(this).find("td:eq(6)").text();

        // Only include rows where productId is not null or empty
        if (productId && productId !== 'Total Price:') {
            rows.push({
                slNo: index + 1,
                component: component,
                productId: productId,
                quantity: quantity,
                unit: unit,
                price: price,
                amount: amount
            });

            totalPrice += parseFloat(amount) || 0;
        }
    });

    // Add a row for the total price at the end of the table
    rows.push({
        slNo: '',
        component: '',
        productId: '',
        quantity: '',
        unit: '',
        price: 'Total',
        amount: totalPrice.toFixed(2)
    });

    // Table for Components with autoTable
    doc.autoTable({
        columns: columns,
        body: rows,
        startY: rowYOffset,
        theme: 'striped',
        headStyles: { fillColor: [169, 169, 169], textColor: [255, 255, 255] },
        styles: {
            fontSize: 10,
            halign: 'center',
            valign: 'middle',
        },
        columnStyles: {
            slNo: { halign: 'center' },
            component: { halign: 'left' },
            productId: { halign: 'center' },
            quantity: { halign: 'center' },
            unit: { halign: 'center' },
            price: { halign: 'right' },
            amount: { halign: 'right' }
        }
    });

    // Save the PDF
    doc.save("Bill_of_Material_Details.pdf");
}

</script>





</body>
</html>
