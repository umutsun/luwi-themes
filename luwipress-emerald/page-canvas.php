<?php
/**
 * Template Name: Emerald Canvas (Elementor full-bleed)
 *
 * Strips the theme chrome (header / footer) entirely so Elementor Pro
 * Theme Builder can render its own full-page template. Use this when
 * the operator wants a hero-edge-to-edge landing.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

nocache_headers();

while ( have_posts() ) {
	the_post();
}

get_header( 'canvas' );

if ( have_posts() ) {
	rewind_posts();
	while ( have_posts() ) {
		the_post();
		the_content();
	}
}

get_footer( 'canvas' );
