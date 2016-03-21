<?php
/**
 * Loop Price
 *
 * This template is based on the original from woocommerce/loop/price.php.
 *
 * It is only used for virtualized variation products. It allows us to detect sale price versus regular price.
 *
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $product;

$price_output = '';
$reg_price_output = '';

// Get prices added by variations, array of min and max combined
$r = wcvv_get_added_price( $product->id );
$added_min = $r[0];
$added_max = $r[1];

// Calculate display of current price
$price = floatval( $product->get_price() );
$total_min = $price + $added_min;
$total_max = $price + $added_max;
if ( $total_min > 0 || $total_max > 0 ) {
	$price_output = '$' . wcvv_format_price( $total_min );
	if ( $total_min !== $total_max ) {
		$price_output.= ' - $' . wcvv_format_price( $total_max );
	}
}else{
	var_dump('no price?');
	return;
}

// Calculate display of regular price
$reg_price = floatval( $product->get_regular_price() );
$total_min = $reg_price + $added_min;
$total_max = $reg_price + $added_max;
if ( $reg_price !== $price ) {
	$reg_price_output = '$' . wcvv_format_price( $total_min );
	if ( $total_min !== $total_max ) {
		$reg_price_output.= ' - $' . wcvv_format_price( $total_max );
	}
}

// -------
// Output the values

echo '<span class="wcvv-price price">';

if ( $reg_price_output ) {
	echo '<del>';
	echo '<span class="amount">', $reg_price_output, '</span>';
	echo '</del>';
}

if ( $price_output ) {
	if ( $reg_price_output ) echo '<ins>';
	echo '<span class="amount">';
	echo $price_output;
	echo '</span>';;
	if ( $reg_price_output ) echo '</ins>';
}

echo '</span>';