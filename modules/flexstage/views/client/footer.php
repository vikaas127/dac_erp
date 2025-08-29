<nav class="flexstage-nav-bar-footer navbar navbar-default header navbar-fixed-bottom">
    <div class="container">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <p class="tw-ml-5 tw-mt-2">
                <?php echo flexstage_format_date($event['start_date']) ?>
            </p>
            <h4 class="tw-font-semibold tw-ml-5">
                <?php echo ucwords($event['name']) ?>
            </h4>
        </div>
        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="" id="theme-footer-collapse">
            <ul class="nav navbar-nav navbar-right">
                <li class="tw-ml-5">
                    <a class="tw-font-semibold">
                        <?php echo ucfirst($price_range) ?>
                    </a>
                </li>
                <li class="tw-ml-5">
                    <a href="<?php echo fs_get_event_url($event, 'tickets') ?>"
                        class="btn btn-danger btn-lg flex-tickets-cta">
                        <?php echo _l('flexstage_buy_tickets') ?>
                    </a>
                </li>
            </ul>
        </div>
        <!-- /.navbar-collapse -->
    </div>
</nav>