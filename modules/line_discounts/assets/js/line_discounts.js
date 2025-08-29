/**
 * Line Discounts Module JavaScript
 */

(function($) {
    "use strict";

    $(document).ready(function() {


        if ( window.location.href.indexOf("dmin/proposals/proposal") !== -1 && $('#proposal-form').length == 1  )
        {
            initLineDiscounts();
        }
        else if ( window.location.href.indexOf("dmin/estimates/estimat") !== -1 && $('#estimate-form').length == 1 )
        {
            initLineDiscounts();
        }
        else if ( window.location.href.indexOf("dmin/invoices/invoice") !== -1 && $('#invoice-form').length == 1 )
        {
            initLineDiscounts();
        }

        // Bind to Perfex events
        $(document).on('item-added-to-table', function() {

            setTimeout( initLineDiscounts , 500);

        });

        $(document).on('sales-total-calculated', function() {

            setTimeout( function (){

                var table_item_rows = $(".table.has-calculations tbody tr.item");

                $.each( table_item_rows , function () {
                    console.log("burada");
                    calculateLineDiscount( $(this) );
                });


            } , 500);

        });

        // Bind discount input events
        $(document).on('change keyup blur', '.line-discount-input', function() {
            var $row = $(this).closest('tr');
            calculateLineDiscount($row);
            calculateDiscountTotals();
        });


    });


    $(document).on('shown.bs.modal', '.proposal-convert-modal', function () {

        if( $(this).attr('id') == 'convert_to_estimate' )
        {

            initLineDiscounts();

        }
        else if( $(this).attr('id') == 'convert_to_invoice' )
        {

            initLineDiscounts();

        }

    });


})(jQuery);


    function initLineDiscounts() {

        // Find the items table
        var $itemsTable = $('table.items');

        if ($itemsTable.length === 0) {
            console.log('Items table not found');
            return;
        }

        // Add discount header if not exists
        addDiscountHeader($itemsTable);

        // Add discount inputs to all rows
        addDiscountToAllRows($itemsTable);

    }

    function addDiscountHeader($table) {
        var $headerRow = $table.find('thead tr:first, tr:first');

        if ($headerRow.length === 0) {
            console.log('Header row not found');
            return;
        }

        // Check if discount header already exists
        if ($headerRow.find('th').filter(function() {
            return $(this).text().trim() === ld_discount_text;
        }).length > 0) {
            console.log('Discount header already exists');
            return;
        }

        // Find tax column (usually contains "Tax" or "Vergi")
        var $taxHeader = $headerRow.find('th').filter(function() {
            var text = $(this).text();//.toLowerCase();
            return  text.includes(ld_tax_text);
        });

        if ($taxHeader.length > 0) {
            // Insert discount header before tax
            var $discountHeader = $('<th class="text-center" style="min-width: 100px;">'+ld_discount_text+'</th>');
            $discountHeader.insertBefore($taxHeader);
        } else {
            var $allHeaders = $headerRow.find('th');
            if ($allHeaders.length >= 2) {
                var $discountHeader = $('<th class="text-center" style="min-width: 100px;">'+ld_discount_text+'</th>');
                $discountHeader.insertBefore($allHeaders.eq(-2));
            }
        }

    }

    function addDiscountToAllRows($table) {
        var $bodyRows = $table.find('tbody tr, tr').not(':first');

        $bodyRows.each(function() {
            var $row = $(this);

            // Skip if this is not an item row
            if ($row.find('input[name*="qty"], input[name*="description"], textarea[name*="description"]').length === 0) {
                return;
            }

            // Skip if discount cell already exists
            if ($row.find('.line-discount-input').length > 0) {
                console.log('Discount input already exists in this row');
                return;
            }

            // Adding discount to row
            addDiscountToRow( $row );
        });
    }

    function addDiscountToRow($row ) {
        // Find tax cell (usually contains tax dropdown or input)
        var $taxCell = $row.find('td').filter(function() {
            return $(this).find('select[name*="tax"], input[name*="tax"]').length > 0;
        });

        var $discountCell = '';
        if ($taxCell.length > 0) {
            // Insert discount cell before tax
            $discountCell = createDiscountCell( $row );
            $discountCell.insertBefore($taxCell);
        } else {
            // Fallback: insert before last 2 cells (amount and actions)
            var $allCells = $row.find('td');
            if ($allCells.length >= 2) {
                $discountCell = createDiscountCell( $row );
                $discountCell.insertBefore($allCells.eq(-2));
            }
        }


        if ( $discountCell != '' )
        {

            var itemId = $row.find('input[name*="itemid"]').val();

            if ( itemId && itemId > 0 ) {
                // Could make AJAX call to get discount data
                $.ajax({
                    url: admin_url + 'line_discounts/get_item_discount',
                    type: 'POST',
                    dataType: 'json',
                    data: { item_id: itemId },
                    success: function(response) {

                        if (response && response.discount_rate) {
                            $discountCell.find('input[name*="line_discount_rate"]').val(response.discount_rate);
                            calculateLineDiscount($row);
                        }
                    },
                    error: function() {
                        console.log('Could not load existing discount for item:', itemId);
                    }
                });
            }
            else
            {

                var line_discount_rate = $('input[name="line_discount_rate"]').val();
                var itemOrder = $row.find('input[name*="order"]').val();

                if ( $('#proposal_convert_to_estimate_form').length > 0 || $('#proposal_convert_to_invoice_form').length > 0 )
                {

                    if ( itemOrder && typeof proposal_id != "undefined" )
                    {

                        $.ajax({
                            url: admin_url + 'line_discounts/get_item_discount_from_order',
                            type: 'POST',
                            dataType: 'json',
                            data: { proposal_id:proposal_id , order_id : itemOrder },
                            success: function(response) {

                                if (response && response.discount_rate) {
                                    $discountCell.find('input[name*="line_discount_rate"]').val(response.discount_rate);
                                    calculateLineDiscount($row);
                                }
                            },
                            error: function() {
                                console.log('Could not load existing discount for item:', itemId);
                            }
                        });


                    }

                }
                else if ( itemOrder && line_discount_rate )
                {
                    $discountCell.find('input[name*="line_discount_rate"]').val( line_discount_rate );
                    calculateLineDiscount($row);
                    calculateDiscountTotals();
                    $('input[name="line_discount_rate"]').val('');
                }

            }

        }



        // Initialize calculation for this row
        setTimeout(function() {
            calculateLineDiscount($row);
        }, 100);
    }


    function createDiscountCell( $row ) {
        var $cell = $('<td class="text-center line-item-discount-cell"></td>');

        var $container = $('<div class="line-discount-container"></div>');

        // Create input group
        var $inputGroup = $('<div class="discount-input-group"></div>');


        var item_name = $row.find('textarea').eq(0).attr('name').replace('description', 'line_discount_rate');


        var $input = $('<input type="number" class="form-control line-discount-input" name="'+item_name+'" min="0" max="100" step="0.01" value="0">');


        var $addon = $('<span class="input-group-addon">%</span>');

        $inputGroup.append($input);
        $inputGroup.append($addon);

        // Discount amount display
        var $discountAmount = $('<div class="line-discount-amount">0.00 ₺</div>');

        $container.append($inputGroup);
        $container.append($discountAmount);
        $cell.append($container);

        return $cell;

    }

    function calculateLineDiscount($row) {

        var $discountInput = $row.find('.line-discount-input');
        var $discountAmount = $row.find('.line-discount-amount');
        var $qtyInput = $row.find('input[name*="qty"]');
        var $rateInput = $row.find('input[name*="rate"]');

        if ($discountInput.length === 0 || $discountAmount.length === 0) {
            return;
        }


        var qty = parseFloat($qtyInput.val()) || 0;
        var rate = parseFloat($rateInput.val()) || 0;
        var discountRate = parseFloat($discountInput.val()) || 0;

        var lineTotal = qty * rate;
        var discountAmount = (lineTotal * discountRate) / 100;
        var discountedTotal = lineTotal - discountAmount;

        discountAmount = parseFloat( accounting.toFixed( discountAmount, app.options.decimal_places ) );

        // Update discount amount display
        $discountAmount.html( format_money( discountAmount, true) );

        // If amount cell found, update it with discounted total
        if ( $row.find('td.amount').length > 0) {

            discountedTotal = accounting.toFixed( discountedTotal, app.options.decimal_places );
            discountedTotal = parseFloat(discountedTotal);

            $row.find('td.amount').html( format_money( discountedTotal, true) );

        }

        // Add visual indicator
        if (discountRate > 0) {
            $row.addClass('has-discount');
        } else {
            $row.removeClass('has-discount');
        }


    }

    function calculateDiscountTotals() {
        var totalDiscounts = 0;

        $('.line-discount-input').each(function() {
            var $row = $(this).closest('tr');
            var $qtyInput = $row.find('input[name*="qty"]');
            var $rateInput = $row.find('input[name*="rate"]');

            var qty = parseFloat($qtyInput.val()) || 0;
            var rate = parseFloat($rateInput.val()) || 0;
            var discountRate = parseFloat($(this).val()) || 0;

            var lineTotal = qty * rate;
            var discountAmount = (lineTotal * discountRate) / 100;

            totalDiscounts += discountAmount;
        });


        var discount_type = $('select[name="discount_type"]').val();

        if ( !discount_type )
            $('select[name="discount_type"]').selectpicker("val", "before_tax");

        $('.discount-type-fixed').click();
        $('input[name="discount_total"]').val( totalDiscounts );

        // Trigger Perfex's calculation
        setTimeout(function() {
            if (typeof calculate_total === 'function') {
                //calculate_total();
            }
        }, 200);
    }


