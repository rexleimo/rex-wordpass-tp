<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
    </main>
    <footer class="en-footer">
        <div class="en-container en-footer-cta" data-reveal>
            <div><p class="en-kicker">[THE WEEKLY EDIT] / EVERY THURSDAY</p><h2 class="en-display">Stay curious.<br>Stay difficult.</h2></div>
            <a class="en-button" href="<?php echo esc_url(home_url('/#newsletter')); ?>">Get the briefing <span>&rarr;</span></a>
        </div>
        <div class="en-container en-footer-grid">
            <a class="en-footer-brand" href="<?php echo esc_url(home_url('/')); ?>">STUDIO INTERNATIONAL</a>
            <div><h3>SECTIONS</h3><nav><?php wp_nav_menu(array('theme_location' => 'footer', 'container' => false, 'items_wrap' => '<ul>%3$s</ul>', 'fallback_cb' => 'studio_portal_en_menu_fallback')); ?></nav></div>
            <div><h3>SOCIAL</h3><nav><a href="https://x.com/" rel="noopener noreferrer">X / Twitter ↗</a><a href="https://www.linkedin.com/" rel="noopener noreferrer">LinkedIn ↗</a><a href="https://github.com/" rel="noopener noreferrer">GitHub ↗</a></nav></div>
            <div class="en-footer-address"><h3>NEWSROOM</h3><a href="mailto:desk@studio.news">desk@studio.news</a><span>London / New York / Singapore</span><span>Independent since 2019</span></div>
        </div>
        <div class="en-container en-footer-bottom"><span>&copy; <?php echo esc_html(date_i18n('Y')); ?> STUDIO INTERNATIONAL</span><span>Independent ideas for a connected world</span><span>V.0.2 / LONDON 14:32 GMT</span></div>
    </footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
