<?php
/**
 * Featured Products registry.
 *
 * A central "is this product currently featured?" flag that any widget
 * (Hero, Mega Menu, Featured Strip, etc.) can read. Operators flip the
 * flag either from the WP product edit screen meta box, or — faster —
 * from the admin bar while browsing the product on the storefront.
 *
 * Storage:
 *   • Per-product meta: `_luwipress_gold_featured`
 *       '1'    → featured
 *       ''     → not featured (or meta absent)
 *   • Per-product meta: `_luwipress_gold_featured_at`
 *       Unix timestamp of the latest flip-to-featured. Used so widgets
 *       can show "Most recently featured" or "Just featured" indicators.
 *   • Per-product meta: `_luwipress_gold_featured_note` (optional)
 *       Short operator note ("Boxing Week pick", "New arrival") shown
 *       inside widgets as a small chip above the product card.
 *
 * Public helpers:
 *   lwp_gold_is_featured( $product_id ): bool
 *   lwp_gold_get_featured_ids( $args ): int[]
 *   lwp_gold_get_featured_products( $args ): WP_Post[]
 *   lwp_gold_set_featured( $product_id, $on, $note = '' ): bool
 *
 * REST surface (for the admin bar AJAX toggle):
 *   POST /wp-json/luwipress-gold/v1/featured/toggle
 *     body: { post_id: int, on: bool, note?: string }
 *     auth: edit_post capability on the target
 *
 * Hooks/actions exposed:
 *   do_action('luwipress_gold_featured_changed', $post_id, $on, $note)
 *     Fired after a successful flip so consumer widgets/companion
 *     plugins can bust their caches.
 *
 * @package luwipress-gold
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

const LWP_GOLD_FEATURED_META = '_luwipress_gold_featured';
const LWP_GOLD_FEATURED_AT_META = '_luwipress_gold_featured_at';
const LWP_GOLD_FEATURED_NOTE_META = '_luwipress_gold_featured_note';

/* ──────────────────────────────────────────────────────────────────────
 * Public helpers
 * ──────────────────────────────────────────────────────────────────── */

function lwp_gold_is_featured( $post_id ) {
	return get_post_meta( (int) $post_id, LWP_GOLD_FEATURED_META, true ) === '1';
}

/**
 * Get IDs of currently-featured products.
 *
 * @param array $args  number, orderby, order, post_type. Defaults to product + 12 + meta_value_num featured_at DESC.
 * @return int[]
 */
function lwp_gold_get_featured_ids( $args = array() ) {
	$args = wp_parse_args( $args, array(
		'number'    => 12,
		'orderby'   => 'featured_at',
		'order'     => 'DESC',
		'post_type' => 'product',
	) );

	$post_type = $args['post_type'] ?: 'product';
	$number    = max( 1, (int) $args['number'] );

	$meta_query = array(
		array( 'key' => LWP_GOLD_FEATURED_META, 'value' => '1', 'compare' => '=' ),
	);

	$orderby = 'date';
	$meta_key = '';
	if ( $args['orderby'] === 'featured_at' ) {
		$orderby  = 'meta_value_num';
		$meta_key = LWP_GOLD_FEATURED_AT_META;
	} elseif ( in_array( $args['orderby'], array( 'date', 'modified', 'rand', 'title' ), true ) ) {
		$orderby = $args['orderby'];
	}

	$q_args = array(
		'post_type'      => $post_type,
		'post_status'    => 'publish',
		'posts_per_page' => $number,
		'fields'         => 'ids',
		'orderby'        => $orderby,
		'order'          => in_array( strtoupper( $args['order'] ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $args['order'] ) : 'DESC',
		'meta_query'     => $meta_query,
		'no_found_rows'  => true,
	);
	if ( $meta_key ) {
		$q_args['meta_key'] = $meta_key;
	}

	$q = new WP_Query( $q_args );
	return array_map( 'intval', $q->posts );
}

/**
 * Same as get_featured_ids but returns WP_Post objects.
 *
 * @param array $args
 * @return WP_Post[]
 */
function lwp_gold_get_featured_products( $args = array() ) {
	$ids = lwp_gold_get_featured_ids( $args );
	if ( empty( $ids ) ) { return array(); }
	return array_filter( array_map( 'get_post', $ids ) );
}

/**
 * Flip a product's featured flag. Idempotent. Returns true on success.
 *
 * @param int    $post_id
 * @param bool   $on
 * @param string $note  Optional operator note shown in consumer widgets.
 * @return bool
 */
function lwp_gold_set_featured( $post_id, $on, $note = '' ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) { return false; }
	$post = get_post( $post_id );
	if ( ! $post ) { return false; }

	if ( $on ) {
		update_post_meta( $post_id, LWP_GOLD_FEATURED_META, '1' );
		update_post_meta( $post_id, LWP_GOLD_FEATURED_AT_META, time() );
		if ( $note !== '' ) {
			update_post_meta( $post_id, LWP_GOLD_FEATURED_NOTE_META, sanitize_text_field( $note ) );
		}
	} else {
		delete_post_meta( $post_id, LWP_GOLD_FEATURED_META );
		// Leave AT + note metas in place so re-featuring keeps the note.
	}

	do_action( 'luwipress_gold_featured_changed', $post_id, (bool) $on, $note );
	return true;
}

/* ──────────────────────────────────────────────────────────────────────
 * Admin: meta box on product edit screen
 * ──────────────────────────────────────────────────────────────────── */

add_action( 'add_meta_boxes', function ( $post_type ) {
	$types = (array) apply_filters( 'luwipress_gold_featured_post_types', array( 'product' ) );
	if ( ! in_array( $post_type, $types, true ) ) { return; }
	add_meta_box(
		'luwipress_gold_featured',
		__( 'LuwiPress · Feature this product', 'luwipress-gold' ),
		'lwp_gold_render_featured_meta_box',
		$post_type,
		'side',
		'high'
	);
} );

function lwp_gold_render_featured_meta_box( $post ) {
	wp_nonce_field( 'lwp_gold_featured_meta', 'lwp_gold_featured_nonce' );
	$on   = lwp_gold_is_featured( $post->ID );
	$note = (string) get_post_meta( $post->ID, LWP_GOLD_FEATURED_NOTE_META, true );
	$at   = (int) get_post_meta( $post->ID, LWP_GOLD_FEATURED_AT_META, true );
	?>
	<div class="lwp-gold-featured-box">
		<label style="display:flex;gap:8px;align-items:flex-start;cursor:pointer;">
			<input type="checkbox" name="lwp_gold_featured" value="1" <?php checked( $on ); ?> />
			<span>
				<strong><?php esc_html_e( 'Feature across the site', 'luwipress-gold' ); ?></strong><br/>
				<span style="color:#666;font-size:12px;">
					<?php esc_html_e( 'Surfaces this product inside the Mega Menu featured panel, the homepage Featured strip, and any other widget that reads the featured registry.', 'luwipress-gold' ); ?>
				</span>
			</span>
		</label>

		<p style="margin-top:14px;">
			<label for="lwp_gold_featured_note" style="display:block;font-weight:600;margin-bottom:4px;">
				<?php esc_html_e( 'Operator note (optional)', 'luwipress-gold' ); ?>
			</label>
			<input type="text" id="lwp_gold_featured_note" name="lwp_gold_featured_note"
				value="<?php echo esc_attr( $note ); ?>" class="widefat"
				placeholder="<?php esc_attr_e( "e.g. Boxing Week pick · New arrival · Just dropped", 'luwipress-gold' ); ?>" />
		</p>

		<?php if ( $at ) : ?>
			<p style="margin-top:10px;color:#666;font-size:12px;">
				<?php
				printf(
					/* translators: %s: human-readable timestamp */
					esc_html__( 'Last featured: %s ago', 'luwipress-gold' ),
					esc_html( human_time_diff( $at ) )
				);
				?>
			</p>
		<?php endif; ?>
	</div>
	<?php
}

add_action( 'save_post', function ( $post_id, $post ) {
	if ( ! isset( $_POST['lwp_gold_featured_nonce'] ) ) { return; }
	if ( ! wp_verify_nonce( wp_unslash( $_POST['lwp_gold_featured_nonce'] ), 'lwp_gold_featured_meta' ) ) { return; }
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
	if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }

	$on   = ! empty( $_POST['lwp_gold_featured'] );
	$note = isset( $_POST['lwp_gold_featured_note'] ) ? sanitize_text_field( wp_unslash( $_POST['lwp_gold_featured_note'] ) ) : '';
	lwp_gold_set_featured( $post_id, $on, $note );
}, 10, 2 );

/* ──────────────────────────────────────────────────────────────────────
 * Admin bar — frontend single-product toggle
 * ──────────────────────────────────────────────────────────────────── */

add_action( 'admin_bar_menu', function ( $wp_admin_bar ) {
	if ( is_admin() ) { return; }
	if ( ! is_singular() ) { return; }
	$post = get_queried_object();
	if ( ! $post || ! current_user_can( 'edit_post', $post->ID ) ) { return; }
	$types = (array) apply_filters( 'luwipress_gold_featured_post_types', array( 'product' ) );
	if ( ! in_array( $post->post_type, $types, true ) ) { return; }

	$on = lwp_gold_is_featured( $post->ID );
	$wp_admin_bar->add_node( array(
		'id'    => 'lwp-gold-featured-toggle',
		'title' => '<span class="ab-icon" style="font-family:dashicons;">' . ( $on ? '★' : '☆' ) . '</span> ' . ( $on ? esc_html__( 'Unfeature this', 'luwipress-gold' ) : esc_html__( 'Feature this product', 'luwipress-gold' ) ),
		'href'  => '#',
		'meta'  => array(
			'class' => 'lwp-gold-featured-toggle' . ( $on ? ' is-on' : '' ),
			'html'  => '',
		),
	) );
}, 200 );

add_action( 'wp_enqueue_scripts', function () {
	if ( is_admin() || ! is_singular() || ! is_admin_bar_showing() ) { return; }
	$post = get_queried_object();
	if ( ! $post || ! current_user_can( 'edit_post', $post->ID ) ) { return; }

	$rest = esc_url_raw( rest_url( 'luwipress-gold/v1/featured/toggle' ) );
	$nonce = wp_create_nonce( 'wp_rest' );

	$inline = sprintf(
		'(function(){var btn=document.querySelector("#wp-admin-bar-lwp-gold-featured-toggle > a");if(!btn)return;btn.addEventListener("click",function(e){e.preventDefault();var node=document.getElementById("wp-admin-bar-lwp-gold-featured-toggle");var on=node && node.classList.contains("is-on");fetch(%1$s,{method:"POST",credentials:"same-origin",headers:{"Content-Type":"application/json","X-WP-Nonce":%2$s},body:JSON.stringify({post_id:%3$d,on:!on})}).then(function(r){return r.json();}).then(function(j){if(!j||!j.success){alert(j&&j.message?j.message:"Toggle failed");return;}node.classList.toggle("is-on",j.on);var t=btn.lastChild;if(t){t.nodeValue=" "+(j.on?%4$s:%5$s);}var ico=btn.querySelector(".ab-icon");if(ico){ico.textContent=j.on?"★":"☆";}}).catch(function(){alert("Toggle failed");});});})();',
		wp_json_encode( $rest ),
		wp_json_encode( $nonce ),
		(int) $post->ID,
		wp_json_encode( __( 'Unfeature this', 'luwipress-gold' ) ),
		wp_json_encode( __( 'Feature this product', 'luwipress-gold' ) )
	);
	wp_add_inline_script( 'admin-bar', $inline );

	$css = '#wp-admin-bar-lwp-gold-featured-toggle.is-on > a { background:#9A7B3A !important; color:#fff !important; }';
	wp_add_inline_style( 'admin-bar', $css );
}, 30 );

/* ──────────────────────────────────────────────────────────────────────
 * REST endpoint: POST /luwipress-gold/v1/featured/toggle
 * ──────────────────────────────────────────────────────────────────── */

add_action( 'rest_api_init', function () {
	register_rest_route( 'luwipress-gold/v1', '/featured/toggle', array(
		'methods'             => 'POST',
		'permission_callback' => function ( $request ) {
			$post_id = (int) $request->get_param( 'post_id' );
			return $post_id > 0 && current_user_can( 'edit_post', $post_id );
		},
		'args'                => array(
			'post_id' => array( 'type' => 'integer', 'required' => true ),
			'on'      => array( 'type' => 'boolean', 'required' => true ),
			'note'    => array( 'type' => 'string', 'required' => false, 'default' => '' ),
		),
		'callback'            => function ( $request ) {
			$post_id = (int) $request->get_param( 'post_id' );
			$on      = (bool) $request->get_param( 'on' );
			$note    = (string) $request->get_param( 'note' );
			$ok      = lwp_gold_set_featured( $post_id, $on, $note );
			return rest_ensure_response( array(
				'success' => $ok,
				'on'      => $on,
				'post_id' => $post_id,
				'note'    => $note,
			) );
		},
	) );
} );
