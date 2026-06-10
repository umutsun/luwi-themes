<?php
/**
 * Sapphire template helpers — inline icon set + placeholder media.
 *
 * Ports the React `Icon`, `Glyph` and `Ph` primitives from the design
 * handoff (assets/sapphire-shared.jsx) to PHP so the static templates can
 * reproduce the design without React. All output is theme-authored
 * markup (no user input), echoed by the templates.
 *
 * @package luwipress-sapphire
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'sapphire_icon' ) ) {
	/**
	 * Thin-line inline SVG icon. Mirrors the JSX Icon set 1:1.
	 *
	 * @param string $name   Icon key.
	 * @param int    $size   Pixel size.
	 * @param float  $stroke Stroke width.
	 * @return string SVG markup (safe — theme-authored).
	 */
	function sapphire_icon( $name, $size = 18, $stroke = 1.4 ) {
		$paths = array(
			'arrow'    => '<path d="M5 12h14"/><path d="M13 6l6 6-6 6"/>',
			'arrowUR'  => '<path d="M7 17L17 7"/><path d="M8 7h9v9"/>',
			'phone'    => '<path d="M5 4h3l2 5-2 1.5a11 11 0 0 0 5.5 5.5L17 18l5 2v-3a14 14 0 0 1-13-13z" transform="translate(-1 -1)"/>',
			'mail'     => '<rect x="3" y="5" width="18" height="14" rx="1"/><path d="M3 7l9 6 9-6"/>',
			'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
			'pin'      => '<path d="M12 21s7-6.4 7-11a7 7 0 1 0-14 0c0 4.6 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/>',
			'check'    => '<path d="M4 12l5 5L20 6"/>',
			'play'     => '<path d="M8 5v14l11-7z" fill="currentColor" stroke="none"/>',
			'menu'     => '<path d="M3 6h18"/><path d="M3 12h18"/><path d="M3 18h18"/>',
			'close'    => '<path d="M6 6l12 12"/><path d="M18 6L6 18"/>',
			'chat'     => '<path d="M21 12a8 8 0 0 1-11.5 7.2L4 21l1.8-5.4A8 8 0 1 1 21 12z"/>',
			'sun'      => '<circle cx="12" cy="12" r="4.2"/><path d="M12 2.4v2.6M12 19v2.6M4.6 4.6l1.9 1.9M17.5 17.5l1.9 1.9M2.4 12H5M19 12h2.6M4.6 19.4l1.9-1.9M17.5 6.5l1.9-1.9"/>',
			'moon'     => '<path d="M20.5 14.2A8.2 8.2 0 0 1 9.8 3.5a8.2 8.2 0 1 0 10.7 10.7z"/>',
			'globe'    => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/>',
			'bed'      => '<path d="M3 18v-6h18v6"/><path d="M3 14V8h7v4"/><path d="M21 12V9a2 2 0 0 0-2-2h-7"/><path d="M3 18v2M21 18v2"/>',
			'car'      => '<path d="M5 15l1.5-5h11L19 15"/><rect x="3" y="15" width="18" height="4" rx="1"/><circle cx="7" cy="19" r="1"/><circle cx="17" cy="19" r="1"/>',
			'ruler'    => '<rect x="3" y="8" width="18" height="8" rx="1"/><path d="M7 8v3M11 8v4M15 8v3M19 8v4"/>',
			'floor'    => '<rect x="3" y="3" width="18" height="18"/><path d="M3 9h10M13 3v12M13 15h8M9 15v6"/>',
			'heart'    => '<path d="M12 20s-7-4.6-9.2-9C1.3 8 2.8 5 6 5c2 0 3.2 1.4 4 2.6C10.8 6.4 12 5 14 5c3.2 0 4.7 3 3.2 6-2.2 4.4-9.2 9-9.2 9z" transform="translate(0 -0.5)"/>',
			'grid'     => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>',
			'rows'     => '<rect x="3" y="4" width="18" height="5"/><rect x="3" y="15" width="18" height="5"/>',
			'search'   => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
			// SaaS / tech icon set.
			'zap'      => '<path d="M13 2L4 14h7l-1 8 9-12h-7z"/>',
			'shield'   => '<path d="M12 3l8 3v5c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6z"/><path d="M9 12l2 2 4-4"/>',
			'code'     => '<path d="M8 9l-4 3 4 3"/><path d="M16 9l4 3-4 3"/><path d="M13.5 6l-3 12"/>',
			'plug'     => '<rect x="6" y="8" width="12" height="9" rx="2"/><path d="M9 8V4M15 8V4M12 17v3"/>',
			'chart'    => '<path d="M3 21h18"/><rect x="5" y="11" width="3" height="7"/><rect x="10.5" y="7" width="3" height="11"/><rect x="16" y="13" width="3" height="5"/>',
			'lock'     => '<rect x="5" y="11" width="14" height="9" rx="1.5"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
			'layers'   => '<path d="M12 3l9 5-9 5-9-5z"/><path d="M3 13l9 5 9-5"/>',
			'star'     => '<path d="M12 3l2.6 5.6L21 9.3l-4.5 4.3L17.6 21 12 17.6 6.4 21l1.1-7.4L3 9.3l6.4-.7z"/>',
			'sparkle'  => '<path d="M12 3v6M12 15v6M3 12h6M15 12h6"/><path d="M7.5 7.5l2.5 2.5M14 14l2.5 2.5M16.5 7.5L14 10M10 14l-2.5 2.5"/>',
			'rocket'   => '<path d="M5 15c-2 1-3 4-3 4s3-1 4-3M9 11a8 8 0 0 1 9-9c1 5-1 8-3 10l-2 4-4-2z"/><circle cx="14.5" cy="6.5" r="1.3"/>',
			'git'      => '<circle cx="6" cy="6" r="2"/><circle cx="6" cy="18" r="2"/><circle cx="18" cy="9" r="2"/><path d="M6 8v8M18 11a6 6 0 0 1-6 6H8"/>',
			'bolt'     => '<path d="M13 2L4 14h7l-1 8 9-12h-7z"/>',
			'cube'     => '<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9z"/><path d="M12 12l8-4.5M12 12v9M12 12L4 7.5"/>',
		);
		$inner = isset( $paths[ $name ] ) ? $paths[ $name ] : '';
		return sprintf(
			'<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="%2$s" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%3$s</svg>',
			(int) $size,
			esc_attr( (string) $stroke ),
			$inner
		);
	}
}

if ( ! function_exists( 'sapphire_glyph' ) ) {
	/**
	 * Large architectural glyph for placeholder media.
	 *
	 * @param string $kind tower|interior|plan|exterior|portrait.
	 * @return string SVG markup.
	 */
	function sapphire_glyph( $kind = 'plan' ) {
		$attr = 'width="56" height="56" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"';
		if ( 'tower' === $kind ) {
			$inner = '<path d="M20 44V14l4-6 4 6v30M16 44V22h16M14 44h20M22 18h4M22 24h4M22 30h4M22 36h4"/>';
		} elseif ( 'interior' === $kind ) {
			$inner = '<path d="M6 30h36M10 30V18a4 4 0 0 1 4-4h20a4 4 0 0 1 4 4v12M14 30v6M34 30v6M14 22h8v8M26 22h8"/>';
		} else {
			$inner = '<rect x="8" y="8" width="32" height="32"/><path d="M8 20h20M28 8v20M28 28h12M20 28v12"/>';
		}
		return '<svg ' . $attr . '>' . $inner . '</svg>';
	}
}

if ( ! function_exists( 'sapphire_ph' ) ) {
	/**
	 * Placeholder / photo media block. Ports the JSX <Ph>.
	 *
	 * When $img is supplied (e.g. a WP featured image) it renders the
	 * dark-treated `.photo`; otherwise the architectural `.ph` placeholder
	 * with a faint glyph — the design's intentional, image-free fallback.
	 *
	 * @param array $args glyph, tag, class, style, img, alt.
	 * @return string Markup.
	 */
	function sapphire_ph( $args = array() ) {
		$a = wp_parse_args( $args, array(
			'glyph' => 'plan',
			'tag'   => '',
			'class' => '',
			'style' => '',
			'img'   => '',
			'alt'   => '',
		) );

		$style_attr = $a['style'] !== '' ? ' style="' . esc_attr( $a['style'] ) . '"' : '';
		$tag_html   = $a['tag'] !== '' ? '<span class="ph-tag">' . esc_html( $a['tag'] ) . '</span>' : '';

		if ( $a['img'] !== '' ) {
			return sprintf(
				'<div class="photo %1$s"%2$s><img src="%3$s" alt="%4$s" loading="lazy"><span class="tone"></span>%5$s</div>',
				esc_attr( $a['class'] ),
				$style_attr,
				esc_url( $a['img'] ),
				esc_attr( $a['alt'] ),
				$tag_html
			);
		}

		$glyph_html = $a['glyph'] !== '' ? '<div class="ph-glyph">' . sapphire_glyph( $a['glyph'] ) . '</div>' : '';
		return sprintf(
			'<div class="ph %1$s"%2$s>%3$s%4$s</div>',
			esc_attr( $a['class'] ),
			$style_attr,
			$glyph_html,
			$tag_html
		);
	}
}

if ( ! function_exists( 'sapphire_eyebrow' ) ) {
	/**
	 * Eyebrow label (gold smallcaps with hairline rule).
	 *
	 * @param string $text   Label.
	 * @param bool   $center Add the trailing rule (centered variant).
	 * @return string Markup.
	 */
	function sapphire_eyebrow( $text, $center = false ) {
		return '<span class="eyebrow' . ( $center ? ' center' : '' ) . '">' . esc_html( $text ) . '</span>';
	}
}

if ( ! function_exists( 'sapphire_reading_time' ) ) {
	/**
	 * Estimated reading time in minutes from a post's word count.
	 *
	 * @param int $post_id Post ID (default current).
	 * @return int Minutes (min 1).
	 */
	function sapphire_reading_time( $post_id = 0 ) {
		$post_id = $post_id ? (int) $post_id : get_the_ID();
		$words   = str_word_count( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ) );
		return max( 1, (int) round( $words / 200 ) );
	}
}

if ( ! function_exists( 'sapphire_page_url' ) ) {
	/**
	 * Resolve a logical Sapphire destination to a real URL.
	 *
	 * Looks for a page assigned the matching Sapphire page template, then a
	 * page by slug, then falls back sensibly. Lets the hard-rendered chrome
	 * (mega menu, drawer, footer) point at real pages once the operator
	 * creates them, without a CPT.
	 *
	 * @param string $key home|listings|gallery|about|contact|journal|search.
	 * @return string URL.
	 */
	function sapphire_page_url( $key ) {
		$key = sanitize_key( $key );
		if ( 'home' === $key ) {
			return home_url( '/' );
		}

		$cache = wp_cache_get( 'sapphire_url_' . $key, 'luwipress_sapphire' );
		if ( false !== $cache ) {
			return $cache;
		}

		$template_map = array(
			'listings'     => 'template-listings.php',
			'pricing'      => 'template-listings.php',
			'gallery'      => 'template-gallery.php',
			'integrations' => 'template-gallery.php',
			'property'     => 'template-property.php',
			'about'        => 'page-about.php',
			'contact'      => 'page-contact.php',
			'search'       => 'template-search.php',
		);
		$slug_candidates = array(
			'listings'     => array( 'pricing', 'plans' ),
			'pricing'      => array( 'pricing', 'plans' ),
			'gallery'      => array( 'integrations', 'apps', 'marketplace' ),
			'integrations' => array( 'integrations', 'apps', 'marketplace' ),
			'features'     => array( 'features' ),
			'property'     => array( 'plan', 'product' ),
			'about'        => array( 'about', 'about-us' ),
			'contact'      => array( 'contact', 'contact-us' ),
			'journal'      => array( 'journal', 'blog', 'news' ),
			'search'       => array( 'search' ),
		);

		$url = '';

		// 1. By assigned page template.
		if ( isset( $template_map[ $key ] ) ) {
			$found = get_posts( array(
				'post_type'      => 'page',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_wp_page_template',
				'meta_value'     => $template_map[ $key ],
				'no_found_rows'  => true,
			) );
			if ( ! empty( $found ) ) {
				$url = get_permalink( (int) $found[0] );
			}
		}

		// 2. Journal → the posts page if set.
		if ( '' === $url && 'journal' === $key ) {
			$blog = (int) get_option( 'page_for_posts' );
			if ( $blog ) {
				$url = get_permalink( $blog );
			}
		}

		// 3. By slug.
		if ( '' === $url && isset( $slug_candidates[ $key ] ) ) {
			foreach ( $slug_candidates[ $key ] as $slug ) {
				$p = get_page_by_path( $slug );
				if ( $p ) {
					$url = get_permalink( $p );
					break;
				}
			}
		}

		// 4. Fallbacks.
		if ( '' === $url ) {
			if ( 'search' === $key ) {
				$url = home_url( '/?s=' );
			} elseif ( in_array( $key, array( 'pricing', 'features', 'integrations', 'changelog' ), true ) ) {
				// SaaS homepage sections — fall back to the on-page anchor.
				$url = home_url( '/#' . $key );
			} else {
				$url = home_url( '/' . $key . '/' );
			}
		}

		wp_cache_set( 'sapphire_url_' . $key, $url, 'luwipress_sapphire', 5 * MINUTE_IN_SECONDS );
		return $url;
	}
}

if ( ! function_exists( 'sapphire_logo_url' ) ) {
	/**
	 * Header/footer logo image URL — the WP custom logo if the operator set
	 * one, else an empty string so the chrome falls back to the "Sapphire"
	 * text wordmark (which inherits the theme colour and flips dark/light).
	 *
	 * @return string Image URL, or '' when no custom logo is set.
	 */
	function sapphire_logo_url() {
		$id = (int) get_theme_mod( 'custom_logo' );
		if ( $id ) {
			$src = wp_get_attachment_image_url( $id, 'full' );
			if ( $src ) {
				return $src;
			}
		}
		return '';
	}
}

if ( ! function_exists( 'sapphire_mega_lists' ) ) {
	/**
	 * Mega-menu "By Type" / "By Neighborhood" lists. Filterable so an
	 * operator can re-point them without editing the template.
	 *
	 * @return array{types:array,areas:array} Each entry: [label, url, meta].
	 */
	function sapphire_mega_lists() {
		$home    = home_url( '/' );
		$pricing = sapphire_page_url( 'pricing' );
		// "Features" column — [ label, url, meta ].
		$types = array(
			array( 'Automations',  $home . '#features', '' ),
			array( 'Analytics',    $home . '#features', '' ),
			array( 'Integrations', $home . '#integrations', '' ),
			array( 'API & SDK',    $home . '#features', '' ),
			array( 'Security',     $home . '#features', '' ),
			array( 'Workspaces',   $home . '#features', '' ),
		);
		// "Use cases" column — [ label, url ].
		$areas = array(
			array( 'Engineering', $home . '#features' ),
			array( 'Product',     $home . '#features' ),
			array( 'Marketing',   $home . '#features' ),
			array( 'Sales',       $pricing ),
			array( 'Support',     $home . '#features' ),
			array( 'Startups',    $pricing ),
		);
		return apply_filters( 'luwipress_sapphire_mega_lists', array( 'types' => $types, 'areas' => $areas ) );
	}
}

