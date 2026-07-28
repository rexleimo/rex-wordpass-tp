<?php
/**
 * Template Name: Contact
 * General contact page with form and studio details.
 */
get_header();
$sent = isset($_GET['contact_sent']);
$errors = isset($_GET['contact_error']) ? explode(',', sanitize_text_field(wp_unslash($_GET['contact_error']))) : array();
$field_error = function($key) use ($errors) {
    return in_array($key, $errors, true) ? ' is-error' : '';
};
$air_error = isset($_GET['air_error']);
?>
<section class="page-hero page-hero--compact">
    <div class="page-hero-inner">
        <div class="eyebrow">Contact</div>
        <h1>Get in touch.</h1>
        <p>Questions about a quote, a material, or a production run? Send us a message and we will reply within one business day.</p>
    </div>
</section>

<section class="section contact-section">
    <div class="section-inner contact-layout">
        <div class="contact-info">
            <div class="contact-block">
                <h2>Studio</h2>
                <p>toKraft Manufacturing<br>123 Example Street, Unit 400<br>Toronto, ON M5A 1A1</p>
                <p><a href="mailto:hello@tokraft.ca">hello@tokraft.ca</a><br><a href="tel:+14165551234">+1 (416) 555-1234</a></p>
            </div>
            <div class="contact-block">
                <h2>Hours</h2>
                <p>Monday – Friday<br>09:00 – 18:00 EST</p>
                <p class="text-soft">Production runs 24/5. Rush quotes are answered outside business hours when possible.</p>
            </div>
            <div class="contact-block">
                <h2>Quick links</h2>
                <p><a href="<?php echo esc_url(home_url('/quote/')); ?>">Request a quote</a></p>
                <p><a href="<?php echo esc_url(home_url('/materials/')); ?>">Material library</a></p>
                <p><a href="<?php echo esc_url(get_post_type_archive_link('tokraft_case_study') ?: home_url('/case-studies/')); ?>">Case studies</a></p>
            </div>
        </div>

        <form class="contact-form air-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" novalidate>
            <input type="hidden" name="action" value="tokraft_contact">
            <?php wp_nonce_field('tokraft_contact', 'tokraft_contact_nonce'); ?>

            <?php if ($sent) : ?>
                <div class="notice notice-success" role="status">
                    Thanks for reaching out. We have received your message and will reply within one business day.
                </div>
            <?php endif; ?>

            <?php if ($errors || $air_error) : ?>
                <div class="notice notice-error" role="alert">
                    <strong>Could not send your message.</strong> Please check the marked fields below and try again.
                </div>
            <?php endif; ?>

            <div class="form-row">
                <div class="field<?php echo esc_attr($field_error('name')); ?>">
                    <label for="contact-name">Name <span aria-hidden="true">*</span></label>
                    <input id="contact-name" name="name" type="text" autocomplete="name" required aria-required="true" aria-describedby="name-error">
                    <span id="name-error" class="field-error" aria-live="polite">Please enter your name.</span>
                </div>
                <div class="field<?php echo esc_attr($field_error('email')); ?>">
                    <label for="contact-email">Email <span aria-hidden="true">*</span></label>
                    <input id="contact-email" name="email" type="email" autocomplete="email" required aria-required="true" aria-describedby="email-error">
                    <span id="email-error" class="field-error" aria-live="polite">Please enter a valid email address.</span>
                </div>
            </div>

            <div class="field<?php echo esc_attr($field_error('subject')); ?>">
                <label for="contact-subject">Subject</label>
                <input id="contact-subject" name="subject" type="text" autocomplete="off">
            </div>

            <div class="field<?php echo esc_attr($field_error('message')); ?>">
                <label for="contact-message">Message <span aria-hidden="true">*</span></label>
                <textarea id="contact-message" name="message" rows="6" required aria-required="true" aria-describedby="message-error"></textarea>
                <span id="message-error" class="field-error" aria-live="polite">Please enter a message.</span>
            </div>

            <div class="field field-honeypot" aria-hidden="true">
                <label for="contact-air-confirm">Leave this empty</label>
                <input id="contact-air-confirm" type="text" name="air_confirm" tabindex="-1" autocomplete="off">
            </div>

            <button class="btn btn-primary" type="submit">Send message <span aria-hidden="true">→</span></button>
        </form>
    </div>
</section>

<?php get_footer(); ?>
