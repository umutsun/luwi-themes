<?php
/**
 * Widget: Story Split.
 *
 * Two-column "our story" / atelier layout — one side is a large image
 * tile with an optional floating info card; the other side is a story
 * with eyebrow, heading, lead paragraph, numbered bullet rows and a
 * primary CTA.
 *
 * Replaces the `tap_atelier_l_html` + `tap_atelier_r_html` pair in the
 * homepage kit. Each side renders as a single block so it can sit
 * inside any 2-column Elementor section.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Gold_Widget_Story_Split extends Widget_Base {

	public function get_name()        { return 'lwp-story-split'; }
	public function get_title()       { return __( 'Story Split', 'luwipress-gold' ); }
	public function get_icon()        { return 'eicon-image-box'; }
	public function get_categories()  { return [ 'luwipress-gold' ]; }
	public function get_keywords()    { return [ 'story', 'about', 'atelier', 'split', 'image', 'bullets' ]; }
	public function get_style_depends() { return [ 'luwipress-gold-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_layout', [ 'label' => __( 'Layout', 'luwipress-gold' ) ] );

		$this->add_control(
			'image_side',
			[
				'label'   => __( 'Image side', 'luwipress-gold' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'left',
				'options' => [
					'left'  => __( 'Image on left', 'luwipress-gold' ),
					'right' => __( 'Image on right', 'luwipress-gold' ),
				],
			]
		);

		$this->end_controls_section();

		/* Image side */
		$this->start_controls_section( 'section_image', [ 'label' => __( 'Image tile', 'luwipress-gold' ) ] );

		$this->add_control(
			'image',
			[
				'label'   => __( 'Image', 'luwipress-gold' ),
				'type'    => Controls_Manager::MEDIA,
				'default' => [ 'url' => '' ],
			]
		);
		$this->add_control(
			'image_alt_grad_from',
			[
				'label'     => __( 'Gradient start (when no image)', 'luwipress-gold' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#3d2f1f',
			]
		);
		$this->add_control(
			'image_alt_grad_to',
			[
				'label'     => __( 'Gradient end (when no image)', 'luwipress-gold' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#7a5a2c',
			]
		);
		$this->add_control(
			'card_eyebrow',
			[
				'label'   => __( 'Floating card — eyebrow', 'luwipress-gold' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			]
		);
		$this->add_control(
			'card_title',
			[
				'label'   => __( 'Floating card — title', 'luwipress-gold' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			]
		);
		$this->add_control(
			'card_sub',
			[
				'label'   => __( 'Floating card — sub line', 'luwipress-gold' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			]
		);
		$this->end_controls_section();

		/* Story side */
		$this->start_controls_section( 'section_story', [ 'label' => __( 'Story', 'luwipress-gold' ) ] );

		$this->add_control(
			'eyebrow',
			[
				'label'   => __( 'Eyebrow', 'luwipress-gold' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '',
			]
		);
		$this->add_control(
			'heading',
			[
				'label'       => __( 'Heading', 'luwipress-gold' ),
				'description' => __( 'Use Enter for multi-line. Wrap with [em]…[/em] for italic gold accent.', 'luwipress-gold' ),
				'type'        => Controls_Manager::TEXTAREA,
				'rows'        => 3,
				'default'     => '',
			]
		);
		$this->add_control(
			'lead',
			[
				'label' => __( 'Lead paragraph', 'luwipress-gold' ),
				'type'  => Controls_Manager::TEXTAREA,
				'rows'  => 4,
			]
		);
		$this->add_control(
			'cta_label',
			[
				'label' => __( 'CTA label', 'luwipress-gold' ),
				'type'  => Controls_Manager::TEXT,
			]
		);
		$this->add_control(
			'cta_url',
			[
				'label' => __( 'CTA URL', 'luwipress-gold' ),
				'type'  => Controls_Manager::URL,
				'default' => [ 'url' => '' ],
			]
		);

		$rep = new Repeater();
		$rep->add_control( 'title', [ 'label' => __( 'Title', 'luwipress-gold' ), 'type' => Controls_Manager::TEXT ] );
		$rep->add_control( 'body',  [ 'label' => __( 'Body', 'luwipress-gold' ),  'type' => Controls_Manager::TEXTAREA, 'rows' => 2 ] );

		$this->add_control(
			'bullets',
			[
				'label'       => __( 'Numbered bullets', 'luwipress-gold' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $rep->get_controls(),
				'title_field' => '{{{ title || "Bullet" }}}',
				'default'     => [
					[ 'title' => 'Direct from masters.',          'body' => 'No middlemen. Each instrument is signed by the luthier who made it.' ],
					[ 'title' => 'Tuned before dispatch.',         'body' => 'Every saz, oud and santur is set up and intonated by our atelier team.' ],
					[ 'title' => 'Worldwide insured shipping.',    'body' => 'DHL Express in custom-built crates · 3–7 working days.' ],
					[ 'title' => 'Lifetime support.',              'body' => 'Free maintenance for two years · paid service forever after.' ],
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render_heading( $raw ) {
		$out = esc_html( $raw );
		$out = preg_replace_callback(
			'/\[em\](.*?)\[\/em\]/u',
			static function ( $m ) { return '<em>' . $m[1] . '</em>'; },
			$out
		);
		$out = nl2br( $out );
		return $out;
	}

	protected function render() {
		$s = $this->get_settings_for_display();

		$image_side = ( $s['image_side'] ?? 'left' ) === 'right' ? 'right' : 'left';
		$image      = $s['image']['url'] ?? '';
		$gf         = $s['image_alt_grad_from'] ?? '#3d2f1f';
		$gt         = $s['image_alt_grad_to']   ?? '#7a5a2c';
		$card_eb    = trim( (string) ( $s['card_eyebrow'] ?? '' ) );
		$card_tt    = trim( (string) ( $s['card_title']   ?? '' ) );
		$card_sub   = trim( (string) ( $s['card_sub']     ?? '' ) );

		$eyebrow   = trim( (string) ( $s['eyebrow'] ?? '' ) );
		$heading   = trim( (string) ( $s['heading'] ?? '' ) );
		$lead      = trim( (string) ( $s['lead'] ?? '' ) );
		$cta_label = trim( (string) ( $s['cta_label'] ?? '' ) );
		$cta_url   = $s['cta_url']['url'] ?? '';
		$cta_ext   = ! empty( $s['cta_url']['is_external'] );
		$bullets   = is_array( $s['bullets'] ?? null ) ? $s['bullets'] : [];
		?>
		<div class="lwp-story" data-image-side="<?php echo esc_attr( $image_side ); ?>">
			<div class="lwp-story__image-side">
				<div class="lwp-story__image"
					<?php if ( $image ) : ?>
						style="background-image: linear-gradient(rgba(0,0,0,0.05), rgba(0,0,0,0.35)), url(<?php echo esc_url( $image ); ?>); background-size: cover; background-position: center;"
					<?php else : ?>
						style="background: linear-gradient(135deg, <?php echo esc_attr( $gf ); ?>, <?php echo esc_attr( $gt ); ?>);"
					<?php endif; ?>>
					<?php if ( $card_eb || $card_tt || $card_sub ) : ?>
						<div class="lwp-story__card">
							<?php if ( $card_eb ) : ?><span class="lwp-story__card-eb"><?php echo esc_html( $card_eb ); ?></span><?php endif; ?>
							<?php if ( $card_tt ) : ?><strong><?php echo esc_html( $card_tt ); ?></strong><?php endif; ?>
							<?php if ( $card_sub ) : ?><span class="lwp-story__card-sub"><?php echo esc_html( $card_sub ); ?></span><?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
			<div class="lwp-story__copy-side">
				<?php if ( $eyebrow ) : ?>
					<span class="lwp-story__eyebrow">— <?php echo esc_html( $eyebrow ); ?></span>
				<?php endif; ?>
				<?php if ( $heading ) : ?>
					<h2 class="lwp-story__title"><?php echo $this->render_heading( $heading ); ?></h2>
				<?php endif; ?>
				<?php if ( $lead ) : ?>
					<p class="lwp-story__lead"><?php echo esc_html( $lead ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $bullets ) ) : ?>
					<ol class="lwp-story__bullets">
						<?php $i = 0; foreach ( $bullets as $b ) :
							$t = trim( (string) ( $b['title'] ?? '' ) );
							$body = trim( (string) ( $b['body']  ?? '' ) );
							if ( $t === '' && $body === '' ) { continue; }
							$i++;
							?>
							<li>
								<span class="lwp-story__num"><?php echo esc_html( sprintf( '%02d', $i ) ); ?></span>
								<div>
									<?php if ( $t ) : ?><strong><?php echo esc_html( $t ); ?></strong><?php endif; ?>
									<?php if ( $body ) : ?><span><?php echo esc_html( $body ); ?></span><?php endif; ?>
								</div>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>
				<?php if ( $cta_label && $cta_url ) : ?>
					<a class="lwp-story__cta" href="<?php echo esc_url( $cta_url ); ?>"
						<?php echo $cta_ext ? ' target="_blank" rel="noopener"' : ''; ?>>
						<?php echo esc_html( $cta_label ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
