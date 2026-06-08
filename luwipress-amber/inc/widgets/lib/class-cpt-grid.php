<?php
/**
 * Widget: CPT Grid — generic, engine-driven profile / card grid.
 *
 * Renders ANY LuwiPress CPT Engine post type (Vendors, Team, Events, or any
 * operator-defined type) as a responsive card grid. Where `lwp-master-grid` is
 * hard-wired to the Vendor CPT and its `_lwp_vendor_*` meta, this widget reads
 * the post-type list from `LuwiPress_CPT_Engine::get_post_types()` and lets the
 * operator map up to two meta fields onto each card's two sub-lines.
 *
 * Reuses the `.lwp-mgrid` CSS (no new styles) so it inherits the same dark/light
 * card chrome as the Master Grid. Falls back to listing every public non-builtin
 * post type when the CPT Engine (LuwiPress core 3.8.0+) is unavailable, so the
 * widget degrades gracefully on sites without the engine.
 *
 * Part of CPT Engine Phase 2 (Elementor surfacing). See CLAUDE.md.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Amber_Widget_CPT_Grid extends Widget_Base {

	public function get_name()          { return 'lwp-cpt-grid'; }
	public function get_title()         { return __( 'CPT Grid', 'luwipress-amber' ); }
	public function get_icon()          { return 'eicon-posts-grid'; }
	public function get_categories()    { return [ 'luwipress-amber' ]; }
	public function get_keywords()      { return [ 'cpt', 'engine', 'vendors', 'team', 'events', 'people', 'grid', 'profiles' ]; }
	public function get_style_depends() { return [ 'luwipress-amber-widgets' ]; }

	/**
	 * Post types the operator may pick — engine-managed types first, falling back
	 * to every public non-builtin post type, then a last-ditch vendor entry.
	 *
	 * @return array<string,string> post_type => label
	 */
	private function post_type_options() {
		$opts = [];
		if ( class_exists( 'LuwiPress_CPT_Engine' ) ) {
			foreach ( LuwiPress_CPT_Engine::get_instance()->get_post_types() as $pt ) {
				$obj         = get_post_type_object( $pt );
				$opts[ $pt ] = $obj ? $obj->labels->singular_name : $pt;
			}
		}
		if ( empty( $opts ) ) {
			foreach ( get_post_types( [ 'public' => true, '_builtin' => false ], 'objects' ) as $obj ) {
				$opts[ $obj->name ] = $obj->labels->singular_name;
			}
		}
		if ( empty( $opts ) ) {
			$opts['lwp_vendor'] = __( 'Vendor', 'luwipress-amber' );
		}
		return $opts;
	}

	protected function register_controls() {

		$pt_options = $this->post_type_options();
		$default_pt = '';
		foreach ( $pt_options as $k => $v ) { $default_pt = (string) $k; break; }

		// Taxonomy filter options — engine-declared taxonomies, tagged with owner.
		$tax_options = [ '' => __( '— No taxonomy filter —', 'luwipress-amber' ) ];
		if ( class_exists( 'LuwiPress_CPT_Engine' ) ) {
			foreach ( LuwiPress_CPT_Engine::get_instance()->get_taxonomies() as $t ) {
				if ( empty( $t['slug'] ) ) { continue; }
				$tax_options[ $t['slug'] ] = ( $t['label'] ?? $t['slug'] ) . ' — ' . ( $t['post_type'] ?? '' );
			}
		}

		// Static field-key hint so operators know which meta keys to map onto the
		// card sub-lines for each engine type (controls can't cascade per-CPT live).
		$field_hint = '';
		if ( class_exists( 'LuwiPress_CPT_Engine' ) ) {
			foreach ( LuwiPress_CPT_Engine::get_instance()->get_types() as $def ) {
				if ( empty( $def['enabled'] ) || empty( $def['field_schema'] ) ) { continue; }
				$keys = [];
				foreach ( $def['field_schema'] as $f ) {
					if ( ! empty( $f['in_elementor'] ) && ! empty( $f['key'] ) ) { $keys[] = $f['key']; }
				}
				if ( $keys ) {
					$field_hint .= '<strong>' . esc_html( (string) ( $def['post_type'] ?? $def['key'] ) ) . ':</strong> '
						. esc_html( implode( ', ', $keys ) ) . '<br>';
				}
			}
		}

		/* ── Source ── */
		$this->start_controls_section( 'section_query', [ 'label' => __( 'Source', 'luwipress-amber' ) ] );

		$this->add_control(
			'info',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => '<em style="color:#8b7f6a;">' . esc_html__( 'Pulls live from a LuwiPress CPT Engine type. Add or edit entries under LuwiPress → the matching CPT; this grid updates automatically.', 'luwipress-amber' ) . '</em>',
			]
		);

		$this->add_control(
			'post_type',
			[
				'label'   => __( 'Content type', 'luwipress-amber' ),
				'type'    => Controls_Manager::SELECT,
				'default' => $default_pt,
				'options' => $pt_options,
			]
		);

		$this->add_control(
			'limit',
			[
				'label'   => __( 'Max entries', 'luwipress-amber' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 4,
				'min'     => 1,
				'max'     => 48,
			]
		);

		$this->add_control(
			'filter_taxonomy',
			[
				'label'     => __( 'Filter by taxonomy', 'luwipress-amber' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => $tax_options,
				'condition' => [ 'include_ids' => '' ],
			]
		);

		$this->add_control(
			'filter_term',
			[
				'label'       => __( 'Term slug', 'luwipress-amber' ),
				'description' => __( 'Slug of the term in the chosen taxonomy (e.g. luthiers). Leave empty to show all.', 'luwipress-amber' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'condition'   => [ 'include_ids' => '', 'filter_taxonomy!' => '' ],
			]
		);

		$this->add_control(
			'include_ids',
			[
				'label'       => __( 'Curated IDs (optional)', 'luwipress-amber' ),
				'description' => __( 'Comma-separated post IDs to show in this order. Leave empty to auto-query.', 'luwipress-amber' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'e.g. 1234, 1235, 1236',
				'default'     => '',
			]
		);

		$this->add_control(
			'orderby',
			[
				'label'     => __( 'Order by', 'luwipress-amber' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'menu_order',
				'options'   => [
					'menu_order' => __( 'Manual (menu order)', 'luwipress-amber' ),
					'date'       => __( 'Date published', 'luwipress-amber' ),
					'title'      => __( 'Title (A→Z)', 'luwipress-amber' ),
					'rand'       => __( 'Random', 'luwipress-amber' ),
				],
				'condition' => [ 'include_ids' => '' ],
			]
		);

		$this->add_control(
			'order',
			[
				'label'     => __( 'Order direction', 'luwipress-amber' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'ASC',
				'options'   => [ 'ASC' => 'ASC', 'DESC' => 'DESC' ],
				'condition' => [ 'include_ids' => '', 'orderby!' => 'rand' ],
			]
		);

		$this->end_controls_section();

		/* ── Display ── */
		$this->start_controls_section( 'section_display', [ 'label' => __( 'Display', 'luwipress-amber' ) ] );

		$this->add_control(
			'columns',
			[
				'label'   => __( 'Columns', 'luwipress-amber' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '4',
				'options' => [ '2' => '2', '3' => '3', '4' => '4' ],
			]
		);

		$this->add_control(
			'variant',
			[
				'label'   => __( 'Variant', 'luwipress-amber' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'dark',
				'options' => [
					'dark'  => __( 'Dark (for #1A1612 section bg)', 'luwipress-amber' ),
					'light' => __( 'Light (for cream section bg)', 'luwipress-amber' ),
				],
			]
		);

		if ( '' !== $field_hint ) {
			$this->add_control(
				'field_hint',
				[
					'type' => Controls_Manager::RAW_HTML,
					'raw'  => '<small style="color:#8b7f6a;line-height:1.6;">'
						. esc_html__( 'Available field keys per type:', 'luwipress-amber' )
						. '<br>' . $field_hint . '</small>',
				]
			);
		}

		$this->add_control(
			'meta_line_1',
			[
				'label'       => __( 'Sub-line 1 — meta key', 'luwipress-amber' ),
				'description' => __( 'Meta key shown under the title (e.g. _lwp_vendor_location). Leave empty to hide.', 'luwipress-amber' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
			]
		);

		$this->add_control(
			'meta_line_2',
			[
				'label'       => __( 'Sub-line 2 — meta key', 'luwipress-amber' ),
				'description' => __( 'Second meta key (e.g. _lwp_vendor_specialty). Leave empty to hide.', 'luwipress-amber' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
			]
		);

		$this->add_control(
			'view_all_link',
			[
				'label'        => __( 'Show "View all" link', 'luwipress-amber' ),
				'description'  => __( 'Adds a CTA below the grid linking to the type archive. Useful when the grid is capped.', 'luwipress-amber' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'view_all_label',
			[
				'label'       => __( '"View all" label', 'luwipress-amber' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'View all →', 'luwipress-amber' ),
				'placeholder' => __( 'View all →', 'luwipress-amber' ),
				'condition'   => [ 'view_all_link' => 'yes' ],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$s       = $this->get_settings_for_display();
		$pt      = sanitize_key( (string) ( $s['post_type'] ?? '' ) );
		$columns = in_array( $s['columns'] ?? '4', [ '2', '3', '4' ], true ) ? $s['columns'] : '4';
		$variant = ( $s['variant'] ?? 'dark' ) === 'light' ? 'light' : 'dark';
		$limit   = max( 1, min( 48, (int) ( $s['limit'] ?? 4 ) ) );

		if ( '' === $pt || ! post_type_exists( $pt ) ) {
			echo '<div class="lwp-mgrid lwp-mgrid--placeholder"><em>'
				. esc_html__( '[CPT Grid] Pick a content type. None is registered — add one in LuwiPress → CPT types.', 'luwipress-amber' )
				. '</em></div>';
			return;
		}

		$args = [
			'post_type'      => $pt,
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
		];

		$include = trim( (string) ( $s['include_ids'] ?? '' ) );
		if ( '' !== $include ) {
			$ids = array_values( array_filter( array_map( 'absint', preg_split( '/[\s,]+/', $include ) ) ) );
			if ( ! empty( $ids ) ) {
				$args['post__in']       = $ids;
				$args['orderby']        = 'post__in';
				$args['posts_per_page'] = count( $ids );
			}
		} else {
			$args['orderby'] = sanitize_key( $s['orderby'] ?? 'menu_order' );
			$args['order']   = strtoupper( $s['order'] ?? 'ASC' ) === 'DESC' ? 'DESC' : 'ASC';

			$tax  = sanitize_key( (string) ( $s['filter_taxonomy'] ?? '' ) );
			$term = trim( (string) ( $s['filter_term'] ?? '' ) );
			if ( '' !== $tax && '' !== $term && taxonomy_exists( $tax ) ) {
				$args['tax_query'] = [
					[
						'taxonomy' => $tax,
						'field'    => 'slug',
						'terms'    => $term,
					],
				];
			}
		}

		$q = new WP_Query( $args );
		if ( ! $q->have_posts() ) {
			$obj   = get_post_type_object( $pt );
			$label = $obj ? $obj->labels->name : $pt;
			echo '<div class="lwp-mgrid lwp-mgrid--empty"><em>'
				/* translators: %s: post type label */
				. esc_html( sprintf( __( '[CPT Grid] No %s published yet.', 'luwipress-amber' ), $label ) )
				. '</em></div>';
			wp_reset_postdata();
			return;
		}

		$meta_1 = trim( (string) ( $s['meta_line_1'] ?? '' ) );
		$meta_2 = trim( (string) ( $s['meta_line_2'] ?? '' ) );
		?>
		<div class="lwp-mgrid lwp-mgrid--<?php echo esc_attr( $variant ); ?>" data-columns="<?php echo esc_attr( $columns ); ?>">
			<?php while ( $q->have_posts() ) : $q->the_post();
				$pid     = get_the_ID();
				$name    = get_the_title( $pid );
				$initial = mb_substr( $name, 0, 1 );
				$avatar  = get_the_post_thumbnail_url( $pid, 'medium' );
				$line1   = ( '' !== $meta_1 ) ? (string) get_post_meta( $pid, $meta_1, true ) : '';
				$line2   = ( '' !== $meta_2 ) ? (string) get_post_meta( $pid, $meta_2, true ) : '';
				$url     = get_permalink( $pid );
				?>
				<a href="<?php echo esc_url( $url ); ?>" class="lwp-mgrid__card">
					<div class="lwp-mgrid__avatar"
						<?php if ( ! $avatar ) : ?>
							style="background: linear-gradient(135deg, #9A7B3A, #D4AF37);"
						<?php endif; ?>>
						<?php if ( $avatar ) : ?>
							<img loading="lazy" decoding="async" src="<?php echo esc_url( $avatar ); ?>" alt="<?php echo esc_attr( $name ); ?>" width="120" height="120" />
						<?php else : ?>
							<span aria-hidden="true"><?php echo esc_html( $initial ); ?></span>
						<?php endif; ?>
					</div>
					<div class="lwp-mgrid__meta">
						<h4 class="lwp-mgrid__name"><?php echo esc_html( $name ); ?></h4>
						<?php if ( '' !== $line1 ) : ?>
							<span class="lwp-mgrid__loc"><?php echo esc_html( $line1 ); ?></span>
						<?php endif; ?>
						<?php if ( '' !== $line2 ) : ?>
							<span class="lwp-mgrid__spec"><?php echo esc_html( $line2 ); ?></span>
						<?php endif; ?>
					</div>
				</a>
			<?php endwhile; ?>
		</div>
		<?php
		wp_reset_postdata();

		if ( ( $s['view_all_link'] ?? '' ) === 'yes' ) {
			$archive_url = get_post_type_archive_link( $pt );
			if ( $archive_url ) {
				$label = trim( (string) ( $s['view_all_label'] ?? '' ) );
				if ( '' === $label ) {
					$label = __( 'View all →', 'luwipress-amber' );
				}
				?>
				<div class="lwp-mgrid__viewall">
					<a href="<?php echo esc_url( $archive_url ); ?>" class="lwp-mgrid__viewall-link"><?php echo esc_html( $label ); ?></a>
				</div>
				<?php
			}
		}
	}
}
