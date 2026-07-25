    </main>
    <footer class="site-footer">
        <div class="footer-inner">
            <div><a class="brand" href="<?php echo esc_url(home_url('/')); ?>"><span class="brand-mark" aria-hidden="true"><i></i><i></i></span>toKraft</a><p class="footer-intro">Production-ready 3D printing for prototypes, parts and products. Made for the real world.</p></div>
            <div class="footer-group"><h4>Services</h4><a href="<?php echo esc_url(home_url('/quote/')); ?>">Request a quote</a><a href="<?php echo esc_url(home_url('/materials/')); ?>">Material library</a><a href="<?php echo esc_url(get_post_type_archive_link('tokraft_case_study') ?: home_url('/case-studies/')); ?>">Case studies</a><a href="<?php echo esc_url(home_url('/#equipment')); ?>">Equipment</a></div>
            <div class="footer-group"><h4>Shop</h4><a href="<?php echo esc_url(function_exists('wc_get_page_permalink') ? wc_get_page_permalink('shop') : home_url('/shop/')); ?>">All products</a><a href="<?php echo esc_url(function_exists('wc_get_cart_url') ? wc_get_cart_url() : home_url('/cart/')); ?>">Your cart</a><a href="<?php echo esc_url(get_option('page_for_posts') ? get_permalink((int) get_option('page_for_posts')) : home_url('/blog/')); ?>">Blog</a></div>
            <div class="footer-group"><h4>Contact</h4><a href="mailto:hello@tokraft.ca">hello@tokraft.ca</a><a href="<?php echo esc_url(home_url('/quote/')); ?>">Business quotes</a><a href="<?php echo esc_url(home_url('/quote/')); ?>">Terms & disclaimer</a></div>
        </div>
        <div class="footer-bottom">&copy; <?php echo esc_html(wp_date('Y')); ?> toKraft. Prices and production times are estimates until a file review is complete.</div>
    </footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
