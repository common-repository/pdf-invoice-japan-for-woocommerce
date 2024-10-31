<?php
/**
 * Uninstall
 *
 * @package PDF Invoice Japan for WooCommerce
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

global $wpdb;
/* For Single site */
if ( ! is_multisite() ) {
	delete_option( 'invoicejapan' );
	delete_option( 'invoicejapan_mail_timing' );
	delete_option( 'invoicejapan_gateway_mail_timing' );
	delete_option( 'invoicejapan_gateway_remarks' );
	delete_option( 'invoicejapan_gateway_refunds' );
} else {
	/* For Multisite */
	$blog_ids         = $wpdb->get_col( "SELECT blog_id FROM {$wpdb->prefix}blogs" );
	$original_blog_id = get_current_blog_id();
	foreach ( $blog_ids as $blogid ) {
		switch_to_blog( $blogid );
		delete_option( 'invoicejapan' );
		delete_option( 'invoicejapan_mail_timing' );
		delete_option( 'invoicejapan_gateway_mail_timing' );
		delete_option( 'invoicejapan_gateway_remarks' );
		delete_option( 'invoicejapan_gateway_refunds' );
	}
	switch_to_blog( $original_blog_id );
}


