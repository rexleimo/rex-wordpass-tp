    </main>
    <footer class="site-footer">
        <div class="footer-inner">
            <div><a class="brand" href="<?php echo esc_url(home_url('/')); ?>"><span class="brand-mark" aria-hidden="true"><i></i><i></i></span>toKraft</a><p class="footer-intro">Production-ready 3D printing for prototypes, parts and products. Made for the real world.</p></div>
            <div class="footer-group"><h4>Services</h4><a href="<?php echo esc_url(home_url('/quote/')); ?>">Request a quote</a><a href="<?php echo esc_url(home_url('/#materials')); ?>">Material library</a><a href="<?php echo esc_url(home_url('/#equipment')); ?>">Equipment</a></div>
            <div class="footer-group"><h4>Shop</h4><a href="<?php echo esc_url(home_url('/shop/')); ?>">All products</a><a href="<?php echo esc_url(home_url('/cart/')); ?>">Your cart</a><a href="#">Shipping & returns</a></div>
            <div class="footer-group"><h4>Contact</h4><a href="mailto:hello@tokraft.example">hello@tokraft.example</a><a href="#">Business accounts</a><a href="#">Terms & disclaimer</a></div>
        </div>
        <div class="footer-bottom">&copy; <?php echo esc_html(wp_date('Y')); ?> toKraft. Prices and production times are estimates until a file review is complete.</div>
    </footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
