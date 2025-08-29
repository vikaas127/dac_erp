<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th><?php echo _l('id'); ?></th>
                <th><?php echo _l('display_order'); ?></th>
                <th><?php echo _l('component'); ?></th>
                <th><?php echo _l('product_qty'); ?></th>
                <th><?php echo _l('unit_id'); ?></th>
                <th><?php echo _l('apply_on_variants'); ?></th>
                <th><?php echo _l('consumed_in_operation'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['display_order']); ?></td>
                    <td><?php echo htmlspecialchars($row['component']); ?></td>
                    <td class="text-right"><?php echo htmlspecialchars($row['product_qty']); ?></td>
                    <td><?php echo htmlspecialchars($row['unit_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['apply_on_variants']); ?></td>
                    <td><?php echo htmlspecialchars($row['consumed_in_operation']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
