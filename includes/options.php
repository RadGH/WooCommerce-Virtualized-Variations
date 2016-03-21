<?php
if ( !defined( 'ABSPATH' ) ) exit;

if( function_exists('acf_add_options_sub_page') ) {
	include( WCVV_PATH . '/fields/variations.php' );
}