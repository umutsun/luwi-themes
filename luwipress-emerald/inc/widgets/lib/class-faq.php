<?php
/**
 * Widget: FAQ Accordion.
 *
 * Collapsible Q&A. Renders Schema.org FAQPage JSON-LD inline so
 * SEO plugins (Rank Math / Yoast / AIOSEO) pick it up and Google
 * shows the FAQ rich result.
 *
 * No JS framework — uses native <details>/<summary>. Hooks into
 * LuwiPress AEO module if available so AEO Coverage report counts
 * the FAQ presence on this page.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

class LuwiPress_Emerald_Widget_FAQ extends Widget_Base {

	public function get_name()        { return 'lwp-faq'; }
	public function get_title()       { return __( 'FAQ Accordion', 'luwipress-emerald' ); }
	public function get_icon()        { return 'eicon-help-o'; }
	public function get_categories()  { return [ 'luwipress-emerald' ]; }
	public function get_keywords()    { return [ 'faq', 'accordion', 'questions', 'q&a', 'schema', 'aeo' ]; }
	public function get_style_depends() { return [ 'luwipress-emerald-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_items', [ 'label' => __( 'Questions', 'luwipress-emerald' ) ] );

		$rep = new Repeater();
		$rep->add_control( 'q', [ 'label' => __( 'Question', 'luwipress-emerald' ), 'type' => Controls_Manager::TEXT, 'default' => '' ] );
		$rep->add_control( 'a', [ 'label' => __( 'Answer', 'luwipress-emerald' ), 'type' => Controls_Manager::WYSIWYG, 'default' => '' ] );
		$rep->add_control( 'open', [
			'label'        => __( 'Open by default', 'luwipress-emerald' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
		] );

		$this->add_control( 'items', [
			'label'       => __( 'Items', 'luwipress-emerald' ),
			'type'        => Controls_Manager::REPEATER,
			'fields'      => $rep->get_controls(),
			'title_field' => '{{{ q || "Question" }}}',
			'default'     => [
				[ 'q' => __( 'How long does shipping take?', 'luwipress-emerald' ),         'a' => __( 'Replace this answer with your real shipping timeline + carriers + insurance details.', 'luwipress-emerald' ) ],
				[ 'q' => __( 'What is your return policy?', 'luwipress-emerald' ),          'a' => __( 'Describe your return / refund policy here — common answers convert best.', 'luwipress-emerald' ) ],
				[ 'q' => __( 'Do you ship worldwide?', 'luwipress-emerald' ),               'a' => __( 'List the regions you ship to and any restrictions.', 'luwipress-emerald' ) ],
				[ 'q' => __( 'How can I contact customer support?', 'luwipress-emerald' ),  'a' => __( 'Add your support email, hours and average response time here.', 'luwipress-emerald' ) ],
			],
		] );

		$this->add_control( 'output_schema', [
			'label'        => __( 'Output FAQPage schema (JSON-LD)', 'luwipress-emerald' ),
			'description'  => __( 'Required for Google FAQ rich result. Disable if you already have FAQPage schema on the page via Rank Math / Yoast.', 'luwipress-emerald' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => 'yes',
		] );

		$this->add_control( 'allow_multiple', [
			'label'        => __( 'Allow multiple open at once', 'luwipress-emerald' ),
			'type'         => Controls_Manager::SWITCHER,
			'return_value' => 'yes',
			'default'      => '',
		] );

		$this->end_controls_section();
	}

	protected function render() {
		$s     = $this->get_settings_for_display();
		$items = is_array( $s['items'] ?? null ) ? $s['items'] : [];
		if ( empty( $items ) ) { return; }
		$schema = ( $s['output_schema'] ?? 'yes' ) === 'yes';
		$multi  = ( $s['allow_multiple'] ?? '' ) === 'yes';
		$name   = 'lwp-faq-' . substr( md5( $this->get_id() ), 0, 6 );

		// Build schema first so we can emit it inline.
		$entities = [];
		foreach ( $items as $it ) {
			$q = trim( wp_strip_all_tags( (string) ( $it['q'] ?? '' ) ) );
			$a = trim( wp_strip_all_tags( (string) ( $it['a'] ?? '' ) ) );
			if ( $q === '' || $a === '' ) { continue; }
			$entities[] = [
				'@type'          => 'Question',
				'name'           => $q,
				'acceptedAnswer' => [ '@type' => 'Answer', 'text' => $a ],
			];
		}
		?>
		<div class="lwp-faq">
			<?php foreach ( $items as $i => $it ) :
				$q = trim( (string) ( $it['q'] ?? '' ) );
				$a = trim( (string) ( $it['a'] ?? '' ) );
				if ( $q === '' || $a === '' ) { continue; }
				$open = ( $it['open'] ?? '' ) === 'yes';
				?>
				<details class="lwp-faq__item" <?php echo $open ? 'open' : ''; ?>
					<?php if ( ! $multi ) : ?>data-lwp-faq-name="<?php echo esc_attr( $name ); ?>"<?php endif; ?>>
					<summary class="lwp-faq__q">
						<span><?php echo esc_html( $q ); ?></span>
						<span class="lwp-faq__chev" aria-hidden="true">+</span>
					</summary>
					<div class="lwp-faq__a"><?php echo wp_kses_post( $a ); ?></div>
				</details>
			<?php endforeach; ?>
		</div>
		<?php if ( $schema && ! empty( $entities ) ) :
			$json = wp_json_encode( [
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'mainEntity' => $entities,
			], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			?>
			<script type="application/ld+json"><?php echo $json; ?></script>
			<?php
		endif;
	}
}
