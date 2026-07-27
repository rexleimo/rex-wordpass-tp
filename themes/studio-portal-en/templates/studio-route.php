<?php
if (!defined('ABSPATH')) {
    exit;
}

$route = (string) get_query_var('studio_portal_en_route');
$projects = studio_portal_en_projects();
$graphic_base = get_template_directory_uri() . '/assets/graphics/';
$titles = array(
    'work' => array('[01] ARCHIVE', 'Selected work.', 'Brand systems, product surfaces and protocol interfaces for teams shipping ambitious products.'),
    'services' => array('[02] CAPABILITIES', 'What we ship.', 'Four connected practice areas. One visual system. We staff the team around the problem.'),
    'studio' => array('[03] THE STUDIO', "Small team.\nSerious systems.", 'A senior group working across brand, interface and implementation. Fast decisions, tight systems, no handoff guessing.'),
    'process' => array('[04] HOW WE WORK', "From brief to\nlaunch.", 'No surprise handoffs. Each phase ships something usable and locks the next scope.'),
    'journal' => array('[05] JOURNAL', "Notes on design,\nsystems and product.", 'Practical essays from the workbench, written by the people doing the work.'),
    'contact' => array('[06] START A PROJECT', "Tell us what\nyou're building.", 'Typical reply in 48 hours. Attach a deck if you have one; we read it before the call.'),
);

if ('case' === $route) {
    $slug = sanitize_key((string) get_query_var('studio_portal_en_case'));
    $project = $projects[$slug] ?? reset($projects);
    get_header();
    ?>
    <article class="en-case-page">
        <header class="en-case-hero en-container">
            <div class="en-breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">HOME</a> / <a href="<?php echo esc_url(home_url('/work/')); ?>">WORK</a> / <?php echo esc_html($project['title']); ?></div>
            <p class="en-kicker">CASE / <?php echo esc_html($project['year']); ?> / <?php echo esc_html($project['discipline']); ?></p>
            <div class="en-case-title" data-hero-item><h1><?php echo esc_html($project['title']); ?></h1><p><?php echo esc_html($project['summary']); ?></p></div>
            <div class="en-case-data" data-stagger><p><span>ROLE</span><strong><?php echo esc_html($project['discipline']); ?></strong></p><p><span>TIMELINE</span><strong>14 WEEKS</strong></p><p><span>TEAM</span><strong>4 DESIGN · 2 ENG</strong></p><p><span>RESULT</span><strong><?php echo esc_html($project['metric']); ?></strong></p></div>
            <figure class="en-figure en-case-cover" data-media><img src="<?php echo esc_url($project['image']); ?>" alt="Abstract product interface created for <?php echo esc_attr($project['title']); ?>" width="1600" height="900"><figcaption><span>FIG.01 — PRODUCT SYSTEM / PRIMARY VIEW</span><span>SCROLL TO STORY ↓</span></figcaption></figure>
        </header>
        <section class="en-section en-container en-case-story">
            <aside><span>[01]</span><strong>THE BRIEF</strong><a href="#outcome">02 / OUTCOME</a><a href="#system">03 / SYSTEM</a></aside>
            <div data-reveal><h2>A technical product needed a surface people could trust before they understood every detail.</h2><p>The existing experience exposed implementation complexity at every turn. Brand, marketing and product were treated as separate tracks, which made each release feel like a different company.</p><p>We started with user states and the language around irreversible actions. That map became the base for identity, interface components and motion behavior, giving every team one shared grammar.</p><blockquote>“Clarity is not simplification. It is the visible structure behind a complex decision.”</blockquote></div>
        </section>
        <section class="en-case-outcome" id="outcome"><div class="en-container" data-stagger><p><strong><?php echo esc_html($project['metric']); ?></strong><span>PRIMARY OUTCOME</span></p><p><strong>9 MIN</strong><span>MEDIAN ONBOARD</span></p><p><strong>46</strong><span>SHARED COMPONENTS</span></p></div></section>
        <section class="en-section en-container" id="system"><header class="en-section-head"><p class="en-kicker">[03] THE SYSTEM</p><h2>One language.<br>Every surface.</h2><p>Product states, content hierarchy and launch assets all use the same underlying token logic.</p></header><div class="en-case-gallery"><figure class="en-figure" data-media><img src="<?php echo esc_url($project['image']); ?>" alt="<?php echo esc_attr($project['title']); ?> interface detail" width="1600" height="900"><figcaption><span>FIG.02 — DESKTOP APP</span><span>DARK STATE</span></figcaption></figure><figure class="en-figure" data-media><img src="<?php echo esc_url($graphic_base . 'safe-grid.svg'); ?>" alt="Interface component system" width="1600" height="900"><figcaption><span>FIG.03 — COMPONENT LAB</span><span>46 UNITS</span></figcaption></figure></div></section>
        <a class="en-next-case" href="<?php echo esc_url(home_url('/work/zora-marketplace/')); ?>"><span>NEXT CASE</span><strong>ZORA MARKETPLACE</strong><b>OPEN →</b></a>
    </article>
    <?php
    get_footer();
    return;
}

$hero = $titles[$route] ?? $titles['work'];
get_header();
?>
<header class="en-page-hero en-container">
    <div class="en-breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">HOME</a> / <?php echo esc_html(strtoupper($route)); ?></div>
    <div class="en-page-hero-grid" data-hero-item><div><p class="en-kicker"><?php echo esc_html($hero[0]); ?></p><h1 class="en-display"><?php echo nl2br(esc_html($hero[1])); ?></h1></div><p><?php echo esc_html($hero[2]); ?></p></div>
</header>

<?php if ('work' === $route) : ?>
    <section class="en-section en-container en-work-archive" data-work-browser>
        <div class="en-filter-bar"><div role="group" aria-label="Filter projects"><button class="is-active" type="button" data-filter="all">ALL</button><button type="button" data-filter="brand">BRAND</button><button type="button" data-filter="product">PRODUCT</button><button type="button" data-filter="protocol">PROTOCOL</button><button type="button" data-filter="systems">SYSTEMS</button></div><label>⌕ <input type="search" placeholder="SEARCH PROJECTS…" aria-label="Search projects" data-work-search></label></div>
        <div class="en-work-table"><div class="en-work-table-head"><span>#</span><span>PREVIEW</span><span>PROJECT</span><span>DISCIPLINE</span><span>YEAR</span><span>→</span></div><?php $index = 0; foreach ($projects as $slug => $project) : $index++; $keywords = strtolower($project['title'] . ' ' . $project['discipline']); ?><a href="<?php echo esc_url(home_url('/work/' . $slug . '/')); ?>" data-project data-keywords="<?php echo esc_attr($keywords); ?>"><span><?php echo esc_html(str_pad((string) $index, 2, '0', STR_PAD_LEFT)); ?></span><span><img src="<?php echo esc_url($project['image']); ?>" alt="" width="220" height="120"></span><strong><?php echo esc_html($project['title']); ?></strong><small><?php echo esc_html($project['discipline']); ?></small><time><?php echo esc_html($project['year']); ?></time><b>↗</b></a><?php endforeach; ?></div>
        <p class="en-filter-status" aria-live="polite"><span data-result-count><?php echo esc_html((string) count($projects)); ?></span> PROJECTS SHOWN</p>
    </section>

<?php elseif ('services' === $route) : ?>
    <section class="en-section en-container">
        <div class="en-service-grid" data-stagger>
            <?php $services = array(array('01', 'BRAND SYSTEMS', 'Identity, type, motion language and launch kits that survive product velocity.', array('Logo system', 'Voice + messaging', 'Launch assets')), array('02', 'PRODUCT UI', 'Dense, keyboard-first interfaces for dashboards, wallets and operator tools.', array('IA + flows', 'Component library', 'Handoff kits')), array('03', 'PROTOCOL UX', 'Onboarding tunnels, wallet states and trust patterns for irreversible actions.', array('Wallet states', 'Risk copy', 'Empty/error sets')), array('04', 'DESIGN SYSTEMS', 'Tokens, components and documentation that keep multi-team shipping aligned.', array('Token map', 'Storybook', 'Contribution rules'))); foreach ($services as $service) : ?>
                <article><span><?php echo esc_html($service[0]); ?></span><h2><?php echo esc_html($service[1]); ?></h2><p><?php echo esc_html($service[2]); ?></p><ul><?php foreach ($service[3] as $item) : ?><li>→ <?php echo esc_html($item); ?></li><?php endforeach; ?></ul><a href="<?php echo esc_url(home_url('/contact/?service=' . sanitize_title($service[1]))); ?>">DISCUSS THIS ↗</a></article>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="en-section en-service-models"><div class="en-container"><header class="en-section-head"><p class="en-kicker">[05] ENGAGEMENT MODELS</p><h2>Choose the shape,<br>not the headcount.</h2><p>We recommend the smallest senior pod that can own the outcome.</p></header><div class="en-model-grid"><article><span>2—4 WEEKS</span><h3>SPRINT</h3><p>One focused surface. Fixed scope. Ideal for decisions and launches.</p><a href="<?php echo esc_url(home_url('/contact/?model=sprint')); ?>">SELECT MODEL →</a></article><article class="is-featured"><span>8—16 WEEKS</span><h3>EMBEDDED</h3><p>Shared backlog and direct engineering pairing. Best for products.</p><a href="<?php echo esc_url(home_url('/contact/?model=embedded')); ?>">RECOMMENDED →</a></article><article><span>MONTHLY</span><h3>RETAINER</h3><p>Ongoing system care, reviews and surge capacity when needed.</p><a href="<?php echo esc_url(home_url('/contact/?model=retainer')); ?>">SELECT MODEL →</a></article></div></div></section>

<?php elseif ('studio' === $route) : ?>
    <section class="en-section en-container"><div class="en-about-intro"><figure class="en-figure" data-media><img src="<?php echo esc_url($graphic_base . 'studio-wall.svg'); ?>" alt="Studio wall with typographic and interface studies" width="1600" height="1100"><figcaption><span>STUDIO / BERLIN MITTE</span><span>THURSDAY CRITIQUE</span></figcaption></figure><div data-stagger><p><strong>18</strong><span>PEOPLE</span></p><p><strong>180+</strong><span>SHIPPED PROJECTS</span></p><p><strong>12</strong><span>COUNTRIES</span></p><p><strong>0</strong><span>ACCOUNT MANAGERS</span></p></div></div></section>
    <section class="en-section en-container"><header class="en-section-head"><p class="en-kicker">HOW WE THINK</p><h2>Systems before<br>decoration.</h2><p>We use a small set of principles to make decisions quickly across disciplines.</p></header><div class="en-principle-grid" data-stagger><article><span>01</span><h3>SYSTEMS FIRST</h3><p>Reusable tokens and components before one-off screens. Every decision pays forward.</p></article><article><span>02</span><h3>PROTOTYPE TRUTH</h3><p>Interactive prototypes early. Static approvals hide the friction that matters.</p></article><article><span>03</span><h3>ENGINEERING PAIRING</h3><p>Designers sit with engineers. Handoff is a conversation, not a file drop.</p></article></div></section>
    <section class="en-section en-team-section"><div class="en-container"><header class="en-section-head"><p class="en-kicker">[TEAM] 04 OF 18</p><h2>The people in<br>the room.</h2><p>A deliberately senior group with deep product and technical fluency.</p></header><div class="en-team-grid" data-stagger><article><span>01</span><div class="en-avatar">MV</div><h3>MARA VOSS</h3><p>FOUNDER / CREATIVE DIRECTOR</p></article><article><span>02</span><div class="en-avatar">JP</div><h3>JUN-HO PARK</h3><p>HEAD OF PRODUCT DESIGN</p></article><article><span>03</span><div class="en-avatar">EK</div><h3>ELIF KAYA</h3><p>PRINCIPAL / PROTOCOL UX</p></article><article><span>04</span><div class="en-avatar">TR</div><h3>TOMAS RIVERA</h3><p>MOTION / 3D LEAD</p></article></div></div></section>

<?php elseif ('process' === $route) : ?>
    <section class="en-section en-container"><div class="en-process-timeline" data-stagger><?php $phases = array(array('01', 'DISCOVER', 'WEEK 1—2', 'Stakeholder interviews, competitive audit and user-state mapping.', 'Scope paper + decision log'), array('02', 'DESIGN', 'WEEK 3—6', 'Flows, visual system and a production-shaped interactive prototype.', 'System library + clickable demo'), array('03', 'SHIP', 'WEEK 7—10', 'Component handoff, engineering pairing, QA and launch assets.', 'Production code + documentation')); foreach ($phases as $phase) : ?><article><span><?php echo esc_html($phase[0]); ?></span><h2><?php echo esc_html($phase[1]); ?></h2><time><?php echo esc_html($phase[2]); ?></time><p><?php echo esc_html($phase[3]); ?></p><strong>OUTPUT: <?php echo esc_html($phase[4]); ?></strong></article><?php endforeach; ?></div></section>
    <section class="en-section en-container"><header class="en-section-head"><p class="en-kicker">WORKING PRINCIPLES</p><h2>Visible work.<br>Fast decisions.</h2><p>The process exists to create momentum, not ceremony.</p></header><div class="en-faq" data-accordion><article class="is-open"><button type="button" aria-expanded="true"><span>01</span><strong>How often do we meet?</strong><b>+</b></button><div><p>One weekly working session, short async reviews and direct access to the senior pod throughout the engagement.</p></div></article><article><button type="button" aria-expanded="false"><span>02</span><strong>Do you write production code?</strong><b>+</b></button><div><p>For web products we can ship component-level code and pair with your team through integration and QA.</p></div></article><article><button type="button" aria-expanded="false"><span>03</span><strong>How fast can we start?</strong><b>+</b></button><div><p>Most projects begin within two to four weeks of a signed scope and a confirmed internal decision maker.</p></div></article></div></section>

<?php elseif ('journal' === $route) : $posts = studio_portal_en_journal_posts(12); ?>
    <section class="en-section en-container"><div class="en-journal-filters"><span>ALL</span><span>DESIGN SYSTEMS</span><span>PROTOCOL UX</span><span>CASE STUDIES</span><span>OPINION</span></div><div class="en-journal-grid" data-stagger><?php if ($posts) : foreach ($posts as $post) : $categories = get_the_category($post->ID); ?><article><div class="en-journal-thumb"><span>NOTE / <?php echo esc_html(studio_portal_en_post_date('y', $post)); ?></span><b>↗</b></div><p class="en-kicker"><?php echo esc_html(($categories ? $categories[0]->name : 'ESSAY') . ' · ' . studio_portal_en_post_date('M d, Y', $post)); ?></p><h2><a href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html(get_the_title($post)); ?></a></h2><p><?php echo esc_html(get_the_excerpt($post)); ?></p><a href="<?php echo esc_url(get_permalink($post)); ?>">BY <?php echo esc_html(get_the_author_meta('display_name', (int) $post->post_author) ?: 'STUDIO EDITORIAL'); ?> →</a></article><?php endforeach; else : ?><div class="en-empty">Publish a post to start the journal.</div><?php endif; ?></div></section>

<?php elseif ('contact' === $route) : ?>
    <section class="en-section en-container en-contact-layout">
        <aside><p class="en-kicker">2 SLOTS OPEN / Q4 2026</p><h2>PROJECT INTAKE</h2><p>Share the product, timeline and current constraint. We will reply with the right next conversation, or a clear no.</p><dl><div><dt>EMAIL</dt><dd><a href="mailto:hello@studio.xyz">hello@studio.xyz</a></dd></div><div><dt>BERLIN</dt><dd>Torstrasse 12</dd></div><div><dt>SEOUL</dt><dd>Seongsu-dong</dd></div><div><dt>REPLY</dt><dd>Within 48 hours</dd></div></dl></aside>
        <div class="en-contact-form-wrap">
            <?php if (isset($_GET['contact_sent'])) : ?><div class="en-form-state is-success">✓ MESSAGE SENT — WE WILL REPLY WITHIN 48 HOURS.</div><?php elseif (isset($_GET['contact_error'])) : ?><div class="en-form-state is-error">✗ CHECK THE REQUIRED FIELDS AND TRY AGAIN.</div><?php endif; ?>
            <form class="en-contact-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post"><input type="hidden" name="action" value="studio_portal_en_contact"><input type="hidden" name="studio_portal_en_contact_nonce" value="<?php echo esc_attr(wp_create_nonce('studio_portal_en_contact')); ?>"><label class="en-honeypot">Website<input type="text" name="studio_portal_en_website" tabindex="-1" autocomplete="off"></label><label><span>NAME *</span><input required type="text" name="name" placeholder="Ada Lovelace" autocomplete="name"></label><label><span>EMAIL *</span><input required type="email" name="email" placeholder="ada@lab.xyz" autocomplete="email"></label><label><span>COMPANY</span><input type="text" name="company" placeholder="Optional" autocomplete="organization"></label><label><span>BUDGET RANGE</span><select name="budget"><option value="">Select…</option><option>$20K—$50K</option><option>$50K—$100K</option><option>$100K—$200K</option><option>$200K+</option></select></label><label class="is-wide"><span>PROJECT NOTES *</span><textarea required name="notes" rows="8" placeholder="What are you shipping, when, and what is currently getting in the way?"></textarea></label><button class="en-button en-button--flame is-wide" type="submit">Send project brief <span>↗</span></button></form><p class="en-form-privacy">By submitting you agree to our privacy policy. We never share your data.</p>
        </div>
    </section>
<?php endif; ?>

<?php get_footer(); ?>
