<?php
/*
 Plugin Name: What Template
 Plugin URI: http://wordpress.org/extend/plugins/what-template/
 Description: Adds the current page's template name to the admin bar.
 Version: 0.1
 Author: ironprogrammer
 Author URI: http://github.com/ironprogrammer
 */

function ip_admin_bar_what_template() {
	global $wp_admin_bar;
	global $template;
	if ( $template ) {
		$wp_admin_bar->add_menu( array(
			'id' => 'ip-template',
			'parent' => 'top-secondary',
			'title' => __( basename( $template ) )
		) );
	}
}
add_action( 'wp_before_admin_bar_render', 'ip_admin_bar_what_template' );
