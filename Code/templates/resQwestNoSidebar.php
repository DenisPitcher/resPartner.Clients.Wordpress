<?php
/**
 * Template Name: resQwestNoSidebar
 *
 * The second template used to demonstrate how to include the template
 * using this plugin.
 *
 * @package resQwest
 * @since 	1.0.0
 * @version	1.0.0
 */
?>

<?php
	$pte = pageTemplate::get_instance();
	$locale = $pte->get_locale();
?>
 
<?php
    get_header();
?>

<div id="main-content">
    
	<div class="container">
		<div id="content-area" class="clearfix">
			<?php while ( have_posts() ) : the_post(); ?>
					<div class="entry-content">
					<?php
						the_content();
					?>
					</div> <!-- .entry-content -->
			<?php endwhile; ?>
		</div> <!-- #content-area -->
	</div> <!-- .container -->
</div> <!-- #main-content -->

<?php get_footer(); ?>