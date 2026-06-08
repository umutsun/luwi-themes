<?php
/**
 * LuwiPress Emerald — comments.php
 *
 * Threaded comments using the WP defaults wrapped in Emerald
 * typography. No bespoke walker — keeps it framework-friendly.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="emerald-comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="emerald-h3" style="margin-bottom:var(--sp-4);">
			<?php
			$count = (int) get_comments_number();
			/* translators: %d: number of comments */
			printf( esc_html( _n( '%d reply', '%d replies', $count, 'luwipress-emerald' ) ), $count );
			?>
		</h2>
		<ol class="emerald-comment-list" style="list-style:none;padding:0;margin:0 0 var(--sp-8);">
			<?php
			wp_list_comments( array(
				'style'      => 'ol',
				'short_ping' => true,
				'avatar_size'=> 40,
			) );
			?>
		</ol>
		<?php
		the_comments_pagination( array(
			'mid_size'  => 2,
			'prev_text' => __( '&larr; Older', 'luwipress-emerald' ),
			'next_text' => __( 'Newer &rarr;', 'luwipress-emerald' ),
		) );
	endif;

	if ( ! comments_open() && get_comments_number() && post_type_supports( get_post_type(), 'comments' ) ) {
		echo '<p class="emerald-caption">' . esc_html__( 'Comments are closed.', 'luwipress-emerald' ) . '</p>';
	}

	comment_form( array(
		'class_submit' => 'emerald-btn emerald-btn--primary',
		'title_reply'  => __( 'Leave a reply', 'luwipress-emerald' ),
	) );
	?>
</section>
