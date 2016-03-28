<?php
function wcvv_get_submitted_product( $valid, $product_id, $quantity ) {
	if ( !isset($_REQUEST['vv']['nonce']) ) return false;
	if ( !wp_verify_nonce( stripslashes($_REQUEST['vv']['nonce']), 'wcvv-add-to-cart' ) ) return false;

	// Data that was sent in the request:
	$submitted_steps = stripslashes_deep($_REQUEST['vv']['step']);
	$calculated_price = floatval( stripslashes($_REQUEST['vv']['calculated_price']) );

	// Variables used during our calculation:
	$skipped_steps = array();
	$features = array();
	$steps = wcvv_get_product_steps( $product_id );

	ob_start();

	// Run through original steps, validate them among the submitted steps
	foreach( $steps as $step ) {
		$step_title = $step['variation_title'];

		// Check if we should skip this step
		if ( in_array( $step_title, $skipped_steps, true ) ) {
			$features[] = array(
				'title' => $step['variation_title'],
				'value' => 'N/A',
				'price' => 0,
				'image' => false
			);
			continue; // Skip this step!
		}

		// Look up the step in the user's submitted data
		$selection = wcvv_step_search_for_submitted( $submitted_steps, $step['variation_title'], $step['options'] );
		if ( !$selection ) {
			return false;
		}

		$skip_string = $selection['option']['skip'];

		if ( $skip_string ) {
			$s = explode( ',', $skip_string );
			$s = array_map('trim', $s);
			if ( !empty($s) ) $skipped_steps = array_merge( $skipped_steps, $s );
		}

		$features[] = array(
			'title' => $step['variation_title'],
			'value' => $selection['option']['name'],
			'price' => floatval( $selection['option']['price'] ),
			'image' => empty($selection['option']['image']['ID']) ? false : $selection['option']['image']['ID']
		);
	}

	// Get the base price, without any options
	$product = new WC_Product($product_id);
	//remove_filter( 'woocommerce_get_price', 'wcvv_minimum_finished_price', 10 );
	$base_price = floatval( $product->get_price() );
	//add_filter( 'woocommerce_get_price', 'wcvv_minimum_finished_price', 10, 2 );

	// Calculate a new total price
	$total_price = wcvv_calculate_price( $base_price, $features );
	if ( abs($calculated_price - $total_price) > 0.25 ) {
		// Price has changed by more than $0.25 compared to what the client saw. Give a warning.
		wc_add_notice( 'The price of the "'. esc_html(get_the_title($product_id)).'" has changed from $'. esc_html(wc_price($calculated_price)) .' to $'. esc_html(wc_price($total_price)) .'. Please double-check that your customizations have been added correctly.', 'notice' );
	}

	return array(
		'base_price' => $base_price,
		'total_price' => $total_price,
		'features' => $features,
	);
}


// Calculate the price of a product with added features, not including multiple quantities
function wcvv_calculate_price( $base_price, $features ) {
	$prices = wp_list_pluck( $features, 'price' );

	return empty($prices) ? $base_price : ($base_price + array_sum( $prices ));
}

// Get a submitted step with the same step title or slug as the provided step
function wcvv_step_search_for_submitted( $submitted_steps, $target_title, $target_options ) {
	foreach( $submitted_steps as $x => $step ) {
		if ( $target_title === $step['title'] ) {
			// Title matches, search for an option
			foreach( $target_options as $y => $option ) {
				if ( $option['name'] === $step['option'] ) {
					return array(
						'step_key' => $x,
						'step' => $step,
						'option_key' => $y,
						'option' => $option
					);
				}
			}
			wc_add_notice( 'The required step ('. esc_html($target_title) .') was found, but the selection ('. esc_html($step['option']) .') does not exist.', 'error' );
			return false;
		}
	}
	wc_add_notice( 'The required step ('. esc_html($target_title) .') was not found in your request.', 'error' );
	return false;
}

// Add to cart validation
function wcvv_add_to_cart_validation( $valid, $product_id, $quantity ) {
	if ( !wcvv_is_variable($product_id) ) return $valid;

	global $wcvv_product_data;

/*
$wcvv_product_data:

array(2) {
  ["base_price"]=>
  float(512)
  ["total_price"]=>
  float(699)
  ["features"]=>
  array(12) {
    [0]=>
    array(4) {
      ["title"]=>
      string(20) "Size and Blade Color"
      ["value"]=>
      string(44) "52 inch Carved Star Blades in Antique Walnut"
      ["price"]=>
      float(120)
      ["image"]=>
      bool(false)
    }
    [1]=>
    array(4) {
      ["title"]=>
      string(20) "Housing Finish Color"
      ["value"]=>
		...
	}
  }
}
*/

	$wcvv_product_data = wcvv_get_submitted_product( $valid, $product_id, $quantity  );
	if ( !$wcvv_product_data ) return false;

	if ( $wcvv_product_data ) {
		return $valid;
	}else{
		wc_add_notice( 'Failed to retrieve submitted product details', 'error' );
		return false;
	}
}
add_action( 'woocommerce_add_to_cart_validation', 'wcvv_add_to_cart_validation', 10, 3 );

// Add metadata to the item while it is in your cart.
function wcvv_add_cart_item_data( $cart_item_data, $product_id ) {
	if ( !wcvv_is_variable($product_id) ) return $cart_item_data;

	global $wcvv_product_data;
	if ( !isset($wcvv_product_data) ) {
		wc_add_notice( 'The product data for your product ('. esc_html(get_the_title($product_id)) .') could not be loaded.', 'error' );
		return $cart_item_data;
	}

	$cart_item_data['_wcvv_total_price'] = $wcvv_product_data['total_price'];
	$cart_item_data['_wcvv_base_price'] = $wcvv_product_data['base_price'];
	$cart_item_data['_wcvv_features'] = $wcvv_product_data['features'];

	return $cart_item_data;
}
add_action( 'woocommerce_add_cart_item_data', 'wcvv_add_cart_item_data', 10, 2 );

// Render the data on the cart page
function wcvv_display_cart_item_data( $cart_data, $cart_item = null ) {
	$custom_items = array();

	/* Woo 2.4.2 updates */
	if( !empty( $cart_data ) ) $custom_items = $cart_data; // WooCommerce 2.4.2 Update

	if( isset( $cart_item['_wcvv_features'] ) ) {
		foreach( $cart_item['_wcvv_features'] as $feature ) {
			if ( $feature['value'] == "N/A" ) continue; // Don't display skipped options to the customer, that might confuse them.

			$name = $feature['title'];
			$value = $feature['value'];

			// Insert price after value eg: "Horseshoe Pattern (Add $10)"
			if ( $feature['price'] > 0 ) {
				$value.= ' (Add '. wc_price($feature['price']) .')';
			}

			// Insert thumbnail left of title
			if ( $feature['image'] ) {
				$img_full = wp_get_attachment_image_src( $feature['image'], 'full' );
				$img = wp_get_attachment_image_src( $feature['image'], 'thumbnail' );

				$alt = get_post_meta( $feature['image'], '_wp_attachment_image_alt', true );
				if ( !$alt && $v = get_post($feature['image']) ) $alt = $v->post_excerpt;

				if ( !empty($img_full[0]) && !empty($img[0]) ) {
					$name = sprintf(
						'<a href="%s" class="vv-icon" target="_blank"><img src="%s" width="30" height="%s" alt="%s"></a> %s',
						esc_attr( $img_full[0] ),
						esc_attr( $img[0] ),
						esc_attr( ($img[2]/$img[1]) * 30 ), // width: 100: height: 50 == width: 30, height == 15; using: (height/width)*new_width
						esc_attr( $alt ),
						$name
					);
				}
			}

			// Add the fields to the custom item data to be displayed below the product name
			$custom_items[] = array(
				'name' => $name,
				'value' => $value
			);
		}
	}

	return $custom_items;
}
add_filter( 'woocommerce_get_item_data', 'wcvv_display_cart_item_data', 10, 2 );


// Convert the cart item data into similar order item metadata for the sales log, etc.
function wcvv_add_order_item_meta( $item_id, $values, $cart_item_key ) {
	if( isset( $values['_wcvv_total_price'] ) ) {
		wc_add_order_item_meta( $item_id, '_wcvv_total_price', $values['_wcvv_total_price'], true );
	}

	if( isset( $values['_wcvv_base_price'] ) ) {
		wc_add_order_item_meta( $item_id, '_wcvv_base_price', $values['_wcvv_base_price'], true );
	}

	if( isset( $values['_wcvv_features'] ) ) {
		wc_add_order_item_meta( $item_id, '_wcvv_features', $values['_wcvv_features'], true );
	}
}
add_action( 'woocommerce_add_order_item_meta', 'wcvv_add_order_item_meta', 10, 3 );


// Adjust the price of products given the _wcvv_total_price
function wcvv_calculate_total_price( $cart_object ) {
	foreach ( $cart_object->cart_contents as $cart_item_key => $item ) {
		if ( !wcvv_is_variable($item['product_id']) ) continue;

		// Update the price of the item to the total price
		if ( isset($item['_wcvv_total_price']) ) {
			$item['data']->price = $item['_wcvv_total_price'];
		}
	}
}
add_action( 'woocommerce_before_calculate_totals', 'wcvv_calculate_total_price' );


// Display options on receipt / order received screens
function wcvv_display_options_on_order_meta( $item_id, $item, $order ) {
//	$base_price = !empty($item['item_meta']['_wcvv_base_price'][0]) ? maybe_unserialize( $item['item_meta']['_wcvv_base_price'][0] ) : false;
//	$total_price = !empty($item['item_meta']['_wcvv_total_price'][0]) ? maybe_unserialize( $item['item_meta']['_wcvv_total_price'][0] ) : false;
	$features = !empty($item['item_meta']['_wcvv_features'][0]) ? maybe_unserialize( $item['item_meta']['_wcvv_features'][0] ) : false;

	$display_items = array();

	if ( $features ) {
		$count = 1;
		foreach( $features as $i => $feat ) {
			// Don't show N/A options to customers (skipped options), that might confuse them
			if ( !current_user_can( 'edit_shop_orders' ) && $feat['value'] == "N/A" ) continue;

			$val = esc_html($feat['value']);
			if ( $feat['price'] ) $val .= " (Add " . wp_strip_all_tags( wc_price( $feat['price'] ) ) . ")";

			$display_items[] = array(
				'key' => wcvv_get_step_id( $feat['title'] ),
				'label' => $count . ') ' . esc_html($feat['title']),
				'value' => $val
			);

			$count++;
		}
	}

	if ( !empty($display_items) ) {
		?>
		<div class="wcvv-order-meta">
			<?php foreach( $display_items as $display ) { ?>
				<dl class="variation">
					<dt class="variation-vv-<?php echo esc_attr($display['key']); ?>"><?php echo $display['label']; ?>:</dt>
					<dd class="variation-vv-<?php echo esc_attr($display['key']); ?>"><?php echo $display['value']; ?></dd>
				</dl>
			<?php } ?>
		</div>
		<?php
	}
}
add_action( 'woocommerce_order_item_meta_end', 'wcvv_display_options_on_order_meta', 30, 3 );


// Set post meta to be hidden from the order screen in the backend
function wcvv_hide_meta_keys_from_dashboard( $hidden_keys ) {
	$hidden_keys[] = '_wcvv_base_price';
	$hidden_keys[] = '_wcvv_total_price';
	$hidden_keys[] = '_wcvv_features';
	return $hidden_keys;
}
add_filter( 'woocommerce_hidden_order_itemmeta', 'wcvv_hide_meta_keys_from_dashboard' );


// Display order meta on the dashboard, formatted properly
function wcvv_display_custom_meta_on_dashboard( $item_id, $item, $_product) {
	$order_id = isset($_REQUEST['post']) ? intval($_REQUEST['post']) : get_the_ID();
	if ( !$order_id ) return;

	$order = wc_get_order($order_id);
	if ( !$order || is_wp_error($order) ) return;

	$base_price = $order->get_item_meta( $item_id, '_wcvv_base_price', true );
	$total_price = $order->get_item_meta( $item_id, '_wcvv_total_price', true );
	$features = $order->get_item_meta( $item_id, '_wcvv_features', true );

	//- ----

	$display_items = array();

	if ( $base_price ) {
		$display_items[] = array(
			'key' => 'base-price',
			'label' => 'Base Price',
			'value' => wc_price($base_price),
		);
	}

	if ( $total_price ) {
		$display_items[] = array(
			'key' => 'customization-price',
			'label' => 'Addon Fee',
			'value' => wc_price($total_price - $base_price),
		);
	}

	if ( !empty($display_items) && !empty($features) ) {

		$display_items[] = 'separator';
	}

	if ( $features ) {
		$count = 1;
		foreach( $features as $i => $feat ) {
			// Don't show N/A options to customers (skipped options), that might confuse them
			if ( !current_user_can( 'edit_shop_orders' ) && $feat['value'] == "N/A" ) continue;

			$val = esc_html($feat['value']);
			if ( $feat['price'] ) $val .= " (Add " . wp_strip_all_tags( wc_price( $feat['price'] ) ) . ")";

			$display_items[] = array(
				'key' => wcvv_get_step_id( $feat['title'] ),
				'label' => $count . ') ' . esc_html($feat['title']),
				'value' => $val
			);

			$count++;
		}
	}

	if ( !empty($display_items) ) {
		?>
		<div class="view wcvv-view">
			<table cellspacing="0" class="display_meta">
				<?php
				foreach( $display_items as $display ) {
					if ( $display === 'separator' ) {
						echo '<tr class="vv-sep"><td colspan="2"></td></tr>';
						continue;
					}
					?>
					<tr class="vv-<?php echo esc_attr($display['key']); ?>">
						<th><?php echo $display['label']; ?></th>
						<td><?php echo $display['value']; ?></td>
					</tr>
					<?php
				}
				?>
			</table>
		</div>
		<?php
	}
}
add_action( 'woocommerce_before_order_itemmeta', 'wcvv_display_custom_meta_on_dashboard', 7, 3 );