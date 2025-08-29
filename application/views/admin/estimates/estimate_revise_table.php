<?php if (!empty($estimate_id)) {
    $revisions = $this->estimates_model->get_estimate_revisions($estimate_id);

    if (count($revisions) > 0) { ?>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th><?php echo _l('version'); ?></th>
                    <th><?php echo _l('total'); ?></th>
                    <th><?php echo _l('total_tax'); ?></th>
                    <th><?php echo _l('client'); ?></th>
                    <th><?php echo _l('project'); ?></th>
                    <th><?php echo _l('tags'); ?></th>
                    <th><?php echo _l('date'); ?></th>
                    <th><?php echo _l('expirydate'); ?></th>
                    <th><?php echo _l('reference_no'); ?></th>
                    <th><?php echo _l('status'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($revisions as $rev) { ?>
                    <tr>
                        <td>
                                <a href="<?php echo admin_url('estimates/view_revision/' . $estimate_id . '/' . $rev['version']); ?>">
                                    <?php echo $rev['version']; ?>
                                </a>
                        </td>

   
                        <td><?php echo app_format_money($rev['total'], $estimate->currency); ?></td>
                        <td><?php echo app_format_money($rev['total_tax'], $estimate->currency); ?></td>
                        <td><?php echo $rev['company']; ?></td>
                        <td><?php echo $rev['project_name']; ?></td>
                        <td><?php echo $rev['tags']; ?></td>
                        <td><?php echo _d($rev['date']); ?></td>
                        <td><?php echo _d($rev['expirydate']); ?></td>
                        <td><?php echo $rev['reference_no']; ?></td>
                        <td><?php echo format_estimate_status($rev['status']); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
<?php } else { ?>
    <p class="text-muted"><?php echo _l('no_revision_found'); ?></p>
<?php }
} ?>
