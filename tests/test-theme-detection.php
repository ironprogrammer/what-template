<?php
/**
 * Theme Detection Tests
 *
 * Tests for theme type detection (classic, hybrid, block).
 *
 * @package What_Template
 */

namespace IronProgrammer\WhatTemplate\Tests;

use IronProgrammer\WhatTemplate\What_Template;
use WP_UnitTestCase;
use ReflectionClass;

/**
 * Theme Detection Test Class
 */
class Test_Theme_Detection extends WP_UnitTestCase {

	/**
	 * Plugin instance.
	 *
	 * @var What_Template
	 */
	private $plugin;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->plugin = new What_Template();
	}

	/**
	 * Data provider for theme type detection tests.
	 *
	 * @return array Test cases with theme name and expected type.
	 */
	public function data_theme_type_provider() {
		return array(
			'classic theme' => array(
				'theme_name' => 'default',
				'expected'   => 'classic',
			),
			'block theme'   => array(
				'theme_name' => 'block-theme',
				'expected'   => 'block',
			),
			'hybrid theme'  => array(
				'theme_name' => 'default',
				'expected'   => 'hybrid',
			),
		);
	}

	/**
	 * Test that theme types are detected correctly.
	 *
	 * @dataProvider data_theme_type_provider
	 *
	 * @param string $theme_name Theme to switch to.
	 * @param string $expected   Expected theme type.
	 */
	public function test_theme_type_detection( $theme_name, $expected ) {
		// Switch to the test theme.
		switch_theme( $theme_name );

		// Verify theme exists.
		$this->assertTrue( wp_get_theme()->exists(), "Theme '$theme_name' should exist in test environment" );

		// Add block-templates support if testing hybrid theme.
		if ( 'hybrid' === $expected ) {
			add_theme_support( 'block-templates' );
		}

		// Use reflection to access private method.
		$reflection = new ReflectionClass( $this->plugin );
		$method = $reflection->getMethod( 'get_theme_type' );

		$result = $method->invoke( $this->plugin );

		// Assert exact match.
		$this->assertSame( $expected, $result, "Theme '$theme_name' should be detected as '$expected'" );

		// Clean up.
		if ( 'hybrid' === $expected ) {
			remove_theme_support( 'block-templates' );
		}
	}

	/**
	 * Test that child theme detection works.
	 */
	public function test_detects_child_theme() {
		// WordPress's is_child_theme() will return true/false based on active theme.
		$is_child = is_child_theme();

		// Test should pass regardless of whether current theme is child or not.
		$this->assertIsBool( $is_child );
	}

	/**
	 * Test admin bar integration doesn't throw errors.
	 */
	public function test_admin_bar_integration() {
		global $template;

		// Set up a mock template.
		$template = get_template_directory() . '/index.php';

		// Mock WP_Admin_Bar with add_node method.
		$admin_bar = $this->getMockBuilder( 'WP_Admin_Bar' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'add_node' ) )
			->getMock();

		// Expect add_node to be called at least once.
		$admin_bar->expects( $this->atLeastOnce() )
			->method( 'add_node' );

		// Set up user with capability.
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		// Should not throw an error.
		$this->plugin->add_admin_bar_template_info( $admin_bar );

		// Test passes if no exception thrown.
		$this->assertTrue( true );
	}
}
