<?php
/**
 * Widget: Process Steps.
 *
 * Numbered horizontal cards (1→2→3→4) for "how we work" / "how an
 * instrument is made" explainers. Each step has a number, title,
 * body, optional icon and optional connecting arrow.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Sapphire_Widget_Process_Steps extends Widget_Base {

	public function get_name()        { return 'lwp-process-steps'; }
	public function get_title()       { return __( 'Process Steps', 'luwipress-sapphire' ); }
	public function get_icon()        { return 'eicon-steps'; }
	public function get_categories()  { return [ 'luwipress-sapphire' ]; }
	public function get_keywords()    { return [ 'process', 'steps', 'how', 'timeline', 'flow' ]; }
	public function get_style_depends() { return [ 'luwipress-sapphire-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_items', [ 'label' => __( 'Steps', 'luwipress-sapphire' ) ] );

		$rep = new Repeater();
		$rep->add_control( 'title', [ 'label' => __( 'Title', 'luwipress-sapphire' ), 'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$rep->add_control( 'body',  [ 'label' => __( 'Body', 'luwipress-sapphire' ),  'type' => Controls_Manager::TEXTAREA, 'rows' => 3, 'default' => '' ] );
		$rep->add_control( 'icon',  [ 'label' => __( 'Icon (optional, overrides number)', 'luwipress-sapphire' ), 'type' => Controls_Manager::ICONS, 'default' => [ 'value' => '', 'library' => '' ] ] );

		$this->add_control( 'items', [
			'label'       => __( 'Steps', 'luwipress-sapphire' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'title_field' => '{{{ title || "Step" }}}',
			'default'     => [
				[ 'title' => __( 'Step 1',  'luwipress-sapphire' ), 'body' => __( 'Describe the first step of your customer journey here.', 'luwipress-sapphire' ) ],
				[ 'title' => __( 'Step 2',  'luwipress-sapphire' ), 'body' => __( 'Second step — what happens after the customer takes step 1.', 'luwipress-sapphire' ) ],
				[ 'title' => __( 'Step 3',  'luwipress-sapphire' ), 'body' => __( 'Third step — the build / fulfillment / processing phase.', 'luwipress-sapphire' ) ],
				[ 'title' => __( 'Step 4',  'luwipress-sapphire' ), 'body' => __( 'Final step — delivery / handoff / after-sale.', 'luwipress-sapphire' ) ],
			],
		] );

		$this->add_control( 'show_arrows', [
			'label'        => __( 'Show connecting arrows', 'luwipress-sapphire' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );
		$this->add_control( 'orientation', [
			'label'   => __( 'Orientation', 'luwipress-sapphire' ),
			'type'    => Controls_Manager::SELECT,
			'default' => 'horizontal',
			'options' => [
				'horizontal' => __( 'Horizontal (cards in a row)', 'luwipress-sapphire' ),
				'vertical'   => __( 'Vertical (left-aligned list)', 'luwipress-sapphire' ),
			],
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$items = is_array( $s['items'] ?? null ) ? $s['items'] : [];
		if ( empty( $items ) ) { return; }
		$arrows = ( $s['show_arrows'] ?? 'yes' ) === 'yes';
		$orient = ( $s['orientation'] ?? 'horizontal' ) === 'vertical' ? 'vertical' : 'horizontal';
		$count  = count( $items );
		?>
		<ol class="lwp-ps lwp-ps--<?php echo esc_attr( $orient ); ?> <?php echo $arrows ? 'lwp-ps--arrows' : ''; ?>" data-count="<?php echo esc_attr( $count ); ?>">
			<?php $i = 0; foreach ( $items as $it ) :
				$title = trim( (string) ( $it['title'] ?? '' ) );
				$body  = trim( (string) ( $it['body']  ?? '' ) );
				$icon  = $it['icon'] ?? [];
				$i++;
				?>
				<li class="lwp-ps__step">
					<div class="lwp-ps__head">
						<?php if ( ! empty( $icon['value'] ) ) : ?>
							<span class="lwp-ps__icon" aria-hidden="true"><?php \Elementor\Icons_Manager::render_icon( $icon, [ 'aria-hidden' => 'true' ] ); ?></span>
						<?php else : ?>
							<span class="lwp-ps__num"><?php echo esc_html( sprintf( '%02d', $i ) ); ?></span>
						<?php endif; ?>
						<?php if ( $arrows && $i < $count ) : ?>
							<span class="lwp-ps__arrow" aria-hidden="true">→</span>
						<?php endif; ?>
					</div>
					<?php if ( $title ) : ?><h3 class="lwp-ps__title"><?php echo esc_html( $title ); ?></h3><?php endif; ?>
					<?php if ( $body ) : ?><p class="lwp-ps__body"><?php echo esc_html( $body ); ?></p><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
		<?php
	}
}
