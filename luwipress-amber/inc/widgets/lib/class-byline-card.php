<?php
/**
 * Widget: Byline Card.
 *
 * Author avatar + name + date + read time. Used at the top of single-post
 * pages right after the title. Auto-pulls from the current post when source
 * = "auto" (the common case on Theme Builder Single templates).
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! class_exists( 'Elementor\\Widget_Base' ) ) { return; }

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class LuwiPress_Amber_Widget_Byline_Card extends Widget_Base {

	public function get_name()        { return 'lwp-byline-card'; }
	public function get_title()       { return __( 'Byline Card', 'luwipress-amber' ); }
	public function get_icon()        { return 'eicon-author'; }
	public function get_categories()  { return [ 'luwipress-amber' ]; }
	public function get_keywords()    { return [ 'byline', 'author', 'date', 'read', 'time' ]; }
	public function get_style_depends() { return [ 'luwipress-amber-widgets' ]; }

	protected function register_controls() {

		$this->start_controls_section( 'section_content', [ 'label' => __( 'Byline', 'luwipress-amber' ) ] );

		$this->add_control(
			'source',
			[
				'label'   => __( 'Source', 'luwipress-amber' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => [
					'auto'   => __( 'Auto (current post)', 'luwipress-amber' ),
					'manual' => __( 'Manual', 'luwipress-amber' ),
				],
			]
		);
		$this->add_control(
			'manual_avatar',
			[ 'label' => __( 'Avatar image', 'luwipress-amber' ), 'type' => Controls_Manager::MEDIA, 'default' => [ 'url' => '' ], 'condition' => [ 'source' => 'manual' ] ]
		);
		$this->add_control(
			'manual_name',
			[ 'label' => __( 'Author name', 'luwipress-amber' ), 'type' => Controls_Manager::TEXT, 'default' => 'Author', 'condition' => [ 'source' => 'manual' ] ]
		);
		$this->add_control(
			'manual_role',
			[ 'label' => __( 'Author role / byline sub', 'luwipress-amber' ), 'type' => Controls_Manager::TEXT, 'default' => 'Editor', 'condition' => [ 'source' => 'manual' ] ]
		);
		$this->add_control(
			'manual_date',
			[ 'label' => __( 'Date (free text)', 'luwipress-amber' ), 'type' => Controls_Manager::TEXT, 'default' => '', 'condition' => [ 'source' => 'manual' ] ]
		);

		$this->add_control(
			'show_read_time',
			[ 'label' => __( 'Show read time', 'luwipress-amber' ), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes' ]
		);

		$this->end_controls_section();

		/* Style */
		$this->start_controls_section( 'section_style', [
			'label' => __( 'Style', 'luwipress-amber' ),
			'tab'   => Controls_Manager::TAB_STYLE,
		] );
		$this->add_control( 'name_color',   [ 'label' => __( 'Name color', 'luwipress-amber' ),   'type' => Controls_Manager::COLOR, 'default' => '#1A1612',
			'selectors' => [ '{{WRAPPER}} .lwp-byline__name' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'meta_color',   [ 'label' => __( 'Meta color', 'luwipress-amber' ),   'type' => Controls_Manager::COLOR, 'default' => '#8b7f6a',
			'selectors' => [ '{{WRAPPER}} .lwp-byline__meta' => 'color: {{VALUE}};' ] ] );
		$this->add_control( 'avatar_gradient', [ 'label' => __( 'Fallback avatar gradient', 'luwipress-amber' ), 'type' => Controls_Manager::COLOR, 'default' => '#9A7B3A',
			'selectors' => [ '{{WRAPPER}} .lwp-byline__avatar' => 'background: {{VALUE}};' ] ] );
		$this->end_controls_section();
	}

	private function estimate_read_time( $content ) {
		$words = str_word_count( wp_strip_all_tags( (string) $content ) );
		return max( 1, (int) ceil( $words / 220 ) );
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		$source = $s['source'] ?? 'auto';
		$show_read = ( $s['show_read_time'] ?? 'yes' ) === 'yes';

		$avatar_url = '';
		$initial    = '?';
		$name       = '';
		$role       = '';
		$date       = '';
		$read_min   = 0;

		if ( $source === 'auto' ) {
			$post = get_post();
			if ( ! $post ) return;
			$author_id  = (int) $post->post_author;
			$user       = get_userdata( $author_id );
			$name       = $user ? $user->display_name : __( 'Author', 'luwipress-amber' );
			$role       = $user ? ( $user->user_description ? wp_trim_words( $user->user_description, 8, '' ) : '' ) : '';
			$avatar_url = $user ? get_avatar_url( $author_id, [ 'size' => 88 ] ) : '';
			$initial    = mb_substr( $name, 0, 1 );
			$date       = get_the_date( '', $post );
			$read_min   = $this->estimate_read_time( $post->post_content );
		} else {
			$avatar_url = $s['manual_avatar']['url'] ?? '';
			$name       = trim( (string) ( $s['manual_name'] ?? 'Author' ) );
			$role       = trim( (string) ( $s['manual_role'] ?? '' ) );
			$date       = trim( (string) ( $s['manual_date'] ?? '' ) );
			$initial    = mb_substr( $name, 0, 1 );
		}

		$meta_parts = [];
		if ( $date )         $meta_parts[] = $date;
		if ( $show_read && $read_min ) {
			$meta_parts[] = sprintf( _n( '%d min read', '%d min read', $read_min, 'luwipress-amber' ), $read_min );
		}
		if ( $role )         $meta_parts[] = $role;
		?>
		<div class="lwp-byline">
			<div class="lwp-byline__avatar" <?php if ( $avatar_url ) : ?>style="background-image: url(<?php echo esc_url( $avatar_url ); ?>); background-size: cover; background-position: center;"<?php endif; ?>>
				<?php echo $avatar_url ? '' : esc_html( $initial ); ?>
			</div>
			<div class="lwp-byline__copy">
				<span class="lwp-byline__name"><?php echo esc_html( $name ); ?></span>
				<?php if ( ! empty( $meta_parts ) ) : ?>
					<span class="lwp-byline__meta"><?php echo esc_html( implode( ' · ', $meta_parts ) ); ?></span>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
