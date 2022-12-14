<?php
/**
 * Plugin Name:       Optional Force Sells
 * Plugin URI:        https://github.com/melek/optional-force-sells/
 * Description:       Extends WooCommerce Force Sells by allowing force sells on a product to be optional.
 * Version:           1.0a
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Lionel Di Giacomo
 * Author URI:        https://github.com/melek
 * License:           GPL 3.0
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       woocommerce-optional-force-sells
 */

if ( class_exists( 'WC_Force_Sells' ) && class_exists( 'WooCommerce' ) ) {
	add_action( 'woocommerce_before_add_to_cart_button', 'ofs_synced_product_add_ons', 9 );     // Front end: Show custom input field above Add to Cart
	add_filter( 'woocommerce_add_cart_item_data', 'ofs_product_add_on_cart_item_data', 10, 2 ); // Save custom input field value into cart item data
	add_filter( 'wc_force_sell_add_to_cart_product', 'ofs_filter_forced_sells', 10, 2 );        // Make sure the selected IDs are the only force-sells added to the cart
	add_action( 'woocommerce_product_options_related', 'ofs_display_options', 11 );                     // Render Optional Force Sells checkbox in Linked Products tab.
	add_action( 'woocommerce_admin_process_product_object', 'ofs_save_options', 1 );              // Save Optional Force Sell option in post meta when product is saved.
	add_action(
		'wp_head',
		function () {
			// A bit of custom CSS.
			echo '<style>.ofs-container { padding-bottom: 1.5rem; }</style>';
		}
	);

	function ofs_synced_product_add_ons() {
		if ( ! get_post_meta( get_the_ID(), 'ofs_enabled', false ) ) {
			return;
		}

		// Remove the default single product display of force-sells
		remove_action( 'woocommerce_after_add_to_cart_button', array( WC_Force_Sells::get_instance(), 'show_force_sell_products' ) );
		echo '<div class="wc-force-sells ofs-container">' . wp_kses_post( __( 'Options', 'woocommerce-optional-force-sells' ) );
		$fs_ids = ofs_get_force_sell_ids( get_the_ID(), array( 'normal', 'synced' ) );
		foreach ( $fs_ids as $fs_id ) {
			$fs_product = wc_get_product( $fs_id );
			printf(
				'<div class="ofs-single-product-option">'
				. '<input type="checkbox" id="%s" name="ofs_selected_force_sells[]" value="%s">'
				. '<label for="%s">%s - %s</label></div>',
				esc_attr( 'ofs_option_' . $fs_id ),
				esc_attr( $fs_id ),
				esc_attr( 'ofs_option_' . $fs_id ),
				wp_kses_post( wp_unslash( $fs_product->get_title() ) ),
				wp_kses_post( wp_unslash( $fs_product->get_price_html() ) ),
			);
		}
		echo '<input type="hidden" name="ofs_selected_force_sells[]" value="">';
		echo '</div>';
	}

	function ofs_product_add_on_cart_item_data( $cart_item, $product_id ) {
		if ( ! empty( $_POST['ofs_selected_force_sells'] ) ) {
			$cart_item['ofs_selected_force_sells'] = array_map( 'intval', (array) $_POST['ofs_selected_force_sells'] );
		}
		return $cart_item;
	}

	function ofs_filter_forced_sells( $params, $cart_item ) {
		if ( ! isset( $_POST['ofs_selected_force_sells'] ) || ! is_array( $cart_item['ofs_selected_force_sells'] ) ) {
			return $params;
		}

		// If optional force sells are present, allow main products to be added even if force sells aren't.
		add_filter(
			'wc_force_sell_disallow_no_stock',
			function( $data ) {
				return false;
			}
		);
		if ( ! in_array( $params['id'], $cart_item['ofs_selected_force_sells'], false ) ) {
			$params['id'] = null;
		}
		return $params;
	}

	function ofs_display_options() {
		woocommerce_wp_checkbox(
			array(
				'id'          => 'ofs_enabled',
				'label'       => __( 'Optional Force Sells', 'woocommerce-optional-force-sells' ),
				'value'       => get_post_meta( get_the_ID(), 'ofs_enabled', true ) ? 'yes' : 'no',
				'description' => __( 'Allow customers to opt into force sells on the Single Product page.', 'woocommerce-optional-force-sells' ),
				'desc_tip'    => true,
			)
		);
	}

	function ofs_save_options( $product ) {
		if ( ! empty( $_POST['ofs_enabled'] ) ) {
			$product->update_meta_data( 'ofs_enabled', true );
		} else {
			$product->delete_meta_data( 'ofs_enabled' );
		}
	}

	// Some Force Sells functionality is private, so this duplicates the 'get_force_sell_ids' function with the $synced_types array built-in.
	function ofs_get_force_sell_ids( $product_id, $types ) {

		// Array & function are private, so copied from woocommerce-force-sells.php:38
		// Force Sells version 1.1.31
		$synced_types = array(
			'normal' => array(
				'field_name' => 'force_sell_ids',
				'meta_name'  => '_force_sell_ids',
			),
			'synced' => array(
				'field_name' => 'force_sell_synced_ids',
				'meta_name'  => '_force_sell_synced_ids',
			),
		);

		// Function is private, so copied from woocommerce-force-sells.php:457
		// Force Sells version 1.1.31
		if ( ! is_array( $types ) || empty( $types ) ) {
			return array();
		}
		$ids = array();

		foreach ( $types as $type ) {
			$new_ids = array();

			if ( isset( $synced_types[ $type ] ) ) {
				$new_ids = get_post_meta( $product_id, $synced_types[ $type ]['meta_name'], true );

				if ( is_array( $new_ids ) && ! empty( $new_ids ) ) {
					$ids = array_merge( $ids, $new_ids );
				}
			}
		}

		return $ids;
	}
}
