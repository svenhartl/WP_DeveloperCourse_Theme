<?php

get_header();

while (have_posts()) {
  the_post();
  pageBanner();
  ?>

  <div class="container container--narrow page-section">
    <div class="search-form">
      <h2 class="headline headline--medium">Perform a New Search:</h2>
      <form action="<?php echo esc_url(site_url('/')); ?>" method="get">
        <div class="search-form-row">
          <input id="traditional-search-term" class="s" type="search" name="s" value="<?php echo esc_attr(get_search_query()); ?>" aria-label="Search term">
          <input class="search-submit" type="submit" value="Search">
        </div>
      </form>
    </div>

    <div class="generic-content">
      <?php the_content(); ?>
    </div>
  </div>

<?php }

get_footer();

?>
