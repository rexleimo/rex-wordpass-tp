<?php
/**
 * Case study detail aligned with the Pencil hYU38 reference.
 */
get_header();

while (have_posts()) :
    the_post();

    $materials = get_the_terms(get_the_ID(), 'tokraft_material');
    $material_names = (!is_wp_error($materials) && $materials) ? wp_list_pluck($materials, 'name') : array();
    $material_label = $material_names ? implode(', ', $material_names) : __('Material review', 'tokraft');
    $industry = trim((string) get_post_meta(get_the_ID(), '_tokraft_case_industry', true));
    $application_label = $industry ?: __('Custom application', 'tokraft');
    $has_manual_summary = has_excerpt();
    $summary_source = $has_manual_summary
        ? (string) get_post_field('post_excerpt', get_the_ID())
        : wp_strip_all_tags(get_the_content());
    $summary = $has_manual_summary ? $summary_source : wp_trim_words($summary_source, 32);
    $archive_url = get_post_type_archive_link('tokraft_case_study') ?: home_url('/case-studies/');
    ?>
    <article <?php post_class('tk-case-detail'); ?> id="post-<?php the_ID(); ?>">
        <div class="tk-case-detail-shell">
            <nav class="tk-case-breadcrumb" aria-label="<?php esc_attr_e('Breadcrumb', 'tokraft'); ?>">
                <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'tokraft'); ?></a>
                <span>/</span>
                <a href="<?php echo esc_url($archive_url); ?>"><?php esc_html_e('Case studies', 'tokraft'); ?></a>
                <span>/</span>
                <span><?php the_title(); ?></span>
            </nav>

            <div class="tk-case-detail-grid">
                <div class="tk-case-media">
                    <span class="tk-case-media-label"><?php esc_html_e('CASE STUDY / REAL PROJECT PHOTO', 'tokraft'); ?></span>
                    <?php if (has_post_thumbnail()) : ?>
                        <?php the_post_thumbnail('large', array('loading' => 'eager')); ?>
                    <?php else : ?>
                        <div class="tk-case-media-fallback" aria-hidden="true"></div>
                    <?php endif; ?>
                    <dl class="tk-case-facts">
                        <div><dt><?php esc_html_e('Material', 'tokraft'); ?></dt><dd><?php echo esc_html($material_label); ?></dd></div>
                        <div><dt><?php esc_html_e('Application', 'tokraft'); ?></dt><dd><?php echo esc_html($application_label); ?></dd></div>
                        <div><dt><?php esc_html_e('Updated', 'tokraft'); ?></dt><dd><?php echo esc_html(get_the_modified_date('M Y')); ?></dd></div>
                    </dl>
                </div>

                <div class="tk-case-summary">
                    <p class="tk-case-kicker"><?php echo esc_html(sprintf(__('CASE STUDY / %s', 'tokraft'), tokraft_uppercase($application_label))); ?></p>
                    <h1><?php the_title(); ?></h1>
                    <p class="tk-case-reading-meta"><?php echo esc_html(sprintf(__('UPDATED %s', 'tokraft'), tokraft_uppercase(get_the_modified_date('F Y')))); ?></p>
                    <p class="tk-case-intro"><?php echo esc_html($summary); ?></p>

                    <div class="tk-case-label-group">
                        <p><?php esc_html_e('Application', 'tokraft'); ?></p>
                        <div class="tk-case-pills"><span class="is-active"><?php echo esc_html($application_label); ?></span></div>
                    </div>
                    <div class="tk-case-label-group">
                        <p><?php esc_html_e('Material used', 'tokraft'); ?></p>
                        <div class="tk-case-pills">
                            <?php foreach ($material_names ?: array($material_label) as $material_name) : ?>
                                <span><?php echo esc_html($material_name); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="tk-case-action-row">
                        <a class="tk-case-share" href="#tk-case-story"><?php esc_html_e('Read the build', 'tokraft'); ?> <span aria-hidden="true">↓</span></a>
                        <p><?php esc_html_e('Need a similar part? We review every geometry before production.', 'tokraft'); ?></p>
                    </div>
                    <a class="tk-case-primary-action" href="<?php echo esc_url(home_url('/quote/')); ?>"><?php esc_html_e('Request a print quote', 'tokraft'); ?> <span aria-hidden="true">→</span></a>
                    <p class="tk-case-quote-note"><?php esc_html_e('Share dimensions, a drawing or a photo for a human-reviewed recommendation.', 'tokraft'); ?></p>
                </div>
            </div>
        </div>

        <section class="tk-case-decision-path" id="tk-case-story">
            <div>
                <p><?php esc_html_e('THE DECISION PATH', 'tokraft'); ?></p>
                <h2><?php esc_html_e('Solve the fit before you print.', 'tokraft'); ?></h2>
            </div>
            <p><?php esc_html_e('Every durable part starts with the actual job: understand the failure, measure the remaining interface, then select material and orientation for the conditions it will face.', 'tokraft'); ?></p>
        </section>

        <section class="tk-case-story" aria-label="<?php esc_attr_e('Case study details', 'tokraft'); ?>">
            <div class="tk-case-story-meta">
                <p><?php esc_html_e('PROJECT NOTES', 'tokraft'); ?></p>
                <strong><?php echo esc_html($material_label); ?></strong>
            </div>
            <div class="tk-case-story-content"><?php the_content(); ?></div>
        </section>
    </article>
<?php endwhile; ?>
<?php get_footer(); ?>
