<?php
/**
 * Blog page auto-fallback.
 *
 * Common operator pattern: a "Blog" / "Journal" / "News" Page exists in
 * the site, the visitor menu links to it, but it was never wired up as
 * the WordPress "Posts page" (Settings → Reading), and the operator
 * never put any content into it. Result: clicking BLOG in the menu
 * opens an empty page chrome — header + footer with nothing in
 * between.
 *
 * This module rescues that case at runtime, with zero operator action:
 *
 *   1. AUTO-PROMOTE — on `init`, if a published Page named/slugged
 *      "blog" / "journal" / "news" / "haberler" / "magazine" exists AND
 *      the WP "Posts page" option is empty AND there is at least one
 *      published post, we set `page_for_posts` + `show_on_front=page`
 *      so WordPress natively serves the post archive at that URL.
 *      One-time, idempotent — operator can override via Settings →
 *      Reading and we don't fight that.
 *
 *   2. CONTENT INJECT — for visits that still land on a Page (e.g.
 *      operator chose not to flip to Posts-page mode), if the page is
 *      empty AND its slug/title matches the blog-name allowlist AND
 *      published posts exist, we inject a recent-posts grid into
 *      `the_content`. Same template treatment as `home.php` so the
 *      visual surface stays consistent.
 *
 * The allowlist is filterable via `luwipress_amber_blog_page_slugs` so
 * sites with non-English blog conventions (`actualites`, `noticias`,
 * `revista`…) can extend without touching theme code.
 *
 * @package luwipress-amber
 * @since   1.6.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'luwipress_amber_get_blog_page_slugs' ) ) {

	/**
	 * @return string[] Lower-case slug allowlist.
	 */
	function luwipress_amber_get_blog_page_slugs() {
		$defaults = [
			'blog',
			'journal',
			'news',
			'magazine',
			'haberler',   // tr
			'noticias',   // es
			'actualites', // fr
			'novita',     // it
		];
		return (array) apply_filters( 'luwipress_amber_blog_page_slugs', $defaults );
	}
}

if ( ! function_exists( 'luwipress_amber_find_blog_page_id' ) ) {

	/**
	 * Locate a blog-style page in the site, in this priority order:
	 *   1. Settings → Reading "Posts page" (already wired)
	 *   2. First published page whose slug exactly matches the allowlist
	 *   3. First published page whose title (lowercased) matches the
	 *      allowlist
	 *
	 * @return int Page ID or 0.
	 */
	function luwipress_amber_find_blog_page_id() {
		$existing = (int) get_option( 'page_for_posts' );
		if ( $existing > 0 && get_post_status( $existing ) === 'publish' ) {
			return $existing;
		}

		$slugs = array_map( 'strtolower', luwipress_amber_get_blog_page_slugs() );

		// 2) by slug.
		foreach ( $slugs as $slug ) {
			$p = get_page_by_path( $slug, OBJECT, 'page' );
			if ( $p && $p->post_status === 'publish' ) {
				return (int) $p->ID;
			}
		}

		// 3) by title (lowercase compare).
		$pages = get_pages( [
			'post_status' => 'publish',
			'number'      => 50,
			'sort_column' => 'menu_order',
		] );
		if ( is_array( $pages ) ) {
			foreach ( $pages as $p ) {
				$t = strtolower( trim( (string) $p->post_title ) );
				if ( $t !== '' && in_array( $t, $slugs, true ) ) {
					return (int) $p->ID;
				}
			}
		}
		return 0;
	}
}

if ( ! function_exists( 'luwipress_amber_page_is_effectively_empty' ) ) {

	/**
	 * Heuristic: page has effectively no visible content. Considers raw
	 * `post_content` AND Elementor's `_elementor_data`. Anything under 80
	 * characters of meaningful content counts as empty (ignoring
	 * whitespace and a stray BOM).
	 *
	 * @param int $page_id
	 * @return bool
	 */
	function luwipress_amber_page_is_effectively_empty( $page_id ) {
		$post = get_post( $page_id );
		if ( ! $post ) {
			return false;
		}
		$raw = trim( wp_strip_all_tags( (string) $post->post_content ) );
		if ( strlen( $raw ) >= 80 ) {
			return false;
		}
		// Elementor data is usually a JSON array; the empty-Elementor case
		// is `[]` or absent.
		$elementor = (string) get_post_meta( $page_id, '_elementor_data', true );
		$elementor = trim( $elementor );
		if ( $elementor === '' || $elementor === '[]' ) {
			return true;
		}
		// Decoded length; tolerate a few bytes of structural overhead.
		$decoded = json_decode( $elementor, true );
		if ( ! is_array( $decoded ) ) {
			return true;
		}
		return count( $decoded ) === 0;
	}
}

if ( ! function_exists( 'luwipress_amber_auto_promote_blog_page' ) ) {

	/**
	 * One-time auto-promotion: if a blog-style page exists, no Posts page
	 * is set, and at least one post is published — wire the page into
	 * Settings → Reading. Records a flag option so we don't keep
	 * re-promoting if the operator later un-sets it on purpose.
	 */
	function luwipress_amber_auto_promote_blog_page() {
		if ( wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		// Already promoted by us? Don't re-fight an operator decision.
		if ( get_option( 'luwipress_amber_blog_promoted' ) ) {
			return;
		}
		// Operator already configured? Respect it.
		$existing = (int) get_option( 'page_for_posts' );
		if ( $existing > 0 ) {
			return;
		}
		// Need at least one published post.
		$post_count = (int) wp_count_posts( 'post' )->publish;
		if ( $post_count < 1 ) {
			return;
		}
		$page_id = luwipress_amber_find_blog_page_id();
		if ( $page_id <= 0 ) {
			return;
		}
		// Avoid clobbering a static-front-page setup that doesn't have a
		// homepage assigned — only set show_on_front when reasonable.
		$front_id = (int) get_option( 'page_on_front' );
		if ( $front_id > 0 || (string) get_option( 'show_on_front' ) === 'page' ) {
			update_option( 'page_for_posts', $page_id );
		} else {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_for_posts', $page_id );
			// Don't set page_on_front; leave that to the operator's choice
			// (they may prefer the WP latest-posts homepage).
		}
		update_option( 'luwipress_amber_blog_promoted', $page_id );
	}
	add_action( 'init', 'luwipress_amber_auto_promote_blog_page', 20 );
}

if ( ! function_exists( 'luwipress_amber_inject_blog_loop_into_empty_page' ) ) {

	/**
	 * Runtime fallback: when an empty blog-style page is rendered (e.g.
	 * because show_on_front is 'posts' and the operator didn't auto-promote,
	 * or some plugin clobbered page_for_posts), inject a 9-card recent-
	 * posts grid into the_content. Markup mirrors `home.php` cards so
	 * spacing stays consistent.
	 *
	 * @param string $content
	 * @return string
	 */
	function luwipress_amber_inject_blog_loop_into_empty_page( $content ) {
		if ( is_admin() || is_feed() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		if ( ! is_page() ) {
			return $content;
		}
		$page_id = (int) get_queried_object_id();
		if ( $page_id <= 0 ) {
			return $content;
		}
		// Don't inject when the page IS the Posts page — WP serves the
		// archive natively there, no need for the_content fallback.
		if ( (int) get_option( 'page_for_posts' ) === $page_id ) {
			return $content;
		}
		// Slug / title gate — only act on blog-style pages.
		$post = get_post( $page_id );
		if ( ! $post ) {
			return $content;
		}
		$slugs = array_map( 'strtolower', luwipress_amber_get_blog_page_slugs() );
		$slug  = strtolower( (string) $post->post_name );
		$title = strtolower( trim( (string) $post->post_title ) );
		if ( ! in_array( $slug, $slugs, true ) && ! in_array( $title, $slugs, true ) ) {
			return $content;
		}
		// Empty enough?
		if ( ! luwipress_amber_page_is_effectively_empty( $page_id ) ) {
			return $content;
		}
		// Fetch recent posts.
		$query = new WP_Query( [
			'post_type'           => 'post',
			'posts_per_page'      => 9,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		] );
		if ( ! $query->have_posts() ) {
			return $content;
		}
		ob_start();
		?>
		<section class="lwp-journal-grid lwp-journal-grid--fallback" aria-label="<?php esc_attr_e( 'Recent posts', 'luwipress-amber' ); ?>">
			<?php while ( $query->have_posts() ) :
				$query->the_post();
				$thumb = get_the_post_thumbnail_url( get_the_ID(), 'medium_large' );
				?>
				<article class="lwp-journal-card">
					<a href="<?php the_permalink(); ?>" class="lwp-journal-card__media"<?php
						echo $thumb ? ' style="background-image:url(' . esc_url( $thumb ) . ')"' : '';
					?>></a>
					<div class="lwp-journal-card__copy">
						<span class="lwp-eyebrow"><?php echo esc_html( get_the_date( 'M Y' ) ); ?></span>
						<h3 class="lwp-journal-card__title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h3>
						<p class="lwp-journal-card__excerpt">
							<?php echo esc_html( wp_trim_words( get_the_excerpt(), 22, '…' ) ); ?>
						</p>
						<a class="lwp-journal-card__cta" href="<?php the_permalink(); ?>">
							<?php esc_html_e( 'Read →', 'luwipress-amber' ); ?>
						</a>
					</div>
				</article>
			<?php endwhile; ?>
		</section>
		<style id="lwp-amber-blog-fallback-css">
			.lwp-journal-grid--fallback{
				display:grid;grid-template-columns:repeat(3,1fr);gap:28px;margin:24px 0 48px;
			}
			.lwp-journal-grid--fallback .lwp-journal-card{
				display:flex;flex-direction:column;background:#fff;border:1px solid var(--line,#e7e3d6);
				border-radius:12px;overflow:hidden;
			}
			.lwp-journal-grid--fallback .lwp-journal-card__media{
				display:block;aspect-ratio:4/3;background:#f3efe5 center/cover no-repeat;
			}
			.lwp-journal-grid--fallback .lwp-journal-card__copy{padding:18px 20px;display:flex;flex-direction:column;gap:8px;}
			.lwp-journal-grid--fallback .lwp-journal-card__title{
				margin:0;font-family:var(--lwp-amber-serif,"Playfair Display",serif);font-size:20px;line-height:1.25;
			}
			.lwp-journal-grid--fallback .lwp-journal-card__title a{color:var(--ink,#1b1c1c);text-decoration:none;}
			.lwp-journal-grid--fallback .lwp-journal-card__excerpt{margin:0;font-size:14px;line-height:1.5;color:#5b5247;}
			.lwp-journal-grid--fallback .lwp-journal-card__cta{
				margin-top:auto;font-size:13px;font-weight:600;color:var(--gold,#735c00);text-decoration:none;text-transform:uppercase;letter-spacing:.06em;
			}
			@media (max-width:900px){.lwp-journal-grid--fallback{grid-template-columns:repeat(2,1fr);gap:18px}}
			@media (max-width:600px){.lwp-journal-grid--fallback{grid-template-columns:1fr;gap:16px}}
		</style>
		<?php
		wp_reset_postdata();
		$loop_html = ob_get_clean();
		return $content . $loop_html;
	}
	add_filter( 'the_content', 'luwipress_amber_inject_blog_loop_into_empty_page', 99 );
}
