<?php
/**
 * Sample file for changing the template display of the single snippet post
 *
 * Based off WordPress' twentyfifteen default theme
 *
 */

get_header(); ?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

		<?php
		// Start the loop.
		while ( have_posts() ) : the_post();
			
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<header class="entry-header">
					<?php
						if ( is_single() ) :
							the_title( '<h1 class="entry-title">', '</h1>' );
						else :
							the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' );
						endif;
					?>
				</header><!-- .entry-header -->

				<div class="entry-content">
					<?php
						/* translators: %s: Name of current post */
						the_content( sprintf(
							__( 'Continue reading %s', 'twentyfifteen' ),
							the_title( '<span class="screen-reader-text">', '</span>', false )
						) );

					?>
				</div><!-- .entry-content -->
			</article>
			<?php
		// End the loop.
		endwhile;
		?>

		</main><!-- .site-main -->
	</div><!-- .content-area -->

<?php get_footer(); ?>
