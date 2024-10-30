<?php
/**
 * Sample file for changing the template display of the snippet archive list pages
 *
 * Based off WordPress' twentyfifteen default theme
 *
 */

get_header(); ?>

	<section id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

		<?php
			// Change the posts_per_page to suit your needs
			query_posts( array( 
				'post_type' => 'snippets',
				'posts_per_page' => 25 ) 
			); 
			
			if ( have_posts() ) : ?>
		
			<header class="page-header">
				All Code Snippets
			</header><!-- .page-header -->

			<?php
			// Start the Loop.
			while ( have_posts() ) : the_post();
				$snippetLang = get_post_meta($post->ID, "_snippet_lang", true);
				$snippetFileName = get_post_meta($post->ID, "_snippet_filename", true);
			
				?>
				<a href="<?php echo esc_url( get_permalink($post->ID) ); ?>">
				<div class="snippet-entry">
					<div class="snippet-top-row">
						<div class="snippet-title">
							<?php the_title(); ?>
						</div>
						<div class="snippet-file">
							<?php echo $snippetFileName; ?>
						</div>
					</div>
					<div class="clear"></div>
					<div class="snippet-bottom-row">
						<div class="snippet-date">
							<?php echo get_the_date(); ?>
						</div>
						<div class="snippet-lang">
							<?php echo $snippetLang; ?>
						</div>
					</div>
					<div class="clear"></div>
				</div><!-- .snippet-entry -->
				</a>
				<?php
				
			endwhile;

			// Previous/next page navigation.
			the_posts_pagination( array(
				'prev_text'          => __( 'Previous page', 'twentyfifteen' ),
				'next_text'          => __( 'Next page', 'twentyfifteen' ),
				'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'twentyfifteen' ) . ' </span>',
			) );

		// If no content, include the "No posts found" template.
		else :
			echo 'No snippets were found.';

		endif;
		?>

		</main><!-- .site-main -->
	</section><!-- .content-area -->

<?php get_footer(); ?>
