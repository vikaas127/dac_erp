<?php if (count($socials) > 0) { ?>
    <div class="flexstage-social-wrapper mb-4">
        <p><button class="btn btn-primary btn-md" type="button" data-toggle="collapse" data-target="#socials" aria-expanded="false" aria-controls="socials">
            <i class="fa fa-share-alt"></i> <?php echo _l('flexstage_share'); ?>
        </button>
        </p>
        <div class="collapse" id="socials">
        <ul>
        <?php foreach ($socials as $social) { ?>
            <li>
                <a href="<?php echo $social['url'] ?>" target="_blank">
                    <i class="fab fa-lg <?php echo 'fa-' . $social['channel_id']; ?>"></i>
                </a>
            </li>
        <?php } ?>
        </ul>
            <br/>
    </div>
    </div>
<?php } ?>