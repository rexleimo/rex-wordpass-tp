<?php
/**
 * Template Name: Studio
 * Manufacturing-first studio page: equipment, workflow, team and quality standards.
 */
get_header();
?>
<section class="page-hero">
    <div class="page-hero-inner">
        <div class="eyebrow">Studio</div>
        <h1>Where your parts are made.</h1>
        <p>Two print farms, one inspection bench, and a workflow built around repeatability. From first prototype to short-run production, every part is tracked and checked before it leaves.</p>
    </div>
</section>

<section class="section studio-equipment">
    <div class="section-inner">
        <div class="section-head">
            <span class="eyebrow">Equipment</span>
            <h2>Print capacity</h2>
            <p>FDM, SLA and resin workflows scaled to the part, not the project size.</p>
        </div>
        <div class="equipment-grid">
            <article class="equipment-card">
                <h3>Bambu Lab H2D</h3>
                <p>Dual-toolhead FDM for large engineering parts, soluble supports and multi-material assemblies.</p>
                <ul class="spec-list">
                    <li><span>Build volume</span><strong>350 × 350 × 400 mm</strong></li>
                    <li><span>Layer height</span><strong>0.08 – 0.30 mm</strong></li>
                    <li><span>Materials</span><strong>PLA, PETG, ASA, TPU, PA-CF</strong></li>
                </ul>
            </article>
            <article class="equipment-card">
                <h3>Bambu Lab H2S</h3>
                <p>High-throughput FDM workhorse tuned for repeatable short runs and quick-turn prototypes.</p>
                <ul class="spec-list">
                    <li><span>Build volume</span><strong>256 × 256 × 256 mm</strong></li>
                    <li><span>Layer height</span><strong>0.10 – 0.28 mm</strong></li>
                    <li><span>Materials</span><strong>PLA, PETG, ASA, TPU</strong></li>
                </ul>
            </article>
            <article class="equipment-card is-wide">
                <h3>Post-processing & inspection</h3>
                <p>Every order is measured, photographed and packed with a traveler card that ties the part back to its print settings.</p>
                <ul class="spec-list">
                    <li><span>QC tools</span><strong>Digital calipers, surface plate, macro inspection</strong></li>
                    <li><span>Finishing</span><strong>Vapor smoothing, annealing, support removal</strong></li>
                    <li><span>Packaging</span><strong>Anti-static bags, custom foam, fragiles marked</strong></li>
                </ul>
            </article>
        </div>
    </div>
</section>

<section class="section studio-process">
    <div class="section-inner">
        <div class="section-head">
            <span class="eyebrow">Workflow</span>
            <h2>From file to finished part</h2>
        </div>
        <ol class="process-steps">
            <li>
                <span class="step-number">01</span>
                <h3>Upload</h3>
                <p>Drop an STL, 3MF, STEP or OBJ into the quote form. We accept meshes and solid models.</p>
            </li>
            <li>
                <span class="step-number">02</span>
                <h3>Review</h3>
                <p>We check orientation, wall thickness, overhangs and tolerances before printing.</p>
            </li>
            <li>
                <span class="step-number">03</span>
                <h3>Print</h3>
                <p>Parts are scheduled to the right machine and tracked through each layer.</p>
            </li>
            <li>
                <span class="step-number">04</span>
                <h3>Inspect</h3>
                <p>Dimensional checks and surface photos before packaging with a traveler card.</p>
            </li>
            <li>
                <span class="step-number">05</span>
                <h3>Ship</h3>
                <p>Tracked, insured, packed for the part. Rush options available at quote time.</p>
            </li>
        </ol>
    </div>
</section>

<section class="section studio-cta">
    <div class="section-inner">
        <div class="cta-card">
            <div class="cta-copy">
                <span class="eyebrow">Next step</span>
                <h2>See what we can make for you.</h2>
                <p>Upload a model and we will return a manufacturability review with a firm quote in one business day.</p>
            </div>
            <div class="cta-actions">
                <a class="btn btn-primary" href="<?php echo esc_url(home_url('/quote/')); ?>">Request a quote <span aria-hidden="true">→</span></a>
                <a class="btn btn-ghost" href="<?php echo esc_url(home_url('/materials/')); ?>">Browse materials</a>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
