<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * The template for displaying all single posts
 *
 * Do not overload this file directly. Instead have a look at templates/single.php file in us-core plugin folder:
 * you should find all the needed hooks there.
 */

if ( function_exists( 'us_load_template' ) ) {

	us_load_template( 'templates/single' );

} else {
	get_header();
	?>
<!-- blog schema -->
<script type="application/ld+json">
	{
	  "@context": "https://schema.org",
	  "@type": "BlogPosting",
	  "mainEntityOfPage": {
	    "@type": "WebPage",
	    "@id": "<?php echo the_permalink(); ?>"
	  },
	  "headline": "<?php echo get_post_meta( get_the_ID(), '_yoast_wpseo_title', true); ?>",
	  "description": "<?php echo get_post_meta( get_the_ID(), '_yoast_wpseo_metadesc', true); ?>",
	  "image": "<?php echo $featured_img_url = get_the_post_thumbnail_url(get_the_ID(),'full');  ?>",  
	  "author": {
	    "@type": "Organization",
	    "name": "Author"
	  },  
	  "publisher": {
	    "@type": "Organization",
	    "name": "Admin",
	    "logo": {
	      "@type": "ImageObject",
	      "url": "https://naivo.in/wp-content/uploads/2024/08/Naivo_Logo_rgb.svg"
	    }
	  },
	  "datePublished": "<?php echo get_the_date( 'Y-m-j' ); ?>"
	}
</script>
	<main id="page-content" class="l-main">
		<?php
		while ( have_posts() ) {
			the_post();

			get_template_part( 'content' );
		}
		?>
	</main>
	<?php
	get_footer();
}
