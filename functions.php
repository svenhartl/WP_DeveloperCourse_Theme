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

    if ('related_programs' === $selector || 'related_campus' === $selector) {
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
  wp_localize_script('main-university-js', 'universityData', array(
    'root_url' => get_site_url()
  ));
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
  if (!is_admin() AND is_post_type_archive('campus') AND $query->is_main_query()) {
    $query->set('posts_per_page', -1);
  }

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

function university_register_search_route() {
  register_rest_route('university/v1', 'search', array(
    'methods' => WP_REST_Server::READABLE,
    'callback' => 'university_search_results',
    'permission_callback' => '__return_true'
  ));
}

add_action('rest_api_init', 'university_register_search_route');

function university_searchable_post_types() {
  $postTypes = get_post_types(array(
    'public' => true,
    'exclude_from_search' => false
  ), 'names');

  return array_values(array_diff($postTypes, array('attachment')));
}

function university_search_label_matches($label, $searchTerm) {
  $cleanLabel = trim(wp_strip_all_tags((string) $label));
  $cleanSearchTerm = trim((string) $searchTerm);

  if ('' === $cleanLabel || '' === $cleanSearchTerm) {
    return false;
  }

  return false !== stripos($cleanLabel, $cleanSearchTerm) || false !== stripos($cleanSearchTerm, $cleanLabel);
}

function university_get_search_result_author_name($postId) {
  if ('post' !== get_post_type($postId)) {
    return '';
  }

  $authorId = (int) get_post_field('post_author', $postId);

  if (!$authorId) {
    return '';
  }

  return get_the_author_meta('display_name', $authorId);
}

function university_add_unique_search_result(&$results, &$seenUrls, $result) {
  $result = wp_parse_args($result, array(
    'title' => '',
    'url' => '',
    'type' => '',
    'authorName' => ''
  ));

  if (empty($result['url'])) {
    return;
  }

  $normalizedUrl = untrailingslashit((string) $result['url']);

  if (isset($seenUrls[$normalizedUrl])) {
    return;
  }

  $seenUrls[$normalizedUrl] = true;
  $results[] = $result;
}

function university_archive_search_targets($searchTerm) {
  $targets = array();
  $postTypes = get_post_types(array('public' => true), 'objects');

  foreach ($postTypes as $postType => $postTypeObject) {
    if (empty($postTypeObject->has_archive)) {
      continue;
    }

    $archiveUrl = get_post_type_archive_link($postType);

    if (!$archiveUrl) {
      continue;
    }

    $archiveLabel = isset($postTypeObject->labels->name) ? $postTypeObject->labels->name : $postTypeObject->label;

    if (!university_search_label_matches($archiveLabel, $searchTerm)) {
      continue;
    }

    $targets[] = array(
      'title' => $archiveLabel,
      'url' => $archiveUrl,
      'type' => 'archive'
    );
  }

  $postsPageId = (int) get_option('page_for_posts');

  if ($postsPageId) {
    $postsPageTitle = get_the_title($postsPageId);

    if (university_search_label_matches($postsPageTitle, $searchTerm) || university_search_label_matches('Blog', $searchTerm)) {
      $targets[] = array(
        'title' => $postsPageTitle,
        'url' => get_permalink($postsPageId),
        'type' => 'archive'
      );
    }
  } elseif (university_search_label_matches('Blog', $searchTerm)) {
    $targets[] = array(
      'title' => 'Blog',
      'url' => site_url('/blog'),
      'type' => 'archive'
    );
  }

  $privacyPolicyPageId = (int) get_option('wp_page_for_privacy_policy');

  if ($privacyPolicyPageId) {
    $privacyPolicyTitle = get_the_title($privacyPolicyPageId);

    if (university_search_label_matches($privacyPolicyTitle, $searchTerm) || university_search_label_matches('Privacy Policy', $searchTerm)) {
      $targets[] = array(
        'title' => $privacyPolicyTitle,
        'url' => get_permalink($privacyPolicyPageId),
        'type' => 'page'
      );
    }
  }

  return $targets;
}

function university_get_related_program_search_results($programIds) {
  $programIds = array_values(array_filter(array_map('absint', (array) $programIds)));

  if (empty($programIds)) {
    return array();
  }

  $metaQuery = array('relation' => 'OR');

  foreach ($programIds as $programId) {
    $metaQuery[] = array(
      'key' => 'related_programs',
      'compare' => 'LIKE',
      'value' => '"' . $programId . '"'
    );
  }

  $results = array();
  $relatedProfessors = new WP_Query(array(
    'posts_per_page' => -1,
    'post_type' => 'professor',
    'orderby' => 'title',
    'order' => 'ASC',
    'meta_query' => $metaQuery
  ));

  while ($relatedProfessors->have_posts()) {
    $relatedProfessors->the_post();

    $results[] = array(
      'title' => get_the_title(),
      'url' => get_permalink(),
      'type' => get_post_type(),
      'authorName' => university_get_search_result_author_name(get_the_ID())
    );
  }

  wp_reset_postdata();

  $today = date('Ymd');
  $eventMetaQuery = $metaQuery;
  array_unshift($eventMetaQuery, array(
    'key' => 'event_date',
    'compare' => '>=',
    'value' => $today,
    'type' => 'numeric'
  ));
  $eventMetaQuery['relation'] = 'AND';

  $relatedEvents = new WP_Query(array(
    'posts_per_page' => -1,
    'post_type' => 'event',
    'meta_key' => 'event_date',
    'orderby' => 'meta_value_num',
    'order' => 'ASC',
    'meta_query' => $eventMetaQuery
  ));

  while ($relatedEvents->have_posts()) {
    $relatedEvents->the_post();

    $results[] = array(
      'title' => get_the_title(),
      'url' => get_permalink(),
      'type' => get_post_type(),
      'authorName' => university_get_search_result_author_name(get_the_ID())
    );
  }

  wp_reset_postdata();

  return $results;
}

function university_search_results($request) {
  $searchTerm = sanitize_text_field($request->get_param('term'));

  if ('' === $searchTerm) {
    return array();
  }

  $results = array();
  $seenUrls = array();
  $matchingProgramIds = array();
  $contentQuery = new WP_Query(array(
    'post_type' => university_searchable_post_types(),
    'post_status' => 'publish',
    'posts_per_page' => 20,
    's' => $searchTerm
  ));

  while ($contentQuery->have_posts()) {
    $contentQuery->the_post();

    university_add_unique_search_result($results, $seenUrls, array(
      'title' => get_the_title(),
      'url' => get_permalink(),
      'type' => get_post_type(),
      'authorName' => university_get_search_result_author_name(get_the_ID())
    ));

    if ('program' === get_post_type()) {
      $matchingProgramIds[] = get_the_ID();
    }
  }

  wp_reset_postdata();

  $taxonomies = array_diff(
    get_taxonomies(array('public' => true), 'names'),
    array('post_format', 'nav_menu', 'link_category')
  );

  if (!empty($taxonomies)) {
    $matchingTerms = get_terms(array(
      'taxonomy' => $taxonomies,
      'hide_empty' => false,
      'number' => 10,
      'search' => $searchTerm
    ));

    if (!is_wp_error($matchingTerms)) {
      foreach ($matchingTerms as $term) {
        $termLink = get_term_link($term);

        if (is_wp_error($termLink)) {
          continue;
        }

        university_add_unique_search_result($results, $seenUrls, array(
          'title' => $term->name,
          'url' => $termLink,
          'type' => $term->taxonomy
        ));
      }
    }
  }

  foreach (university_get_related_program_search_results($matchingProgramIds) as $relatedResult) {
    university_add_unique_search_result($results, $seenUrls, $relatedResult);
  }

  foreach (university_archive_search_targets($searchTerm) as $target) {
    university_add_unique_search_result($results, $seenUrls, $target);
  }

  return array_values($results);
}

function universityMapKey($api) {
  $api['key'] = 'yourKeyGoesHere';
  return $api;
}

add_filter('acf/fields/google_map/api', 'universityMapKey');
