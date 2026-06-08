<?php
/**
 * Engine-CPT template routing (1.10.18+).
 *
 * Operator-defined CPT Engine content types (Team, Events, Music-Academy
 * Teacher, …) ship no theme template, so WordPress falls back to the bare
 * blog/index layout. This routes any ENABLED engine-managed post type to the
 * generic styled templates (single-lwp-cpt.php / archive-lwp-cpt.php) which
 * reuse the Vendor profile chrome and populate it from the type's field
 * schema. A type-specific theme template still wins — single-lwp_vendor.php
 * keeps the Vendor layout untouched.
 *
 * @package luwipress-amber
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Is this post type an ENABLED CPT Engine type? Cheap + null-safe.
 *
 * @param string $post_type
 * @return bool
 */
function lwp_amber_is_engine_cpt( $post_type ) {
	if ( '' === (string) $post_type || ! class_exists( 'LuwiPress_CPT_Engine' ) ) {
		return false;
	}
	$def = LuwiPress_CPT_Engine::get_instance()->get_type_by_post_type( (string) $post_type );
	return ( is_array( $def ) && ! empty( $def['enabled'] ) );
}

/**
 * Single — route enabled engine CPTs to the generic profile template, unless a
 * type-specific template (single-<cpt>.php) already resolved.
 */
add_filter( 'single_template', function ( $template ) {
	if ( ! is_singular() ) {
		return $template;
	}
	$pt = get_post_type();
	if ( basename( (string) $template ) === 'single-' . $pt . '.php' ) {
		return $template; // a dedicated template (e.g. Vendor) wins.
	}
	if ( lwp_amber_is_engine_cpt( $pt ) ) {
		$generic = get_template_directory() . '/single-lwp-cpt.php';
		if ( file_exists( $generic ) ) {
			return $generic;
		}
	}
	return $template;
}, 20 );

/**
 * Archive — same routing for the type's post-type archive.
 */
add_filter( 'archive_template', function ( $template ) {
	$pt = get_query_var( 'post_type' );
	if ( is_array( $pt ) ) {
		$pt = reset( $pt );
	}
	if ( ! $pt && is_post_type_archive() ) {
		$obj = get_queried_object();
		$pt  = ( $obj instanceof WP_Post_Type ) ? $obj->name : '';
	}
	if ( $pt && basename( (string) $template ) === 'archive-' . $pt . '.php' ) {
		return $template; // a dedicated archive (e.g. Vendor) wins.
	}
	if ( $pt && lwp_amber_is_engine_cpt( $pt ) ) {
		$generic = get_template_directory() . '/archive-lwp-cpt.php';
		if ( file_exists( $generic ) ) {
			return $generic;
		}
	}
	return $template;
}, 20 );

/**
 * Map a social-field label to an Elementor icon class. Used by
 * single-lwp-cpt.php to render social chips for url / sameAs fields.
 *
 * @param string $label
 * @return string Elementor icon class.
 */
function lwp_amber_cpt_social_icon( $label ) {
	$l   = strtolower( (string) $label );
	$map = array(
		'facebook'   => 'eicon-social-icon',
		'instagram'  => 'eicon-instagram',
		'youtube'    => 'eicon-youtube',
		'soundcloud' => 'eicon-soundcloud',
		'linkedin'   => 'eicon-linkedin',
		'twitter'    => 'eicon-twitter',
		'behance'    => 'eicon-behance',
		'tiktok'     => 'eicon-tiktok',
		'spotify'    => 'eicon-spotify',
		'website'    => 'eicon-globe',
		'site'       => 'eicon-globe',
		'web'        => 'eicon-globe',
		'email'      => 'eicon-envelope',
	);
	foreach ( $map as $kw => $icon ) {
		if ( false !== strpos( $l, $kw ) ) {
			return $icon;
		}
	}
	return 'eicon-globe';
}
