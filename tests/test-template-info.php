<?php
/**
 * Template Information Tests
 *
 * Tests for template information retrieval and display.
 *
 * @package What_Template
 */

namespace IronProgrammer\WhatTemplate\Tests;

use IronProgrammer\WhatTemplate\What_Template;
use WP_UnitTestCase;
use ReflectionClass;

/**
 * Template Info Test Class
 */
class Test_Template_Info extends WP_UnitTestCase {

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
	 * Data provider for friendly name tests.
	 *
	 * @return array Test cases with setup type, URL type, and expected substring.
	 */
	public function data_friendly_name_provider() {
		return array(
			'home page'   => array(
				'setup_type' => 'post',
				'url_type'   => 'home',
				'expected'   => array( 'Blog', 'Front' ), // Could be "Blog Home" or "Front Page".
			),
			'single post' => array(
				'setup_type' => 'post',
				'url_type'   => 'single_post',
				'expected'   => 'Post',
			),
			'page'        => array(
				'setup_type' => 'page',
				'url_type'   => 'single_page',
				'expected'   => 'Page',
			),
			'404'         => array(
				'setup_type' => null,
				'url_type'   => '404',
				'expected'   => '404',
			),
			'search'      => array(
				'setup_type' => null,
				'url_type'   => 'search',
				'expected'   => 'Search',
			),
		);
	}

	/**
	 * Test friendly name resolution for different page types.
	 *
	 * @dataProvider data_friendly_name_provider
	 *
	 * @param string|null  $setup_type Type of object to create ('post', 'page', or null).
	 * @param string       $url_type   Type of URL to navigate to.
	 * @param string|array $expected   Expected substring(s) in friendly name.
	 */
	public function test_friendly_name_resolution( $setup_type, $url_type, $expected ) {
		$object_id = null;

		// Create object if needed.
		if ( 'post' === $setup_type ) {
			$object_id = $this->factory->post->create();
		} elseif ( 'page' === $setup_type ) {
			$object_id = $this->factory->post->create( array( 'post_type' => 'page' ) );
		}

		// Navigate to URL based on type.
		switch ( $url_type ) {
			case 'home':
				$this->go_to( home_url() );
				break;
			case 'single_post':
			case 'single_page':
				$this->go_to( get_permalink( $object_id ) );
				break;
			case '404':
				// Trigger a 404 by going to a non-existent page.
				global $wp_query;
				$wp_query->set_404();
				status_header( 404 );
				break;
			case 'search':
				$this->go_to( home_url( '?s=test' ) );
				break;
		}

		// Get friendly name.
		$reflection = new ReflectionClass( $this->plugin );
		$method = $reflection->getMethod( 'get_friendly_name' );

		$result = $method->invoke( $this->plugin );

		// Assert result.
		$this->assertIsString( $result );
		$this->assertNotEmpty( $result );

		// Check expected substring(s).
		if ( is_array( $expected ) ) {
			// At least one of the expected substrings should be present.
			$found = false;
			foreach ( $expected as $substr ) {
				if ( str_contains( $result, $substr ) ) {
					$found = true;
					break;
				}
			}
			$this->assertTrue( $found, "Expected one of [" . implode( ', ', $expected ) . "] in '$result'" );
		} else {
			$this->assertStringContainsString( $expected, $result );
		}
	}

	/**
	 * Test edit link generation returns valid URL or null.
	 */
	public function test_edit_link_generation() {
		global $template;

		// Set up a mock template.
		$template = get_template_directory() . '/index.php';

		$template_info = array(
			'theme_type'    => 'classic',
			'template_file' => 'index.php',
		);

		$reflection = new ReflectionClass( $this->plugin );
		$method = $reflection->getMethod( 'get_template_edit_link' );

		$result = $method->invoke( $this->plugin, $template_info );

		// Should return string URL or null.
		$this->assertTrue( is_string( $result ) || is_null( $result ) );

		if ( is_string( $result ) ) {
			$this->assertStringContainsString( 'theme-editor.php', $result );
		}
	}

	/**
	 * Test that template info structure is correct for classic themes.
	 */
	public function test_classic_template_info_structure() {
		global $template;

		// Set up a mock template.
		$template = get_template_directory() . '/index.php';

		$reflection = new ReflectionClass( $this->plugin );
		$method = $reflection->getMethod( 'get_classic_template_info' );

		$result = $method->invoke( $this->plugin );

		// Should return array or null.
		$this->assertTrue( is_array( $result ) || is_null( $result ) );

		if ( is_array( $result ) ) {
			// Verify required keys exist.
			$this->assertArrayHasKey( 'theme_type', $result );
			$this->assertArrayHasKey( 'friendly_name', $result );
			$this->assertArrayHasKey( 'template_file', $result );
			$this->assertArrayHasKey( 'is_child_theme', $result );
			$this->assertArrayHasKey( 'theme_slug', $result );
			$this->assertArrayHasKey( 'parent_slug', $result );
		}
	}

	/**
	 * Data provider for capability check tests.
	 *
	 * @return array Test cases with role and expected behavior.
	 */
	public function data_capability_provider() {
		return array(
			'subscriber without capability' => array(
				'role'     => 'subscriber',
				'should_show' => false,
			),
			'editor without capability'      => array(
				'role'     => 'editor',
				'should_show' => false,
			),
			'administrator with capability'  => array(
				'role'     => 'administrator',
				'should_show' => true,
			),
		);
	}

	/**
	 * Test that plugin only shows for users with edit_theme_options capability.
	 *
	 * @dataProvider data_capability_provider
	 *
	 * @param string $role        User role to test.
	 * @param bool   $should_show Whether the admin bar item should be shown.
	 */
	public function test_capability_check( $role, $should_show ) {
		global $template;

		// Set up a mock template.
		$template = get_template_directory() . '/index.php';

		// Navigate to frontend.
		$this->go_to( home_url( '/' ) );

		$admin_bar = $this->getMockBuilder( 'WP_Admin_Bar' )
			->disableOriginalConstructor()
			->onlyMethods( array( 'add_node' ) )
			->getMock();

		// Set up user with specified role.
		wp_set_current_user( $this->factory->user->create( array( 'role' => $role ) ) );

		// Set expectation based on whether user should see the admin bar item.
		if ( $should_show ) {
			$admin_bar->expects( $this->atLeastOnce() )
				->method( 'add_node' );
		} else {
			$admin_bar->expects( $this->never() )
				->method( 'add_node' );
		}

		$this->plugin->add_admin_bar_template_info( $admin_bar );
	}
}
