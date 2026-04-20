<?php

function wp_developercourse_theme_files() {
  wp_enqueue_script(
    'wp-developercourse-scripts',
    get_theme_file_uri('/build/index.js'),
    array('jquery'),
    '1.0',
    true
    
  );

  wp_enqueue_style('wp-developercourse-main-styles', get_theme_file_uri('/build/style-index.css'));
  wp_enqueue_style('wp-developercourse-main-styles-1', get_theme_file_uri('/build/index.css'));
  wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
  wp_enqueue_style('google-font', '//fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i');
  }

add_action('wp_enqueue_scripts', 'wp_developercourse_theme_files');

function wp_developercourse_features() {
  add_theme_support('title-tag');
}
add_action('after_setup_theme', 'wp_developercourse_features');

function adjust_queries($query) {
  if(!is_admin() AND is_post_type_archive('program') AND is_main_query()){

    $query->set('orderby','title');
    $query->set('order','ASC');
    $query->set('posts_per_page','-1');

  }
  if(!is_admin() and is_post_type_archive('event') and $query->is_main_query()){
    $today= date('Ymd');
    $query->set('meta_key','event_date');
    $query->set('orderby','meta_value_num');
     $query->set('order','ASC');
    $query->set('meta_query',array(
                array(
                  'key'=>'event_date',
                  'compare'=>'>=',
                  'value'=>$today,
                  'type'=> 'numeric' 
                )
              )

    );
  }
}
add_action('pre_get_posts','adjust_queries');
