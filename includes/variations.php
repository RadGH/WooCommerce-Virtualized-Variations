<?php
if ( !defined( 'ABSPATH' ) ) exit;

function wcvv_is_variable( $post_id = false ) {
	$has_variations = get_field( 'product_has_variations', $post_id );
	return $has_variations ? true : false;
}

function wcvv_is_customizable( $post_id = false ) {
	$is_customizable = get_field( 'product_is_customizable', $post_id );
	return $is_customizable ? true : false;
}

function wcvv_get_step_id( $step_title ) {
	return sanitize_title_with_dashes($step_title);
}

// Use custom price templates for virtualized variation products
function wcvv_custom_price_templates() {
	if ( has_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price' ) ) {
		// Archive prices
		remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price' );
		add_action( 'woocommerce_after_shop_loop_item_title', 'wcvv_template_loop_price' );

		// Singular prices
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price' );
		add_action( 'woocommerce_single_product_summary', 'wcvv_template_loop_single_price' );
	}
}
add_action( 'init', 'wcvv_custom_price_templates' );

function wcvv_template_loop_price() {
	if ( wcvv_is_variable( get_the_ID() ) ) {
		include( WCVV_PATH . '/templates/price-archive.php' );
	}else{
		wc_get_template( 'single-product/price.php' );
	}
}

function wcvv_template_loop_single_price() {
	if ( wcvv_is_variable( get_the_ID() ) ) {
		include( WCVV_PATH . '/templates/price-single.php' );
	}else{
		wc_get_template( 'loop/price.php' );
	}
}

// Load the customizer for "builder" variations
function wcvv_render_customizer_wizard() {
	//$steps = '{"step1":{"title":"Size and Blade Color","options":[{"title":"Option Title","image":"https:\/\/image.png","caption":"This is a caption","price":10,"skip":["step2","step4"]},{"title":"Option Title2","image":"https:\/\/image.png","caption":"This is a caption2","price":0,"skip":[]}]},"step2":{"title":"Another Option","options":[{"title":"Option Title","image":"https:\/\/image.png","caption":"This is a caption","price":0,"skip":[]},{"title":"Option Title2","image":"https:\/\/image.png","caption":"This is a caption2","price":0,"skip":[]}]}}';
	//update_post_meta(get_the_ID(), 'kiva_custom', $steps);

	if( wcvv_is_variable() ) {
		add_filter( 'wcvv_doing_builder', '__return_true' );
		include( WCVV_PATH . '/templates/variations-builder.php' );
		remove_filter( 'wcvv_doing_builder', '__return_true' );
	}
}
add_action( 'woocommerce_after_single_product', 'wcvv_render_customizer_wizard' );


// Prevent some sections from showing up on customizable products
function wcvv_hide_obsolete_fields_from_customizer_products() {
	if( wcvv_is_variable() ) {
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
		remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
	}
}
add_action( 'woocommerce_before_single_product', 'wcvv_hide_obsolete_fields_from_customizer_products' );

function wcvv_format_price( $v ) {
	$v = floatval($v);
	$v = number_format( $v, 2 ); //ceil( $v * 100 ) / 100;
	$v = wc_trim_zeros( $v );
	return $v;
}

function wcvv_get_added_price( $post_id ) {
	$min = get_post_meta( $post_id, 'wcvv-price-min', true );
	$max = get_post_meta( $post_id, 'wcvv-price-max', true );

	if ( $min === null || $min === "" || $min === false ) {
		wcvv_update_cached_variable_price( $post_id );
		$min = get_post_meta( $post_id, 'wcvv-price-min', true );
		$max = get_post_meta( $post_id, 'wcvv-price-max', true );
	}

	return array( floatval($min), floatval($max) );
}

// Indicate customizable products cost more than original price
/*
function wcvv_customizable_product_price_indicator( $price, $product, $sale_price = true ) {
	$price = wc_trim_zeros( $price );

	if( wcvv_is_variable() && !apply_filters( 'wcvv_doing_builder', false ) ) {
		list ( $min, $max ) = wcvv_get_added_price( $product->id, $sale_price );

		$variable_product_min = floatval($min) + floatval($price);

		if ( is_singular('product') && get_the_ID() === get_queried_object_id() ) {
			// Single product price
			if ( $variable_product_min ) {
				if ( $variable_product_min < $variable_product_max ) {
					// Some combinations cost more than others, indicate that "and up"
					$price = $variable_product_min . '<span class="wcvv-variable-price wcvv-single-price"> and up</span>';
				}else{
					// Only ever a specific price
					$price = $variable_product_min;
				}
			}else{
				$price .= '<span class="wcvv-variable-price wcvv-single-price"> and up</span>';
			}
		}else{
			if ( $variable_product_min ) {
				if ( ($variable_product_min && $variable_product_max) && $variable_product_min !== $variable_product_max ) {
					// Both values are set, and they are not equal. Show a range, eg $100 - $150
					$price = $variable_product_min . ' - $' . $variable_product_max;
				}else{
					// Only minimum price is relevant
					$price = $variable_product_min;
				}
			}else{
				// Price wasn't calculated, which shouldn't be possible at this point. Use the old method of adding a +
				$price .= '<span class="wcvv-variable-price">+</span>';
			}
		}
	}

	return $price;
}
add_filter( 'formatted_woocommerce_price', 'wcvv_customizable_product_price_indicator', 15, 5 );
*/


// Modify the product price outside of the main loop
/*
function wcvv_minimum_finished_price( $price, $product ) {
	if ( is_admin() ) return $price;
	if ( is_checkout() || is_cart() ) return $price;
	if ( !wcvv_is_variable( $product->id ) ) return $price;

	// Don't filter the price within the builder
	if ( apply_filters( 'wcvv_doing_builder', false ) ) return $price;

	list( $min, $max ) = wcvv_update_cached_variable_price( $product->id, false );

	return $price + $min;
}
add_filter( 'woocommerce_get_price', 'wcvv_minimum_finished_price', 10, 2 );
*/


function wcvv_update_cached_variable_price( $post_id ) {
	$steps = wcvv_get_product_steps( $post_id );
	$step_min_prices = array();
	$step_max_prices = array();
	$steps_skip = array();

	foreach( $steps as $step ) {
		if ( in_array( wcvv_get_step_id($step['variation_title']), $steps_skip, true ) ) continue;

		// Some insane price so that min() works.
		$lowest_price = 1000000;
		$highest_price = 0;
		foreach( $step['options'] as $opt ) {
			$highest_price = max( $highest_price, floatval($opt['price']) );

			// Skippable fields do not get added for min price
			if ( $opt['skip'] ) $steps_skip[] = wcvv_get_step_id($opt['skip']);
			$lowest_price = min( $lowest_price, floatval($opt['price']) );
		}
		if ( $lowest_price !== 1000000 && $lowest_price > 0 ) $step_min_prices[] = $lowest_price;
		if ( $highest_price > 0 ) $step_max_prices[] = $highest_price;
	}

	$added_min_price = array_sum( $step_min_prices );
	$added_max_price = array_sum( $step_max_prices );

	if ( !$added_min_price ) $added_min_price = 0;
	if ( !$added_max_price ) $added_max_price = 0;

	update_post_meta( $post_id, 'wcvv-price-min', $added_min_price );
	update_post_meta( $post_id, 'wcvv-price-max', $added_max_price );
}


function wcvv_update_cached_price_on_safe( $post_id ) {
	if ( get_post_type( $post_id ) !== 'product' ) return;
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;

	wcvv_update_cached_variable_price( $post_id );
}
add_action( 'save_post', 'wcvv_update_cached_price_on_safe', 40 );


// Replace the "Add to cart" form with a "Build your own" button for customizable products
function wcvv_customizable_start_catching_addtocart() {
	if( wcvv_is_variable() ) {
		add_action( 'woocommerce_after_add_to_cart_form', 'wcvv_customizable_stop_catching_addtocart', 20 );
		ob_start();
	}
}
function wcvv_customizable_stop_catching_addtocart() {
	ob_end_clean();
	?>
	<p class="wcvv-customize-button"><a href="#product-builder" class="goto_customizer_button button alt">Customize</a></p>
	<?php
}
add_action( 'woocommerce_before_add_to_cart_form', 'wcvv_customizable_start_catching_addtocart', 20 );


// Format and validate a list of steps to use for a product
function wcvv_get_product_steps( $post_id = null ) {
	if ( $post_id === null ) $post_id = get_the_ID();

	$steps = get_field( 'builder_variations', $post_id );
	if ( !$steps ) return false;

	$existing_titles = array();

	foreach( $steps as $step_index => $step ) {
		if ( empty($step['options']) ) {
			// No options shouldn't be allowed, but we can't remove it because the index will get wrecked.
			$step['options'] = array(
				'variation_title' => 'Empty Option',
				'options' => array(
					array(
						'name' => 'Default',
						'price' => 0,
						'image' => false,
						'skip' => '',
					),
				),
			);
		}

		if ( empty($step['variation_title']) ) {
			$steps[$step_index]['variation_title'] = '(Untitled Option)';
		}

		// Let's give an indication in case the same step title is used for two variations
		$initial_title = $step['variation_title'];
		$counter = 0;

		while ( isset($existing_titles[$steps[$step_index]['variation_title']]) ) {
			if ( $counter === 0 ) {
				$steps[$step_index]['variation_title'] = $initial_title . ' (Copy)';
			}else{
				$steps[$step_index]['variation_title'] = $initial_title . ' (Copy #'. $counter .')';
			}

			$counter++;
		}

		$existing_titles[$steps[$step_index]['variation_title']] = $steps[$step_index]['variation_title'];
	}

	return $steps;
}