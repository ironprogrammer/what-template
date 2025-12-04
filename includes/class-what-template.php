<?php
/**
 * Main plugin class.
 *
 * @package What_Template
 */

namespace IronProgrammer\WhatTemplate;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class for What Template.
 */
class What_Template {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize WordPress hooks.
	 */
	private function init_hooks() {
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_template_info' ), 100 );
	}

	/**
	 * Add template information to the admin bar.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 */
	public function add_admin_bar_template_info( $wp_admin_bar ) {
		// Only show on frontend for users with edit_theme_options capability.
		if ( is_admin() || ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}

		$template_info = $this->get_template_info();

		if ( ! $template_info ) {
			return;
		}

		$this->render_admin_bar_menu( $wp_admin_bar, $template_info );
	}

	/**
	 * Get current template information.
	 *
	 * @return array|null Template info array or null if unavailable.
	 */
	private function get_template_info() {
		$theme_type = $this->get_theme_type();

		switch ( $theme_type ) {
			case 'block':
				return $this->get_block_template_info();
			case 'hybrid':
			case 'classic':
			default:
				return $this->get_classic_template_info();
		}
	}

	/**
	 * Detect the current theme type.
	 *
	 * @return string Theme type: 'block', 'hybrid', or 'classic'.
	 */
	private function get_theme_type() {
		// Check if it's a block theme.
		if ( wp_is_block_theme() ) {
			return 'block';
		}

		// Check if it's a hybrid theme (has block template support).
		if ( current_theme_supports( 'block-templates' ) ) {
			return 'hybrid';
		}

		return 'classic';
	}

	/**
	 * Get template information for classic/hybrid themes.
	 *
	 * @return array|null Template information array.
	 */
	private function get_classic_template_info() {
		global $template;

		if ( ! $template ) {
			return null;
		}

		$theme_type     = $this->get_theme_type();
		$template_file  = basename( $template );
		$friendly_name  = $this->get_friendly_name();
		$is_child_theme = is_child_theme();
		$theme          = wp_get_theme();

		return array(
			'theme_type'      => $theme_type,
			'friendly_name'   => $friendly_name,
			'template_file'   => $template_file,
			'template_slug'   => null,
			'template_source' => null,
			'is_child_theme'  => $is_child_theme,
			'theme_slug'      => $theme->get_stylesheet(),
			'parent_slug'     => $is_child_theme ? $theme->get_template() : null,
		);
	}

	/**
	 * Get template information for block themes.
	 *
	 * @return array|null Template information array.
	 */
	private function get_block_template_info() {
		global $_wp_current_template_id;

		if ( ! $_wp_current_template_id ) {
			return null;
		}

		$template = get_block_template( $_wp_current_template_id );

		if ( ! $template ) {
			return null;
		}

		$is_child_theme = is_child_theme();
		$theme          = wp_get_theme();

		// Determine source with customization info.
		$source = '';
		if ( 'custom' === $template->source && ! empty( $template->origin ) ) {
			// If customized, show original source.
			$source = ucfirst( $template->origin ) . ' (customized)';
		} elseif ( 'custom' === $template->source ) {
			// Fully custom template (no origin).
			$source = 'Custom';
		} else {
			// Theme or plugin source.
			$source = ucfirst( $template->source );
		}

		return array(
			'theme_type'      => 'block',
			'friendly_name'   => $template->title,
			'template_file'   => null,
			'template_slug'   => $template->slug,
			'template_source' => $source,
			'is_child_theme'  => $is_child_theme,
			'theme_slug'      => $theme->get_stylesheet(),
			'parent_slug'     => $is_child_theme ? $theme->get_template() : null,
		);
	}

	/**
	 * Get friendly name for the current template context.
	 *
	 * @return string Friendly template name.
	 */
	private function get_friendly_name() {
		// Check for custom page template first.
		if ( is_singular() && is_page_template() ) {
			$page_template = get_page_template_slug();
			if ( $page_template ) {
				// Convert slug to friendly name (e.g., "page-about.php" -> "Page About").
				$name = basename( $page_template, '.php' );
				$name = str_replace( array( '-', '_' ), ' ', $name );
				return ucwords( $name );
			}
		}

		// Use conditional tags to determine context.
		if ( is_front_page() && is_home() ) {
			return __( 'Front Page (Blog)', 'what-template' );
		} elseif ( is_front_page() ) {
			return __( 'Front Page', 'what-template' );
		} elseif ( is_home() ) {
			return __( 'Blog Home', 'what-template' );
		} elseif ( is_singular( 'post' ) ) {
			return __( 'Single Post', 'what-template' );
		} elseif ( is_singular( 'page' ) ) {
			return __( 'Page', 'what-template' );
		} elseif ( is_singular() ) {
			$post_type        = get_post_type();
			$post_type_object = get_post_type_object( $post_type );
			if ( $post_type_object ) {
				/* translators: %s: Post type singular name */
				return sprintf( __( 'Single %s', 'what-template' ), $post_type_object->labels->singular_name );
			}
			return __( 'Single', 'what-template' );
		} elseif ( is_category() ) {
			return __( 'Category Archive', 'what-template' );
		} elseif ( is_tag() ) {
			return __( 'Tag Archive', 'what-template' );
		} elseif ( is_author() ) {
			return __( 'Author Archive', 'what-template' );
		} elseif ( is_date() ) {
			return __( 'Date Archive', 'what-template' );
		} elseif ( is_post_type_archive() ) {
			$post_type = get_query_var( 'post_type' );
			if ( is_array( $post_type ) ) {
				$post_type = reset( $post_type );
			}
			$post_type_object = get_post_type_object( $post_type );
			if ( $post_type_object ) {
				/* translators: %s: Post type name */
				return sprintf( __( '%s Archive', 'what-template' ), $post_type_object->labels->name );
			}
			return __( 'Archive', 'what-template' );
		} elseif ( is_tax() ) {
			$taxonomy   = get_query_var( 'taxonomy' );
			$tax_object = get_taxonomy( $taxonomy );
			if ( $tax_object ) {
				/* translators: %s: Taxonomy singular name */
				return sprintf( __( '%s Archive', 'what-template' ), $tax_object->labels->singular_name );
			}
			return __( 'Taxonomy Archive', 'what-template' );
		} elseif ( is_archive() ) {
			return __( 'Archive', 'what-template' );
		} elseif ( is_search() ) {
			return __( 'Search Results', 'what-template' );
		} elseif ( is_404() ) {
			return __( '404 Not Found', 'what-template' );
		} elseif ( is_attachment() ) {
			return __( 'Attachment', 'what-template' );
		}

		return __( 'Index', 'what-template' );
	}

	/**
	 * Get edit link for the current template.
	 *
	 * @param array $template_info Template information array.
	 * @return string|null Edit URL or null if not available.
	 */
	private function get_template_edit_link( $template_info ) {
		if ( ! $template_info ) {
			return null;
		}

		// Block theme - link to Site Editor.
		if ( 'block' === $template_info['theme_type'] ) {
			global $_wp_current_template_id;
			if ( ! $_wp_current_template_id ) {
				return null;
			}

			return add_query_arg(
				array(
					'postType' => 'wp_template',
					'postId'   => $_wp_current_template_id,
					'canvas'   => 'edit',
				),
				admin_url( 'site-editor.php' )
			);
		}

		// Classic/Hybrid theme - link to theme file editor.
		// Check if theme editor is disabled.
		if ( ! wp_is_file_mod_allowed( 'theme_editor' ) ) {
			return null;
		}

		if ( ! $template_info['template_file'] ) {
			return null;
		}

		return admin_url( 'theme-editor.php?file=' . rawurlencode( $template_info['template_file'] ) );
	}

	/**
	 * Get SVG icon for theme type.
	 *
	 * @param string $theme_type  Theme type (block, hybrid, classic).
	 * @param bool   $is_child    Whether this is a child theme.
	 * @return string SVG markup.
	 */
	private function get_theme_icon( $theme_type, $is_child = false ) {
		// If child theme, show child theme icon.
		if ( $is_child ) {
			return '<svg xmlns="http://www.w3.org/2000/svg" width="1.2em" height="1.2em" viewBox="0 0 100 100" style="fill: currentColor; margin-right: 5px; vertical-align: text-bottom;"><path d="M85.414,28.586l-22-22C63.039,6.211,62.53,6,62,6H16c-1.104,0-2,0.896-2,2v84c0,1.104,0.896,2,2,2h68c1.104,0,2-0.896,2-2V30C86,29.47,85.789,28.961,85.414,28.586z M64,12.828L79.171,28H64V12.828z M18,90V10h42v20c0,1.104,0.896,2,2,2h20v58H18z"/><path d="M70,50h-8v-6c0-1.104-0.896-2-2-2H30c-1.104,0-2,0.896-2,2v24c0,1.104,0.896,2,2,2h8v6c0,1.104,0.896,2,2,2h30c1.104,0,2-0.896,2-2V52C72,50.896,71.104,50,70,50z M38,52v14h-6V46h26v4H40C38.896,50,38,50.896,38,52z M68,74H42V54h26V74z"/></svg>';
		}

		// Return appropriate icon based on theme type.
		switch ( $theme_type ) {
			case 'block':
				return '<svg xmlns="http://www.w3.org/2000/svg" width="1.2em" height="1.2em" viewBox="0 0 100 100" style="fill: currentColor; margin-right: 5px; vertical-align: text-bottom;"><path d="M85.4,28.6l-22-22C63,6.2,62.5,6,62,6H16c-1.1,0-2,0.9-2,2v84c0,1.1,0.9,2,2,2h68c1.1,0,2-0.9,2-2V30C86,29.5,85.8,29,85.4,28.6z M64,12.8L79.2,28H64V12.8z M18,90V10h42v20c0,1.1,0.9,2,2,2h20v58H18z"/><path d="M72,75.8c0,0.1-0.1,0.2-0.2,0.2H28.2c-0.1,0-0.2-0.1-0.2-0.2V50.2c0-0.1,0.1-0.2,0.2-0.2h5.5c0.1,0,0.2-0.1,0.2-0.2v-5.5c0-0.1,0.1-0.2,0.2-0.2h13.5c0.1,0,0.2,0.1,0.2,0.2v5.5c0,0.1,0.1,0.2,0.2,0.2h3.5c0.1,0,0.2-0.1,0.2-0.2v-5.5c0-0.1,0.1-0.2,0.2-0.2h13.5c0.1,0,0.2,0.1,0.2,0.2v5.5c0,0.1,0.1,0.2,0.2,0.2h5.5c0.1,0,0.2,0.1,0.2,0.2V75.8z M32,71.8c0,0.1,0.1,0.2,0.2,0.2h35.5c0.1,0,0.2-0.1,0.2-0.2V54.2c0-0.1-0.1-0.2-0.2-0.2h-5.5c-0.1,0-0.2-0.1-0.2-0.2v-5.5c0-0.1-0.1-0.2-0.2-0.2h-5.5c-0.1,0-0.2,0.1-0.2,0.2v5.5c0,0.1-0.1,0.2-0.2,0.2H44.2c-0.1,0-0.2-0.1-0.2-0.2v-5.5c0-0.1-0.1-0.2-0.2-0.2h-5.5c-0.1,0-0.2,0.1-0.2,0.2v5.5c0,0.1-0.1,0.2-0.2,0.2h-5.5c-0.1,0-0.2,0.1-0.2,0.2V71.8z"/></svg>';

			case 'hybrid':
				return '<svg xmlns="http://www.w3.org/2000/svg" width="1.2em" height="1.2em" viewBox="0 0 100 100" style="fill: currentColor; margin-right: 5px; vertical-align: text-bottom;"><path d="M85.414,28.586l-22-22C63.039,6.211,62.53,6,62,6H16c-1.104,0-2,0.896-2,2v84c0,1.104,0.896,2,2,2h68c1.104,0,2-0.896,2-2V30C86,29.47,85.789,28.961,85.414,28.586z M64,12.828L79.171,28H64V12.828z M18,90V10h42v20c0,1.104,0.896,2,2,2h20v58H18z"/><path d="M61.997,74c-0.993,0-1.855-0.74-1.981-1.752c-0.137-1.096,0.641-2.096,1.736-2.232c1.642-0.205,1.84-0.348,1.55-3.37c-0.175-1.829-0.451-4.701,1.486-6.646c-1.938-1.944-1.661-4.816-1.486-6.646c0.29-3.022,0.092-3.165-1.55-3.37c-1.096-0.137-1.873-1.136-1.736-2.232s1.143-1.875,2.232-1.736c5.708,0.713,5.271,5.271,5.036,7.721c-0.283,2.95-0.379,3.944,2.915,4.273C71.222,58.112,72,58.973,72,60s-0.778,1.888-1.801,1.99c-3.294,0.329-3.198,1.323-2.915,4.273c0.234,2.449,0.672,7.008-5.036,7.721C62.164,73.995,62.08,74,61.997,74z"/><path d="M38.002,74c-0.083,0-0.167-0.005-0.25-0.016c-5.708-0.713-5.271-5.271-5.036-7.721c0.283-2.95,0.378-3.944-2.916-4.273C28.779,61.888,28,61.027,28,60s0.779-1.888,1.801-1.99c3.294-0.329,3.199-1.323,2.916-4.273c-0.235-2.449-0.672-7.007,5.036-7.721c1.094-0.136,2.095,0.641,2.232,1.736c0.137,1.096-0.64,2.096-1.736,2.232c-1.642,0.206-1.84,0.348-1.55,3.37c0.175,1.829,0.451,4.701-1.486,6.646c1.938,1.944,1.662,4.816,1.486,6.646c-0.29,3.022-0.092,3.165,1.55,3.37c1.096,0.137,1.874,1.137,1.736,2.232C39.858,73.26,38.997,74,38.002,74z"/><circle cx="44" cy="68" r="2"/><circle cx="56" cy="68" r="2"/><circle cx="50" cy="68" r="2"/></svg>';

			case 'classic':
			default:
				return '<svg xmlns="http://www.w3.org/2000/svg" width="1.2em" height="1.2em" viewBox="0 0 100 100" style="fill: currentColor; margin-right: 5px; vertical-align: text-bottom;"><path d="M85.4,28.6l-22-22C63,6.2,62.5,6,62,6H16c-1.1,0-2,0.9-2,2v84c0,1.1,0.9,2,2,2h68c1.1,0,2-0.9,2-2V30C86,29.5,85.8,29,85.4,28.6z M64,12.8L79.2,28H64V12.8z M18,90V10h42v20c0,1.1,0.9,2,2,2h20v58H18z"/><path d="M56.1,52.2c-0.6-0.9-1.4-1.5-2.4-2c-1-0.5-2.2-0.7-3.5-0.7c-1.4,0-2.7,0.3-3.8,0.9c-1.1,0.6-1.9,1.3-2.5,2.2c-0.6,0.9-0.9,1.8-0.9,2.7c0,0.5,0.2,0.9,0.6,1.3c0.4,0.4,0.8,0.6,1.4,0.6c0.9,0,1.6-0.5,1.9-1.6c0.3-1,0.7-1.7,1.2-2.2c0.4-0.5,1.2-0.7,2.1-0.7c0.8,0,1.5,0.2,2,0.7c0.5,0.5,0.8,1.1,0.8,1.7c0,0.3-0.1,0.7-0.2,1c-0.2,0.3-0.4,0.6-0.6,0.8c-0.3,0.3-0.7,0.6-1.3,1.1c-0.7,0.6-1.2,1.1-1.6,1.5c-0.4,0.4-0.7,0.9-1,1.5c-0.2,0.6-0.4,1.3-0.4,2c0,0.6,0.2,1.1,0.5,1.5c0.3,0.3,0.8,0.5,1.3,0.5c1,0,1.6-0.5,1.7-1.5c0.1-0.5,0.2-0.8,0.2-0.9c0-0.2,0.1-0.3,0.2-0.5c0.1-0.2,0.2-0.4,0.4-0.6c0.2-0.2,0.4-0.5,0.7-0.7c1.1-1,1.9-1.7,2.3-2.1c0.4-0.4,0.8-0.9,1.1-1.5c0.3-0.6,0.5-1.3,0.5-2.1C57,54,56.7,53.1,56.1,52.2z"/><path d="M49.9,66c-0.6,0-1.1,0.2-1.6,0.6c-0.4,0.4-0.6,0.9-0.6,1.5c0,0.7,0.2,1.2,0.7,1.6c0.4,0.4,1,0.6,1.5,0.6c0.6,0,1.1-0.2,1.5-0.6c0.4-0.4,0.7-0.9,0.7-1.6c0-0.6-0.2-1.1-0.6-1.5C51,66.2,50.5,66,49.9,66z"/><path d="M38,70c-0.5,0-1-0.2-1.4-0.6l-8-8c-0.8-0.8-0.8-2,0-2.8l8-8c0.8-0.8,2-0.8,2.8,0c0.8,0.8,0.8,2,0,2.8L32.8,60l6.6,6.6c0.8,0.8,0.8,2,0,2.8C39,69.8,38.5,70,38,70z"/><path d="M62,70c-0.5,0-1-0.2-1.4-0.6c-0.8-0.8-0.8-2,0-2.8l6.6-6.6l-6.6-6.6c-0.8-0.8-0.8-2,0-2.8c0.8-0.8,2-0.8,2.8,0l8,8c0.8,0.8,0.8,2,0,2.8l-8,8C63,69.8,62.5,70,62,70z"/></svg>';
		}
	}

	/**
	 * Render the admin bar menu items.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 * @param array         $template_info Template information array.
	 */
	private function render_admin_bar_menu( $wp_admin_bar, $template_info ) {
		// Get icon for theme type.
		$icon = $this->get_theme_icon( $template_info['theme_type'], $template_info['is_child_theme'] );

		// Add parent menu item with icon and friendly name.
		$wp_admin_bar->add_node(
			array(
				'id'    => 'what-template',
				'title' => $icon . esc_html( $template_info['friendly_name'] ),
			)
		);

		// Add template file/slug.
		if ( $template_info['template_file'] ) {
			$wp_admin_bar->add_node(
				array(
					'id'     => 'what-template-file',
					'parent' => 'what-template',
					/* translators: %s: Template filename */
					'title'  => sprintf( __( 'Template: %s', 'what-template' ), esc_html( $template_info['template_file'] ) ),
				)
			);
		} elseif ( $template_info['template_slug'] ) {
			$wp_admin_bar->add_node(
				array(
					'id'     => 'what-template-slug',
					'parent' => 'what-template',
					/* translators: %s: Template slug */
					'title'  => sprintf( __( 'Slug: %s', 'what-template' ), esc_html( $template_info['template_slug'] ) ),
				)
			);
		}

		// Add theme type.
		$theme_type_label = ucfirst( $template_info['theme_type'] );
		if ( 'block' === $template_info['theme_type'] ) {
			$theme_type_label = __( 'Block (FSE)', 'what-template' );
		} elseif ( 'hybrid' === $template_info['theme_type'] ) {
			$theme_type_label = __( 'Hybrid Theme', 'what-template' );
		} else {
			$theme_type_label = __( 'Classic Theme', 'what-template' );
		}

		$wp_admin_bar->add_node(
			array(
				'id'     => 'what-template-type',
				'parent' => 'what-template',
				/* translators: %s: Theme type */
				'title'  => sprintf( __( 'Type: %s', 'what-template' ), esc_html( $theme_type_label ) ),
			)
		);

		// Add theme slug.
		if ( ! empty( $template_info['theme_slug'] ) ) {
			$wp_admin_bar->add_node(
				array(
					'id'     => 'what-template-theme-slug',
					'parent' => 'what-template',
					/* translators: %s: Theme slug */
					'title'  => sprintf( __( 'Theme: %s', 'what-template' ), esc_html( $template_info['theme_slug'] ) ),
				)
			);
		}

		// Add parent theme slug for child themes.
		if ( $template_info['is_child_theme'] && ! empty( $template_info['parent_slug'] ) ) {
			$wp_admin_bar->add_node(
				array(
					'id'     => 'what-template-parent-slug',
					'parent' => 'what-template',
					/* translators: %s: Parent theme slug */
					'title'  => sprintf( __( 'Parent: %s', 'what-template' ), esc_html( $template_info['parent_slug'] ) ),
				)
			);
		}

		// Add template source.
		if ( ! empty( $template_info['template_source'] ) ) {
			$wp_admin_bar->add_node(
				array(
					'id'     => 'what-template-source',
					'parent' => 'what-template',
					/* translators: %s: Template source (Theme/Plugin/Custom) */
					'title'  => sprintf( __( 'Source: %s', 'what-template' ), esc_html( $template_info['template_source'] ) ),
				)
			);
		}

		// Add edit link.
		$edit_link = $this->get_template_edit_link( $template_info );
		if ( $edit_link ) {
			$edit_text = 'block' === $template_info['theme_type']
				? __( 'Edit in Site Editor →', 'what-template' )
				: __( 'Edit Template →', 'what-template' );

			$wp_admin_bar->add_node(
				array(
					'id'     => 'what-template-edit',
					'parent' => 'what-template',
					'title'  => esc_html( $edit_text ),
					'href'   => esc_url( $edit_link ),
				)
			);
		}
	}
}
