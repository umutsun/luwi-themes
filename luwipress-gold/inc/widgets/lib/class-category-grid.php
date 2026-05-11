<?php
/**
 * Widget: Category Grid.
 *
 * The "Pick the voice you're looking for" pattern — N gradient tiles with
 * an eyebrow count, h3, sub line and an arrow icon. Each tile links to a
 * category archive.
 *
 * Can either: (a) pull live from WooCommerce top-level product_cat terms,
 * or (b) accept a manual repeater for non-Woo or curated displays.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Gold_Widget_Category_Grid extends Widget_Base {

	public function get_name()        { return 'lwp-category-grid'; }
	public function get_title()       { return __( 'Category Grid', 'luwipress-gold' ); }
	public function get_icon()        { return 'eicon-product-categories'; }
	public function get_categories()  { return [ 'luwipress-gold' ]; }
	public function get_keywords()    { return [ 'category', 'grid', 'product', 'taxonomy', 'browse' ]; }
	public function get_style_depends() { return [ 'luwipress-gold-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_source', [ 'label' => __( 'Source', 'luwipress-gold' ) ] );

		$this->add_control(
			'source',
			[
				'label'   => __( 'Source', 'luwipress-gold' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'manual',
				'options' => [
					'manual'      => __( 'Manual list', 'luwipress-gold' ),
					'product_cat' => __( 'WooCommerce top-level categories', 'luwipress-gold' ),
				],
			]
		);
		$this->add_control(
			'count',
			[
				'label'       => __( 'Number of tiles (when pulling from WC)', 'luwipress-gold' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 4,
				'min'         => 2,
				'max'         => 12,
				'condition'   => [ 'source' => 'product_cat' ],
			]
		);
		$this->add_control(
			'count_label',
			[
				'label'       => __( 'Eyebrow label after the count', 'luwipress-gold' ),
				'description' => __( 'e.g. "instruments" → "62 instruments". Leave empty to hide the count line.', 'luwipress-gold' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'instruments', 'luwipress-gold' ),
				'condition'   => [ 'source' => 'product_cat' ],
			]
		);

		$this->end_controls_section();

		/* Manual tile repeater */
		$this->start_controls_section( 'section_items', [
			'label'     => __( 'Tiles', 'luwipress-gold' ),
			'condition' => [ 'source' => 'manual' ],
		] );

		$rep = new Repeater();
		$rep->add_control( 'eyebrow',  [ 'label' => __( 'Eyebrow / count line', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$rep->add_control( 'title',    [ 'label' => __( 'Title', 'luwipress-gold' ),      'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$rep->add_control( 'sub',      [ 'label' => __( 'Sub line', 'luwipress-gold' ),   'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$rep->add_control( 'url',      [ 'label' => __( 'URL', 'luwipress-gold' ),        'type' => Controls_Manager::URL,  'default' => [ 'url' => '' ] ] );
		$rep->add_control( 'image',    [ 'label' => __( 'Background image (optional)', 'luwipress-gold' ), 'type' => Controls_Manager::MEDIA, 'default' => [ 'url' => '' ] ] );
		$rep->add_control( 'grad_from',[ 'label' => __( 'Gradient start (fallback)', 'luwipress-gold' ), 'type' => Controls_Manager::COLOR, 'default' => '#3d2f1f' ] );
		$rep->add_control( 'grad_to',  [ 'label' => __( 'Gradient end (fallback)', 'luwipress-gold' ),   'type' => Controls_Manager::COLOR, 'default' => '#7a5a2c' ] );

		$this->add_control(
			'items',
			[
				'label'       => __( 'Tiles', 'luwipress-gold' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $rep->get_controls(),
				'title_field' => '{{{ title || "Tile" }}}',
				'default'     => [
					[ 'eyebrow' => '', 'title' => __( 'Category 1', 'luwipress-gold' ), 'sub' => '', 'grad_from' => '#d4b97a', 'grad_to' => '#7a5a2c' ],
					[ 'eyebrow' => '', 'title' => __( 'Category 2', 'luwipress-gold' ), 'sub' => '', 'grad_from' => '#3d2f1f', 'grad_to' => '#7a5a2c' ],
					[ 'eyebrow' => '', 'title' => __( 'Category 3', 'luwipress-gold' ), 'sub' => '', 'grad_from' => '#5a3a2a', 'grad_to' => '#c89a5a' ],
					[ 'eyebrow' => '', 'title' => __( 'Category 4', 'luwipress-gold' ), 'sub' => '', 'grad_from' => '#2a3d3a', 'grad_to' => '#7aa494' ],
				],
			]
		);

		$this->add_control(
			'columns',
			[
				'label'   => __( 'Columns', 'luwipress-gold' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '4',
				'options' => [ '2' => '2', '3' => '3', '4' => '4' ],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Build the list of tiles when source = product_cat.
	 *
	 * @return array  Each item: eyebrow, title, sub, url, image, grad_from, grad_to.
	 */
	protected function build_from_wc( $count, $count_label ) {
		if ( ! function_exists( 'get_terms' ) || ! taxonomy_exists( 'product_cat' ) ) {
			return [];
		}
		$terms = get_terms( [
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => true,
			'number'     => max( 1, (int) $count ),
			'orderby'    => 'count',
			'order'      => 'DESC',
		] );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return [];
		}
		$palette = [
			[ '#d4b97a', '#7a5a2c' ],
			[ '#3d2f1f', '#7a5a2c' ],
			[ '#5a3a2a', '#c89a5a' ],
			[ '#2a3d3a', '#7aa494' ],
			[ '#7a5a3a', '#b89a6a' ],
			[ '#3a342c', '#9a7b3a' ],
		];
		$out = [];
		$i = 0;
		foreach ( $terms as $t ) {
			$thumb = function_exists( 'get_term_meta' ) ? get_term_meta( $t->term_id, 'thumbnail_id', true ) : '';
			$img   = $thumb ? wp_get_attachment_image_url( (int) $thumb, 'large' ) : '';
			$pal   = $palette[ $i % count( $palette ) ];
			$out[] = [
				'eyebrow'   => $count_label ? sprintf( '%d %s', (int) $t->count, $count_label ) : '',
				'title'     => $t->name,
				'sub'       => wp_strip_all_tags( (string) $t->description ),
				'url'       => get_term_link( $t ),
				'image'     => [ 'url' => $img ?: '' ],
				'grad_from' => $pal[0],
				'grad_to'   => $pal[1],
			];
			$i++;
		}
		return $out;
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		$source  = ( $s['source'] ?? 'manual' ) === 'product_cat' ? 'product_cat' : 'manual';
		$columns = in_array( $s['columns'] ?? '4', [ '2', '3', '4' ], true ) ? $s['columns'] : '4';

		$items = $source === 'product_cat'
			? $this->build_from_wc( $s['count'] ?? 4, $s['count_label'] ?? '' )
			: ( is_array( $s['items'] ?? null ) ? $s['items'] : [] );

		if ( empty( $items ) ) {
			return;
		}
		?>
		<div class="lwp-cgrid" data-columns="<?php echo esc_attr( $columns ); ?>">
			<?php foreach ( $items as $it ) :
				$title   = trim( (string) ( $it['title'] ?? '' ) );
				if ( $title === '' ) { continue; }
				$eyebrow = trim( (string) ( $it['eyebrow'] ?? '' ) );
				$sub     = trim( (string) ( $it['sub'] ?? '' ) );
				$url     = $it['url']['url'] ?? $it['url'] ?? '';
				$ext     = ! empty( $it['url']['is_external'] );
				$image   = $it['image']['url'] ?? '';
				$gf      = $it['grad_from'] ?? '#3d2f1f';
				$gt      = $it['grad_to']   ?? '#7a5a2c';
				$bg      = $image
					? 'background-image: linear-gradient(rgba(0,0,0,0.15), rgba(0,0,0,0.45)), url(' . esc_url( $image ) . '); background-size: cover; background-position: center;'
					: 'background: linear-gradient(135deg, ' . esc_attr( $gf ) . ', ' . esc_attr( $gt ) . ');';
				?>
				<a class="lwp-cgrid__tile" href="<?php echo esc_url( $url ); ?>" style="<?php echo $bg; ?>"
					<?php echo $ext ? ' target="_blank" rel="noopener"' : ''; ?>>
					<?php if ( $eyebrow ) : ?>
						<span class="lwp-cgrid__eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
					<?php endif; ?>
					<h3 class="lwp-cgrid__title"><?php echo esc_html( $title ); ?></h3>
					<?php if ( $sub ) : ?>
						<span class="lwp-cgrid__sub"><?php echo esc_html( $sub ); ?></span>
					<?php endif; ?>
					<span class="lwp-cgrid__arrow" aria-hidden="true">→</span>
				</a>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
