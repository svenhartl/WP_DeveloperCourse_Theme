<?php
function wp_developercourse_theme_files() {
  wp_enqueue_script('wp-developercourse-scripts', get_theme_file_uri('/build/index.js'),array('jquery'),'1.0.','true');
  wp_enqueue_style('wp-developercourse-main-styles', get_theme_file_uri('/build/style-index.css'));
  wp_enqueue_style('wp-developercourse-main-styles-1', get_theme_file_uri('/build/index.css'));
  wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
  wp_enqueue_style('google-font', '//fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i');

}

add_action('wp_enqueue_scripts', 'wp_developercourse_theme_files');
 
function admin_bar(){

  if(is_user_logged_in()){
    
    add_filter( 'show_admin_bar', '__return_true' , 1000 );
  }
}

function features(){  

//                      Dynamic navigation menu                           //

//************************************************************************

//  register_nav_menu('header-menu-location','Header Menu Location');
// register_nav_menu('footer-menu-location-1','Footer Menu Location 1');
// register_nav_menu('footer-menu-location-2','Footer Menu Location 2');


//************************************************************************

  add_theme_support('title-tag');
}

add_action('after_setup_theme','features');