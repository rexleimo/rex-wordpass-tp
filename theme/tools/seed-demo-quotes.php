<?php
/**
 * Create clearly labelled local sample records for the toKraft quote workflow.
 * Usage: php /var/www/html/wp-content/themes/tokraft/tools/seed-demo-quotes.php
 */

if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

require '/var/www/html/wp-load.php';

if (!post_type_exists('tokraft_quote')) {
    fwrite(STDERR, "toKraft quote post type is not available\n");
    exit(1);
}

$admins = get_users(array('role' => 'administrator', 'number' => 1));
$owner_id = !empty($admins) ? (int) $admins[0]->ID : 0;
$records = array(
    array(
        'key' => 'demo-robotics-bracket',
        'number' => 'TKQ-DEMO-001',
        'name' => 'Northfield Robotics (Demo)',
        'email' => 'operations@example.test',
        'company' => 'Northfield Robotics',
        'material' => 'PETG',
        'color' => 'Black',
        'quantity' => 12,
        'infill' => 35,
        'walls' => 4,
        'layer_height' => '0.20 mm',
        'support' => 'No',
        'adhesion' => 'Brim',
        'notes' => '演示数据：需要用于工作台固定，优先保证孔位精度与耐用性。',
        'status' => 'quoted',
        'price' => '186.00',
        'follow_up_note' => '演示跟进：已确认孔位与交期，等待客户确认。',
        'with_file' => true,
    ),
    array(
        'key' => 'demo-outdoor-cover',
        'number' => 'TKQ-DEMO-002',
        'name' => 'Prairie Field Service (Demo)',
        'email' => 'service@example.test',
        'company' => 'Prairie Field Service',
        'material' => 'ASA',
        'color' => 'Natural',
        'quantity' => 4,
        'infill' => 25,
        'walls' => 3,
        'layer_height' => '0.24 mm',
        'support' => 'Yes',
        'adhesion' => 'None',
        'notes' => '演示数据：户外电缆护盖，需要评估紫外暴露和安装方向。',
        'status' => 'new',
        'price' => '',
        'follow_up_note' => '演示跟进：等待工程团队完成文件可制造性审核。',
        'with_file' => false,
    ),
    array(
        'key' => 'demo-fit-test',
        'number' => 'TKQ-DEMO-003',
        'name' => 'Maya Chen (Demo)',
        'email' => 'maya@example.test',
        'company' => '',
        'material' => 'PLA',
        'color' => 'White',
        'quantity' => 2,
        'infill' => 15,
        'walls' => 2,
        'layer_height' => '0.16 mm',
        'support' => 'No',
        'adhesion' => 'None',
        'notes' => '演示数据：低批量装配验证件，客户需要在本周内确认试装结果。',
        'status' => 'won',
        'price' => '42.00',
        'follow_up_note' => '演示跟进：客户已接受报价，转入生产排期。',
        'with_file' => false,
    ),
);

foreach ($records as $record) {
    $existing = get_posts(array(
        'post_type' => 'tokraft_quote',
        'posts_per_page' => 1,
        'meta_key' => '_tokraft_quote_demo_key',
        'meta_value' => $record['key'],
        'fields' => 'ids',
    ));
    $post_data = array(
        'post_type' => 'tokraft_quote',
        'post_status' => 'publish',
        'post_title' => $record['number'] . ' - ' . $record['name'],
    );
    if ($existing) {
        $post_data['ID'] = (int) $existing[0];
        $quote_id = wp_update_post($post_data, true);
    } else {
        $quote_id = wp_insert_post($post_data, true);
    }
    if (is_wp_error($quote_id)) {
        fwrite(STDERR, "Unable to save {$record['number']}: " . $quote_id->get_error_message() . "\n");
        continue;
    }

    foreach (array('name', 'email', 'company', 'material', 'color', 'quantity', 'infill', 'walls', 'layer_height', 'support', 'adhesion', 'notes', 'status', 'price', 'follow_up_note') as $field) {
        update_post_meta($quote_id, '_tokraft_quote_' . $field, $record[$field]);
    }
    update_post_meta($quote_id, '_tokraft_quote_number', $record['number']);
    update_post_meta($quote_id, '_tokraft_quote_owner', $owner_id);
    update_post_meta($quote_id, '_tokraft_quote_demo_key', $record['key']);

    if ($record['with_file'] && !get_post_meta($quote_id, '_tokraft_quote_file_attachment', true)) {
        $upload = wp_upload_bits('tokraft-demo-bracket.stl', null, "solid demo_bracket\nendsolid demo_bracket\n");
        if (empty($upload['error'])) {
            $attachment_id = wp_insert_attachment(array(
                'post_mime_type' => 'application/sla',
                'post_title' => 'tokraft-demo-bracket.stl',
                'post_status' => 'inherit',
                'post_parent' => $quote_id,
            ), $upload['file'], $quote_id);
            if (!is_wp_error($attachment_id)) {
                update_post_meta($quote_id, '_tokraft_quote_file_attachment', $attachment_id);
                update_post_meta($quote_id, '_tokraft_quote_file_url', $upload['url']);
            }
        }
    }

    echo "Saved {$record['number']} (#{$quote_id})\n";
}
