<?php
/**
 * Plugin Name: What Template
 * Plugin URI: https://wordpress.org/plugins/what-template/
 * Description: Shows the current template name in the WordPress admin bar with support for classic, hybrid, and block themes.
 * Author: ironprogrammer
 * Author URI: https://brianalexander.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: what-template
 * Version: 2.0.0
 * Requires at least: 5.9
 * Tested up to: 6.9
 * Requires PHP: 7.4
 *
 * @package What_Template
 */

namespace IronProgrammer\WhatTemplate;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load the main plugin class.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-what-template.php';

// Initialize the plugin.
new What_Template();
