<?php

  get_header();

  while(have_posts()) {
    the_post();

    $slideTitle = university_get_slideshow_title(get_the_ID());
    $slideSubtitle = get_field('slide_subtitle');
    $slideImage = university_get_slideshow_image_url(get_the_ID());
    $slidePageContent = get_field('page_content');
    $slideExternalLink = trim((string) get_field('slide_link_value'));
    $slideLinkText = university_get_slideshow_link_text(get_the_ID());

    pageBanner(array(
      'title' => esc_html($slideTitle),
      'subtitle' => esc_html($slideSubtitle),
      'photo' => $slideImage ? esc_url($slideImage) : null
    ));
     ?>

    <div class="container container--narrow page-section">
      <div class="metabox metabox--position-up metabox--with-home-link">
        <p><a class="metabox__blog-home-link" href="<?php echo esc_url(site_url('/')); ?>"><i class="fa fa-home" aria-hidden="true"></i> Home</a> <span class="metabox__main"><?php echo esc_html($slideTitle); ?></span></p>
      </div>

      <div class="generic-content">
        <?php
          if ($slidePageContent) {
            echo wp_kses_post(wpautop($slidePageContent));
          } elseif (trim(get_the_content())) {
            the_content();
          } elseif ($slideSubtitle) {
            echo wpautop(esc_html($slideSubtitle));
          }

          if ($slideExternalLink && '#' !== $slideExternalLink) { ?>
            <p><a class="btn btn--blue" href="<?php echo esc_url($slideExternalLink); ?>"><?php echo esc_html($slideLinkText); ?></a></p>
          <?php }
        ?>
      </div>
    </div>

  <?php }

  get_footer();

?>
