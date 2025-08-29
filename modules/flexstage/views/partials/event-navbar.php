<div class="panel_s">
    <div class="panel-body">
        <?php
        $home_url = get_flexstage_client_url($event['slug']);
        $speakers_url = get_flexstage_client_url($event['slug'], 'speakers');
        $tickets_url = get_flexstage_client_url($event['slug'], 'tickets');
        ?>
        <div class="row">
            <div class="col-sm-4 col-md-2">
                <?php get_company_logo($home_url); ?>
            </div>
            <div class="col-sm-5 col-sm-offset-3 col-md-5 col-md-offset-5">
                <ul class="nav nav-pills navigation">
                    <li role="presentation" class="<?= current_url() == $home_url ? 'active' : '' ?>">
                        <a href="<?= $home_url ?>">Home</a>
                    </li>
                    <li role="presentation" class="<?= current_url() == $speakers_url ? 'active' : '' ?>">
                        <a href="<?= $speakers_url ?>">Speakers</a>
                    </li>

                    <li role="presentation" class="<?= current_url() == $tickets_url ? 'active' : '' ?>">
                        <a href="<?= $tickets_url ?>">Tickets</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>