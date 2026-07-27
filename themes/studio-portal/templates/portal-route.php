<?php
if (!defined('ABSPATH')) {
    exit;
}

$route = get_query_var('studio_portal_route');
$all_posts = studio_portal_content_posts();
$topics = studio_portal_editorial_topics();
$selected_topic = isset($_GET['topic']) ? sanitize_key(wp_unslash($_GET['topic'])) : '';
$journal_posts = 'journal' === $route ? studio_portal_filter_posts_by_topic($all_posts, $selected_topic) : $all_posts;

$route_copy = array(
    'work' => array('SELECTED DIRECTIONS', '让复杂系统，变得可以理解。', '从产品、内容到工程工作流，我们关心的是：如何把难以解释的系统，变成可以判断、可以使用、也可以长期维护的东西。'),
    'services' => array('TOPIC DIRECTORY', '按主题，走进技术背后的选择。', '这里不是标签云，而是一张持续更新的阅读地图。沿着真实问题进入，找到模型、Agent、开源部署与工程实践之间的联系。'),
    'journal' => array('LATEST STORIES', '今天，值得读什么。', '按时间浏览编辑部最新整理的技术观察。我们保留必要的背景、证据和工程边界，让每一篇内容都值得再次查阅。'),
    'about' => array('ABOUT THE EDITORIAL', '我们不追逐噪音，只整理可复用的判断。', '这是一个面向中文开发者的独立技术内容站。英文保留在品牌与栏目眉题里，真正需要被理解的内容，优先用清楚、自然的中文表达。'),
    'contact' => array('CONTACT', '把问题说清楚，往往是解决它的第一步。', '无论是内容合作、选题建议，还是你正在推进的技术项目，都可以从一封结构清楚的来信开始。'),
    'process' => array('READING GUIDES', '从哪里开始，取决于你正在解决什么。', '我们把文章整理成几条可继续走下去的阅读路径。无需从头补课，先找到眼前的问题，再逐步建立完整判断。'),
);
$current = $route_copy[$route] ?? $route_copy['journal'];

$post_category = static function (WP_Post $post): string {
    $categories = get_the_category($post->ID);
    return $categories ? $categories[0]->name : '深度文章';
};

$render_post_card = static function (WP_Post $post, string $class = '') use ($post_category): void {
    ?>
    <article class="sp-inner-card <?php echo esc_attr($class); ?>">
        <a class="sp-inner-card-link" href="<?php echo esc_url(get_permalink($post)); ?>">
            <span class="sp-inner-card-media">
                <?php if (has_post_thumbnail($post)) : ?>
                    <?php echo get_the_post_thumbnail($post, 'medium_large', array('loading' => 'lazy', 'decoding' => 'async', 'alt' => get_the_title($post))); ?>
                <?php else : ?>
                    <span class="sp-inner-card-fallback" role="img" aria-label="<?php echo esc_attr(get_the_title($post)); ?>"><small><?php echo esc_html($post_category($post)); ?></small><b><?php echo esc_html(get_the_date('d', $post)); ?></b></span>
                <?php endif; ?>
            </span>
            <span class="sp-inner-card-meta"><b><?php echo esc_html($post_category($post)); ?></b><time datetime="<?php echo esc_attr(get_the_date('c', $post)); ?>"><?php echo esc_html(get_the_date('Y.m.d', $post)); ?></time></span>
            <strong><?php echo esc_html(get_the_title($post)); ?></strong>
            <span class="sp-inner-card-excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt($post), 30)); ?></span>
            <span class="sp-inner-card-foot"><small><?php echo esc_html(studio_portal_reading_time($post->ID)); ?> 分钟阅读</small><b>阅读文章 -&gt;</b></span>
        </a>
    </article>
    <?php
};

get_header();
?>

<section class="sp-inner-hero <?php echo 'about' === $route ? 'is-dark' : ''; ?>">
    <div class="sp-inner-container sp-inner-hero-grid">
        <div>
            <p class="sp-inner-eyebrow"><span></span><?php echo esc_html($current[0]); ?></p>
            <h1><?php echo esc_html($current[1]); ?></h1>
        </div>
        <div class="sp-inner-hero-note">
            <span><?php echo esc_html(str_pad((string) (array_search($route, array_keys($route_copy), true) + 1), 2, '0', STR_PAD_LEFT)); ?></span>
            <p><?php echo esc_html($current[2]); ?></p>
        </div>
    </div>
</section>

<?php if ('journal' === $route) : ?>
    <nav class="sp-topic-tabs" aria-label="文章主题筛选">
        <div class="sp-inner-container">
            <a class="<?php echo '' === $selected_topic ? 'is-current' : ''; ?>" href="<?php echo esc_url(home_url('/journal/')); ?>">全部</a>
            <?php foreach ($topics as $slug => $topic) : ?>
                <a class="<?php echo $slug === $selected_topic ? 'is-current' : ''; ?>" href="<?php echo esc_url(add_query_arg('topic', $slug, home_url('/journal/'))); ?>"><?php echo esc_html($topic['label']); ?></a>
            <?php endforeach; ?>
        </div>
    </nav>
    <section class="sp-inner-section sp-inner-container">
        <header class="sp-inner-section-head">
            <div><p><?php echo $selected_topic && isset($topics[$selected_topic]) ? esc_html($topics[$selected_topic]['eyebrow']) : 'EDITOR\'S PICK'; ?></p><h2><?php echo $selected_topic && isset($topics[$selected_topic]) ? esc_html($topics[$selected_topic]['label']) : '最新发布'; ?></h2></div>
            <p><?php echo esc_html(count($journal_posts)); ?> 篇内容</p>
        </header>
        <?php if ($journal_posts) : ?>
            <div class="sp-inner-featured-row">
                <?php $render_post_card($journal_posts[0], 'is-featured'); ?>
                <aside class="sp-inner-index">
                    <p>QUICK INDEX</p>
                    <?php foreach (array_slice($journal_posts, 1, 4) as $index => $post) : ?>
                        <a href="<?php echo esc_url(get_permalink($post)); ?>"><span><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span><strong><?php echo esc_html(get_the_title($post)); ?></strong><b>-&gt;</b></a>
                    <?php endforeach; ?>
                </aside>
            </div>
            <div class="sp-inner-card-grid">
                <?php foreach (array_slice($journal_posts, 1) as $post) : $render_post_card($post); endforeach; ?>
            </div>
        <?php else : ?>
            <div class="sp-inner-empty"><span>NO STORIES YET</span><h2>这个主题的内容正在整理中。</h2><a href="<?php echo esc_url(home_url('/journal/')); ?>">返回全部文章 -&gt;</a></div>
        <?php endif; ?>
    </section>

<?php elseif ('services' === $route) : ?>
    <section class="sp-inner-section sp-inner-container">
        <header class="sp-inner-section-head"><div><p>EXPLORE BY SUBJECT</p><h2>四条持续更新的内容线索</h2></div><p>不是孤立分类，而是彼此连接的技术问题。</p></header>
        <div class="sp-topic-directory-grid">
            <?php foreach ($topics as $index => $topic) : $topic_posts = studio_portal_filter_posts_by_topic($all_posts, $index); ?>
                <a href="<?php echo esc_url(add_query_arg('topic', $index, home_url('/journal/'))); ?>">
                    <span><?php echo esc_html($topic['eyebrow']); ?></span>
                    <small><?php echo esc_html(str_pad((string) (array_search($index, array_keys($topics), true) + 1), 2, '0', STR_PAD_LEFT)); ?></small>
                    <h2><?php echo esc_html($topic['label']); ?></h2>
                    <p><?php echo esc_html($topic['description']); ?></p>
                    <div><b><?php echo esc_html(count($topic_posts)); ?> 篇相关文章</b><strong>-&gt;</strong></div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="sp-inner-dark-band">
        <div class="sp-inner-container">
            <p class="sp-inner-eyebrow"><span></span>CONNECTIONS</p>
            <h2>好的分类，不是把内容分开，<br>而是让关系变得可见。</h2>
            <p>模型能力会改变工具边界，工具边界会重塑 Agent 架构，最终仍要回到部署、成本与真实工作流。这里的每条路径，都可以继续走向另一条路径。</p>
        </div>
    </section>

<?php elseif ('process' === $route) : ?>
    <?php
    $paths = array(
        array('01', '刚开始搭建 AI 工作流', 'START HERE', '先理解确定性代码如何与概率系统协作，再进入 Agent 的上下文与检索问题。', array_slice($all_posts, 0, 2)),
        array('02', '正在解决工程可靠性', 'BUILD WITH EVIDENCE', '从约束、事实链和验证门开始，减少“能演示、不能维护”的系统。', array_slice($all_posts, 2, 2)),
        array('03', '准备选择部署方案', 'SHIP ON YOUR TERMS', '比较模型与推理框架的真实边界，把隐私、性能和维护成本一起放进决策。', array_slice($all_posts, 4, 2)),
    );
    ?>
    <section class="sp-inner-section sp-inner-container">
        <div class="sp-guide-paths">
            <?php foreach ($paths as $path) : ?>
                <article>
                    <header><span><?php echo esc_html($path[0]); ?></span><p><?php echo esc_html($path[2]); ?></p></header>
                    <div><h2><?php echo esc_html($path[1]); ?></h2><p><?php echo esc_html($path[3]); ?></p></div>
                    <ol>
                        <?php foreach ($path[4] as $post) : ?><li><a href="<?php echo esc_url(get_permalink($post)); ?>"><span><?php echo esc_html(get_the_title($post)); ?></span><b>-&gt;</b></a></li><?php endforeach; ?>
                    </ol>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

<?php elseif ('about' === $route) : ?>
    <section class="sp-inner-section sp-inner-container sp-about-intro">
        <p>OUR POSITION</p>
        <div><h2>中文是阅读界面，英文是专业语境。两者不需要互相取代。</h2><p>首页的中文化不是为了做成传统中文门户，而是为了降低理解复杂技术的阻力。我们保留英文术语、栏目眉题与国际化的版式节奏，但把叙事、判断和操作入口放回中文用户最自然的阅读路径里。</p></div>
    </section>
    <section class="sp-inner-container sp-principle-grid">
        <?php foreach (array(
            array('01', '来源清楚', '每个判断都应该能追溯到来源、适用范围和维护者。'),
            array('02', '证据可用', '交付物不是一句承诺，而是下一位读者可以继续使用的东西。'),
            array('03', '反馈够短', '用更短的验证回路，让内容始终靠近真实用户与工程约束。'),
        ) as $principle) : ?>
            <article><span><?php echo esc_html($principle[0]); ?></span><h2><?php echo esc_html($principle[1]); ?></h2><p><?php echo esc_html($principle[2]); ?></p></article>
        <?php endforeach; ?>
    </section>
    <section class="sp-editorial-process">
        <div class="sp-inner-container"><header><p>HOW WE EDIT</p><h2>从变化，到可以保存的判断。</h2></header><ol><li><span>01</span><b>发现变化</b><p>识别真正改变工作方式的信号。</p></li><li><span>02</span><b>核对证据</b><p>把宣传、体验与工程事实分开。</p></li><li><span>03</span><b>补全边界</b><p>说明适用场景、限制与维护成本。</p></li><li><span>04</span><b>形成内容</b><p>用中文重新组织成可复用的阅读路径。</p></li></ol></div>
    </section>

<?php elseif ('contact' === $route) : ?>
    <section class="sp-contact-editorial sp-inner-container">
        <aside><p>PROJECT / EDITORIAL INTAKE</p><h2>一封清楚的来信，胜过漫长的来回确认。</h2><p>请告诉我们正在发生什么、什么必须保持不变，以及你期待看到怎样的结果。表单会直接发送到站点管理员。</p><dl><div><dt>邮箱</dt><dd><?php echo esc_html(get_option('admin_email')); ?></dd></div><div><dt>回复时间</dt><dd>两个工作日内</dd></div></dl></aside>
        <div class="sp-contact-form-wrap">
            <?php if (isset($_GET['contact_sent'])) : ?><div class="sp-form-state is-success">信息已发送，我们会尽快回复。</div><?php elseif (isset($_GET['contact_error'])) : ?><div class="sp-form-state is-error">请检查必填内容后再试一次。</div><?php endif; ?>
            <form class="sp-contact-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                <input type="hidden" name="action" value="studio_portal_contact"><input type="hidden" name="studio_portal_contact_nonce" value="<?php echo esc_attr(wp_create_nonce('studio_portal_contact')); ?>">
                <p class="sp-honeypot"><label>Website<input type="text" name="studio_portal_website" tabindex="-1" autocomplete="off"></label></p>
                <label>怎么称呼你？<input required name="name" type="text" placeholder="姓名"></label>
                <label>你的邮箱<input required name="email" type="email" placeholder="you@company.com"></label>
                <label>公司或团队<input name="company" type="text" placeholder="选填"></label>
                <label>合作范围<select name="budget"><option value="">请选择</option><option>内容与选题合作</option><option>产品与技术咨询</option><option>长期合作</option><option>其他</option></select></label>
                <label>想和我们聊什么？<textarea required name="notes" rows="7" placeholder="背景、目标、时间与重要约束……"></textarea></label>
                <button class="sp-editorial-button" type="submit">发送信息 <span>-&gt;</span></button>
            </form>
        </div>
    </section>

<?php else : ?>
    <section class="sp-inner-section sp-inner-container">
        <div class="sp-work-editorial">
            <?php foreach (array_slice($all_posts, 0, 6) as $index => $post) : ?>
                <a href="<?php echo esc_url(get_permalink($post)); ?>"><span><?php echo esc_html(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)); ?></span><strong><?php echo esc_html(get_the_title($post)); ?></strong><small><?php echo esc_html($post_category($post)); ?> · <?php echo esc_html(get_the_date('Y', $post)); ?></small><b>-&gt;</b></a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if ('contact' !== $route) : ?>
<section class="sp-inner-cta">
    <div class="sp-inner-container"><p>KEEP READING</p><h2>少一点信息焦虑，<br>多一点可靠判断。</h2><a href="<?php echo esc_url(home_url('/journal/')); ?>">浏览全部文章 <span>-&gt;</span></a></div>
</section>
<?php endif; ?>

<?php get_footer(); ?>
