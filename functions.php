<?php

if (!function_exists('university_resolve_field_post_id')) {
  function university_resolve_field_post_id($post_id = false) {
    if ($post_id instanceof WP_Post) {
      return $post_id->ID;
    }

    if (is_object($post_id) && isset($post_id->ID)) {
      return (int) $post_id->ID;
    }

    if (!$post_id) {
      $post_id = get_the_ID();
    }

    return is_numeric($post_id) ? (int) $post_id : false;
  }
}

if (!function_exists('university_normalize_field_value')) {
  function university_normalize_field_value($selector, $value) {
    if ('' === $value || null === $value) {
      return false;
    }

    if ('page_banner_background_image' === $selector) {
      if (is_array($value)) {
        return $value;
      }

      $attachmentId = absint($value);

      if (!$attachmentId) {
        return false;
      }

      $fullImage = wp_get_attachment_image_src($attachmentId, 'full');
      $bannerImage = wp_get_attachment_image_src($attachmentId, 'pageBanner');

      return array(
        'ID' => $attachmentId,
        'url' => $fullImage ? $fullImage[0] : '',
        'sizes' => array(
          'pageBanner' => $bannerImage ? $bannerImage[0] : ($fullImage ? $fullImage[0] : '')
        )
      );
    }

    if ('related_programs' === $selector) {
      return is_array($value) ? $value : array($value);
    }

    return $value;
  }
}

if (!function_exists('get_field')) {
  function get_field($selector, $post_id = false) {
    $resolvedPostId = university_resolve_field_post_id($post_id);

    if (!$resolvedPostId || !metadata_exists('post', $resolvedPostId, $selector)) {
      return false;
    }

    $value = get_post_meta($resolvedPostId, $selector, true);

    return university_normalize_field_value($selector, $value);
  }
}

if (!function_exists('the_field')) {
  function the_field($selector, $post_id = false) {
    $value = get_field($selector, $post_id);

    if (is_scalar($value)) {
      echo esc_html($value);
    }
  }
}

function pageBanner($args = NULL) {
  $args = wp_parse_args($args, array(
    'title' => get_the_title(),
    'subtitle' => get_field('page_banner_subtitle'),
    'photo' => null
  ));

  if (!$args['photo']) {
    $bannerImage = get_field('page_banner_background_image');

    if ($bannerImage AND !is_archive() AND !is_home() ) {
      $args['photo'] = $bannerImage['sizes']['pageBanner'];
    } else {
      $args['photo'] = get_theme_file_uri('/images/ocean.jpg');
    }
  }

  ?>
  <div class="page-banner">
    <div class="page-banner__bg-image" style="background-image: url(<?php echo $args['photo']; ?>);"></div>
    <div class="page-banner__content container container--narrow">
      <h1 class="page-banner__title"><?php echo $args['title'] ?></h1>
      <div class="page-banner__intro">
        <p><?php echo $args['subtitle']; ?></p>
      </div>
    </div>  
  </div>
<?php }

function university_files() {
  wp_enqueue_script('googleMap', '//maps.googleapis.com/maps/api/js?key=AIzaSyDin3iGCdZ7RPomFLyb2yqFERhs55dmfTI', NULL, '1.0', true);
  wp_enqueue_script('main-university-js', get_theme_file_uri('/build/index.js'), array('jquery'), '1.0', true);
  wp_enqueue_style('custom-google-fonts', '//fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i');
  wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
  wp_enqueue_style('university_main_styles', get_theme_file_uri('/build/style-index.css'));
  wp_enqueue_style('university_extra_styles', get_theme_file_uri('/build/index.css'));
}

add_action('wp_enqueue_scripts', 'university_files');

function university_features() {
  add_theme_support('title-tag');
  add_theme_support('post-thumbnails');
  add_image_size('professorLandscape', 400, 260, true);
  add_image_size('professorPortrait', 480, 650, true);
  add_image_size('pageBanner', 1500, 350, true);
}

add_action('after_setup_theme', 'university_features');

function university_adjust_queries($query) {
  if (!is_admin() AND is_post_type_archive('program') AND $query->is_main_query()) {
    $query->set('orderby', 'title');
    $query->set('order', 'ASC');
    $query->set('posts_per_page', -1);
  }

  if (!is_admin() AND is_post_type_archive('event') AND $query->is_main_query()) {
    $today = date('Ymd');
    $query->set('meta_key', 'event_date');
    $query->set('orderby', 'meta_value_num');
    $query->set('order', 'ASC');
    $query->set('meta_query', array(
              array(
                'key' => 'event_date',
                'compare' => '>=',
                'value' => $today,
                'type' => 'numeric'
              )
            ));
  }
}

add_action('pre_get_posts', 'university_adjust_queries');

function universityMapKey($api) {
  $api['key'] = 'yourKeyGoesHere';
  return $api;
}

add_filter('acf/fields/google_map/api', 'universityMapKey');
