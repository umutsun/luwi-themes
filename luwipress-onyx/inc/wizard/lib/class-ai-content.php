<?php
/**
 * AI Content Generator — bridges the theme's `{{LWP:ai:slot[|args]}}` placeholders
 * to the LuwiPress core plugin's AI engine REST API.
 *
 * Strategy
 * --------
 * 1. Operator opts-in during the wizard (4th step "AI polish").
 * 2. Compiler encounters `{{LWP:ai:hero_lead}}` while compiling a JSON
 *    template. It calls AI_Content::resolve('hero_lead', $context).
 * 3. resolve() checks transient cache (default 7 days). On miss, it sends
 *    a single REST request to LuwiPress's AI dispatch endpoint with:
 *      - prompt template ('hero_lead', 'about_story', 'master_bio', 'faq')
 *      - site context (name, sector, top categories, top product names)
 *    and writes the response to cache.
 * 4. If LuwiPress core isn't installed OR the request fails, we fall back
 *    to a static `default` string so the page never renders empty.
 *
 * Slots
 * -----
 * Each slot has a prompt template + a default fallback. New slots add to
 * the SLOTS table; the compiler doesn't need to know about them.
 *
 * Cache key: `lwp_onyx_ai_<slot>_<context_hash>` so the operator
 * regenerates simply by clearing transients (which the wizard offers).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LuwiPress_Onyx_AI_Content {

	const CACHE_TTL = WEEK_IN_SECONDS;
	const ENABLED_OPTION = 'luwipress_onyx_ai_enabled';
	const PROVIDER_OPTION = 'luwipress_onyx_ai_provider';
	const META_OPTION = 'luwipress_onyx_ai_meta';

	/**
	 * Slot manifest. Each entry:
	 *   - prompt:   format string for the AI request, %s = site context blob
	 *   - default:  fallback shown when AI is disabled / unavailable
	 *   - max_words: hint to the model
	 */
	public static function slots() {
		return [
			'hero_lead' => [
				'label'     => __( 'Hero — lead paragraph', 'luwipress-onyx' ),
				'max_words' => 35,
				'prompt'    => 'Write a concise lead paragraph (max %2$d words) for the homepage hero of an artisan e-commerce store called "%1$s". The store sells: %3$s. Tone: editorial, warm, confident, no marketing fluff. Mention the craft tradition without naming any specific person or place. Plain text, no quotation marks.',
				'default'   => 'A curated catalogue from master craftspeople — every piece in the atelier is signed, tuned and packed by hand before shipping worldwide.',
			],
			'about_story' => [
				'label'     => __( 'About — short brand story', 'luwipress-onyx' ),
				'max_words' => 90,
				'prompt'    => 'Write a 2-paragraph brand story (max %2$d words total) for the About page of "%1$s", an artisan store that sells: %3$s. Each paragraph stands on its own. Tone: editorial, slow, hand-crafted feel. No exclamation marks. Plain text, no quotation marks.',
				'default'   => "We work directly with artisans whose hands have shaped this craft for decades. Every product is sourced, prepared and quality-checked in our atelier before it ships.\n\nWe do three things, and we try to do them well: meet the makers, set up the work, and ship it so it arrives ready to use.",
			],
			'master_bio' => [
				'label'     => __( 'Master profile — short bio', 'luwipress-onyx' ),
				'max_words' => 50,
				'prompt'    => 'Write a brief one-paragraph biography (max %2$d words) for an artisan named "%4$s", who specialises in: %3$s. Mention craftsmanship and time; do not invent locations or biographical details. Tone: editorial, factual, respectful. Plain text.',
				'default'   => 'A long-time master of the craft. Their work has been in our atelier for many years and continues to define what excellence looks like in this category.',
			],
			'faq_intro' => [
				'label'     => __( 'Contact — FAQ intro', 'luwipress-onyx' ),
				'max_words' => 25,
				'prompt'    => 'Write a 1-sentence intro (max %2$d words) for the FAQ section of an artisan e-commerce store called "%1$s" that sells: %3$s. Tone: warm, helpful. Plain text.',
				'default'   => 'Common questions from the workshop — pick a topic below or write to us directly if you don\'t see your answer.',
			],
			'shipping_blurb' => [
				'label'     => __( 'Info-bar — shipping note', 'luwipress-onyx' ),
				'max_words' => 12,
				'prompt'    => 'Write a 1-line shipping reassurance (max %2$d words) for an artisan store. Plain text.',
				'default'   => 'Worldwide DHL Express — every package insured.',
			],
		];
	}

	/**
	 * Master entry point — resolve a slot for the current site.
	 *
	 * @param string $slot     Slot key (see slots()).
	 * @param array  $context  Optional per-call extras (e.g. master name).
	 * @return string  AI-generated text, or the slot's default fallback.
	 */
	public static function resolve( $slot, $context = [] ) {
		$slots = self::slots();
		if ( ! isset( $slots[ $slot ] ) ) return '';

		$cfg = $slots[ $slot ];

		// Honor the master switch — if AI is disabled, return default.
		if ( ! self::is_enabled() ) {
			return $cfg['default'];
		}

		// Cache check.
		$cache_key = self::cache_key( $slot, $context );
		$cached = get_transient( $cache_key );
		if ( $cached !== false ) {
			return $cached;
		}

		// Build the prompt.
		$site_ctx = self::site_context();
		$prompt = sprintf(
			$cfg['prompt'],
			$site_ctx['name'],          // %1$s
			(int) $cfg['max_words'],    // %2$d
			$site_ctx['sector'],        // %3$s
			$context['name'] ?? '',     // %4$s — used by master_bio
			$context['extra'] ?? ''     // %5$s — free slot for future
		);

		$result = self::dispatch_to_luwipress( $prompt, $cfg['max_words'] );

		if ( ! $result ) {
			// On failure: cache the default for a short time so we don't
			// hammer the API; long-cache only successful results.
			set_transient( $cache_key, $cfg['default'], HOUR_IN_SECONDS );
			return $cfg['default'];
		}

		set_transient( $cache_key, $result, self::CACHE_TTL );
		self::record_meta( $slot, $cache_key, $prompt );
		return $result;
	}

	/**
	 * Master switch — operator opt-in via wizard.
	 */
	public static function is_enabled() {
		return (bool) get_option( self::ENABLED_OPTION, false ) && self::is_luwipress_available();
	}

	public static function is_luwipress_available() {
		return defined( 'LUWIPRESS_VERSION' ) || class_exists( 'LuwiPress_AI_Engine' );
	}

	/**
	 * Build a stable site context — name, sector, top categories.
	 * Used to make the AI prompts site-specific without leaking PII.
	 */
	private static function site_context() {
		$name    = get_bloginfo( 'name' );
		$tagline = get_bloginfo( 'description' );

		$sectors = [];
		if ( taxonomy_exists( 'product_cat' ) ) {
			$terms = get_terms( [
				'taxonomy'   => 'product_cat',
				'parent'     => 0,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'number'     => 5,
				'hide_empty' => true,
			] );
			if ( ! is_wp_error( $terms ) ) {
				$sectors = wp_list_pluck( $terms, 'name' );
			}
		}
		$sector_blob = ! empty( $sectors )
			? implode( ', ', $sectors )
			: ( $tagline ?: __( 'hand-crafted goods', 'luwipress-onyx' ) );

		return [
			'name'   => $name,
			'sector' => $sector_blob,
			'tagline'=> $tagline,
		];
	}

	/**
	 * Send the prompt to LuwiPress core's AI dispatch.
	 *
	 * Tries multiple integration points so the bridge survives core API
	 * tweaks: direct PHP class first, REST fallback.
	 *
	 * @return string|null  Trimmed text on success, null on failure.
	 */
	private static function dispatch_to_luwipress( $prompt, $max_words ) {
		// 1. Direct PHP — fastest, no HTTP overhead.
		if ( class_exists( 'LuwiPress_AI_Engine' ) && method_exists( 'LuwiPress_AI_Engine', 'dispatch' ) ) {
			try {
				$response = \LuwiPress_AI_Engine::dispatch( [
					'prompt'    => $prompt,
					'task'      => 'theme_static_copy',
					'max_words' => (int) $max_words,
					'temperature' => 0.7,
				] );
				if ( is_array( $response ) && ! empty( $response['text'] ) ) {
					return self::sanitize_ai_output( $response['text'] );
				}
				if ( is_string( $response ) ) {
					return self::sanitize_ai_output( $response );
				}
			} catch ( \Throwable $e ) {
				// fall through to REST attempt
			}
		}

		// 2. REST fallback — internal request, no auth needed if we run as
		//    an admin (the wizard runs as admin, so AI generation does too).
		if ( function_exists( 'rest_do_request' ) ) {
			$req = new \WP_REST_Request( 'POST', '/luwipress/v1/ai/dispatch' );
			$req->set_param( 'prompt', $prompt );
			$req->set_param( 'task', 'theme_static_copy' );
			$req->set_param( 'max_words', (int) $max_words );
			$resp = rest_do_request( $req );
			if ( ! is_wp_error( $resp ) && $resp->get_status() === 200 ) {
				$data = $resp->get_data();
				if ( is_array( $data ) && ! empty( $data['text'] ) ) {
					return self::sanitize_ai_output( $data['text'] );
				}
				if ( is_string( $data ) ) {
					return self::sanitize_ai_output( $data );
				}
			}
		}

		return null;
	}

	/**
	 * Light cleanup of model output — strip surrounding quotes, normalize
	 * whitespace, drop "Sure, here is..." prefixes some models add.
	 */
	private static function sanitize_ai_output( $text ) {
		$text = trim( (string) $text );
		// Drop any model preamble before the actual content.
		$preambles = [
			'/^(sure|certainly|of course|here is|here\'s|here you go)[,:!.]?\s*/i',
		];
		foreach ( $preambles as $re ) {
			$text = preg_replace( $re, '', $text );
		}
		// Strip wrapping quotes if present.
		$text = trim( $text, "\"'`“”‘’ \t\n\r" );
		// Collapse whitespace.
		$text = preg_replace( '/[ \t]+/', ' ', $text );
		$text = preg_replace( '/\n{3,}/', "\n\n", $text );
		return wp_kses_post( $text );
	}

	/**
	 * Cache key — hashes site URL + slot + per-call context so a brand
	 * change (logo, sector) regenerates the cache automatically.
	 */
	private static function cache_key( $slot, $context = [] ) {
		$ctx = self::site_context();
		$blob = wp_json_encode( [ 'site' => $ctx['name'], 'sector' => $ctx['sector'], 'extra' => $context ] );
		return 'lwp_onyx_ai_' . $slot . '_' . substr( md5( $blob ), 0, 12 );
	}

	/**
	 * Index of generated slots (for the wizard's "Regenerate AI text" button).
	 */
	private static function record_meta( $slot, $cache_key, $prompt ) {
		$meta = get_option( self::META_OPTION, [] );
		$meta[ $slot ] = [
			'cache_key' => $cache_key,
			'generated' => current_time( 'mysql' ),
			'prompt_excerpt' => mb_substr( $prompt, 0, 140 ),
		];
		update_option( self::META_OPTION, $meta, false );
	}

	/**
	 * Drop every cached AI text — invoked by the wizard's "Regenerate" button.
	 */
	public static function flush_cache() {
		$meta = get_option( self::META_OPTION, [] );
		foreach ( $meta as $slot => $entry ) {
			if ( ! empty( $entry['cache_key'] ) ) {
				delete_transient( $entry['cache_key'] );
			}
		}
		delete_option( self::META_OPTION );
	}
}
