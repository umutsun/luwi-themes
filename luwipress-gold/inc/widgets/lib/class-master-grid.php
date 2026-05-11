<?php
/**
 * Widget: Master Grid.
 *
 * 4-up grid of master luthier cards used on the dark-band homepage
 * section. Each card has an avatar (image or single-letter initial on
 * gradient), name, location, specialty + years, and instrument count.
 *
 * Pairs with the existing `lwp-master-profile` widget which renders a
 * single full-bleed profile card; this grid widget is the compact
 * 4-across summary that links to individual profile pages.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Gold_Widget_Master_Grid extends Widget_Base {

	public function get_name()        { return 'lwp-master-grid'; }
	public function get_title()       { return __( 'Master Grid', 'luwipress-gold' ); }
	public function get_icon()        { return 'eicon-person'; }
	public function get_categories()  { return [ 'luwipress-gold' ]; }
	public function get_keywords()    { return [ 'masters', 'luthiers', 'team', 'grid', 'profiles' ]; }
	public function get_style_depends() { return [ 'luwipress-gold-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_items', [ 'label' => __( 'Masters', 'luwipress-gold' ) ] );

		$rep = new Repeater();
		$rep->add_control( 'name',     [ 'label' => __( 'Name', 'luwipress-gold' ),       'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$rep->add_control( 'initial',  [ 'label' => __( 'Avatar initial (fallback)', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$rep->add_control( 'avatar',   [ 'label' => __( 'Avatar image (optional)', 'luwipress-gold' ),  'type' => Controls_Manager::MEDIA, 'default' => [ 'url' => '' ] ] );
		$rep->add_control( 'location', [ 'label' => __( 'Location', 'luwipress-gold' ),   'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$rep->add_control( 'specialty',[ 'label' => __( 'Specialty + years', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$rep->add_control( 'count',    [ 'label' => __( 'Instrument count line', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$rep->add_control( 'url',      [ 'label' => __( 'Profile URL', 'luwipress-gold' ), 'type' => Controls_Manager::URL, 'default' => [ 'url' => '' ] ] );
		$rep->add_control( 'grad_from',[ 'label' => __( 'Avatar gradient start', 'luwipress-gold' ), 'type' => Controls_Manager::COLOR, 'default' => '#9A7B3A' ] );
		$rep->add_control( 'grad_to',  [ 'label' => __( 'Avatar gradient end', 'luwipress-gold' ),   'type' => Controls_Manager::COLOR, 'default' => '#D4AF37' ] );

		$this->add_control(
			'items',
			[
				'label'       => __( 'Masters', 'luwipress-gold' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $rep->get_controls(),
				'title_field' => '{{{ name || "Master" }}}',
				'default'     => [
					[ 'name' => 'Feramis Aktas',      'initial' => 'F', 'location' => 'Istanbul · TR', 'specialty' => 'Oud · 28 years',          'count' => '42 instruments', 'grad_from' => '#9a7b3a', 'grad_to' => '#d4af37' ],
					[ 'name' => 'Yildirim Palabiyik', 'initial' => 'Y', 'location' => 'Istanbul · TR', 'specialty' => 'Saz · Tanbur · 32 years', 'count' => '68 instruments', 'grad_from' => '#5a3a2a', 'grad_to' => '#a87a4a' ],
					[ 'name' => 'Hamid',              'initial' => 'H', 'location' => 'Tehran · IR',   'specialty' => 'Setar · Tar · 24 years',  'count' => '31 instruments', 'grad_from' => '#3d2f1f', 'grad_to' => '#7a5a2c' ],
					[ 'name' => 'A. Golestani',       'initial' => 'A', 'location' => 'Tehran · IR',   'specialty' => 'Tar · Kamancheh · 35 years', 'count' => '22 instruments', 'grad_from' => '#7a5a3a', 'grad_to' => '#b89a6a' ],
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

		$this->add_control(
			'variant',
			[
				'label'   => __( 'Variant', 'luwipress-gold' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'dark',
				'options' => [
					'dark'  => __( 'Dark (for #1A1612 section bg)', 'luwipress-gold' ),
					'light' => __( 'Light (for cream section bg)', 'luwipress-gold' ),
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render() {
		$s       = $this->get_settings_for_display();
		$items   = is_array( $s['items'] ?? null ) ? $s['items'] : [];
		$columns = in_array( $s['columns'] ?? '4', [ '2', '3', '4' ], true ) ? $s['columns'] : '4';
		$variant = ( $s['variant'] ?? 'dark' ) === 'light' ? 'light' : 'dark';

		if ( empty( $items ) ) {
			return;
		}
		?>
		<div class="lwp-mgrid lwp-mgrid--<?php echo esc_attr( $variant ); ?>" data-columns="<?php echo esc_attr( $columns ); ?>">
			<?php foreach ( $items as $it ) :
				$name = trim( (string) ( $it['name'] ?? '' ) );
				if ( $name === '' ) { continue; }
				$initial   = trim( (string) ( $it['initial'] ?? '' ) ) ?: mb_substr( $name, 0, 1 );
				$avatar    = $it['avatar']['url'] ?? '';
				$location  = trim( (string) ( $it['location'] ?? '' ) );
				$specialty = trim( (string) ( $it['specialty'] ?? '' ) );
				$count     = trim( (string) ( $it['count'] ?? '' ) );
				$url       = $it['url']['url'] ?? '';
				$ext       = ! empty( $it['url']['is_external'] );
				$gf        = $it['grad_from'] ?? '#9A7B3A';
				$gt        = $it['grad_to']   ?? '#D4AF37';
				$tag       = $url ? 'a' : 'div';
				$href_attr = $url ? ' href="' . esc_url( $url ) . '"' : '';
				$ext_attr  = $ext ? ' target="_blank" rel="noopener"' : '';
				?>
				<<?php echo $tag . $href_attr . $ext_attr; ?> class="lwp-mgrid__card">
					<div class="lwp-mgrid__avatar"
						<?php if ( ! $avatar ) : ?>
							style="background: linear-gradient(135deg, <?php echo esc_attr( $gf ); ?>, <?php echo esc_attr( $gt ); ?>);"
						<?php endif; ?>>
						<?php if ( $avatar ) : ?>
							<img loading="lazy" decoding="async" src="<?php echo esc_url( $avatar ); ?>" alt="" width="120" height="120" />
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
							<span class="lwp-mgrid__spec"><?php echo esc_html( $specialty ); ?></span>
						<?php endif; ?>
						<?php if ( $count ) : ?>
							<span class="lwp-mgrid__count"><?php echo esc_html( $count ); ?></span>
						<?php endif; ?>
					</div>
				</<?php echo $tag; ?>>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
