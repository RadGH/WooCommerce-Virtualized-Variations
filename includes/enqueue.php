<?php
if ( !defined( 'ABSPATH' ) ) exit;

add_action( 'wp_enqueue_scripts', 'wcvv_enqueue_scripts', 25 );

function wcvv_enqueue_scripts() {
	wp_enqueue_script( 'virtualized-variations', WCVV_URL . '/assets/virtualized-variations.js', array( 'jquery' ), WCVV_VERSION );
	wp_enqueue_style( 'virtualized-variations', WCVV_URL . '/assets/virtualized-variations.css', array(), WCVV_VERSION );
}

add_action( 'admin_enqueue_scripts', 'wcvv_admin_enqueue_scripts', 25 );

function wcvv_admin_enqueue_scripts() {
	wp_enqueue_style( 'admin-virtualized-variations', WCVV_URL . '/assets/admin-vv.css', array(), WCVV_VERSION );
}