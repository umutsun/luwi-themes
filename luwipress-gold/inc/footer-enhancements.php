<?php
/**
 * Footer enhancements.
 *
 * Adds the missing layers operators expect on a premium WooCommerce
 * footer: real social-media SVG icons, an optional newsletter signup
 * strip, an auto-generated payment-methods row, and a 3-up trust strip
 * (free shipping, secure checkout, returns…). Everything is opt-in via
 * Customizer — empty values render nothing.
 *
 * Customizer panel: Appearance → Customize → LuwiPress Gold → Footer.
 *
 * Generic — works on any store regardless of operator's social-media
 * presence, payment processor mix, or shipping policy. No Tapadum
 * specifics hard-coded.
 *
 * @package luwipress-gold
 * @since   1.6.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------
 * Customizer: register footer panel + sections + settings
 * ---------------------------------------------------------------- */

if ( ! function_exists( 'luwipress_gold_footer_customizer' ) ) {

	function luwipress_gold_footer_customizer( $wp_customize ) {

		// Use the existing top-level "LuwiPress Gold" panel if present;
		// fall back to creating one.
		$panel_id = 'luwipress_gold';
		if ( ! $wp_customize->get_panel( $panel_id ) ) {
			$wp_customize->add_panel( $panel_id, [
				'title'    => __( 'LuwiPress Gold', 'luwipress-gold' ),
				'priority' => 10,
			] );
		}

		// Section: Footer (re-use if already created; create otherwise).
		$section_id = 'luwipress_gold_footer';
		if ( ! $wp_customize->get_section( $section_id ) ) {
			$wp_customize->add_section( $section_id, [
				'title'    => __( 'Footer', 'luwipress-gold' ),
				'panel'    => $panel_id,
				'priority' => 50,
			] );
		}

		/* --- Social URLs ------------------------------------------- */
		$socials = luwipress_gold_footer_social_platforms();
		foreach ( $socials as $key => $cfg ) {
			$setting_id = 'luwipress_gold_social_' . $key;
			$wp_customize->add_setting( $setting_id, [
				'type'              => 'theme_mod',
				'default'           => '',
				'capability'        => 'edit_theme_options',
				'transport'         => 'refresh',
				'sanitize_callback' => 'esc_url_raw',
			] );
			$wp_customize->add_control( $setting_id, [
				'label'       => sprintf( /* translators: %s: platform name */ __( '%s URL', 'luwipress-gold' ), $cfg['label'] ),
				'description' => $cfg['placeholder'],
				'section'     => $section_id,
				'type'        => 'url',
				'priority'    => 10,
			] );
		}

		/* --- Newsletter ------------------------------------------- */
		$wp_customize->add_setting( 'luwipress_gold_newsletter_action', [
			'type'              => 'theme_mod',
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		] );
		$wp_customize->add_control( 'luwipress_gold_newsletter_action', [
			'label'       => __( 'Newsletter form action URL', 'luwipress-gold' ),
			'description' => __( 'POST endpoint that receives the email address — Mailchimp action URL, ConvertKit form URL, or any service that accepts a single "EMAIL" or "email" field. Leave empty to hide the newsletter strip.', 'luwipress-gold' ),
			'section'     => $section_id,
			'type'        => 'url',
			'priority'    => 30,
		] );

		$wp_customize->add_setting( 'luwipress_gold_newsletter_field_name', [
			'type'              => 'theme_mod',
			'default'           => 'EMAIL',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		] );
		$wp_customize->add_control( 'luwipress_gold_newsletter_field_name', [
			'label'       => __( 'Newsletter field name', 'luwipress-gold' ),
			'description' => __( 'Form input "name" attribute. Mailchimp uses EMAIL, ConvertKit uses email_address, native WP "Subscribe" plugins typically use email.', 'luwipress-gold' ),
			'section'     => $section_id,
			'type'        => 'text',
			'priority'    => 31,
		] );

		$wp_customize->add_setting( 'luwipress_gold_newsletter_headline', [
			'type'              => 'theme_mod',
			'default'           => __( 'Letters from the atelier', 'luwipress-gold' ),
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		] );
		$wp_customize->add_control( 'luwipress_gold_newsletter_headline', [
			'label'    => __( 'Newsletter headline', 'luwipress-gold' ),
			'section'  => $section_id,
			'type'     => 'text',
			'priority' => 32,
		] );

		$wp_customize->add_setting( 'luwipress_gold_newsletter_blurb', [
			'type'              => 'theme_mod',
			'default'           => __( 'New pieces, master profiles and field notes — once a month, no spam.', 'luwipress-gold' ),
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		] );
		$wp_customize->add_control( 'luwipress_gold_newsletter_blurb', [
			'label'    => __( 'Newsletter blurb', 'luwipress-gold' ),
			'section'  => $section_id,
			'type'     => 'textarea',
			'priority' => 33,
		] );

		/* --- Trust strip (3 short signals) ------------------------ */
		for ( $i = 1; $i <= 3; $i++ ) {
			$wp_customize->add_setting( "luwipress_gold_trust_signal_{$i}", [
				'type'              => 'theme_mod',
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'refresh',
			] );
			$wp_customize->add_control( "luwipress_gold_trust_signal_{$i}", [
				'label'    => sprintf( /* translators: %d: position */ __( 'Trust signal %d', 'luwipress-gold' ), $i ),
				'description' => $i === 1 ? __( 'Short value-prop lines (e.g. "Free DHL over €450", "Hand-tuned in our atelier", "30-day returns"). Empty = hide.', 'luwipress-gold' ) : '',
				'section'  => $section_id,
				'type'     => 'text',
				'priority' => 40 + $i,
			] );
		}

		/* --- Payment row toggle ----------------------------------- */
		$wp_customize->add_setting( 'luwipress_gold_show_payment_row', [
			'type'              => 'theme_mod',
			'default'           => true,
			'sanitize_callback' => function ( $v ) { return ! empty( $v ); },
			'transport'         => 'refresh',
		] );
		$wp_customize->add_control( 'luwipress_gold_show_payment_row', [
			'label'       => __( 'Show payment-method row', 'luwipress-gold' ),
			'description' => __( 'Renders the names of every active WooCommerce payment gateway as a discreet row in the footer bottom. Multi-method processors (e.g. WooPayments with Card / Alipay / iDEAL …) are grouped under one brand pill so the row stays readable. Hidden when WC is inactive or no gateway is enabled.', 'luwipress-gold' ),
			'section'     => $section_id,
			'type'        => 'checkbox',
			'priority'    => 50,
		] );

		/* --- Shop categories column (footer) ---------------------- */
		$wp_customize->add_setting( 'luwipress_gold_show_shop_col', [
			'type'              => 'theme_mod',
			'default'           => true,
			'sanitize_callback' => function ( $v ) { return ! empty( $v ); },
			'transport'         => 'refresh',
		] );
		$wp_customize->add_control( 'luwipress_gold_show_shop_col', [
			'label'       => __( 'Show "Shop" column', 'luwipress-gold' ),
			'description' => __( 'Adds a 5th footer column listing the top WooCommerce product categories by sales count. Hidden when WC is inactive or no categories exist.', 'luwipress-gold' ),
			'section'     => $section_id,
			'type'        => 'checkbox',
			'priority'    => 55,
		] );
		$wp_customize->add_setting( 'luwipress_gold_shop_col_limit', [
			'type'              => 'theme_mod',
			'default'           => 6,
			'sanitize_callback' => function ( $v ) { return max( 3, min( 12, (int) $v ) ); },
			'transport'         => 'refresh',
		] );
		$wp_customize->add_control( 'luwipress_gold_shop_col_limit', [
			'label'       => __( 'Shop column item count', 'luwipress-gold' ),
			'description' => __( 'How many top product categories to list. Range 3–12, default 6.', 'luwipress-gold' ),
			'section'     => $section_id,
			'type'        => 'number',
			'input_attrs' => [ 'min' => 3, 'max' => 12, 'step' => 1 ],
			'priority'    => 56,
		] );
	}
	add_action( 'customize_register', 'luwipress_gold_footer_customizer', 30 );
}

/* ------------------------------------------------------------------
 * Helpers — used by footer.php
 * ---------------------------------------------------------------- */

if ( ! function_exists( 'luwipress_gold_footer_social_platforms' ) ) {

	/**
	 * The platforms we know how to render. Each entry has:
	 *   - label       human-readable name
	 *   - placeholder hint shown in Customizer field
	 *   - icon        inline SVG path (24x24 viewBox)
	 *
	 * Filterable via `luwipress_gold_footer_social_platforms` so
	 * operators or sister plugins can add custom platforms (Pinterest,
	 * Discord, LinkedIn, Spotify, Bandcamp…) without forking the theme.
	 *
	 * @return array<string,array{label:string,placeholder:string,icon:string}>
	 */
	function luwipress_gold_footer_social_platforms() {
		$defaults = [
			'instagram' => [
				'label'       => 'Instagram',
				'placeholder' => 'https://instagram.com/your-handle',
				'icon'        => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="5" ry="5"/><circle cx="12" cy="12" r="4"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/></svg>',
			],
			'facebook' => [
				'label'       => 'Facebook',
				'placeholder' => 'https://facebook.com/your-page',
				'icon'        => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M22 12a10 10 0 1 0-11.56 9.88V14.9H7.9V12h2.54V9.8c0-2.5 1.5-3.9 3.78-3.9c1.1 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.62.77-1.62 1.56V12h2.76l-.44 2.9h-2.32v6.98A10 10 0 0 0 22 12z"/></svg>',
			],
			'youtube' => [
				'label'       => 'YouTube',
				'placeholder' => 'https://youtube.com/@your-channel',
				'icon'        => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.6 3.6 12 3.6 12 3.6s-7.6 0-9.4.5A3 3 0 0 0 .5 6.2C0 8 0 12 0 12s0 4 .5 5.8a3 3 0 0 0 2.1 2.1c1.8.5 9.4.5 9.4.5s7.6 0 9.4-.5a3 3 0 0 0 2.1-2.1C24 16 24 12 24 12s0-4-.5-5.8zM9.6 15.6V8.4l6.4 3.6l-6.4 3.6z"/></svg>',
			],
			'whatsapp' => [
				'label'       => 'WhatsApp',
				'placeholder' => 'https://wa.me/901234567890',
				'icon'        => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.05 4.91A9.82 9.82 0 0 0 12.05 2c-5.46 0-9.91 4.45-9.91 9.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.21h.01c5.46 0 9.91-4.45 9.91-9.91c0-2.65-1.03-5.14-2.91-7.01zm-7 15.24c-1.48 0-2.93-.4-4.2-1.15l-.3-.18l-3.12.82l.83-3.04l-.2-.31a8.26 8.26 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.25-8.24c2.2 0 4.27.86 5.82 2.42a8.18 8.18 0 0 1 2.41 5.83c0 4.54-3.7 8.23-8.24 8.23zm4.52-6.16c-.25-.12-1.47-.72-1.69-.81c-.23-.08-.39-.12-.56.13c-.17.25-.64.81-.78.97c-.14.17-.29.19-.54.06c-.25-.12-1.05-.39-2-1.23a7.5 7.5 0 0 1-1.39-1.72c-.14-.25-.02-.38.11-.51c.11-.11.25-.29.37-.43c.12-.14.17-.25.25-.41c.08-.17.04-.31-.02-.43c-.06-.12-.56-1.34-.76-1.84c-.2-.48-.41-.42-.56-.43c-.14-.01-.31-.01-.48-.01c-.17 0-.43.06-.66.31c-.23.25-.86.84-.86 2.05c0 1.21.88 2.38 1 2.55c.12.17 1.74 2.65 4.21 3.72c.59.25 1.05.4 1.41.52c.59.19 1.13.16 1.55.1c.47-.07 1.47-.6 1.67-1.18c.21-.58.21-1.07.14-1.18c-.06-.11-.22-.17-.46-.29z"/></svg>',
			],
			'pinterest' => [
				'label'       => 'Pinterest',
				'placeholder' => 'https://pinterest.com/your-handle',
				'icon'        => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.4 0 0 5.4 0 12c0 5 3.1 9.4 7.5 11.2c-.1-1-.2-2.4 0-3.5c.2-1 1.4-6 1.4-6s-.4-.7-.4-1.8c0-1.7 1-3 2.2-3c1 0 1.5.8 1.5 1.7c0 1-.7 2.6-1 4c-.3 1.2.6 2.1 1.7 2.1c2.1 0 3.7-2.2 3.7-5.4c0-2.8-2-4.8-5-4.8c-3.4 0-5.4 2.6-5.4 5.2c0 1 .4 2.1.9 2.7c.1.1.1.2.1.3l-.3 1.2c0 .2-.2.2-.4.1c-1.4-.7-2.3-2.7-2.3-4.4c0-3.6 2.6-6.9 7.5-6.9c3.9 0 7 2.8 7 6.6c0 3.9-2.5 7.1-5.9 7.1c-1.2 0-2.3-.6-2.6-1.3l-.7 2.7c-.3 1-1 2.4-1.5 3.2c1.1.3 2.3.5 3.5.5c6.6 0 12-5.4 12-12S18.6 0 12 0z"/></svg>',
			],
			'tiktok' => [
				'label'       => 'TikTok',
				'placeholder' => 'https://tiktok.com/@your-handle',
				'icon'        => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74a2.89 2.89 0 0 1 2.31-4.64a2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-.88-.05A6.33 6.33 0 0 0 5.8 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1.84-.1z"/></svg>',
			],
			'x' => [
				'label'       => 'X (Twitter)',
				'placeholder' => 'https://x.com/your-handle',
				'icon'        => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26l8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
			],
			'linkedin' => [
				'label'       => 'LinkedIn',
				'placeholder' => 'https://linkedin.com/company/your-handle',
				'icon'        => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.32 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.79M6.88 8.56a1.68 1.68 0 0 0 1.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 0 0-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.37H5.5v8.37h2.77z"/></svg>',
			],
			'spotify' => [
				'label'       => 'Spotify',
				'placeholder' => 'https://open.spotify.com/artist/your-id',
				'icon'        => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12s12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24c-2.82-1.74-6.36-2.101-10.561-1.141c-.418.122-.779-.179-.899-.539c-.12-.421.18-.78.54-.9c4.56-1.021 8.52-.6 11.64 1.32c.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3c-3.239-1.98-8.159-2.58-11.939-1.38c-.479.12-1.02-.12-1.14-.6c-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721c-.18-.601.18-1.2.72-1.381c4.26-1.26 11.28-1.02 15.721 1.621c.539.3.719 1.02.42 1.56c-.299.421-1.02.599-1.559.3z"/></svg>',
			],
		];
		return (array) apply_filters( 'luwipress_gold_footer_social_platforms', $defaults );
	}
}

if ( ! function_exists( 'luwipress_gold_footer_render_social_icons' ) ) {

	/**
	 * Render the configured social icons. Skips platforms with empty
	 * URLs. Returns nothing when no social URL is set on the site.
	 */
	function luwipress_gold_footer_render_social_icons() {
		$platforms = luwipress_gold_footer_social_platforms();
		$any       = false;
		ob_start();
		echo '<div class="lwp-site-footer-social">';
		foreach ( $platforms as $key => $cfg ) {
			$url = (string) get_theme_mod( 'luwipress_gold_social_' . $key, '' );
			if ( $url === '' ) {
				continue;
			}
			$any = true;
			printf(
				'<a href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s" class="lwp-social-pill lwp-social-pill--%s">%s</a>',
				esc_url( $url ),
				esc_attr( $cfg['label'] ),
				esc_attr( $key ),
				$cfg['icon'] // phpcs:ignore — vetted SVG markup defined above
			);
		}
		echo '</div>';
		$markup = ob_get_clean();
		if ( $any ) {
			echo $markup; // phpcs:ignore
		}
	}
}

if ( ! function_exists( 'luwipress_gold_footer_render_newsletter' ) ) {

	function luwipress_gold_footer_render_newsletter() {
		$action = (string) get_theme_mod( 'luwipress_gold_newsletter_action', '' );
		if ( $action === '' ) {
			return;
		}
		$field    = (string) get_theme_mod( 'luwipress_gold_newsletter_field_name', 'EMAIL' );
		$headline = (string) get_theme_mod( 'luwipress_gold_newsletter_headline',
			__( 'Letters from the atelier', 'luwipress-gold' )
		);
		$blurb    = (string) get_theme_mod( 'luwipress_gold_newsletter_blurb',
			__( 'New pieces, master profiles and field notes — once a month, no spam.', 'luwipress-gold' )
		);
		?>
		<section class="lwp-site-footer-newsletter" aria-label="<?php esc_attr_e( 'Newsletter signup', 'luwipress-gold' ); ?>">
			<div class="lwp-site-footer-newsletter__copy">
				<h3 class="lwp-site-footer-newsletter__title"><?php echo esc_html( $headline ); ?></h3>
				<?php if ( $blurb !== '' ) : ?>
					<p class="lwp-site-footer-newsletter__blurb"><?php echo esc_html( $blurb ); ?></p>
				<?php endif; ?>
			</div>
			<form class="lwp-site-footer-newsletter__form" action="<?php echo esc_url( $action ); ?>" method="post" target="_blank" novalidate>
				<label class="screen-reader-text" for="lwp-newsletter-email"><?php esc_html_e( 'Your email', 'luwipress-gold' ); ?></label>
				<input type="email" required
					id="lwp-newsletter-email"
					name="<?php echo esc_attr( $field ?: 'EMAIL' ); ?>"
					placeholder="<?php esc_attr_e( 'you@example.com', 'luwipress-gold' ); ?>"
				>
				<button type="submit" class="lwp-site-footer-newsletter__submit"><?php esc_html_e( 'Subscribe', 'luwipress-gold' ); ?></button>
			</form>
		</section>
		<?php
	}
}

if ( ! function_exists( 'luwipress_gold_footer_render_trust_strip' ) ) {

	function luwipress_gold_footer_render_trust_strip() {
		$signals = [];
		for ( $i = 1; $i <= 3; $i++ ) {
			$v = trim( (string) get_theme_mod( "luwipress_gold_trust_signal_{$i}", '' ) );
			if ( $v !== '' ) {
				$signals[] = $v;
			}
		}
		// Sensible defaults for stores that haven't filled in the field —
		// gives the footer some life on day-one without the operator
		// being forced through Customizer first. Only applies when WC
		// is active (otherwise "30-day returns" is wrong messaging for
		// a content site).
		if ( empty( $signals ) && function_exists( 'WC' ) ) {
			$signals = (array) apply_filters( 'luwipress_gold_footer_trust_signal_defaults', [
				__( 'Secure checkout', 'luwipress-gold' ),
				__( 'Worldwide shipping', 'luwipress-gold' ),
				__( '30-day returns', 'luwipress-gold' ),
			] );
		}
		if ( empty( $signals ) ) {
			return;
		}
		echo '<ul class="lwp-site-footer-trust" role="list">';
		foreach ( $signals as $s ) {
			echo '<li>' . esc_html( $s ) . '</li>';
		}
		echo '</ul>';
	}
}

if ( ! function_exists( 'luwipress_gold_footer_render_shop_categories' ) ) {

	/**
	 * Render a "Shop" column listing the top product categories by
	 * count. Adds depth to the footer for commerce sites without
	 * forcing the operator to maintain a parallel "footer-shop" nav
	 * menu in Görünüm → Menüler.
	 *
	 * Hidden when:
	 *   - WC is inactive
	 *   - product_cat taxonomy doesn't exist
	 *   - operator opted out via Customizer
	 *   - no terms have any products
	 */
	function luwipress_gold_footer_render_shop_categories() {
		if ( ! (bool) get_theme_mod( 'luwipress_gold_show_shop_col', true ) ) {
			return;
		}
		if ( ! function_exists( 'WC' ) || ! taxonomy_exists( 'product_cat' ) ) {
			return;
		}
		$limit = max( 3, min( 12, (int) get_theme_mod( 'luwipress_gold_shop_col_limit', 6 ) ) );
		$terms = get_terms( [
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => 0,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'number'     => $limit,
		] );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return;
		}
		?>
		<div class="lwp-site-footer-col lwp-site-footer-shop">
			<h4><?php esc_html_e( 'Shop', 'luwipress-gold' ); ?></h4>
			<ul>
				<?php foreach ( $terms as $t ) :
					$link = get_term_link( $t );
					if ( is_wp_error( $link ) ) continue; ?>
					<li><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $t->name ); ?> <span class="lwp-site-footer-col-count"><?php echo (int) $t->count; ?></span></a></li>
				<?php endforeach; ?>
				<?php if ( $shop = wc_get_page_permalink( 'shop' ) ) : ?>
					<li><a href="<?php echo esc_url( $shop ); ?>" class="lwp-site-footer-col-allink"><?php esc_html_e( 'View all collections →', 'luwipress-gold' ); ?></a></li>
				<?php endif; ?>
			</ul>
		</div>
		<?php
	}
}

if ( ! function_exists( 'luwipress_gold_footer_render_payment_row' ) ) {

	/**
	 * Render the active WC payment gateways as a discreet horizontal
	 * row in the footer bottom. Plain-text labels — no logo SVGs (they
	 * vary per processor and we'd rather not maintain an icon library
	 * for every gateway in WC's ecosystem).
	 *
	 * Multi-method processors are grouped under their brand: a single
	 * pill saying "WooPayments" with a tooltip listing Card, Alipay,
	 * iDEAL …  is far more readable than nine "WooPayments (X)" pills
	 * stacked side-by-side. Detection is purely on the label shape
	 * "Brand (Method)" so it works with any processor that follows
	 * WC's recommended naming convention.
	 *
	 * Hidden when WC is inactive or no gateway is enabled.
	 */
	function luwipress_gold_footer_render_payment_row() {
		if ( ! (bool) get_theme_mod( 'luwipress_gold_show_payment_row', true ) ) {
			return;
		}
		if ( ! function_exists( 'WC' ) ) {
			return;
		}
		$gateways = WC()->payment_gateways();
		if ( ! $gateways ) {
			return;
		}
		$available = $gateways->get_available_payment_gateways();
		if ( empty( $available ) ) {
			return;
		}

		$brand_methods = []; // brand => [ method, method, … ]
		$standalone    = []; // raw label
		foreach ( $available as $g ) {
			$label = method_exists( $g, 'get_method_title' )
				? $g->get_method_title()
				: ( isset( $g->title ) ? $g->title : ( isset( $g->id ) ? $g->id : '' ) );
			$label = trim( (string) $label );
			if ( $label === '' ) {
				continue;
			}
			// "Brand (Method)" → group by brand. Uses Unicode-aware regex
			// so brand names with accents render correctly.
			if ( preg_match( '/^([^()]+?)\s*\(([^()]+)\)\s*$/u', $label, $m ) ) {
				$brand  = trim( $m[1] );
				$method = trim( $m[2] );
				if ( $brand !== '' ) {
					$brand_methods[ $brand ][] = $method;
					continue;
				}
			}
			$standalone[ $label ] = $label;
		}

		// Compose the rendered entries: single-method brands collapse back
		// to "Brand · Method", multi-method brands become one pill with
		// the methods in a tooltip.
		$entries = [];
		foreach ( $brand_methods as $brand => $methods ) {
			if ( count( $methods ) === 1 ) {
				$entries[] = [ 'label' => $brand . ' · ' . $methods[0], 'tooltip' => '' ];
			} else {
				$entries[] = [
					'label'   => $brand,
					'tooltip' => sprintf(
						/* translators: %s: comma-separated payment method list */
						__( 'Includes: %s', 'luwipress-gold' ),
						implode( ', ', array_unique( $methods ) )
					),
				];
			}
		}
		foreach ( $standalone as $label ) {
			$entries[] = [ 'label' => $label, 'tooltip' => '' ];
		}

		/**
		 * Allow operators to override / re-order / extend the rendered
		 * payment row. Each entry is `[ 'label' => string, 'tooltip' => string ]`.
		 */
		$entries = (array) apply_filters( 'luwipress_gold_footer_payment_entries', $entries );
		if ( empty( $entries ) ) {
			return;
		}

		echo '<ul class="lwp-site-footer-payments" aria-label="' . esc_attr__( 'Accepted payment methods', 'luwipress-gold' ) . '">';
		foreach ( $entries as $e ) {
			$label = isset( $e['label'] ) ? (string) $e['label'] : '';
			$tip   = isset( $e['tooltip'] ) ? (string) $e['tooltip'] : '';
			if ( $label === '' ) {
				continue;
			}
			if ( $tip !== '' ) {
				printf( '<li title="%s">%s</li>', esc_attr( $tip ), esc_html( $label ) );
			} else {
				printf( '<li>%s</li>', esc_html( $label ) );
			}
		}
		echo '</ul>';
	}
}

if ( ! function_exists( 'luwipress_gold_footer_print_styles' ) ) {

	/**
	 * Inline styles for the new footer blocks. Scoped to footer
	 * selectors so it can't leak into other layouts. ~2 KB.
	 */
	function luwipress_gold_footer_print_styles() {
		if ( is_admin() ) {
			return;
		}
		?>
<style id="lwp-gold-footer-enhancements">
.lwp-site-footer-social{display:flex;gap:10px;margin-top:16px;flex-wrap:wrap}
.lwp-social-pill{
	display:inline-flex;align-items:center;justify-content:center;
	width:36px;height:36px;border-radius:50%;
	background:rgba(255,255,255,.06);color:rgba(255,255,255,.78);
	transition:background .15s,color .15s,transform .15s;
}
.lwp-social-pill svg{width:18px;height:18px;display:block}
.lwp-social-pill:hover{background:var(--lwp-gold-accent,#735c00);color:#fff;transform:translateY(-1px)}
.lwp-site-footer-newsletter{
	display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.2fr);gap:32px;
	align-items:center;padding:28px 0;margin:32px 0;
	border-top:1px solid rgba(255,255,255,.08);border-bottom:1px solid rgba(255,255,255,.08);
}
.lwp-site-footer-newsletter__title{
	margin:0 0 4px;font-family:var(--lwp-gold-serif,"Playfair Display",serif);
	font-size:22px;color:#fff;font-weight:600;
}
.lwp-site-footer-newsletter__blurb{
	margin:0;color:rgba(255,255,255,.66);font-size:14px;line-height:1.55;
}
.lwp-site-footer-newsletter__form{display:flex;gap:8px;align-items:stretch}
.lwp-site-footer-newsletter__form input[type="email"]{
	flex:1;min-width:0;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);
	color:#fff;padding:11px 14px;border-radius:999px;font-size:14px;
}
.lwp-site-footer-newsletter__form input::placeholder{color:rgba(255,255,255,.4)}
.lwp-site-footer-newsletter__form input:focus{outline:0;border-color:var(--lwp-gold-accent,#735c00)}
.lwp-site-footer-newsletter__submit{
	background:var(--lwp-gold-accent,#735c00);color:#fff;border:0;
	padding:11px 22px;border-radius:999px;font-size:13px;
	letter-spacing:.06em;text-transform:uppercase;cursor:pointer;font-weight:600;
}
.lwp-site-footer-newsletter__submit:hover{filter:brightness(1.08)}
.lwp-site-footer-trust{
	list-style:none;margin:0 0 24px;padding:0;
	display:flex;gap:24px;flex-wrap:wrap;justify-content:center;
}
.lwp-site-footer-trust li{
	font-size:12px;letter-spacing:.08em;text-transform:uppercase;
	color:rgba(255,255,255,.62);position:relative;
}
.lwp-site-footer-trust li:not(:last-child)::after{
	content:'·';position:absolute;right:-14px;color:rgba(255,255,255,.3);
}
.lwp-site-footer-payments{
	list-style:none;margin:14px 0 0;padding:0;
	display:flex;gap:14px;flex-wrap:wrap;justify-content:center;
}
.lwp-site-footer-payments li{
	font-size:11px;letter-spacing:.06em;color:rgba(255,255,255,.42);
	padding:3px 10px;background:rgba(255,255,255,.05);border-radius:999px;
}
@media (max-width:780px){
	.lwp-site-footer-newsletter{grid-template-columns:1fr;gap:18px;padding:22px 0;margin:24px 0}
	.lwp-site-footer-newsletter__form{flex-direction:column;gap:8px}
	.lwp-site-footer-trust{gap:14px}
	.lwp-site-footer-trust li:not(:last-child)::after{display:none}
}
</style>
		<?php
	}
	add_action( 'wp_head', 'luwipress_gold_footer_print_styles', 99 );
}
