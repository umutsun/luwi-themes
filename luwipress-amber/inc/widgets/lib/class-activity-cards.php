<?php
/**
 * Elementor widget — Activity Cards.
 *
 * The home "Top Activities" horizontal scroller of cinematic `.act-card`s.
 * Reuses the #actScroller prev/next handler already shipped in chrome.js — no
 * new JS. Curated via a repeater (the hero of the homepage is hand-picked).
 *
 * @package luwipress-amber
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Amber_Widget_Activity_Cards extends Widget_Base {

	public function get_name() { return 'lwp-activity-cards'; }
	public function get_title() { return __( 'Activity Cards', 'luwipress-amber' ); }
	public function get_icon() { return 'eicon-slides'; }
	public function get_categories() { return [ 'luwipress-amber' ]; }
	public function get_keywords() { return [ 'activity', 'experience', 'cards', 'scroller', 'travel', 'tours' ]; }
	public function get_style_depends() { return [ 'luwipress-amber-widgets' ]; }

	protected function register_controls() {
		$this->start_controls_section( 'head', [ 'label' => __( 'Heading', 'luwipress-amber' ) ] );
		$this->add_control( 'eyebrow', [ 'label' => __( 'Eyebrow', 'luwipress-amber' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Top activities in Dubai', 'luwipress-amber' ) ] );
		$this->add_control( 'heading', [ 'label' => __( 'Heading', 'luwipress-amber' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'The best adventures Dubai has to offer', 'luwipress-amber' ) ] );
		$this->add_control( 'show_nav', [ 'label' => __( 'Show arrows', 'luwipress-amber' ), 'type' => Controls_Manager::SWITCHER, 'default' => 'yes' ] );
		$this->end_controls_section();

		$this->start_controls_section( 'cards', [ 'label' => __( 'Cards', 'luwipress-amber' ) ] );
		$rep = new Repeater();
		$rep->add_control( 'image', [ 'label' => __( 'Image', 'luwipress-amber' ), 'type' => Controls_Manager::MEDIA ] );
		$rep->add_control( 'scene', [
			'label'   => __( 'Scene fallback', 'luwipress-amber' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'scene-sky-city',
			'options' => [
				'scene-sky-city' => __( 'City sky', 'luwipress-amber' ),
				'scene-dunes'    => __( 'Desert dunes', 'luwipress-amber' ),
				'scene-sea-day'  => __( 'Sea', 'luwipress-amber' ),
			],
		] );
		$rep->add_control( 'tag', [ 'label' => __( 'Tag', 'luwipress-amber' ), 'type' => Controls_Manager::TEXT, 'default' => 'Aerial' ] );
		$rep->add_control( 'title', [ 'label' => __( 'Title', 'luwipress-amber' ), 'type' => Controls_Manager::TEXT, 'default' => __( 'Helicopter Tours', 'luwipress-amber' ) ] );
		$rep->add_control( 'body', [ 'label' => __( 'Body', 'luwipress-amber' ), 'type' => Controls_Manager::TEXTAREA ] );
		$rep->add_control( 'price_chip', [ 'label' => __( 'Price chip', 'luwipress-amber' ), 'type' => Controls_Manager::TEXT, 'default' => 'From AED 1,450' ] );
		$rep->add_control( 'duration_chip', [ 'label' => __( 'Duration chip', 'luwipress-amber' ), 'type' => Controls_Manager::TEXT, 'default' => '17 min' ] );
		$rep->add_control( 'link', [ 'label' => __( 'Link', 'luwipress-amber' ), 'type' => Controls_Manager::URL ] );

		$this->add_control( 'items', [
			'label'       => __( 'Activities', 'luwipress-amber' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'title_field' => '{{{ title }}}',
			'default'     => [
				[ 'title' => __( 'Helicopter Tours', 'luwipress-amber' ), 'tag' => 'Aerial', 'scene' => 'scene-sky-city' ],
				[ 'title' => __( 'Desert Safari', 'luwipress-amber' ), 'tag' => 'Desert', 'scene' => 'scene-dunes' ],
				[ 'title' => __( 'Yacht Tour', 'luwipress-amber' ), 'tag' => 'Cruise', 'scene' => 'scene-sea-day' ],
			],
		] );
		$this->end_controls_section();
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$items = is_array( $s['items'] ?? null ) ? $s['items'] : [];
		if ( empty( $items ) ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<em>' . esc_html__( '[Activity Cards] Add at least one activity.', 'luwipress-amber' ) . '</em>';
			}
			return;
		}
		$arrow_l = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>';
		$arrow_r = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
		?>
		<section class="section activities">
			<div class="wrap">
				<div class="sec-head"><div class="row">
					<div>
						<?php if ( ! empty( $s['eyebrow'] ) ) : ?><span class="eyebrow"><?php echo esc_html( $s['eyebrow'] ); ?></span><?php endif; ?>
						<?php if ( ! empty( $s['heading'] ) ) : ?><h2 class="h2"><?php echo esc_html( $s['heading'] ); ?></h2><?php endif; ?>
					</div>
					<?php if ( 'yes' === ( $s['show_nav'] ?? '' ) ) : ?>
						<div class="act-nav">
							<button type="button" id="actPrev" aria-label="<?php esc_attr_e( 'Previous', 'luwipress-amber' ); ?>"><?php echo $arrow_l; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
							<button type="button" id="actNext" aria-label="<?php esc_attr_e( 'Next', 'luwipress-amber' ); ?>"><?php echo $arrow_r; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
						</div>
					<?php endif; ?>
				</div></div>
			</div>
			<div class="act-scroller" id="actScroller">
				<?php foreach ( $items as $it ) :
					$url = ! empty( $it['link']['url'] ) ? $it['link']['url'] : '#';
					$img = ! empty( $it['image']['url'] ) ? $it['image']['url'] : '';
					?>
					<article class="act-card">
						<div class="scene <?php echo esc_attr( $it['scene'] ?? 'scene-sky-city' ); ?>" style="position:absolute;inset:0;">
							<div class="scene-sky"></div>
							<div class="scene-city-band"></div>
							<?php if ( $img ) : ?><img class="scene-img" src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $it['title'] ?? '' ); ?>" loading="lazy"><?php endif; ?>
						</div>
						<div class="act-scrim"></div>
						<div class="act-top">
							<?php if ( ! empty( $it['price_chip'] ) ) : ?><span class="chip price"><?php echo esc_html( $it['price_chip'] ); ?></span><?php endif; ?>
							<?php if ( ! empty( $it['duration_chip'] ) ) : ?><span class="chip"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg> <?php echo esc_html( $it['duration_chip'] ); ?></span><?php endif; ?>
						</div>
						<div class="act-body">
							<?php if ( ! empty( $it['tag'] ) ) : ?><span class="tag"><?php echo esc_html( $it['tag'] ); ?></span><?php endif; ?>
							<h3><?php echo esc_html( $it['title'] ?? '' ); ?></h3>
							<?php if ( ! empty( $it['body'] ) ) : ?><p><?php echo esc_html( $it['body'] ); ?></p><?php endif; ?>
							<a class="book" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Book', 'luwipress-amber' ); ?> <?php echo $arrow_r; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}
}
