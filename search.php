<?php

$searchResults = university_get_search_results(get_search_query());
$groupedResults = university_group_search_results($searchResults);

$getEmptySectionMessage = function($title) {
  $normalizedTitle = strtolower((string) $title);

  if ('general information' === $normalizedTitle) {
    return 'No general information matches that search.';
  }

  return 'No ' . $normalizedTitle . ' match that search.';
};

$createAuthorByline = function($authorName) {
  if (!$authorName) {
    return '';
  }

  return ' <span class="search-overlay__result-author">by ' . esc_html($authorName) . '</span>';
};

$renderResultList = function($results, $emptyMessage = '') use ($createAuthorByline) {
  if (empty($results)) {
    return $emptyMessage ? '<p class="search-overlay__section-message gray">' . esc_html($emptyMessage) . '</p>' : '';
  }

  ob_start();
  ?>
  <ul class="link-list min-list">
    <?php foreach ($results as $result) { ?>
      <li>
        <a href="<?php echo esc_url($result['url']); ?>"><?php echo esc_html($result['title']); ?></a><?php echo $createAuthorByline(isset($result['authorName']) ? $result['authorName'] : ''); ?>
      </li>
    <?php } ?>
  </ul>
  <?php

  return ob_get_clean();
};

$renderProfessorResults = function($results, $emptyMessage = '') use ($renderResultList) {
  if (empty($results)) {
    return $emptyMessage ? '<p class="search-overlay__section-message gray">' . esc_html($emptyMessage) . '</p>' : '';
  }

  $cardResults = array_filter($results, function($result) {
    return !empty($result['imageUrl']);
  });
  $fallbackResults = array_filter($results, function($result) {
    return empty($result['imageUrl']);
  });

  ob_start();

  if (!empty($cardResults)) {
    ?>
    <ul class="professor-cards">
      <?php foreach ($cardResults as $result) { ?>
        <li class="professor-card__list-item">
          <a class="professor-card" href="<?php echo esc_url($result['url']); ?>">
            <img class="professor-card__image" src="<?php echo esc_url($result['imageUrl']); ?>" alt="<?php echo esc_attr($result['title']); ?>">
            <span class="professor-card__name"><?php echo esc_html($result['title']); ?></span>
          </a>
        </li>
      <?php } ?>
    </ul>
    <?php
  }

  echo $renderResultList($fallbackResults);

  return ob_get_clean();
};

$renderEventResults = function($results, $emptyMessage = '') use ($renderResultList) {
  if (empty($results)) {
    return $emptyMessage ? '<p class="search-overlay__section-message gray">' . esc_html($emptyMessage) . '</p>' : '';
  }

  $cardResults = array_filter($results, function($result) {
    return !empty($result['eventMonth']) && !empty($result['eventDay']);
  });
  $fallbackResults = array_filter($results, function($result) {
    return empty($result['eventMonth']) || empty($result['eventDay']);
  });

  ob_start();

  foreach ($cardResults as $result) {
    $description = isset($result['description']) ? (string) $result['description'] : '';
    ?>
    <div class="event-summary">
      <a class="event-summary__date t-center" href="<?php echo esc_url($result['url']); ?>">
        <span class="event-summary__month"><?php echo esc_html($result['eventMonth']); ?></span>
        <span class="event-summary__day"><?php echo esc_html($result['eventDay']); ?></span>
      </a>
      <div class="event-summary__content">
        <h5 class="event-summary__title headline headline--tiny">
          <a href="<?php echo esc_url($result['url']); ?>"><?php echo esc_html($result['title']); ?></a>
        </h5>
        <p><?php if ($description) { echo esc_html($description) . ' '; } ?><a href="<?php echo esc_url($result['url']); ?>" class="nu gray">Learn more</a></p>
      </div>
    </div>
  <?php }

  echo $renderResultList($fallbackResults);

  return ob_get_clean();
};

$renderSection = function($title, $contentHtml) {
  ob_start();
  ?>
  <div class="search-overlay__section">
    <h2 class="search-overlay__section-title"><?php echo esc_html($title); ?></h2>
    <?php echo $contentHtml; ?>
  </div>
  <?php

  return ob_get_clean();
};

get_header();
pageBanner(array(
  'title' => 'Search Results',
  'subtitle' => 'You searched for "' . esc_html(get_search_query()) . '"'
));

?>

<div class="container container--narrow page-section">
  <div class="row group search-overlay__results-layout">
    <div class="one-third search-overlay__column">
      <?php
      echo $renderSection(
        'General Information',
        $renderResultList($groupedResults['generalInfo'], $getEmptySectionMessage('General Information'))
      );
      ?>
    </div>

    <div class="one-third search-overlay__column">
      <?php
      echo $renderSection(
        'Programs',
        $renderResultList($groupedResults['programs'], $getEmptySectionMessage('Programs'))
      );

      echo $renderSection(
        'Professors',
        $renderProfessorResults($groupedResults['professors'], $getEmptySectionMessage('Professors'))
      );
      ?>
    </div>

    <div class="one-third search-overlay__column">
      <?php
      echo $renderSection(
        'Campuses',
        $renderResultList($groupedResults['campuses'], $getEmptySectionMessage('Campuses'))
      );

      echo $renderSection(
        'Events',
        $renderEventResults($groupedResults['events'], $getEmptySectionMessage('Events'))
      );
      ?>
    </div>
  </div>

  <div class="search-form">
    <h2 class="headline headline--medium">Perform a New Search:</h2>
    <form action="<?php echo esc_url(site_url('/')); ?>" method="get">
      <div class="search-form-row">
        <input id="search-term-fallback" class="s" type="search" name="s" value="" placeholder="What are you looking for?" aria-label="Search term">
        <input class="search-submit" type="submit" value="Search">
      </div>
    </form>
  </div>
</div>

<?php get_footer();

?>
