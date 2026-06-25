<footer class="site-footer">
    <div class="footer-inner">
        <span class="footer-copy">&copy; <?php echo date( 'Y' ); ?> Benevolent and Protective Order of Elks Lodge #17. All rights reserved.</span>
        <div class="footer-right">
            <?php denver17_social_links( 'footer' ); ?>
            <?php
            wp_nav_menu( [
                'theme_location' => 'footer',
                'container'      => false,
                'items_wrap'     => '<ul class="footer-nav">%3$s</ul>',
                'fallback_cb'    => false,
            ] );
            ?>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
