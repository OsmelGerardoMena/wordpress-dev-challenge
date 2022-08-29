<?php

if ( ! defined('ABSPATH') ) {
    die('Direct access not permitted.');
}


//meta boxes creation 
add_action( 'add_meta_boxes', 'adding_custom_meta_boxes', 10, 2 );


//Add option to wordpress admin menu
add_action("admin_menu", "menu_link_error");

