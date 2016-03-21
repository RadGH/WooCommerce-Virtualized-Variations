<?php
if( !defined( 'ABSPATH' ) ) exit;

function wcvv_display_unattached_images( $variation_index, $step, $product ) {
	global $product;

	static $uniqs;

	if ( $uniqs === null ) {
		$uniqs = get_option( 'unique_attachments' );
		if ( empty($uniqs) ) $uniqs = array();
	}

	$has_images = false;
	foreach( $step['options'] as $opt ) if ( $opt['image'] ) { $has_images = true; break; }

	// Missing images are OK as long as there is one image.
	if ( $has_images ) return;

	// If no images were imported, check if the import did have some photos
	$other_images = get_post_meta( $product->id, 'imp_v'. ($variation_index+1) .'photos', true ); // Imported variations start from 1, not zero.
	if ( !$other_images ) return;

	$i = explode(",", $other_images);

	ob_start(); // begin capturing output
	foreach( $i as $index => $image ) {
		$key = sanitize_title_with_dashes( $image );
		$attachment_id = isset($uniqs[$key]) ? $uniqs[$key] : false;
		if ( !$attachment_id ) continue;

		$attachment = wp_get_attachment_image_src( $attachment_id, 'full' );
		if ( !$attachment ) continue;

		// If only one or two photos are attached, let them have larger thumbnails.
		if ( count($i) <= 2 ) {
			$thumb = wp_get_attachment_image_src( $attachment_id, 'medium' );
		}else{
			$thumb = wp_get_attachment_image_src( $attachment_id, 'icon' );
			if ( !$thumb || $thumb[0] === $attachment[0] ) $thumb = wp_get_attachment_image_src( $attachment_id, 'thumbnail' );
		}

		if ( $attachment ) {
			$alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			?>
			<div class="reference-image">
				<a href="<?php echo esc_attr($attachment[0]); ?>" target="_blank" data-rel="prettyPhoto[reference-<?php echo $variation_index; ?>]"><img src="<?php echo esc_attr($thumb ? $thumb[0] : $attachment[0]); ?>" alt="<?php echo esc_attr($alt); ?>" title="<?php echo esc_attr($alt); ?>"></a>
				<div class="reference-title"><?php echo esc_html($alt); ?></div>
			</div>
			<?php
		}
	}

	$html = ob_get_clean();

	if ( $html ) {
		$count_term = 'many';
		if ( count($i) == 2 ) $count_term = 'two';
		if ( count($i) == 1 ) $count_term = 'one';
	?>
	<div class="reference-image-container reference-count-<?php echo $count_term; ?>">
		<div class="reference-header">
			<h3>Reference Image<?php if ( count($i) !== 1 ) echo 's'; ?></h3>
		</div>

		<div class="reference-items">
			<?php echo $html; ?>
			<div class="clear"></div>
		</div>
	</div>
	<?php

		add_action( 'wcvv-before-customizer-options', 'wcvv_display_product_options_header_next_to_deprecated_media' );
	}
}
add_action( 'wcvv-unattached-reference-images', 'wcvv_display_unattached_images', 10, 3 );


function wcvv_display_product_options_header_next_to_deprecated_media() {
	remove_action( 'wcvv-before-customizer-options', 'wcvv_display_product_options_header_next_to_deprecated_media' );
	?>
	<div class="step-option-title">
		<h3>Choose an option</h3>
	</div>
	<?php
}