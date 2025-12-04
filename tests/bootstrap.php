<?php
/**
 * PHPUnit bootstrap file for What Template plugin tests.
 *
 * @package What_Template
 */

// Load Composer autoloader.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Load WordPress test library.
$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php\n";
	echo "Please set WP_TESTS_DIR environment variable or run tests/bin/install-wp-tests.sh\n";
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( __DIR__ ) . '/what-template.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";

/**
 * Stub WP_Admin_Bar class for testing.
 *
 * WordPress test environment doesn't always load the admin bar class,
 * so we provide a minimal stub for mocking in tests.
 */
if ( ! class_exists( 'WP_Admin_Bar' ) ) {
	class WP_Admin_Bar {
		/**
		 * Add a node to the admin bar.
		 *
		 * @param array $args Node arguments.
		 */
		public function add_node( $args ) {
			// Stub method for testing.
		}
	}
}
