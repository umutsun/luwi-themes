<?php
/**
 * Widget: Master Grid (CPT auto-query mode).
 *
 * 4-up grid that auto-pulls Vendor CPT (lwp_vendor) — generic vendor /
 * atelier / luthier / chef / artist profiles. Each card shows portrait
 * (featured image), name, location, specialty, and links to the single
 * profile at /<archive_slug>/<post_name>/.
 *
 * Pre-1.10.0 the widget held its own repeater of items. From 1.10.0 the
 * source of truth is the CPT; widget settings only control DISPLAY:
 * column count, variant (dark/light), label override, limit, ordering.
 *
 * Operators who want manual curation use the optional `include_ids` field
 * (comma-separated CPT IDs) — falls back to auto-query when empty.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Onyx_Widget_Master_Grid extends Widget_Base {

	public function get_name()        { return 'lwp-master-grid'; }
	public function get_title()       { return __( 'Master Grid', 'luwipress-onyx' ); }
	public function get_icon()        { return 'eicon-person'; }
	public function get_categories()  { return [ 'luwipress-onyx' ]; }
	public function get_keywords()    { return [ 'masters', 'luthiers', 'vendors', 'ateliers', 'team', 'people', 'grid' ]; }
	public function get_style_depends() { return [ 'luwipress-onyx-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_query', [ 'label' => __( 'Source', 'luwipress-onyx' ) ] );

		$this->add_control(
			'info',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => '<em style="color:#8b7f6a;">' . esc_html__( 'Pulls live from the Vendors CPT (LuwiPress → Vendors). Add or edit profiles there; this grid updates automatically.', 'luwipress-onyx' ) . '</em>',
			]
		);

		$this->add_control(
			'limit',
			[
				'label'   => __( 'Max profiles', 'luwipress-onyx' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 4,
				'min'     => 1,
				'max'     => 24,
			]
		);

		// Filter by vendor group taxonomy (luwipress core 3.5.7+).
		// One site can run multiple personnel verticals side-by-side
		// (Luthiers + Team), so this grid scopes to whichever subset
		// the operator picks. Empty value = every vendor regardless
		// of group, matching the pre-3.5.7 behaviour.
		$group_options = [ '' => __( '— All vendors —', 'luwipress-onyx' ) ];
		if ( taxonomy_exists( 'lwp_vendor_group' ) ) {
			$terms = get_terms( [
				'taxonomy'   => 'lwp_vendor_group',
				'hide_empty' => false,
			] );
			if ( is_array( $terms ) ) {
				foreach ( $terms as $t ) {
					if ( $t instanceof \WP_Term ) {
						$group_options[ $t->slug ] = $t->name . ' (' . (int) $t->count . ')';
					}
				}
			}
		}
		$this->add_control(
			'vendor_group',
			[
				'label'       => __( 'Vendor group', 'luwipress-onyx' ),
				'description' => __( 'Filter to one vendor group (Luthiers, Team, etc.). Manage groups under LuwiPress → Vendors → Vendor Groups.', 'luwipress-onyx' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => $group_options,
				'condition'   => [ 'include_ids' => '' ],
			]
		);

		$this->add_control(
			'include_ids',
			[
				'label'       => __( 'Curated IDs (optional)', 'luwipress-onyx' ),
				'description' => __( 'Comma-separated CPT IDs to show in this order. Leave empty to auto-query (menu_order ASC).', 'luwipress-onyx' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'e.g. 1234, 1235, 1236',
				'default'     => '',
			]
		);

		$this->add_control(
			'orderby',
			[
				'label'   => __( 'Order by', 'luwipress-onyx' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'menu_order',
				'options' => [
					'menu_order' => __( 'Manual (menu order)', 'luwipress-onyx' ),
					'date'       => __( 'Date published', 'luwipress-onyx' ),
					'title'      => __( 'Name (A→Z)', 'luwipress-onyx' ),
					'rand'       => __( 'Random', 'luwipress-onyx' ),
				],
				'condition' => [ 'include_ids' => '' ],
			]
		);

		$this->add_control(
			'order',
			[
				'label'   => __( 'Order direction', 'luwipress-onyx' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'ASC',
				'options' => [ 'ASC' => 'ASC', 'DESC' => 'DESC' ],
				'condition' => [ 'include_ids' => '', 'orderby!' => 'rand' ],
			]
		);

		$this->end_controls_section();

		/* Display section */
		$this->start_controls_section( 'section_display', [ 'label' => __( 'Display', 'luwipress-onyx' ) ] );

		$this->add_control(
			'columns',
			[
				'label'   => __( 'Columns', 'luwipress-onyx' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '4',
				'options' => [ '2' => '2', '3' => '3', '4' => '4' ],
			]
		);

		$this->add_control(
			'variant',
			[
				'label'   => __( 'Variant', 'luwipress-onyx' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'dark',
				'options' => [
					'dark'  => __( 'Dark (for #1A1612 section bg)', 'luwipress-onyx' ),
					'light' => __( 'Light (for cream section bg)', 'luwipress-onyx' ),
				],
			]
		);

		$this->add_control(
			'show_location',
			[ 'label' => __( 'Show location', 'luwipress-onyx' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ]
		);

		$this->add_control(
			'show_specialty',
			[ 'label' => __( 'Show specialty', 'luwipress-onyx' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ]
		);

		$this->add_control(
			'show_count',
			[
				'label'       => __( 'Show product count', 'luwipress-onyx' ),
				'description' => __( 'Counts WC products attributed to this vendor via _lwp_vendor_ids meta.', 'luwipress-onyx' ),
				'type'        => Controls_Manager::SWITCHER,
				'return_value'=> 'yes',
				'default'     => '',
			]
		);

		$this->add_control(
			'view_all_link',
			[
				'label'        => __( 'Show "View all" link', 'luwipress-onyx' ),
				'description'  => __( 'Adds a CTA below the grid linking to the full Vendors archive (e.g. /luthiers/). Useful when the grid is capped at 4–8 and visitors need a path to the rest.', 'luwipress-onyx' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'view_all_label',
			[
				'label'       => __( '"View all" label', 'luwipress-onyx' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'View all vendors →', 'luwipress-onyx' ),
				'placeholder' => __( 'View all vendors →', 'luwipress-onyx' ),
				'condition'   => [ 'view_all_link' => 'yes' ],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$s       = $this->get_settings_for_display();
		$columns = in_array( $s['columns'] ?? '4', [ '2', '3', '4' ], true ) ? $s['columns'] : '4';
		$variant = ( $s['variant'] ?? 'dark' ) === 'light' ? 'light' : 'dark';
		$limit   = max( 1, min( 24, (int) ( $s['limit'] ?? 4 ) ) );

		if ( ! post_type_exists( 'lwp_vendor' ) ) {
			echo '<div class="lwp-mgrid lwp-mgrid--placeholder"><em>' . esc_html__( '[Master Grid] LuwiPress Vendors module is inactive — enable it in LuwiPress → Vendors.', 'luwipress-onyx' ) . '</em></div>';
			return;
		}

		$args = array(
			'post_type'      => 'lwp_vendor',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
		);

		$include = trim( (string) ( $s['include_ids'] ?? '' ) );
		if ( $include !== '' ) {
			$ids = array_values( array_filter( array_map( 'absint', preg_split( '/[\s,]+/', $include ) ) ) );
			if ( ! empty( $ids ) ) {
				$args['post__in']       = $ids;
				$args['orderby']        = 'post__in';
				$args['posts_per_page'] = count( $ids );
			}
		} else {
			$args['orderby'] = sanitize_key( $s['orderby'] ?? 'menu_order' );
			$args['order']   = strtoupper( $s['order'] ?? 'ASC' ) === 'DESC' ? 'DESC' : 'ASC';

			// Vendor group filter (luwipress core 3.5.7+) — applies in
			// auto-query mode only. Curated `include_ids` is its own
			// explicit pick, so we deliberately let it through unfiltered.
			$group = trim( (string) ( $s['vendor_group'] ?? '' ) );
			if ( $group !== '' && taxonomy_exists( 'lwp_vendor_group' ) ) {
				$args['tax_query'] = [
					[
						'taxonomy' => 'lwp_vendor_group',
						'field'    => 'slug',
						'terms'    => $group,
					],
				];
			}
		}

		$q = new WP_Query( $args );
		if ( ! $q->have_posts() ) {
			echo '<div class="lwp-mgrid lwp-mgrid--empty"><em>' . esc_html__( '[Master Grid] No profiles yet. Add them in LuwiPress → Vendors.', 'luwipress-onyx' ) . '</em></div>';
			return;
		}

		$show_loc   = ( $s['show_location']  ?? 'yes' ) === 'yes';
		$show_spec  = ( $s['show_specialty'] ?? 'yes' ) === 'yes';
		$show_count = ( $s['show_count']     ?? '' )    === 'yes';
		?>
		<div class="lwp-mgrid lwp-mgrid--<?php echo esc_attr( $variant ); ?>" data-columns="<?php echo esc_attr( $columns ); ?>">
			<?php while ( $q->have_posts() ) : $q->the_post();
				$pid       = get_the_ID();
				$name      = get_the_title( $pid );
				$initial   = mb_substr( $name, 0, 1 );
				$avatar    = get_the_post_thumbnail_url( $pid, 'medium' );
				$location  = $show_loc  ? (string) get_post_meta( $pid, '_lwp_vendor_location',  true ) : '';
				$specialty = $show_spec ? (string) get_post_meta( $pid, '_lwp_vendor_specialty', true ) : '';
				$years     = (int) get_post_meta( $pid, '_lwp_vendor_years_active', true );

				$count_label = '';
				if ( $show_count ) {
					// REGEXP tolerates both quoted (`["123"]`) and unquoted
					// (`[123]`) JSON formats of the _lwp_vendor_ids meta — see
					// single-lwp_vendor.php for the same idiom.
					$vendor_regex = '(\\[|,)[[:space:]]*"?' . preg_quote( (string) $pid, '/' ) . '"?[[:space:]]*(,|\\])';
					$count_q = get_posts( array(
						'post_type'      => 'product',
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'meta_query'     => array(
							array(
								'key'     => '_lwp_vendor_ids',
								'value'   => $vendor_regex,
								'compare' => 'REGEXP',
							),
						),
					) );
					$cnt = count( $count_q );
					if ( $cnt > 0 ) {
						$count_label = sprintf(
							/* translators: %d: product count */
							_n( '%d work', '%d works', $cnt, 'luwipress-onyx' ),
							$cnt
						);
					}
				}

				$url = get_permalink( $pid );
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
						<?php if ( $location ) : ?>
							<span class="lwp-mgrid__loc"><?php echo esc_html( $location ); ?></span>
						<?php endif; ?>
						<?php if ( $specialty ) : ?>
							<span class="lwp-mgrid__spec"><?php echo esc_html( $specialty ); ?><?php if ( $years > 0 ) : ?> · <?php echo (int) $years; ?>y<?php endif; ?></span>
						<?php endif; ?>
						<?php if ( $count_label ) : ?>
							<span class="lwp-mgrid__count"><?php echo esc_html( $count_label ); ?></span>
						<?php endif; ?>
					</div>
				</a>
			<?php endwhile; ?>
		</div>
		<?php
		wp_reset_postdata();

		// "View all" CTA — auto-links to the Vendors CPT archive
		// (e.g. /luthiers/) so visitors can reach the full roster
		// even when the grid is capped at 4–8 cards.
		if ( ( $s['view_all_link'] ?? 'yes' ) === 'yes' ) {
			$archive_url = get_post_type_archive_link( 'lwp_vendor' );
			if ( $archive_url ) {
				$label = trim( (string) ( $s['view_all_label'] ?? '' ) );
				if ( $label === '' ) {
					$label = __( 'View all vendors →', 'luwipress-onyx' );
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
