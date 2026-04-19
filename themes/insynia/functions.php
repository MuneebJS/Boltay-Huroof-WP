<?php
/**
 * Insynia child theme — loads parent styles, fonts, and landing page CSS.
 *
 * @package Insynia
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'wp_enqueue_scripts',
	function () {
		$parent = wp_get_theme( get_template() );
		wp_enqueue_style(
			'insynia-parent',
			get_template_directory_uri() . '/style.css',
			array(),
			$parent ? $parent->get( 'Version' ) : null
		);
		wp_enqueue_style(
			'insynia-fonts',
			'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Outfit:wght@400..800&display=swap',
			array(),
			null
		);
		wp_enqueue_style(
			'insynia-landing',
			get_stylesheet_directory_uri() . '/assets/css/landing.css',
			array( 'insynia-parent', 'insynia-fonts' ),
			wp_get_theme()->get( 'Version' )
		);

		if ( is_front_page() ) {
			wp_enqueue_script(
				'insynia-front-page-cleanup',
				get_stylesheet_directory_uri() . '/assets/js/front-page-cleanup.js',
				array(),
				wp_get_theme()->get( 'Version' ),
				true
			);
		}
	},
	20
);
