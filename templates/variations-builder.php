<?php
if ( !defined( 'ABSPATH' ) ) exit;

global $product;

$steps = wcvv_get_product_steps();

?>
<form id="product-builder" method="post" enctype='multipart/form-data'>
	<input type="hidden" value="<?php echo esc_attr(floatval( $product->get_regular_price() )); ?>" name="vv[original_price]" id="vv-original_price">
	<input type="hidden" value="<?php echo esc_attr(floatval( $product->get_price() )); ?>" name="vv[starting_price]" id="vv-starting_price">
	<input type="hidden" value="<?php echo esc_attr( wp_create_nonce('wcvv-add-to-cart') ); ?>" name="vv[nonce]" id="vv-nonce">
	<input type="hidden" value="<?php echo esc_attr(isset($_REQUEST['vv']['calculated_price']) ? floatval($_REQUEST['vv']['calculated_price']) : 0); ?>" name="vv[calculated_price]" id="vv-calculated_price">
	<input type="hidden" value="<?php echo esc_attr(get_the_ID()); ?>" name="add-to-cart" >

	<div class="builder-actual-steps">
	<?php
	foreach( $steps as $x => $step ) {
		$step_title = $step['variation_title'];
		$step_slug = wcvv_get_step_id( $step_title );
		$step_number = $x + 1; // Start with step 1

		$step_field_name = 'vv[step]['. $step_number .']';

		$has_images = false;
		$has_prices = false;
		foreach( $step['options'] as $opt ) {
			if ( $opt['image'] ) { $has_images = true; }
			if ( floatval($opt['price']) > 0 ) { $has_prices = true; }
		}

		$selection = false;
		if ( isset($_REQUEST['vv']['step'][$step_number]['option']) ) $selection = stripslashes($_REQUEST['vv']['step'][$step_number]['option']);

		$classes = array();
		$classes[] = 'builder-step';
		$classes[] = 'builder-step-' . $step_number;
		$classes[] = $has_images ? 'builder-has-images' : 'builder-no-images';
		$classes[] = $has_prices ? 'builder-has-prices' : 'builder-no-prices';

		?>
		<div class="<?php echo implode(' ', $classes); ?>" data-step="<?php echo $step_number; ?>">

			<?php do_action( 'wcvv-before-variation', $step, $step_number, $product ); ?>

			<div class="step-header">
				<h3 class="step-title"><span class="step-number">Step <?php echo $step_number; ?></span>: <?php echo esc_html($step_title); ?></h3>

				<input type="hidden" value="<?php echo esc_attr($step_title); ?>" name="<?php echo $step_field_name; ?>[title]" id="vv-step_<?php echo $step_number; ?>_title" class="step-field-title">
				<input type="hidden" value="<?php echo esc_attr($step_slug); ?>" name="<?php echo $step_field_name; ?>[slug]" id="vv-step_<?php echo $step_number; ?>_slug" class="step-field-slug">
				<input type="hidden" value="<?php echo esc_attr($step_number); ?>" name="<?php echo $step_field_name; ?>[number]" id="vv-step_<?php echo $step_number; ?>_number" class="step-field-number">
			</div>

			<div class="step-collapsible">
				<?php do_action( 'wcvv-unattached-reference-images', $x, $step, $product ); ?>

				<div class="step-options">

					<?php do_action( 'wcvv-before-customizer-options' ); ?>

					<div class="vv-option-skip">
					<input type="radio" value="_skip" <?php checked($selection && $selection === '_skip'); ?> name="<?php echo $step_field_name; ?>[option]" id="vv-step_<?php echo $step_number; ?>_option-skip" tabindex="-1" class="step-field-skip">
					</div>

					<div class="step-option-container">
						<?php
						foreach( $step['options'] as $y => $option ) {
							$name = $option['name'];
							$price = $option['price'];
							$image = $option['image'];
							$skip = $option['skip'];

							$image_src = false;

							if ( $has_images ) {
								// Find the best size image src to use
								// NOTE: Remove the "FALSE &&" to use small, cropped photos
								if ( FALSE && !empty($image['sizes']['icon']) ) $image_src = $image['sizes']['icon'];
								else if ( !empty($image['sizes']['shop_thumbnail']) ) $image_src = $image['sizes']['shop_thumbnail'];
								else if ( !empty($image['sizes']['thumbnail']) ) $image_src = $image['sizes']['thumbnail'];
								else if ( !empty($image['url']) ) $image_src = $image['url'];
								else {
									$image_src = WCVV_URL . '/assets/placeholder.png';
									$image = array( 'url' => $image_src, 'alt' => 'No image' );
								}
							}

							$checked = $selection && $selection === $name;
							?>
							<div class="step-option step-option-<?php echo $y; ?>" data-option="<?php echo $y; ?>">

								<?php do_action( 'wcvv-before-variation-option', $option, $y, $step, $step_number, $product ); ?>

								<input type="radio" value="<?php echo esc_attr($name); ?>" <?php checked($checked, true); ?> name="<?php echo $step_field_name; ?>[option]" id="vv-step_<?php echo $step_number; ?>_option-<?php echo $y; ?>" data-skip-steps="<?php echo esc_attr($skip); ?>" data-title="<?php echo esc_attr($name); ?>" data-price="<?php echo esc_attr($price); ?>">
								<label class="step-option-inner" for="vv-step_<?php echo $step_number; ?>_option-<?php echo $y; ?>">
									<?php if ( $has_images ) { ?>
									<div class="option-image">
										<?php if ( $image['url'] !== $image_src ) { ?>
										<a href="<?php echo esc_attr($image['url']); ?>#<?php echo (int) $y; ?>" class="option-zoom" data-rel="prettyPhoto[kiva-step-<?php echo $step_number; ?>]" title="<?php echo esc_attr($name); ?>" ><span class="icon icon-zoom"><span>Zoom</span></span></a>
										<?php } ?>

										<img src="<?php echo esc_attr($image_src); ?>" alt="<?php echo esc_attr(empty($image['alt']) ? '' : $image['alt']); ?>" title="<?php echo esc_attr(empty($image['alt']) ? '' : $image['alt']); ?>">
									</div>
									<?php } ?>

									<div class="option-title"><?php echo esc_html($name); ?></div>

									<?php if ( $has_prices ) { ?>
									<div class="option-price option-price-<?php echo floatval($price) > 0 ? 'cost' : 'free'; ?>"><?php echo floatval($price) > 0 ? 'Add ' . wc_price($price) : 'Add $0'; ?></div>
									<?php } ?>
								</label>

								<?php do_action( 'wcvv-after-variation-option', $option, $y, $step, $step_number, $product ); ?>

							</div>
							<?php
						}
						?>
					</div>
					<div class="clear"></div>
				</div>
				<div class="clear"></div>
			</div>

			<?php do_action( 'wcvv-after-variation', $step, $step_number, $product ); ?>

		</div>
		<?php
	}
	?>
	</div>

	<div id="builder-final" class="builder-step">
		<div class="builder-final-inner">
			<div class="step-header">
				<h3 class="step-title builder-step-indicator"><span class="step-current">Step 1</span> of <span class="step-total"><?php echo $step_number; ?></span></h3>
				<h3 class="step-title builder-step-complete" style="display: none;">Completed</h3>
			</div>

			<div class="builder-subtotal"><span class="total-label">Subtotal</span> <span class="total-value">$<span class="total-price">0.00</span></span></div>
			<div class="builder-total" style="display: none;"><span class="total-label">Total</span> <span class="total-value">$<span class="total-price">0.00</span></span></div>

			<div class="builder-add-cart">
				<div class="builder-add-to-cart-placeholder">
					<button type="button" class="button builder-add-to-cart disabled" disabled>Add to Cart</button>
				</div>
				<div class="builder-add-to-cart-submit" style="display: none;">
					<div class="builder-qty">
						<label class="qty"><strong>Quantity:</strong>
							<?php woocommerce_quantity_input( array(
							'min_value'   => apply_filters( 'woocommerce_quantity_input_min', 1, $product ),
							'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->backorders_allowed() ? '' : $product->get_stock_quantity(), $product ),
							'input_value' => ( isset( $_POST['quantity'] ) ? wc_stock_amount( $_POST['quantity'] ) : 1 )
							) ); ?>
						</label>
					</div>

					<div class="builder-submit">
						<button type="submit" class="button builder-add-to-cart single_add_to_cart_button">Add to Cart</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<?php /*
	$counter = 1;

	<?php foreach($steps as $i => $step): ?>
		<div class="customizer_step_cotainer step_container_<?php echo $counter ?> <?php echo ($counter==1 ? 'current_step' : '') ?>">
			<div class="customizer_cover"></div>
			<a href="#null" class="goto_step_<?php echo $counter ?>" step="<?php echo $counter ?>"><?php echo $counter; ?>. Choose <?php echo $step['variation_title'] ?></a>

			<select name="customizer_step_<?php echo $counter ?>" step="<?php echo $counter ?>">
				<option value="">-</option>
				<?php foreach($step['options'] as $j => $option): ?>
					<option price="<?php echo $option["price"] ?>" skip="<?php echo implode(',',$option["skip"]); ?>" value="<?php echo esc_attr($option["name"]); ?>"><?php echo esc_html($option["name"]); ?></option>
				<?php endforeach; ?>
			</select>

			<?php if($step['options'][0]['image'] != ''): ?>
				<div class="show_picture_choices">
					<?php foreach($step['options'] as $j => $option): ?>
						<a title="<?php echo $option["caption"] ?>" href="#null" class="kiva_customizer_image <?php echo sanitize_title($option["title"]) ?>">
							<div class="kiva_customizer_image" style="background-image:url(<?php echo $option["image"] ?>);"></div>
							<div class="kiva_customizer_caption"><?php echo $option["caption"] ?></div>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

		</div>
		<?php $counter++; ?>
	<?php endforeach; ?>

	<div class="kiva_total_display">Total: <span></span></div>
	<div class="kiva_steps_display">Step 1 of <?php echo ($counter-1) ?></div>
	<div class="kiva_skip_data" style="display:none;"></div>
	*/ ?>
</form>