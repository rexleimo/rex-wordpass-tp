<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
    </main>
    <footer class="sp-footer">
        <div class="sp-footer-top">
            <a class="sp-footer-brand" href="<?php echo esc_url(home_url('/')); ?>"><?php echo esc_html(get_bloginfo('name')); ?></a>
            <p><?php esc_html_e('面向中文开发者的 AI 工程资讯、深度解读与实践笔记。', 'studio-portal'); ?></p>
            <a class="sp-footer-mail" href="<?php echo esc_url(home_url('/journal/')); ?>"><?php esc_html_e('浏览全部文章 ->', 'studio-portal'); ?></a>
        </div>
        <div class="sp-footer-bar">
            <span>&copy; <?php echo esc_html(date_i18n('Y')); ?> <?php echo esc_html(get_bloginfo('name')); ?></span>
            <nav aria-label="<?php esc_attr_e('页尾导航', 'studio-portal'); ?>">
                <?php wp_nav_menu(array('theme_location' => 'footer', 'container' => false, 'items_wrap' => '%3$s', 'fallback_cb' => 'studio_portal_footer_menu_fallback')); ?>
            </nav>
        </div>
    </footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
