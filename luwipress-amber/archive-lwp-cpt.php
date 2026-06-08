<?php
/**
 * Generic archive template for operator-defined CPT Engine content types
 * (lwp_team, lwp_event, …). Renders the type's posts as a styled card grid by
 * reusing the Vendor archive's `.lwp-people-archive*` markup (already styled),
 * populated generically from the engine field schema. Routed here by
 * inc/engine-cpt-templates.php; a type-specific template
 * (e.g. archive-lwp_vendor.php) still wins.
 *
 * @package luwipress-amber
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$lwp_pt = get_query_var( 'post_type' );
if ( is_array( $lwp_pt ) ) { $lwp_pt = reset( $lwp_pt ); }
if ( ! $lwp_pt ) {
	$lwp_obj = get_queried_object();
	$lwp_pt  = ( $lwp_obj instanceof WP_Post_Type ) ? $lwp_obj->name : '';
}

$lwp_def    = ( class_exists( 'LuwiPress_CPT_Engine' ) )
	? LuwiPress_CPT_Engine::get_instance()->get_type_by_post_type( $lwp_pt )
	: null;
$lwp_pt_obj = get_post_type_object( $lwp_pt );
$lwp_plural = ( $lwp_def && ! empty( $lwp_def['labels']['plural'] ) )
	? (string) $lwp_def['labels']['plural']
	: ( $lwp_pt_obj ? (string) $lwp_pt_obj->labels->name : __( 'Items', 'luwipress-amber' ) );
$lwp_fields = ( $lwp_def && ! empty( $lwp_def['field_schema'] ) ) ? $lwp_def['field_schema'] : array();
?>

<main class="lwp-people-archive" id="primary">

	<header class="lwp-people-archive__header">
		<h1 class="lwp-people-archive__heading"><?php echo esc_html( $lwp_plural ); ?></h1>
		<?php
		$lwp_desc = get_the_archive_description();
		if ( $lwp_desc ) :
			?>
			<div class="lwp-people-archive__lead"><?php echo wp_kses_post( $lwp_desc ); ?></div>
		<?php endif; ?>
	</header>

	<?php if ( have_posts() ) : ?>
		<ul class="lwp-people-archive__grid">
			<?php
			while ( have_posts() ) : the_post();
				$pid  = get_the_ID();
				$img  = get_the_post_thumbnail_url( $pid, 'large' );
				$perm = get_permalink( $pid );

				// Up to two short scalar field values for the card subtitle.
				$sub = array();
				foreach ( $lwp_fields as $f ) {
					if ( count( $sub ) >= 2 ) { break; }
					$k = isset( $f['key'] ) ? (string) $f['key'] : '';
					$t = isset( $f['type'] ) ? (string) $f['type'] : 'text';
					if ( '' === $k || in_array( $t, array( 'url', 'image', 'textarea' ), true ) ) { continue; }
					$v = get_post_meta( $pid, $k, true );
					if ( '' !== $v && ! is_array( $v ) ) { $sub[] = (string) $v; }
				}
				?>
				<li class="lwp-people-card">
					<a href="<?php echo esc_url( $perm ); ?>" class="lwp-people-card__link">
						<?php if ( $img ) : ?>
							<div class="lwp-people-card__portrait">
								<img src="<?php echo esc_url( $img ); ?>" alt="<?php the_title_attribute(); ?>" />
							</div>
						<?php else : ?>
							<div class="lwp-people-card__portrait lwp-people-card__portrait--initials">
								<span><?php echo esc_html( mb_substr( get_the_title( $pid ), 0, 1 ) ); ?></span>
							</div>
						<?php endif; ?>
						<div class="lwp-people-card__body">
							<h2 class="lwp-people-card__name"><?php the_title(); ?></h2>
							<?php foreach ( $sub as $s ) : ?>
								<span class="lwp-people-card__specialty"><?php echo esc_html( $s ); ?></span>
							<?php endforeach; ?>
						</div>
					</a>
				</li>
			<?php endwhile; ?>
		</ul>

		<?php
		the_posts_pagination( array(
			'mid_size'  => 1,
			'prev_text' => __( '← Previous', 'luwipress-amber' ),
			'next_text' => __( 'Next →', 'luwipress-amber' ),
		) );

		if ( function_exists( 'lwp_amber_loadmore_render' ) ) {
			lwp_amber_loadmore_render( array(
				'grid'       => '.lwp-people-archive__grid',
				'pagination' => '.pagination',
				'next'       => '.pagination a.next',
			) );
		}
		?>
	<?php else : ?>
		<p class="lwp-people-archive__empty">
			<?php
			echo esc_html( sprintf(
				/* translators: %s: plural label */
				__( 'No %s yet.', 'luwipress-amber' ),
				strtolower( $lwp_plural )
			) );
			?>
		</p>
	<?php endif; ?>

</main>

<?php
get_footer();
