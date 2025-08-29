<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<ul class="nav navbar-pills navbar-pills-flat nav-tabs nav-stacked customer-tabs" role="tablist">
    <?php foreach (flexstage_event_details_menu($event['id']) as $menu): ?>
    <li class="<?php echo ($menu['key'] == $key) ? 'active' : '' ?>">
        <a data-group="" href="<?php echo $menu['href'] ?>">
            <i class="<?php echo $menu['icon'] ?> menu-icon" aria-hidden="true"></i>
            <?php echo $menu['name']; ?>
        </a>
    </li>
    <?php endforeach; ?>
</ul>