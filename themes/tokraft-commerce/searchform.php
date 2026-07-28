<?php
/**
 * Shared search form for the header dialog and search results page.
 */
?>
<form role="search" method="get" class="tokraft-search-form" action="<?php echo esc_url(home_url('/')); ?>">
    <label>
        <span class="screen-reader-text"><?php esc_html_e('Search for:', 'tokraft'); ?></span>
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"></circle><path d="M21 21l-4.3-4.3"></path></svg>
        <input type="search" class="search-field" name="s" value="<?php echo esc_attr(get_search_query()); ?>" placeholder="<?php esc_attr_e('Search products, materials, guides...', 'tokraft'); ?>" enterkeyhint="search" required>
    </label>
    <button class="btn btn-primary" type="submit"><?php esc_html_e('Search', 'tokraft'); ?></button>
</form>
