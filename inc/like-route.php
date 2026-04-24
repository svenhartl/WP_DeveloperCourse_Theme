<?php

add_action('rest_api_init', 'universityLikeRoutes');

function universityLikeRoutes() {
  register_rest_route('university/v1', 'manageLike', array(
    'methods' => 'POST',
    'callback' => 'createLike',
    'permission_callback' => '__return_true'
  ));

  register_rest_route('university/v1', 'manageLike', array(
    'methods' => 'DELETE',
    'callback' => 'deleteLike',
    'permission_callback' => '__return_true'
  ));
}

function createLike($data) {
  if (!is_user_logged_in()) {
    return new WP_Error('rest_forbidden', 'Only logged in users can create a like.', array('status' => 401));
  }

  $professor = absint($data['professorId']);

  if (!$professor || get_post_type($professor) != 'professor') {
    return new WP_Error('invalid_professor_id', 'Invalid professor id.', array('status' => 400));
  }

  $existQuery = new WP_Query(array(
    'author' => get_current_user_id(),
    'post_type' => 'like',
    'meta_query' => array(
      array(
        'key' => 'liked_professor_id',
        'compare' => '=',
        'value' => $professor
      )
    )
  ));

  if ($existQuery->found_posts) {
    return new WP_Error('duplicate_like', 'You already liked this professor.', array('status' => 409));
  }

  return wp_insert_post(array(
    'post_type' => 'like',
    'post_status' => 'publish',
    'post_author' => get_current_user_id(),
    'post_title' => 'Like',
    'meta_input' => array(
      'liked_professor_id' => $professor
    )
  ));
}

function deleteLike($data) {
  $likeId = absint($data['like']);

  if (get_current_user_id() == get_post_field('post_author', $likeId) AND get_post_type($likeId) == 'like') {
    wp_delete_post($likeId, true);
    return 'Congrats, like deleted.';
  } else {
    return new WP_Error('rest_forbidden', 'You do not have permission to delete that.', array('status' => 403));
  }
}
