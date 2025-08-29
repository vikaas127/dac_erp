<?php if (count($images) > 0 || $video) { ?>
<div class=" flexstage-event">
        <div id="slide-show" class="slide-show">
            <?php if (isset($video)) { ?>
                <div class="">
                        <!-- Supports youtube & vimeo -->
                        <iframe width="100%" height="400px" style="" src="<?php echo $video['url'] ?>" title="YouTube video player"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen></iframe>
                </div>
            <?php } ?>
            <?php foreach ($images as $key => $image) { ?>
                    <div class="flex-image-container">
                        <div style="background-image: url('<?php echo fs_image_file_url($image['event_id'], $image['file_name']); ?>');">
                        </div>
                    </div>
            <?php } ?>
        </div>
</div>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js" defer></script>
    <script>
        $(function () {
            $('.slide-show').not('.slick-initialized').slick({
                infinite: true,
                adaptiveHeight: true,
                dots : true,
            });
        });
    </script>
<?php } ?>