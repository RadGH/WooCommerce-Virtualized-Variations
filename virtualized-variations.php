<?php
/*
Plugin Name: WooCommerce - Virtualized Variations
Version:     1.0.0
Plugin URI:  http://radgh.com/
Description: Simplified variations without needing attributes. Options modify the original product price instead of being managed separately. Optional step-by-step product "builder", or use dropdowns like standard WooCommerce. Does not conflict nor convert the default variations, you can use both simultaneously.
Author:      Radley Sustaire
Author URI:  mailto:radleygh@gmail.com
License:     GPL2
*/

/*
GNU GENERAL PUBLIC LICENSE

A WordPress plugin for WooCommerce. Allows products to be customized
with different options that add to the original price of the product,
without creating separate products for every variations.

Copyright (C) 2015 Radley Sustaire

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>
*/

if( !defined( 'ABSPATH' ) ) exit;

define( 'WCVV_URL', untrailingslashit(plugin_dir_url( __FILE__ )) );
define( 'WCVV_PATH', dirname(__FILE__) );
define( 'WCVV_VERSION', '1.0.0' );

add_action( 'plugins_loaded', 'wcvv_init_plugin' );

function wcvv_init_plugin() {
	if ( !class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'wcvv_wc_not_found' );
		return;
	}

	if ( !function_exists('acf') ) {
		add_action( 'admin_notices', 'wcvv_acf_not_found' );
		return;
	}

	include( WCVV_PATH . '/includes/options.php' );
	include( WCVV_PATH . '/includes/enqueue.php' );
	include( WCVV_PATH . '/includes/variations.php' );
	include( WCVV_PATH . '/includes/variations-cart.php' );

	include( WCVV_PATH . '/includes/deprecated.php' );
}

function wcvv_wc_not_found() {
	?>
	<div class="error">
		<p><strong>WooCommerce - Virtualized Variations: Error</strong></p>
		<p>The required plugin <strong>WooCommerce</strong> is not running. Please activate this required plugin, or disable WooCommerce - Virtualized Variations.</p>
	</div>
	<?php
}

function wcvv_acf_not_found() {
	?>
	<div class="error">
		<p><strong>WooCommerce - Virtualized Variations: Error</strong></p>
		<p>The required plugin <strong>Advanced Custom Fields Pro</strong> is not running. Please activate this required plugin, or disable WooCommerce - Virtualized Variations.</p>
	</div>
	<?php
}