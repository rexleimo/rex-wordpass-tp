<?php
/**
 * Order received / thank you — Pencil node VhtZy.
 *
 * @var WC_Order $order
 */
defined('ABSPATH') || exit;

if (!$order) {
    return;
}

$order_number = $order->get_order_number();
$is_paid = $order->is_paid();
$payment_method = $order->get_payment_method_title();
?>
<section class="tk-order-received">
    <div class="tk-order-announce">
        ORDER RECEIVED  /  CHECK YOUR EMAIL FOR PAYMENT DETAILS  /  PRODUCTION BEGINS AFTER CONFIRMATION
    </div>

    <div class="tk-order-shell">
        <div class="tk-order-head">
            <div>
                <p class="tk-shop-breadcrumb">
                    <a href="<?php echo esc_url(home_url('/')); ?>">HOME</a>
                    <span>/</span>
                    <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">SHOP</a>
                    <span>/</span>
                    <span>ORDER RECEIVED</span>
                </p>
                <?php if ($order->has_status('failed')) : ?>
                    <h1><?php esc_html_e('Payment failed. Please try again.', 'tokraft'); ?></h1>
                    <p><?php esc_html_e('Your order is still saved. Use the payment button below or contact us with your order number.', 'tokraft'); ?></p>
                <?php else : ?>
                    <h1><?php esc_html_e('Order received. Clear next steps.', 'tokraft'); ?></h1>
                    <p>
                        <?php
                        printf(
                            /* translators: %s email */
                            esc_html__('We saved your order and sent confirmation to %s. Complete payment and we will begin fulfilment.', 'tokraft'),
                            esc_html($order->get_billing_email())
                        );
                        ?>
                    </p>
                <?php endif; ?>
            </div>
            <div class="tk-order-progress" aria-label="<?php esc_attr_e('Checkout progress', 'tokraft'); ?>">
                <span>01 CART</span>
                <span class="line"></span>
                <span class="is-active">02 ORDER</span>
                <span class="line"></span>
                <span><?php echo $is_paid ? '03 PAID' : '03 PAID'; ?></span>
            </div>
        </div>

        <div class="tk-order-grid">
            <div class="tk-order-main">
                <div class="tk-order-panel">
                    <div class="tk-order-panel-label">
                        <?php
                        printf(
                            esc_html__('ORDER %s  /  %s', 'tokraft'),
                            esc_html($order_number),
                            esc_html(sprintf(_n('%d ITEM', '%d ITEMS', $order->get_item_count(), 'tokraft'), $order->get_item_count()))
                        );
                        ?>
                    </div>
                    <?php foreach ($order->get_items() as $item_id => $item) :
                        $product = $item->get_product();
                        $thumb = $product ? $product->get_image('thumbnail') : '';
                        $meta = wc_display_item_meta($item, array('echo' => false));
                        ?>
                        <div class="tk-order-item">
                            <div><?php echo $thumb ? $thumb : ''; ?></div>
                            <div>
                                <div class="meta"><?php echo esc_html(tokraft_uppercase($product ? implode(' / ', wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'names'))) : 'PRODUCT')); ?></div>
                                <h3><?php echo esc_html($item->get_name()); ?></h3>
                                <div class="meta">
                                    <?php
                                    echo esc_html(sprintf(__('Qty %s', 'tokraft'), $item->get_quantity()));
                                    if ($meta) {
                                        echo ' · ';
                                        echo wp_kses_post(wp_strip_all_tags($meta));
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="price"><?php echo wp_kses_post($order->get_formatted_line_subtotal($item)); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="tk-order-panel" style="margin-top:18px">
                    <div class="tk-order-panel-label" style="display:flex;align-items:center;gap:8px">
                        <span aria-hidden="true">☰</span> <?php esc_html_e('What happens next', 'tokraft'); ?>
                    </div>
                    <p style="margin:0 0 8px;color:var(--ink-soft);font-size:12px"><?php esc_html_e('These updates are also sent to your email.', 'tokraft'); ?></p>
                    <div class="tk-order-steps">
                        <div class="tk-order-step">
                            <span>01 / CONFIRMATION</span>
                            <strong><?php esc_html_e('Order receipt sent to your email', 'tokraft'); ?></strong>
                        </div>
                        <div class="tk-order-step">
                            <span>02 / PAYMENT</span>
                            <strong>
                                <?php
                                printf(
                                    esc_html__('Use reference %s', 'tokraft'),
                                    esc_html($order_number)
                                );
                                ?>
                            </strong>
                        </div>
                        <div class="tk-order-step">
                            <span>03 / FULFILMENT</span>
                            <strong><?php echo $is_paid ? esc_html__('Payment received — preparing order', 'tokraft') : esc_html__('Starts after payment', 'tokraft'); ?></strong>
                        </div>
                        <div class="tk-order-step">
                            <span>04 / DELIVERY</span>
                            <strong><?php esc_html_e('Tracking is emailed once your order ships', 'tokraft'); ?></strong>
                        </div>
                    </div>
                    <div class="tk-order-callout">
                        <?php esc_html_e('Save the email receipt — it contains your order number, payment instructions and status details.', 'tokraft'); ?>
                    </div>
                </div>

                <div class="tk-order-mfg">
                    <h2><?php esc_html_e('Your order is queued after payment.', 'tokraft'); ?></h2>
                    <p><?php esc_html_e('Once payment is confirmed, we prepare the selected items and send fulfilment and shipping updates by email.', 'tokraft'); ?></p>
                </div>
            </div>

            <aside class="tk-order-panel">
                <div class="tk-order-panel-label">
                    <?php printf(esc_html__('ORDER SUMMARY  /  %s', 'tokraft'), esc_html($order_number)); ?>
                </div>
                <div class="tk-order-summary-rows">
                    <div><span><?php esc_html_e('Items', 'tokraft'); ?></span><span><?php echo wp_kses_post($order->get_subtotal_to_display()); ?></span></div>
                    <div><span><?php esc_html_e('Shipping', 'tokraft'); ?></span><span><?php echo wp_kses_post($order->get_shipping_to_display()); ?></span></div>
                    <?php foreach ($order->get_tax_totals() as $tax) : ?>
                        <div><span><?php echo esc_html($tax->label); ?></span><span><?php echo wp_kses_post($tax->formatted_amount); ?></span></div>
                    <?php endforeach; ?>
                </div>
                <div class="tk-order-total">
                    <span><?php echo $is_paid ? esc_html__('Amount paid', 'tokraft') : esc_html__('Amount due', 'tokraft'); ?></span>
                    <strong><?php echo wp_kses_post($order->get_formatted_order_total()); ?></strong>
                </div>
                <p style="margin:0 0 12px;color:var(--ink-soft);font-size:10px"><?php esc_html_e('Shipping and tax are included when calculated.', 'tokraft'); ?></p>

                <div class="tk-order-panel-label"><?php esc_html_e('SELECTED PAYMENT', 'tokraft'); ?></div>
                <div class="tk-order-payment">
                    <h4><?php echo esc_html($payment_method ?: __('Payment pending', 'tokraft')); ?></h4>
                    <p>
                        <?php
                        printf(
                            esc_html__('Reference: %s. Instructions were sent to your email.', 'tokraft'),
                            esc_html($order_number)
                        );
                        ?>
                    </p>
                </div>

                <div class="tk-order-actions">
                    <?php if ($order->needs_payment()) : ?>
                        <a class="btn btn-primary" href="<?php echo esc_url($order->get_checkout_payment_url()); ?>">
                            <?php esc_html_e('Pay for this order', 'tokraft'); ?>
                        </a>
                    <?php endif; ?>
                    <button type="button" class="btn btn-ghost" data-copy-order="<?php echo esc_attr($order_number); ?>">
                        <?php printf(esc_html__('Copy transfer reference: %s', 'tokraft'), esc_html($order_number)); ?>
                    </button>
                    <a class="btn btn-ghost" href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>">
                        <?php esc_html_e('Continue shopping', 'tokraft'); ?>
                    </a>
                </div>
            </aside>
        </div>

        <div class="tk-order-trust">
            <article>
                <h3><?php esc_html_e('Receipt sent', 'tokraft'); ?></h3>
                <p><?php esc_html_e('Your order number and line items are in your email.', 'tokraft'); ?></p>
            </article>
            <article>
                <h3><?php esc_html_e('Payment referenced', 'tokraft'); ?></h3>
                <p><?php printf(esc_html__('Use %s so your transfer is matched to this order.', 'tokraft'), esc_html($order_number)); ?></p>
            </article>
            <article>
                <h3><?php esc_html_e('Fulfilment updated', 'tokraft'); ?></h3>
                <p><?php esc_html_e('We email again when the order is prepared and ready to ship.', 'tokraft'); ?></p>
            </article>
        </div>
    </div>
</section>
