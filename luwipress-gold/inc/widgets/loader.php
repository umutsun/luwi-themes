<?php
/**
 * Elementor widget loader.
 *
 * Registers a "LuwiPress Gold" widget category and 7 custom widgets:
 *   - lwp-product-card      → Gold-styled product card with sale badge + quick-add
 *   - lwp-master-profile    → Luthier portrait + stats + dual CTA card
 *   - lwp-timeline          → Year + headline + body rows for the dark-band history
 *   - lwp-info-bar          → V32-style 4-column trust strip with red icon circles
 *   - lwp-editorial-grid    → 1-large + 2-small blog post grid
 *   - lwp-hero              → Hero with 3 layout variants (split / centered / full-bleed)
 *   - lwp-youtube-channel    → YouTube video grid with built-in lightbox + subscribe button
 *   - lwp-instagram-channel  → Instagram post grid with brand-gradient follow button
 *   - lwp-section-head       → Eyebrow + multi-line heading + optional pill tabs + CTA link
 *   - lwp-category-grid      → Gradient category tiles (WC product_cat or manual list)
 *   - lwp-master-grid        → 4-up master luthier cards (dark or light variant)
 *   - lwp-story-split        → 2-column image tile + numbered story bullets
 *   - lwp-hero-split         → Full editorial hero: copy column + image with status pill + master-quote card
 *   - lwp-testimonials       → Carousel/grid of customer reviews with Schema.org Review markup
 *   - lwp-faq                → Accordion FAQ with FAQPage JSON-LD
 *   - lwp-trust-badges       → Horizontal strip of payment / security / certification icons
 *   - lwp-newsletter         → Email signup with FluentCRM/Mailchimp detection + GDPR consent
 *   - lwp-countdown          → Sale / launch countdown timer with timezone awareness
 *   - lwp-cta-banner         → Full-bleed CTA strip with image bg + headline + 1-2 buttons
 *   - lwp-stat-counter       → Big numbers that animate up on scroll (IntersectionObserver)
 *   - lwp-process-steps      → Numbered horizontal cards (1→2→3→4) for "how we work" explainers
 *   - lwp-ai-search          → Prominent AI search overlay trigger with suggestion chips
 *   - lwp-featured-product   → Single hero-style product spotlight (registry / manual / custom)
 *   - lwp-featured-strip     → Horizontal scrolling strip of all currently-featured products
 *   - lwp-kg-stats           → Live store stats from LuwiPress Knowledge Graph (upsells if inactive)
 *   - lwp-kg-trending        → Trending categories/products from KG ranker (upsells if inactive)
 *
 * Boots only when Elementor is loaded.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

// Bail until Elementor is fully loaded.
add_action( 'elementor/init', function () {

	// 1. Custom category — keeps our widgets grouped at the top of the panel.
	add_action( 'elementor/elements/categories_registered', function ( $elements_manager ) {
		$elements_manager->add_category(
			'luwipress-gold',
			[
				'title' => __( 'LuwiPress Gold', 'luwipress-gold' ),
				'icon'  => 'eicon-star',
			]
		);
	} );

	// 2. Register widgets after Elementor's widget classes are loaded.
	add_action( 'elementor/widgets/register', function ( $widgets_manager ) {
		$widgets = [
			'class-product-card.php'     => 'LuwiPress_Gold_Widget_Product_Card',
			'class-master-profile.php'   => 'LuwiPress_Gold_Widget_Master_Profile',
			'class-timeline.php'         => 'LuwiPress_Gold_Widget_Timeline',
			'class-info-bar.php'         => 'LuwiPress_Gold_Widget_Info_Bar',
			'class-editorial-grid.php'   => 'LuwiPress_Gold_Widget_Editorial_Grid',
			'class-hero.php'             => 'LuwiPress_Gold_Widget_Hero',
			'class-mega-menu.php'        => 'LuwiPress_Gold_Widget_Mega_Menu',
			'class-megabar.php'          => 'LuwiPress_Gold_Widget_Megabar',
			'class-youtube-channel.php'  => 'LuwiPress_Gold_Widget_YouTube_Channel',
			'class-instagram-channel.php' => 'LuwiPress_Gold_Widget_Instagram_Channel',
			'class-section-head.php'     => 'LuwiPress_Gold_Widget_Section_Head',
			'class-category-grid.php'    => 'LuwiPress_Gold_Widget_Category_Grid',
			'class-master-grid.php'      => 'LuwiPress_Gold_Widget_Master_Grid',
			'class-story-split.php'      => 'LuwiPress_Gold_Widget_Story_Split',
			'class-hero-split.php'       => 'LuwiPress_Gold_Widget_Hero_Split',
			'class-testimonials.php'     => 'LuwiPress_Gold_Widget_Testimonials',
			'class-faq.php'              => 'LuwiPress_Gold_Widget_FAQ',
			'class-trust-badges.php'     => 'LuwiPress_Gold_Widget_Trust_Badges',
			'class-newsletter.php'       => 'LuwiPress_Gold_Widget_Newsletter',
			'class-countdown.php'        => 'LuwiPress_Gold_Widget_Countdown',
			'class-cta-banner.php'       => 'LuwiPress_Gold_Widget_CTA_Banner',
			'class-stat-counter.php'     => 'LuwiPress_Gold_Widget_Stat_Counter',
			'class-process-steps.php'    => 'LuwiPress_Gold_Widget_Process_Steps',
			'class-ai-search.php'        => 'LuwiPress_Gold_Widget_AI_Search',
			'class-featured-product.php' => 'LuwiPress_Gold_Widget_Featured_Product',
			'class-featured-strip.php'   => 'LuwiPress_Gold_Widget_Featured_Strip',
			'class-kg-stats.php'         => 'LuwiPress_Gold_Widget_KG_Stats',
			'class-kg-trending.php'      => 'LuwiPress_Gold_Widget_KG_Trending',
		];

		foreach ( $widgets as $file => $class ) {
			$path = LUWIPRESS_GOLD_DIR . '/inc/widgets/lib/' . $file;
			if ( ! file_exists( $path ) ) continue;
			require_once $path;
			if ( class_exists( $class ) ) {
				$widgets_manager->register( new $class() );
			}
		}
	} );

	// 3. Load widget CSS only on pages that use them.
	add_action( 'elementor/frontend/after_register_styles', function () {
		wp_register_style(
			'luwipress-gold-widgets',
			LUWIPRESS_GOLD_URI . '/assets/css/widgets.css',
			[ 'luwipress-gold-tokens' ],
			LUWIPRESS_GOLD_VERSION
		);
	} );
} );
