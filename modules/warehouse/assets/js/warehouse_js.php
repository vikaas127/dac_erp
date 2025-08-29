<script> 

    appValidateForm($('#add_warehouse'), {
        warehouse_code: 'required',
        warehouse_name: 'required',
        order: 'required',
        
    });
    
    var warehouseServerParams = {
      "assign_staff_filter": "[name='assign_staff_filter[]']",
    };

    var table_warehouse_name = $('table.table-table_warehouse_name');
    var _table_api = initDataTable(table_warehouse_name, admin_url+'warehouse/table_warehouse_name', [0], [0], warehouseServerParams,  [3, 'asc']);
    $.each(warehouseServerParams, function(i, obj) {
      $('select' + obj).on('change', function() {  
        table_warehouse_name.DataTable().ajax.reload();
      });
    });


var warehouse_type_value = {};
    function new_warehouse_type(){
      "use strict";
        $('#warehouse_type').modal('show');
        $('.edit-title').addClass('hide');
        $('.add-title').removeClass('hide');
        $('#warehouse_type_id').html('');

        var handsontable_html ='<div id="hot_warehouse_type" class="hot handsontable htColumnHeaders"></div>';
        if($('#add_handsontable').html() != null){
          $('#add_handsontable').empty();

          $('#add_handsontable').html(handsontable_html);
        }else{
          $('#add_handsontable').html(handsontable_html);

        }

setTimeout(function(){
  "use strict";
  var hotElement1 = document.querySelector('#hot_warehouse_type');


   var warehouse_type = new Handsontable(hotElement1, {
    contextMenu: true,
    manualRowMove: true,
    manualColumnMove: true,
    stretchH: 'all',
    autoWrapRow: true,
    rowHeights: 30,
    defaultRowHeight: 100,
    maxRows: 22,
    minRows:9,
    width: '100%',
    height: 330,
    rowHeaders: true,
    autoColumnSize: {
      samplingRatio: 23
    },

    licenseKey: 'non-commercial-and-evaluation',
    filters: true,
    manualRowResize: true,
    manualColumnResize: true,
    allowInsertRow: true,
    allowRemoveRow: true,
    columnHeaderHeight: 40,

    colWidths: [20, 50,120,30, 30,120],
    rowHeights: 30,
    rowHeaderWidth: [44],

    columns: [
                {
                  type: 'text',
                  data: 'warehouse_code'
                },
                 {
                  type: 'text',
                  data: 'warehouse_name',
                  // set desired format pattern and
                },
                 {
                  type: 'text',
                  data: 'warehouse_address',
                  // set desired format pattern and
                },
                {
                  type: 'numeric',
                  data: 'order',
                },
                {
                  type: 'checkbox',
                  data: 'display',
                  checkedTemplate: 'yes',
                  uncheckedTemplate: 'no'
                },
                {
                  type: 'text',
                  data: 'note',
                },
              
              ],

    colHeaders: true,
    nestedHeaders: [{"1":"<?php echo _l('warehouse_code') ?>",
                      "2":"<?php echo _l('warehouse_name') ?>",
                      "3":"<?php echo _l('warehouse_address') ?>",
                      "4":"<?php echo _l('order') ?>",
                     "5":"<?php echo _l('display') ?>",
                     "6":"<?php echo _l('note') ?>",
                    }],

    data: [
    {"warehouse_code":"","warehouse_name":"","warehouse_address":"","order":"","display":"yes","note":""},
    {"warehouse_code":"","warehouse_name":"","warehouse_address":"","order":"","display":"yes","note":""},
    {"warehouse_code":"","warehouse_name":"","warehouse_address":"","order":"","display":"yes","note":""},
    {"warehouse_code":"","warehouse_name":"","warehouse_address":"","order":"","display":"yes","note":""},
    {"warehouse_code":"","warehouse_name":"","warehouse_address":"","order":"","display":"yes","note":""},
    {"warehouse_code":"","warehouse_name":"","warehouse_address":"","order":"","display":"yes","note":""},
    {"warehouse_code":"","warehouse_name":"","warehouse_address":"","order":"","display":"yes","note":""},
    {"warehouse_code":"","warehouse_name":"","warehouse_address":"","order":"","display":"yes","note":""},
    {"warehouse_code":"","warehouse_name":"","warehouse_address":"","order":"","display":"yes","note":""},
    ],

  });
   warehouse_type_value = warehouse_type;
  },300);


    }

  function edit_warehouse_type(invoker,id){
      "use strict";

    var warehouse_code = $(invoker).data('warehouse_code');
    var warehouse_name = $(invoker).data('warehouse_name');
    var warehouse_address = $(invoker).data('warehouse_address');

    var order = $(invoker).data('order');
    if($(invoker).data('display') == 0){
      var display = 'no';
    }else{
      var display = 'yes';
    }
    var note = $(invoker).data('note');

        $('#warehouse_type_id').html('');
        $('#warehouse_type_id').append(hidden_input('id',id));

        $('#warehouse_type').modal('show');
        $('.edit-title').addClass('hide');
        $('.add-title').removeClass('hide');

        var handsontable_html ='<div id="hot_warehouse_type" class="hot handsontable htColumnHeaders"></div>';
        if($('#add_handsontable').html() != null){
          $('#add_handsontable').empty();

          $('#add_handsontable').html(handsontable_html);
        }else{
          $('#add_handsontable').html(handsontable_html);

        }

    setTimeout(function(){
      "use strict";
      var hotElement1 = document.querySelector('#hot_warehouse_type');

       var warehouse_type = new Handsontable(hotElement1, {
        contextMenu: true,
        manualRowMove: true,
        manualColumnMove: true,
        stretchH: 'all',
        autoWrapRow: true,
        rowHeights: 30,
        defaultRowHeight: 100,
        maxRows: 1,
        width: '100%',
        height: 130,
        rowHeaders: true,
        autoColumnSize: {
          samplingRatio: 23
        },
        licenseKey: 'non-commercial-and-evaluation',
        filters: true,
        manualRowResize: true,
        manualColumnResize: true,
        columnHeaderHeight: 40,

        colWidths: [40, 100, 30,30, 30, 140],
        rowHeights: 30,
        rowHeaderWidth: [44],

        columns: [
                {
                  type: 'text',
                  data: 'warehouse_code',
                  readOnly:true,
                  
                },
                 {
                  type: 'text',
                  data: 'warehouse_name',
                  // set desired format pattern and
                },
                 {
                  type: 'text',
                  data: 'warehouse_address',
                  // set desired format pattern and
                },
                {
                  type: 'numeric',
                  data: 'order',
                },
                {
                  type: 'checkbox',
                  data: 'display',
                  checkedTemplate: 'yes',
                  uncheckedTemplate: 'no'
                },
                {
                  type: 'text',
                  data: 'note',
                },
              
              ],

        colHeaders: true,
        nestedHeaders: [{"1":"<?php echo _l('warehouse_code') ?>",
                      "2":"<?php echo _l('warehouse_name') ?>",
                      "3":"<?php echo _l('warehouse_address') ?>",
                      "4":"<?php echo _l('order') ?>",
                      "5":"<?php echo _l('display') ?>",
                      "6":"<?php echo _l('note') ?>",
                    }],

        data: [{"warehouse_code":warehouse_code,"warehouse_name":warehouse_name,"warehouse_address":warehouse_address,"order":order,"display":display,"note":note}],

      });
       warehouse_type_value = warehouse_type;
      },300);

    }

    function add_warehouse_type(invoker){
      "use strict";
      var valid_warehouse_type = $('#hot_warehouse_type').find('.htInvalid').html();

      if(valid_warehouse_type){
        alert_float('danger', "<?php echo _l('data_must_number') ; ?>");
      }else{

        $('input[name="hot_warehouse_type"]').val(warehouse_type_value.getData());
        $('#add_warehouse_type').submit(); 

      }
        
    }

  
  function add_one_warehouse(){
    "use strict";

    $('#a_warehouse').modal('show');
    $('.edit-title').addClass('hide');
    $('.add-title').removeClass('hide');
    $('#warehouse_id').html('');

    $('#a_warehouse input[name="warehouse_code"]').val('');
    $('#a_warehouse input[name="warehouse_name"]').val('');
    $('#a_warehouse input[name="order"]').val('');

    $('#a_warehouse textarea[name="warehouse_address"]').val('');
    $('#a_warehouse textarea[name="note"]').val('');

    $('#a_warehouse input[name="display"]').prop("checked", true);
    $('#racks-container').empty();       
    $('#rack_shelf_data').val('');

    $('#lots_data').val('');


    requestGetJSON('warehouse/get_warehouse_custom_fields_html/' + 0).done(function (response) {
      $('#custom_fields_items').html(response.custom_fields_html);

      $("select[name='assign_to_staffs[]']").html(response.assign_to_staff);
      $("select[name='assign_to_staffs[]']").selectpicker('refresh');

        init_selectpicker();
    });

       
  }

  // function edit_warehouse_type(invoker,id){
  //     "use strict";

  //     var $warehouseModal = $('#a_warehouse');

  //    $warehouseModal.find('input[name="warehouse_code"]').val('');
  //    $warehouseModal.find('input[name="warehouse_name"]').val('');
  //    $warehouseModal.find('input[name="order"]').val('');

  //    $warehouseModal.find('textarea[name="warehouse_address"]').val('');
  //    $warehouseModal.find('textarea[name="note"]').val('');

  //    $warehouseModal.find('input[name="display"]').prop("checked", false);
  //    $warehouseModal.find('input[name="hide_warehouse_when_out_of_stock"]').prop("checked", false);

      
  //     $('#a_warehouse').modal('show');
  //     $('.edit-title').removeClass('hide');
  //     $('.add-title').addClass('hide');

  //     $('#warehouse_id').html('');
  //     $('#warehouse_id').append(hidden_input('id',id));

  //       // If id found get the text from the datatable
  //       if (typeof (id) !== 'undefined') {

  //           requestGetJSON('warehouse/get_warehouse_by_id/' + id).done(function (response) {

  //               $warehouseModal.find('input[name="warehouse_code"]').val(response.warehouse_code);
  //               $warehouseModal.find('input[name="warehouse_name"]').val(response.warehouse_name);
  //               $warehouseModal.find('input[name="order"]').val(response.order);
  //               $warehouseModal.find('input[name="city"]').val(response.city);
  //               $warehouseModal.find('input[name="state"]').val(response.state);
  //               $warehouseModal.find('input[name="zip_code"]').val(response.zip_code);


  //               if(response.country != ''){
  //                 $("select[name='country']").val(response.country).change();
  //               }else{
  //                 $("select[name='country']").val('').change();

  //               }

  //               if(response.display == 1){
  //                   $warehouseModal.find('input[name="display"]').prop("checked", true);
  //                 }else{
  //                   $warehouseModal.find('input[name="display"]').prop("checked", false);

  //                 }

  //                 if(response.hide_warehouse_when_out_of_stock == 1){
  //                   $warehouseModal.find('input[name="hide_warehouse_when_out_of_stock"]').prop("checked", true);
  //                 }else{
  //                   $warehouseModal.find('input[name="hide_warehouse_when_out_of_stock"]').prop("checked", false);

  //                 }

  //               $warehouseModal.find('textarea[name="warehouse_address"]').val(response.warehouse_address.replace(/(<|<)br\s*\/*(>|>)/g, " "));
  //               $warehouseModal.find('textarea[name="note"]').val(response.note.replace(/(<|<)br\s*\/*(>|>)/g, " "));
  //               const racksContainer = $('#racks-container'); // Create this div in your modal HTML
  //               racksContainer.html(''); // Clear first in case of edit

  //               if (response.racks && response.racks.length > 0) {
  //                 response.racks.forEach(rack => {
  //                   const rackBlock = createRackBlock(rack.rack_name, rack.rack_id);

  //                   rack.shelves.forEach(shelf => {
  //                     const shelfInput = createShelfInput(shelf.shelf_name, shelf.shelf_id);
  //                     rackBlock.find('.shelf-container').append(shelfInput);
  //                   });

  //                   racksContainer.append(rackBlock);
  //                 });
  //               }


  //               $('#custom_fields_items').html(response.custom_fields_html);

  //               $("select[name='assign_to_staffs[]']").html(response.assign_to_staff);
  //               $("select[name='assign_to_staffs[]']").selectpicker('refresh');
            

  //               init_selectpicker();

  //           });

  //       }
   
       
  // }
function edit_warehouse_type(invoker, id) {
  "use strict";

  const $warehouseModal = $('#a_warehouse');

  // Clear form fields
  $warehouseModal.find('input[name="warehouse_code"]').val('');
  $warehouseModal.find('input[name="warehouse_name"]').val('');
  $warehouseModal.find('input[name="order"]').val('');
  $warehouseModal.find('input[name="city"]').val('');
  $warehouseModal.find('input[name="state"]').val('');
  $warehouseModal.find('input[name="zip_code"]').val('');
  $warehouseModal.find('textarea[name="warehouse_address"]').val('');
  $warehouseModal.find('textarea[name="note"]').val('');
  $warehouseModal.find('input[name="display"]').prop("checked", false);
  $warehouseModal.find('input[name="hide_warehouse_when_out_of_stock"]').prop("checked", false);

  // Reset racks
  $('#racks-container').html('');
  rackIndex = 0;
 
  $('#lots-container').html('');
  lotIndex = 0;

  // Modal setup
  $('#a_warehouse').modal('show');
  $('.edit-title').removeClass('hide');
  $('.add-title').addClass('hide');

  $('#warehouse_id').html('').append(hidden_input('id', id));

  // Fetch & populate data
  if (typeof id !== 'undefined') {
    requestGetJSON('warehouse/get_warehouse_by_id/' + id).done(function (response) {
      $warehouseModal.find('input[name="warehouse_code"]').val(response.warehouse_code);
      $warehouseModal.find('input[name="warehouse_name"]').val(response.warehouse_name);
      $warehouseModal.find('input[name="order"]').val(response.order);
      $warehouseModal.find('input[name="city"]').val(response.city);
      $warehouseModal.find('input[name="state"]').val(response.state);
      $warehouseModal.find('input[name="zip_code"]').val(response.zip_code);

      $("select[name='country']").val(response.country || '').change();
      $warehouseModal.find('input[name="display"]').prop("checked", response.display == 1);
      $warehouseModal.find('input[name="hide_warehouse_when_out_of_stock"]').prop("checked", response.hide_warehouse_when_out_of_stock == 1);
      $warehouseModal.find('textarea[name="warehouse_address"]').val(response.warehouse_address.replace(/(<|<)br\s*\/*(>|>)/g, " "));
      $warehouseModal.find('textarea[name="note"]').val(response.note.replace(/(<|<)br\s*\/*(>|>)/g, " "));

      // Add racks and shelves
      if (response.racks && response.racks.length > 0) {
        response.racks.forEach(rack => {
          const shelves = (rack.shelves || []).map(shelf => shelf.shelf_name);
          addRack(rack.rack_name, shelves);
        });
      }

      //add lots
     if (response.lots_data && response.lots_data.length > 0) {
      response.lots_data.forEach(function (lot_name) {
        addLot(lot_name);  
      });
    }



      $('#custom_fields_items').html(response.custom_fields_html);
      $("select[name='assign_to_staffs[]']").html(response.assign_to_staff);
      $("select[name='assign_to_staffs[]']").selectpicker('refresh');

      init_selectpicker();
    });
  }
}


  $('#warehouse_code').on('keypress', function() {
    var warehouse_code = $('input[name="warehouse_code"]').val();
    if(warehouse_code.length >= 100){
        alert_float('warning', "<?php echo _l('Maximum_length_warehouse_code_is_100_words') ; ?>", 200);
    }
  });
  

</script>
<!-- rack_shelf_ui.js -->
<script>
let rackIndex = 0;

function addRack(rackName = '', shelves = []) {
  const rackHTML = `
    <div class="rack-block" data-rack-index="${rackIndex}">
      <button type="button" class="btn btn-danger btn-xs remove-rack-btn" onclick="removeRack(${rackIndex})" title="Remove Rack">
        <i class="fa fa-times"></i>
      </button>

      <div class="form-group">
        <label>Rack Name</label>
        <input type="text" class="form-control input-sm" name="rack_name[]" placeholder="Rack Name" value="${rackName}">
      </div>

      <div class="shelves-container">
        ${shelves.map(shelf => shelfInputHTML(shelf)).join('')}
      </div>

      <button type="button" class="btn btn-default btn-xs" onclick="addShelf(${rackIndex})">
        <i class="fa fa-plus"></i> Add Shelf
      </button>

      <hr>
    </div>
  `;
  $('#racks-container').append(rackHTML);
  rackIndex++;
}

function addShelf(rackIdx, shelfName = '') {
  const $rackBlock = $('[data-rack-index="' + rackIdx + '"] .shelves-container');
  $rackBlock.append(shelfInputHTML(shelfName));
}

function shelfInputHTML(value = '') {
  return `
    <div class="shelf-row">
      <div class="input-group input-group-sm">
        <input type="text" name="shelf_name[]" class="form-control" placeholder="Shelf Name" value="${value}">
        <span class="input-group-btn">
          <button type="button" class="btn btn-danger btn-sm" onclick="removeShelf(this)" title="Remove Shelf">
            <i class="fa fa-minus"></i>
          </button>
        </span>
      </div>
    </div>
  `;
}

function removeRack(index) {
  $('[data-rack-index="' + index + '"]').remove();
}

function removeShelf(btn) {
  $(btn).closest('.shelf-row').remove();
}

function prepareRackShelfData() {
  const rackShelfData = [];

  $('.rack-block').each(function () {
    const rackName = $(this).find('input[name="rack_name[]"]').val();
    const shelves = [];

    $(this).find('input[name="shelf_name[]"]').each(function () {
      const shelfName = $(this).val();
      if (shelfName.trim() !== '') {
        shelves.push(shelfName);
      }
    });

    if (rackName.trim() !== '') {
      rackShelfData.push({
        rack_name: rackName,
        shelves: shelves
      });
    }
  });

  // Set hidden field
  $('#rack_shelf_data').val(JSON.stringify(rackShelfData));
}

function resetRackShelfUI() {
  $('#racks-container').empty();          // remove all rack blocks
  $('#rack_shelf_data').val('');          // reset hidden field
  rackIndex = 0;                          // reset rack index
}



let lotIndex = 0;

function addLot(lotName = '') {
  const lotHTML = `
    <div class="lot-block" data-lot-index="${lotIndex}">
      <div class="input-group input-group-sm mb-2">
        <input type="text" class="form-control" name="lot_name[]" placeholder="Lot Name" value="${lotName}">
        <span class="input-group-btn">
          <button type="button" class="btn btn-danger btn-sm" onclick="removeLot(${lotIndex})" title="Remove Lot">
            <i class="fa fa-minus"></i>
          </button>
        </span>
      </div>
    </div>
  `;
  $('#lots-container').append(lotHTML);
  lotIndex++;
}

function removeLot(index) {
  $('[data-lot-index="' + index + '"]').remove();
}

function prepareLotsData() {
  const lots = [];

  $('#lots-container input[name="lot_name[]"]').each(function () {
    const name = $(this).val().trim();
    if (name !== '') {
      lots.push(name);
    }
  });

  $('#lots_data').val(JSON.stringify(lots));
}

function resetLotUI() {
  $('#lots-container').empty();      // remove all lot blocks
  $('#lots_data').val('');           // reset hidden field
  lotIndex = 0;                      // reset lot index
}

// Call before form submit
$('#add_warehouse').on('submit', function () {
  prepareRackShelfData();  // existing call
  prepareLotsData();       // new for lots
});


</script>
