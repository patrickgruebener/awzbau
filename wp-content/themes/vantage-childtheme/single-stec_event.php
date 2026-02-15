<?php
/**
 * Single template for STEC events.
 *
 * Keeps the event page inside Vantage containers while letting
 * STEC render the complete single-event content.
 */

get_header();
?>

<div id="primary" class="content-area awz-stec-single-layout">
	<div id="content" class="site-content" role="main">

		<?php while ( have_posts() ) : the_post(); ?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'post awz-stec-single-event' ); ?>>
				<div class="entry-main">
					<?php do_action( 'vantage_entry_main_top' ); ?>
					<div class="entry-content awz-stec-single-entry-content">
						<?php
						the_content();
						wp_link_pages(
							array(
								'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'vantage' ),
								'after'  => '</div>',
							)
						);
						?>
					</div>
					<?php do_action( 'vantage_entry_main_bottom' ); ?>
				</div>
			</article>
		<?php endwhile; ?>

	</div>
</div>

<?php
get_footer();
