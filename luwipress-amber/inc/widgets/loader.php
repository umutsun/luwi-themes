<?php
/**
 * Elementor widget loader.
 *
 * Registers a "LuwiPress Amber" widget category and 7 custom widgets:
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
			'luwipress-amber',
			[
				'title' => __( 'LuwiPress Amber', 'luwipress-amber' ),
				'icon'  => 'eicon-star',
			]
		);
	} );

	// 2. Register widgets after Elementor's widget classes are loaded.
	add_action( 'elementor/widgets/register', function ( $widgets_manager ) {
		$widgets = [
			'class-product-card.php'     => 'LuwiPress_Amber_Widget_Product_Card',
			'class-master-profile.php'   => 'LuwiPress_Amber_Widget_Master_Profile',
			'class-timeline.php'         => 'LuwiPress_Amber_Widget_Timeline',
			'class-info-bar.php'         => 'LuwiPress_Amber_Widget_Info_Bar',
			'class-editorial-grid.php'   => 'LuwiPress_Amber_Widget_Editorial_Grid',
			'class-hero.php'             => 'LuwiPress_Amber_Widget_Hero',
			'class-tour-hero.php'        => 'LuwiPress_Amber_Widget_Tour_Hero',
			'class-mega-menu.php'        => 'LuwiPress_Amber_Widget_Mega_Menu',
			'class-megabar.php'          => 'LuwiPress_Amber_Widget_Megabar',
			'class-youtube-channel.php'  => 'LuwiPress_Amber_Widget_YouTube_Channel',
			'class-instagram-channel.php' => 'LuwiPress_Amber_Widget_Instagram_Channel',
			'class-section-head.php'     => 'LuwiPress_Amber_Widget_Section_Head',
			'class-category-grid.php'    => 'LuwiPress_Amber_Widget_Category_Grid',
			'class-master-grid.php'      => 'LuwiPress_Amber_Widget_Master_Grid',
			'class-story-split.php'      => 'LuwiPress_Amber_Widget_Story_Split',
			'class-hero-split.php'       => 'LuwiPress_Amber_Widget_Hero_Split',
			'class-testimonials.php'     => 'LuwiPress_Amber_Widget_Testimonials',
			'class-faq.php'              => 'LuwiPress_Amber_Widget_FAQ',
			'class-trust-badges.php'     => 'LuwiPress_Amber_Widget_Trust_Badges',
			'class-newsletter.php'       => 'LuwiPress_Amber_Widget_Newsletter',
			'class-countdown.php'        => 'LuwiPress_Amber_Widget_Countdown',
			'class-cta-banner.php'       => 'LuwiPress_Amber_Widget_CTA_Banner',
			'class-stat-counter.php'     => 'LuwiPress_Amber_Widget_Stat_Counter',
			'class-process-steps.php'    => 'LuwiPress_Amber_Widget_Process_Steps',
			'class-ai-search.php'        => 'LuwiPress_Amber_Widget_AI_Search',
			'class-featured-product.php' => 'LuwiPress_Amber_Widget_Featured_Product',
			'class-featured-strip.php'   => 'LuwiPress_Amber_Widget_Featured_Strip',
			'class-kg-stats.php'         => 'LuwiPress_Amber_Widget_KG_Stats',
			'class-kg-trending.php'      => 'LuwiPress_Amber_Widget_KG_Trending',
			// 1.7.37+ header / footer / chrome widgets
			'class-topbar.php'           => 'LuwiPress_Amber_Widget_Topbar',
			'class-logo.php'             => 'LuwiPress_Amber_Widget_Logo',
			'class-header-actions.php'   => 'LuwiPress_Amber_Widget_Header_Actions',
			'class-search-overlay.php'   => 'LuwiPress_Amber_Widget_Search_Overlay',
			'class-footer-brand.php'     => 'LuwiPress_Amber_Widget_Footer_Brand',
			'class-footer-column.php'    => 'LuwiPress_Amber_Widget_Footer_Column',
			'class-footer-bottom.php'    => 'LuwiPress_Amber_Widget_Footer_Bottom',
			// 1.7.38+ shop / single-product widgets
			'class-shop-filters.php'     => 'LuwiPress_Amber_Widget_Shop_Filters',
			'class-shop-toolbar.php'     => 'LuwiPress_Amber_Widget_Shop_Toolbar',
			'class-spec-list.php'        => 'LuwiPress_Amber_Widget_Spec_List',
			'class-perks-list.php'       => 'LuwiPress_Amber_Widget_Perks_List',
			// 1.8.0+ journal / 404 widgets (full widgetization milestone)
			'class-featured-post.php'    => 'LuwiPress_Amber_Widget_Featured_Post',
			'class-byline-card.php'      => 'LuwiPress_Amber_Widget_Byline_Card',
			'class-pullquote.php'        => 'LuwiPress_Amber_Widget_Pullquote',
			'class-reading-progress.php' => 'LuwiPress_Amber_Widget_Reading_Progress',
			'class-load-more.php'        => 'LuwiPress_Amber_Widget_Load_More',
			'class-404-hero.php'         => 'LuwiPress_Amber_Widget_404_Hero',
			// 1.9.0+ dynamic post widgets (Pro Theme Builder-free replacements)
			'class-post-title.php'           => 'LuwiPress_Amber_Widget_Post_Title',
			'class-post-content.php'         => 'LuwiPress_Amber_Widget_Post_Content',
			'class-post-featured-image.php'  => 'LuwiPress_Amber_Widget_Post_Featured_Image',
			// 1.9.0+ dynamic WC widgets Tier 1 (Pro WooCommerce widget replacements — simple)
			'class-wc-title.php'             => 'LuwiPress_Amber_Widget_WC_Title',
			'class-wc-price.php'             => 'LuwiPress_Amber_Widget_WC_Price',
			'class-wc-rating.php'            => 'LuwiPress_Amber_Widget_WC_Rating',
			'class-wc-short-description.php' => 'LuwiPress_Amber_Widget_WC_Short_Description',
			'class-wc-meta.php'              => 'LuwiPress_Amber_Widget_WC_Meta',
			// 1.9.0+ dynamic WC widgets Tier 2/3 (complex — gallery / related / ATC / tabs)
			'class-wc-gallery.php'           => 'LuwiPress_Amber_Widget_WC_Gallery',
			'class-wc-related.php'           => 'LuwiPress_Amber_Widget_WC_Related',
			'class-wc-add-to-cart.php'       => 'LuwiPress_Amber_Widget_WC_Add_To_Cart',
			'class-wc-tabs.php'              => 'LuwiPress_Amber_Widget_WC_Tabs',
			// 1.10.2+ curated products grid (Pro-free wc-products / [products] shortcode replacement)
			'class-product-grid.php'         => 'LuwiPress_Amber_Widget_Product_Grid',
			// 1.10.3+ contact CTA card (auto-pulls WhatsApp/Telegram from LuwiPress chat settings)
			'class-contact-cta.php'          => 'LuwiPress_Amber_Widget_Contact_CTA',
			// 1.10.5+ generic taxonomy-terms grid (any public taxonomy: product_cat / product_brand / lwp_vendor_group / category / tags)
			'class-taxonomy-terms.php'       => 'LuwiPress_Amber_Widget_Taxonomy_Terms',
			// 1.10.6+ generic CPT-Engine grid (any engine post type: lwp_vendor / team / events / operator-defined — CPT Engine Phase 2)
			'class-cpt-grid.php'             => 'LuwiPress_Amber_Widget_CPT_Grid',
			// Travel / tourism (Phase 2A): bookable tours + listing.
			'class-tour-booking-box.php'     => 'LuwiPress_Amber_Widget_Tour_Booking_Box',
			'class-tour-grid.php'            => 'LuwiPress_Amber_Widget_Tour_Grid',
			'class-tour-filters.php'         => 'LuwiPress_Amber_Widget_Tour_Filters',
			'class-tour-toolbar.php'         => 'LuwiPress_Amber_Widget_Tour_Toolbar',
			'class-activity-cards.php'       => 'LuwiPress_Amber_Widget_Activity_Cards',
		];

		foreach ( $widgets as $file => $class ) {
			$path = LUWIPRESS_AMBER_DIR . '/inc/widgets/lib/' . $file;
			if ( ! file_exists( $path ) ) continue;
			require_once $path;
			if ( class_exists( $class ) ) {
				$widgets_manager->register( new $class() );
			}
		}
	} );

	// 3. Widget + WooCommerce card styling lives in assets/css/widgets.css
	//    (Gold-ported, re-tokenised to the Amber palette). The `.lwp-pcard`
	//    product-card chrome and every native lwp-* Elementor widget reference
	//    the `luwipress-amber-widgets` handle via get_style_depends(), so it
	//    MUST resolve to widgets.css — not the tokens bridge. (Pre-1.1.0 this
	//    pointed at tokens.css, which silently shadowed the real enqueue in
	//    inc/enqueue.php → widgets.css never loaded → WC cards/buttons rendered
	//    unstyled. The Amber dark look + layout comes from the WooCommerce
	//    adapter baked into page-styles.css, which loads after widgets.css.)
	add_action( 'elementor/frontend/after_register_styles', function () {
		if ( ! wp_style_is( 'luwipress-amber-widgets', 'registered' ) ) {
			wp_register_style(
				'luwipress-amber-widgets',
				LUWIPRESS_AMBER_URI . '/assets/css/widgets.css',
				[ 'luwipress-amber-design' ],
				LUWIPRESS_AMBER_VERSION
			);
		}
	} );
} );
