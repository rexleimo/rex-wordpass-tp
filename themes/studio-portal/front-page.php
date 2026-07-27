<?php
if (!defined('ABSPATH')) {
    exit;
}

$portal_query = new WP_Query(array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 14,
    'ignore_sticky_posts' => true,
    'meta_key' => '_studio_portal_demo_content',
    'meta_value' => '1',
));

$portal_posts = $portal_query->posts;
if (count($portal_posts) < 4) {
    $portal_query = new WP_Query(array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 14,
        'ignore_sticky_posts' => false,
    ));
    $portal_posts = $portal_query->posts;
}
$lead_post = $portal_posts[0] ?? null;
$side_posts = array_slice($portal_posts, 1, 2);
$brief_posts = array_slice($portal_posts, 3, 4);
$latest_posts = array_slice($portal_posts, 1, 7);
$self_hosted_post = null;

foreach ($portal_posts as $candidate) {
    $haystack = get_the_title($candidate) . ' ' . implode(' ', wp_get_post_categories($candidate->ID, array('fields' => 'names')));
    if (preg_match('/self.?host|自托管|开源|部署|本地模型|ollama/i', $haystack)) {
        $self_hosted_post = $candidate;
        break;
    }
}

if (!$self_hosted_post) {
    $self_hosted_post = $portal_posts[4] ?? $lead_post;
}

$portal_post_ids = wp_list_pluck($portal_posts, 'ID');
$category_terms = $portal_post_ids ? wp_get_object_terms($portal_post_ids, 'category', array(
    'orderby' => 'count',
    'order' => 'DESC',
)) : array();
$category_terms = is_wp_error($category_terms) ? array() : $category_terms;

$fallback_channels = array(
    array('AI 编程', 'AI 工具、工作流与实战方法'),
    array('Agent', '架构、记忆、工具与评测'),
    array('模型与工具', '模型选择、更新与深度解读'),
    array('开源与自托管', '部署、隐私与基础设施'),
    array('工程实践', '来自真实项目的一线经验'),
);

$channels = array();
foreach ($category_terms as $term) {
    $url = get_term_link($term);
    if (is_wp_error($url)) {
        continue;
    }

    $channels[] = array(
        'name' => $term->name,
        'description' => $term->description ?: sprintf('持续追踪 %s 的趋势、工具与工程实践。', $term->name),
        'count' => (int) $term->count,
        'url' => $url,
    );
}

foreach ($fallback_channels as $fallback) {
    if (count($channels) >= 6) {
        break;
    }

    $exists = false;
    foreach ($channels as $channel) {
        if ($channel['name'] === $fallback[0]) {
            $exists = true;
            break;
        }
    }

    if (!$exists) {
        $channels[] = array(
            'name' => $fallback[0],
            'description' => $fallback[1],
            'count' => 0,
            'url' => home_url('/journal/'),
        );
    }
}

$post_category = static function (WP_Post $post): string {
    $categories = get_the_category($post->ID);
    return $categories ? $categories[0]->name : '深度文章';
};

$post_image = static function (WP_Post $post, string $size = 'large', string $class = '') use ($post_category): void {
    if (has_post_thumbnail($post)) {
        echo get_the_post_thumbnail($post, $size, array(
            'class' => $class,
            'loading' => 'lazy',
            'decoding' => 'async',
            'alt' => get_the_title($post),
        ));
        return;
    }

    printf(
        '<span class="sp-image-fallback %s" role="img" aria-label="%s"><small>%s</small><strong>%s</strong></span>',
        esc_attr($class),
        esc_attr(get_the_title($post)),
        esc_html($post_category($post)),
        esc_html(wp_html_excerpt(get_the_title($post), 18, '...'))
    );
};

get_header();
?>

<nav class="sp-channel-bar" aria-label="内容频道">
    <div class="sp-container sp-channel-scroll">
        <span class="sp-channel-label">频道</span>
        <?php foreach (array_slice($channels, 0, 6) as $channel) : ?>
            <a href="<?php echo esc_url($channel['url']); ?>"><?php echo esc_html($channel['name']); ?></a>
        <?php endforeach; ?>
        <a class="sp-channel-all" href="<?php echo esc_url(home_url('/journal/')); ?>">全部文章 <span aria-hidden="true">-></span></a>
    </div>
</nav>

<section class="sp-news-hero sp-container" aria-labelledby="sp-home-title">
    <div class="sp-hero-heading">
        <div>
            <p class="sp-eyebrow"><span></span> AI 工程资讯与实践</p>
            <h1 id="sp-home-title">看见变化，<br>理解技术背后的选择。</h1>
        </div>
        <p>面向中文开发者的独立技术内容站。我们关注 AI Agent、模型工具、开源部署和真实工程实践，不追逐噪声，只提供值得保存的判断。</p>
    </div>

    <?php if ($lead_post instanceof WP_Post) : ?>
        <article class="sp-lead-story">
            <a class="sp-lead-image" href="<?php echo esc_url(get_permalink($lead_post)); ?>">
                <?php $post_image($lead_post, 'large', 'sp-cover-image'); ?>
                <span class="sp-image-badge">今日头条</span>
            </a>
            <div class="sp-lead-copy">
                <div class="sp-story-meta">
                    <a href="<?php echo esc_url(get_permalink($lead_post)); ?>"><?php echo esc_html($post_category($lead_post)); ?></a>
                    <time datetime="<?php echo esc_attr(get_the_date('c', $lead_post)); ?>"><?php echo esc_html(get_the_date('Y.m.d', $lead_post)); ?></time>
                    <span><?php echo esc_html(studio_portal_reading_time($lead_post->ID)); ?> 分钟阅读</span>
                </div>
                <h2><a href="<?php echo esc_url(get_permalink($lead_post)); ?>"><?php echo esc_html(get_the_title($lead_post)); ?></a></h2>
                <p><?php echo esc_html(wp_trim_words(get_the_excerpt($lead_post), 42)); ?></p>
                <a class="sp-arrow-link" href="<?php echo esc_url(get_permalink($lead_post)); ?>">阅读全文 <span aria-hidden="true">-></span></a>
            </div>
        </article>
    <?php endif; ?>

    <div class="sp-hero-side">
        <?php foreach ($side_posts as $index => $side_post) : ?>
            <article class="sp-side-story">
                <a class="sp-side-image" href="<?php echo esc_url(get_permalink($side_post)); ?>">
                    <?php $post_image($side_post, 'medium_large', 'sp-cover-image'); ?>
                </a>
                <div class="sp-story-meta">
                    <span><?php echo esc_html($post_category($side_post)); ?></span>
                    <time datetime="<?php echo esc_attr(get_the_date('c', $side_post)); ?>"><?php echo esc_html(get_the_date('m.d', $side_post)); ?></time>
                </div>
                <h2><a href="<?php echo esc_url(get_permalink($side_post)); ?>"><?php echo esc_html(get_the_title($side_post)); ?></a></h2>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($brief_posts) : ?>
<section class="sp-briefing">
    <div class="sp-container sp-briefing-grid">
        <header>
            <span class="sp-live-dot" aria-hidden="true"></span>
            <div><strong>编辑快讯</strong><small>快速了解今天值得关注的变化</small></div>
        </header>
        <div class="sp-brief-list">
            <?php foreach ($brief_posts as $brief_post) : ?>
                <a href="<?php echo esc_url(get_permalink($brief_post)); ?>">
                    <time datetime="<?php echo esc_attr(get_the_date('c', $brief_post)); ?>"><?php echo esc_html(get_the_date('H:i', $brief_post)); ?></time>
                    <span><?php echo esc_html(get_the_title($brief_post)); ?></span>
                    <b aria-hidden="true">-></b>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="sp-content-section sp-container" id="latest">
    <header class="sp-editorial-heading">
        <div>
            <p class="sp-eyebrow"><span></span> 最新更新</p>
            <h2>今天，值得读什么</h2>
        </div>
        <a class="sp-arrow-link" href="<?php echo esc_url(home_url('/journal/')); ?>">查看全部文章 <span aria-hidden="true">-></span></a>
    </header>

    <div class="sp-feed-layout">
        <div class="sp-article-feed">
            <?php foreach ($latest_posts as $latest_post) : ?>
                <article class="sp-feed-card">
                    <a class="sp-feed-image" href="<?php echo esc_url(get_permalink($latest_post)); ?>">
                        <?php $post_image($latest_post, 'medium_large', 'sp-cover-image'); ?>
                    </a>
                    <div class="sp-feed-copy">
                        <div class="sp-story-meta">
                            <span><?php echo esc_html($post_category($latest_post)); ?></span>
                            <time datetime="<?php echo esc_attr(get_the_date('c', $latest_post)); ?>"><?php echo esc_html(get_the_date('Y.m.d', $latest_post)); ?></time>
                        </div>
                        <h3><a href="<?php echo esc_url(get_permalink($latest_post)); ?>"><?php echo esc_html(get_the_title($latest_post)); ?></a></h3>
                        <p><?php echo esc_html(wp_trim_words(get_the_excerpt($latest_post), 30)); ?></p>
                        <div class="sp-feed-footer">
                            <span><?php echo esc_html(studio_portal_reading_time($latest_post->ID)); ?> 分钟阅读</span>
                            <a href="<?php echo esc_url(get_permalink($latest_post)); ?>" aria-label="阅读：<?php echo esc_attr(get_the_title($latest_post)); ?>">阅读文章 -></a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>

            <?php if (!$latest_posts) : ?>
                <div class="sp-empty-state">
                    <h3>内容正在整理中</h3>
                    <p>新的深度文章会在完成验证后发布。</p>
                </div>
            <?php endif; ?>
        </div>

        <aside class="sp-feed-sidebar" aria-label="热门内容与专题">
            <section class="sp-rank-panel">
                <div class="sp-panel-title"><strong>本周热门</strong><span>POPULAR</span></div>
                <ol>
                    <?php foreach (array_slice($portal_posts, 0, 5) as $rank => $popular_post) : ?>
                        <li>
                            <span><?php echo esc_html(str_pad((string) ($rank + 1), 2, '0', STR_PAD_LEFT)); ?></span>
                            <div>
                                <a href="<?php echo esc_url(get_permalink($popular_post)); ?>"><?php echo esc_html(get_the_title($popular_post)); ?></a>
                                <small><?php echo esc_html($post_category($popular_post)); ?> · <?php echo esc_html(studio_portal_reading_time($popular_post->ID)); ?> 分钟</small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </section>

            <section class="sp-start-panel">
                <span>新读者入口</span>
                <h3>第一次来到这里？</h3>
                <p>从基础概念、工具选择到生产实践，按主题找到最适合你的阅读路径。</p>
                <a href="<?php echo esc_url(home_url('/services/')); ?>">浏览全部主题 -></a>
            </section>
        </aside>
    </div>
</section>

<?php if ($self_hosted_post instanceof WP_Post) : ?>
<section class="sp-self-hosted">
    <div class="sp-container sp-self-hosted-grid">
        <div class="sp-self-hosted-copy">
            <p class="sp-eyebrow"><span></span> 开源与自托管专题</p>
            <h2>把选择权，<br>重新放回自己手里。</h2>
            <p>从本地模型、数据隐私到可观测与部署成本，我们把“能跑起来”继续追问到“能长期维护”。这里不只是安装教程，而是一套面向真实使用的自托管判断框架。</p>
            <div class="sp-self-hosted-actions">
                <a class="sp-button sp-button-light" href="<?php echo esc_url(get_permalink($self_hosted_post)); ?>">阅读专题文章</a>
                <a class="sp-plain-link" href="<?php echo esc_url(home_url('/journal/')); ?>">查看开源内容 -></a>
            </div>
            <dl>
                <div><dt>01</dt><dd>部署与迁移</dd></div>
                <div><dt>02</dt><dd>隐私与权限</dd></div>
                <div><dt>03</dt><dd>性能与成本</dd></div>
            </dl>
        </div>
        <a class="sp-self-hosted-feature" href="<?php echo esc_url(get_permalink($self_hosted_post)); ?>">
            <?php $post_image($self_hosted_post, 'large', 'sp-cover-image'); ?>
            <span class="sp-feature-overlay">
                <small><?php echo esc_html($post_category($self_hosted_post)); ?> · FEATURED</small>
                <strong><?php echo esc_html(get_the_title($self_hosted_post)); ?></strong>
                <b>打开文章 -></b>
            </span>
        </a>
    </div>
</section>
<?php endif; ?>

<section class="sp-topic-directory sp-container" id="topics">
    <header class="sp-editorial-heading">
        <div>
            <p class="sp-eyebrow"><span></span> 内容分类</p>
            <h2>按兴趣，继续探索</h2>
        </div>
        <p>像翻阅一本持续更新的技术杂志，找到你真正关心的那一栏。</p>
    </header>
    <div class="sp-topic-cards">
        <?php foreach (array_slice($channels, 0, 6) as $index => $channel) : ?>
            <a href="<?php echo esc_url($channel['url']); ?>">
                <span><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span>
                <h3><?php echo esc_html($channel['name']); ?></h3>
                <p><?php echo esc_html($channel['description']); ?></p>
                <div><small><?php echo $channel['count'] ? esc_html($channel['count'] . ' 篇文章') : '进入频道'; ?></small><b aria-hidden="true">-></b></div>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="sp-home-newsletter">
    <div class="sp-container">
        <div>
            <p class="sp-eyebrow"><span></span> REXAI EDITORIAL</p>
            <h2>少一点信息焦虑，<br>多一点可靠判断。</h2>
        </div>
        <div>
            <p>我们持续整理 AI 工程领域值得阅读、值得验证、也值得反复查阅的内容。</p>
            <a class="sp-button sp-button-accent" href="<?php echo esc_url(home_url('/journal/')); ?>">开始阅读</a>
        </div>
    </div>
</section>

<?php
wp_reset_postdata();
get_footer();
