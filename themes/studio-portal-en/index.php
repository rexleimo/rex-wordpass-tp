<?php
if (!defined('ABSPATH')) {
    exit;
}
$is_archive_view = is_archive();
if (is_category()) {
    $archive_title = single_cat_title('', false);
} elseif (is_tax('studio_column')) {
    $archive_title = single_term_title('', false);
} else {
    $archive_title = $is_archive_view ? get_the_archive_title() : __('Latest stories', 'studio-portal-en');
}
$archive_description = $is_archive_view ? get_the_archive_description() : '';
if ('' === trim(wp_strip_all_tags($archive_description))) {
    $archive_description = __('Reporting, columns and field notes from the international newsroom.', 'studio-portal-en');
}
get_header();
?>
<section class="en-container en-page-hero"><div class="en-breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">HOME</a> / <?php echo esc_html($is_archive_view ? __('ARCHIVE', 'studio-portal-en') : __('JOURNAL', 'studio-portal-en')); ?></div><div class="en-page-hero-grid"><h1 class="en-display"><?php echo wp_kses_post($archive_title); ?></h1><div><?php echo wp_kses_post(wpautop($archive_description)); ?></div></div></section>
<section class="en-section en-container"><div class="en-journal-grid">
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    <article><p class="en-kicker"><?php echo esc_html(studio_portal_en_post_date('M d, Y', get_the_ID())); ?></p><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><p><?php echo esc_html(get_the_excerpt()); ?></p><a class="en-button" href="<?php the_permalink(); ?>">Read story <span>&rarr;</span></a></article>
<?php endwhile; else : ?><div class="en-empty">No stories published yet.</div><?php endif; ?>
</div></section>
<?php get_footer(); ?>
