<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="reviseHistoryModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">Estimate Revise History</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div id="reviseHistoryLoader" style="display:none;">Loading...</div>
        <table class="table table-bordered" id="reviseHistoryTable">
          <thead>
            <tr>
              <th>Version</th>
              <th>Total</th>
              <th>Total Tax</th>
              <th>Customer</th>
              <th>Project</th>
              <th>Date</th>
              <th>Expiry Date</th>
              <th>Reference</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <!-- Dynamic rows here -->
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
	function loadReviseHistory(estimateId) {
		$('#reviseHistoryTable tbody').html('');
		$('#reviseHistoryLoader').show();

		$('#reviseHistoryModal').modal('show');

		$.ajax({
			url: '<?= admin_url("estimates/get_revise_history") ?>/' + estimateId,
			type: 'GET',
			dataType: 'json',
			success: function(response) {
				$('#reviseHistoryLoader').hide();

				if (response.length > 0) {
					let rows = '';
					response.forEach(function(item) {
						rows += `
							<tr>
								<td>${item.version_label ?? ''}</td>
								<td>${item.total ?? ''}</td>
								<td>${item.total_tax ?? ''}</td>
								<td>${item.customer ?? ''}</td>
								<td>${item.project_id ?? ''}</td>
								<td>${item.date ?? ''}</td>
								<td>${item.expirydate ?? ''}</td>
								<td>${item.reference_no ?? ''}</td>
								<td>${item.status_html ?? ''}</td>
							</tr>
						`;
					});
					$('#reviseHistoryTable tbody').html(rows);
				} else {
					$('#reviseHistoryTable tbody').html(
						'<tr><td colspan="9" class="text-center">No history found</td></tr>'
					);
				}
			},
			error: function() {
				$('#reviseHistoryLoader').hide();
				$('#reviseHistoryTable tbody').html(
					'<tr><td colspan="9" class="text-center text-danger">Failed to load data</td></tr>'
				);
			}
		});
	}
</script>