<?php
/**
 * Plugin Name: Membership System For WooCommerce
 * Description: ระบบสะสมคะแนน ส่วนลด สิทธิพิเศษ สำหรับสมาชิกเว็บไซต์
 * Author: Jirakit Pawnsakunrungrot
 * Author URI: https://www.linkedin.com/in/sunny-jirakit
 * Plugin URI: https://github.com/sunny420x/woocommerce-membership
 */

if (!defined('ABSPATH'))
    exit;

add_action('admin_menu', 'membership_menu');

function membership_menu()
{
    add_menu_page(
        'Membership Settings', // Title ของหน้า
        'ระบบ Membership', // ชื่อเมนูที่โชว์ในแถบข้าง
        'manage_options', //สิทธิ์การเข้าถึง (Admin)
        'woocommerce-membership-settings', // Slug ของหน้า
        'woocommerce_membership_setting_page', // ฟังก์ชันที่ใช้พ่น HTML หน้า Setting
        'dashicons-admin-users', // ไอคอน
        '80' // ตำแหน่งเมนู
    );
}

register_activation_hook( __FILE__, 'woomembership_plugin_install' );

function woomembership_plugin_install() {
    global $wpdb;

    $user_table = $wpdb->prefix . 'users';
    $redeem_table = $wpdb->prefix . 'redeem_history';
    $charset_collate = $wpdb->get_charset_collate();

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $sql_user = "CREATE TABLE $user_table (
        ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        score int(12) DEFAULT 0 NOT NULL,
        PRIMARY KEY  (ID)
    ) $charset_collate;";
    
    // สร้างตาราง redeem_history
    $sql_history = "CREATE TABLE $redeem_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        coupon_code varchar(50) NOT NULL,
        points_used int(11) NOT NULL,
        status varchar(20) DEFAULT 'unused' NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id)
    ) $charset_collate;";

    // รัน dbDelta เพื่ออัปเดต/สร้างตาราง
    dbDelta( $sql_user );
    dbDelta( $sql_history );
}

//Serve Styling.
function worldchem_enqueue_assets()
{
    wp_enqueue_style(
        'worldchem-membership-style',
        plugins_url('/css/membership.css', __FILE__),
        array(),
        time(),
        'all'
    );
}
// ลบ add_action ตัวเก่าออกด้วย แล้วใช้ตัวนี้แทน
add_action('wp_enqueue_scripts', 'worldchem_enqueue_assets');

function woocommerce_membership_setting_page()
{
    ?>
    <style>
        .leftside {
            width: 350px;
            background: #f8f8f8;
            height: max-content;
        }
        .leftside h1 {
            background: #009FE3;
            color: #fff;
            font-size: 16px;
            padding: 10px 20px;
            margin: 0;
        }
        .leftside a {
            padding: 10px 20px;
            font-size: 14px;
            background: #f8f8f8;
            color: #000;
            transition: .2s ease-in-out;
            display: block;
            width: 100%;
            text-decoration: none;
        }
        .leftside a:hover {
            background: #fff;
            cursor: pointer;
        }
        .container {
            width: 1200px;
            background: #fff; 
        }
        .container h1 {
            background: #555;
            color: #fff;
            font-size: 16px;
            padding: 10px 20px;
            margin: 0;
        }
        .container p {
            padding: 0;
        }
        .white-label-zone {
            width: calc(100% + 20px);
            height: auto;
            background: #fff;
            display: flex;
            margin: 0 0 0 -20px;
        }
        .white-label-zone h1,p {
            padding: 0 20px;
        }
    </style>
    <div class="white-label-zone no-print">
        <span style="padding: 60px 10px 60px 40px;float: left;font-size: 60px;">👑</span>
        <div style="padding: 20px 0;">
            <h1>WooCommerce Membership</h1>
            <p>ระบบสิทธิพิเศษ Membership สำหรับ WooCommerce บน WordPress ประกอบไปด้วย คะแนนและระดับของสมาชิก แลกคะแนนเป็นส่วนลด ส่วนลดสำหรับสินค้าพิเศษ ส่วนลดสำหรับ Brand พิเศษ เป็นต้น
                <br>
                <strong>Github Repository:</strong> <a href="https://github.com/sunny420x/woocommerce-membership" target="_blank">https://github.com/sunny420x/woocommerce-membership</a>
            </p>
        </div>
    </div>
    <div class="wrap">
        <div style="display: flex;">
            <div class="leftside">
                <h1>WooCommerce Membership</h1>
                <a href="/wp-admin/admin.php?page=woocommerce-membership-settings&option=statistic">📊 สถิติการใช้งาน</a>
                <a href="/wp-admin/admin.php?page=woocommerce-membership-settings&option=member_privilege">🏆 คะแนนและระดับของสมาชิก</a>
                <a href="/wp-admin/admin.php?page=woocommerce-membership-settings&option=redeem_from_score">🏷️ แลกคะแนนเป็นส่วนลด</a>
                <a href="/wp-admin/admin.php?page=woocommerce-membership-settings&option=special_offers">🎁 ส่วนลดสำหรับสินค้าพิเศษ</a>
                <a href="/wp-admin/admin.php?page=woocommerce-membership-settings&option=brands_privilege">🤝 ส่วนลดสำหรับ Brand พิเศษ</a>
                <a href="/wp-admin/admin.php?page=woocommerce-membership-settings&option=members">👥 สมาชิกทั้งหมด</a>
            </div>
            <div class="container">
                <?php
                if(isset($_GET['option']) && $_GET['option'] == "redeem_from_score") {
                ?>
                <form action="options.php" method="post">
                <?php
                settings_fields('membership_settings_group_redeem_from_score');
                ?>
                <h1>ระบบแลกคะแนนเป็นส่วนลด</h1>
                <div style="padding: 25px 25px 25px 25px;">
                    <table class="wp-list-table widefat fixed striped">
                        <tr>
                            <td>ระบบแลกคะแนนเป็นส่วนลด</td>
                            <td>
                                <select name="membership_enable_redeem" id="">
                                    <option value="yes" <?php selected( get_option('membership_enable_redeem'), 'yes' ); ?>>เปิดใช้งาน</option>
                                    <option value="no" <?php selected( get_option('membership_enable_redeem'), 'no' ); ?>>ปิดใช้งาน</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>ลูกค้าสามารถใช้ 1 คะแนน ในการแลกเป็นส่วนลดได้</td>
                            <td><input type="text" name="membership_baht_per_point" value="<?=get_option('membership_baht_per_point', 1);?>"> บาท </td>
                        </tr>
                    </table>
                    <?php submit_button('บันทึกการเปลี่ยนแปลง'); ?>
                </div>
                </form>
                <?php
                } elseif(isset($_GET['option']) && $_GET['option'] == "member_privilege") {
                ?>
                <form action="options.php" method="post">
                <?php
                settings_fields('membership_settings_group_member_privilege');
                ?>
                <h1>เกณฑ์การคิดคะแนนและให้ส่วนลดทั้งตะกร้า</h1>
                <div style="padding: 25px 25px 25px 25px;">
                    <table class="wp-list-table widefat fixed striped">
                        <tr>
                            <td>ลูกค้าจะได้รับ 1 คะแนนต่อการซื้อ</td>
                            <td><input type="text" name="membership_point_per_baht" value="<?=get_option('membership_point_per_baht', 500);?>"> บาท</td>
                        </tr>
                        <tr>
                            <td>เลือกรับสินค้าเองจะไม่ได้รับส่วนลดตามระดับ</td>
                            <td>
                                <select name="no_discount_self_pickup" id="">
                                    <option value="yes" <?php selected(get_option('no_discount_self_pickup'), 'yes') ?>>ใช่</option>
                                    <option value="no" <?php selected(get_option('no_discount_self_pickup'), 'no') ?>>ไม่ใช่</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                        <thead>
                            <tr>
                                <th>ระดับสมาชิก (Membership Level)</th>
                                <th>คะแนนขั้นต่ำ (Minimum Score)</th>
                                <th>ลดเป็นจำนวนร้อยละ (Discount Percentage)</th>
                                <th>สีที่ใช้แสดงในระบบ (Gradients)</th>
                                <th>สีที่ใช้แสดงในระบบ (single)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Platinum Membership</strong></td>
                                <td><input type="number" name="ms_platinum_score"
                                        value="<?php echo esc_attr(get_option('ms_platinum_score', 30)); ?>" /></td>
                                <td><input type="number" step="0.01" name="ms_platinum_discount"
                                        value="<?php echo esc_attr(get_option('ms_platinum_discount', 3)); ?>" /> %</td>
                                <td><input type="text" name="member-privileges-platinum-color"
                                        value="<?php echo esc_attr(get_option('member-privileges-platinum-color')); ?>" /></td>
                                <td><input type="text" name="member-privileges-platinum-single-color"
                                        value="<?php echo esc_attr(get_option('member-privileges-platinum-single-color')); ?>" /></td>
                            </tr>
                            <tr>
                                <td><strong>Gold Membership</strong></td>
                                <td><input type="number" name="ms_gold_score"
                                        value="<?php echo esc_attr(get_option('ms_gold_score', 20)); ?>" /></td>
                                <td><input type="number" step="0.01" name="ms_gold_discount"
                                        value="<?php echo esc_attr(get_option('ms_gold_discount', 2)); ?>" /> %</td>
                                <td><input type="text" name="member-privileges-gold-color"
                                        value="<?php echo esc_attr(get_option('member-privileges-gold-color')); ?>" /></td>
                                <td><input type="text" name="member-privileges-gold-single-color"
                                        value="<?php echo esc_attr(get_option('member-privileges-gold-single-color')); ?>" /></td>
                            </tr>
                            <tr>
                                <td><strong>Silver Membership</strong></td>
                                <td><input type="number" name="ms_silver_score"
                                        value="<?php echo esc_attr(get_option('ms_silver_score', 10)); ?>" /></td>
                                <td><input type="number" step="0.01" name="ms_silver_discount"
                                        value="<?php echo esc_attr(get_option('ms_silver_discount', 1)); ?>" /> %</td>
                                <td><input type="text" name="member-privileges-silver-color"
                                        value="<?php echo esc_attr(get_option('member-privileges-silver-color')); ?>" /></td>
                                <td><input type="text" name="member-privileges-silver-single-color"
                                        value="<?php echo esc_attr(get_option('member-privileges-silver-single-color')); ?>" /></td>
                            </tr>
                        </tbody>
                    </table>
                    <br><br>
                    หัวข้อการ์ด: <input type="text" name="ms_card_title" value="<?php echo esc_attr(get_option('ms_card_title')); ?>" style="width: 500px;" />
                    <br><br>
                    <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                        <thead>
                            <tr>
                                <th>ระดับสมาชิก (Membership Level)</th>
                                <th>เนื้อหา</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Platinum Membership:</strong></td>
                                <td>
                                    <input type="text" name="ms_platinum_description_title"
                                        value="<?php echo esc_attr(get_option('ms_platinum_description_title')); ?>" style="width: 500px;" />
                                    <textarea name="ms_platinum_description_content" id="" style="width: 500px; height: 200px;"><?=get_option('ms_platinum_description_content')?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Gold Membership:</strong></td>
                                <td>
                                    <input type="text" name="ms_gold_description_title"
                                        value="<?php echo esc_attr(get_option('ms_gold_description_title')); ?>" style="width: 500px;" />
                                    <textarea name="ms_gold_description_content" id="" style="width: 500px; height: 200px;"><?=get_option('ms_gold_description_content')?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Silver Membership:</strong></td>
                                <td>
                                    <input type="text" name="ms_silver_description_title"
                                        value="<?php echo esc_attr(get_option('ms_silver_description_title')); ?>" style="width: 500px;" />
                                    <textarea name="ms_silver_description_content" id="" style="width: 500px; height: 200px;"><?=get_option('ms_silver_description_content')?></textarea>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <?php submit_button('บันทึกการเปลี่ยนแปลง'); ?>
                </div>
                </form>
                <?php
                } elseif(isset($_GET['option']) && $_GET['option'] == "special_offers") {
                ?>
                <form action="options.php" method="post">
                <?php
                settings_fields('membership_settings_group_special_offers');
                ?>
                <h1>⭐ ส่วนลดสำหรับสินค้าพิเศษ</h1>
                <br>
                <div style="padding: 0 25px 25px 25px;">
                    <table class="wp-list-table widefat fixed striped">
                        <tr>
                            <td>ระบบส่วนลดสำหรับสินค้าพิเศษ</td>
                            <td>
                                <select name="membership_enable_member_privileges" id="">
                                    <option value="yes" <?php selected( get_option('membership_enable_member_privileges'), 'yes' ); ?>>เปิดใช้งาน</option>
                                    <option value="no" <?php selected( get_option('membership_enable_member_privileges'), 'no' ); ?>>ปิดใช้งาน</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Slug ของประเภทสินค้า:</td>
                            <td>
                                <input type="text" name="member-privileges-slug" value="<?=get_option('member-privileges-slug', 'member-privileges');?>">
                            </td>
                        </tr>
                        <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                        <thead>
                            <tr>
                                <th>ระดับสมาชิก (Membership Level)</th>
                                <th>ลดเป็นจำนวนร้อยละ (Discount Percentage)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Silver Membership</strong></td>
                                <td><input type="number" name="member-privileges-silver" value="<?php echo esc_attr(get_option('member-privileges-silver', 10)); ?>" /> %</td>
                            </tr>
                            <tr>
                                <td><strong>Gold Membership</strong></td>
                                <td><input type="number" name="member-privileges-gold" value="<?php echo esc_attr(get_option('member-privileges-gold', 20)); ?>" /> %</td>
                            </tr>
                            <tr>
                                <td><strong>Platinum Membership</strong></td>
                                <td><input type="number" name="member-privileges-platinum" value="<?php echo esc_attr(get_option('member-privileges-platinum', 30)); ?>" /> %</td>
                            </tr>
                        </tbody>
                    </table>
                    </table>
                    <?php submit_button('บันทึกการเปลี่ยนแปลง'); ?>
                </div>
                </form>
                <?php
                } elseif(isset($_GET['option']) && $_GET['option'] == "brands_privilege") {
                ?>
                <form action="options.php" method="post">
                <?php
                settings_fields('membership_settings_group_brands_privilege');
                ?>
                <h1>⭐ ส่วนลดสำหรับ Brand พิเศษ</h1>
                <div style="padding: 0 25px 25px 25px;">
                    <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                        <thead>
                            <tr>
                                <th>รายการ</th>
                                <th colspan="2">ตั้งค่า</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>เปิดใช้งาน Brand Privilege:</strong></td>
                                <td colspan="2">
                                    <select name="brands_privilege_enable" id="">
                                        <option value="yes" <?php selected(get_option('brands_privilege_enable', 'no'), 'yes'); ?>>เปิดใช้งาน</option>
                                        <option value="no" <?php selected(get_option('brands_privilege_enable', 'no'), 'no'); ?>>ปิดใช้งาน</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>รายชื่อ Slug Brand ที่อยู่ในโปรโมชั่น:</strong></td>
                                <td colspan="2"><textarea name="brands_privilege_list" /><?php echo esc_attr(get_option('brands_privilege_list')); ?></textarea></td>
                            </tr>
                            <tr>
                                <td><strong>ส่วนลดระดับที่ 1</strong></td>
                                <td>
                                    <input type="number" name="brands_privilege_step_01_start" value="<?php echo esc_attr(get_option('brands_privilege_step_01_start')); ?>" />
                                    -
                                    <input type="number" name="brands_privilege_step_01_end" value="<?php echo esc_attr(get_option('brands_privilege_step_01_end')); ?>" />
                                    บาท
                                </td>
                                <td>
                                    ลดเป็นจำนวนร้อยละ <input type="number" name="brands_privilege_step_01_discount_percent" value="<?php echo esc_attr(get_option('brands_privilege_step_01_discount_percent', 2)); ?>" /> %
                                </td>
                            </tr>
                            <tr>
                                <td><strong>ส่วนลดระดับที่ 2</strong></td>
                                <td>
                                    <input type="number" name="brands_privilege_step_02_start" value="<?php echo esc_attr(get_option('brands_privilege_step_02_start')); ?>" />
                                    -
                                    <input type="number" name="brands_privilege_step_02_end" value="<?php echo esc_attr(get_option('brands_privilege_step_02_end')); ?>" />
                                    บาท
                                </td>
                                <td>
                                    ลดเป็นจำนวนร้อยละ <input type="number" name="brands_privilege_step_02_discount_percent" value="<?php echo esc_attr(get_option('brands_privilege_step_02_discount_percent', 3)); ?>" /> %
                                </td>
                            </tr>
                            <tr>
                                <td><strong>ส่วนลดระดับที่ 3</strong></td>
                                <td>
                                    <input type="number" name="brands_privilege_step_03_start" value="<?php echo esc_attr(get_option('brands_privilege_step_03_start')); ?>" />
                                    -
                                    <input type="number" name="brands_privilege_step_03_end" value="<?php echo esc_attr(get_option('brands_privilege_step_03_end')); ?>" />
                                    บาท
                                </td>
                                <td>
                                    ลดเป็นจำนวนร้อยละ <input type="number" name="brands_privilege_step_03_discount_percent" value="<?php echo esc_attr(get_option('brands_privilege_step_03_discount_percent', 4)); ?>" /> %
                                </td>
                            </tr>
                            <tr>
                                <td><strong>ส่วนลดระดับที่ 4</strong></td>
                                <td>
                                    > <input type="number" name="brands_privilege_step_04" value="<?php echo esc_attr(get_option('brands_privilege_step_04')); ?>" />
                                    บาท
                                </td>
                                <td>
                                    ลดเป็นจำนวนร้อยละ <input type="number" name="brands_privilege_step_04_discount_percent" value="<?php echo esc_attr(get_option('brands_privilege_step_04_discount_percent', 5)); ?>" /> %
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <?php submit_button('บันทึกการเปลี่ยนแปลง'); ?>
                </div>
                </form>
                <?php
                } elseif(isset($_GET['option']) && $_GET['option'] == "members") {
                ?>
                <?php
                function getMemberShipLevel($score)
                {
                    if ($score >= (int) get_option('ms_platinum_score', 30)) {
                        return "Platinum";
                    } else if ($score >= (int) get_option('ms_gold_score', 20)) {
                        return "Gold";
                    } else if ($score >= (int) get_option('ms_silver_score', 10)) {
                        return "Silver";
                    } else {
                        return "-";
                    }
                }
                ?>
                <h1>สมาชิกเว็บไซต์และคะแนนในระบบ</h1>
                <div style="padding: 25px 25px 25px 25px;">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>ชื่อที่แสดงในระบบ (Display Name)</th>
                                <th>อีเมล์ (Email)</th>
                                <th>คะแนนปัจจุบัน (Score)</th>
                                <th>ระดับสมาชิก</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            global $wpdb;
                            $results = $wpdb->get_results("SELECT display_name,user_email,ID,score FROM {$wpdb->prefix}users WHERE score > 0 ORDER BY score DESC");
        
                            foreach ($results as $row) {
                                ?>
                                <tr>
                                    <td><?= $row->ID; ?></td>
                                    <td><a href="/wp-admin/user-edit.php?user_id=<?=$row->ID;?>" target="_blank"><?= $row->display_name; ?></a></td>
                                    <td><a href="/wp-admin/edit.php?s=<?= $row->user_email ?>&post_status=all&post_type=shop_order&action=-1&m=0&_created_via&_customer_user&paged=1&action2=-1"
                                            target="_blank"><?= $row->user_email; ?></a></td>
                                    <td><?= $row->score; ?></td>
                                    <td><?= getMemberShipLevel($row->score); ?></td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php
                } elseif(isset($_GET['option']) && $_GET['option'] == "statistic") {
                    $args = array(
                        'limit' => -1,
                        'status' => ['wc-completed', 'wc-processing']
                    );

                    $orders = wc_get_orders($args);
                    $privilege_orders = [];

                    foreach ($orders as $order) {
                        // วนลูปเช็ค Fee ในแต่ละออเดอร์
                        foreach ($order->get_items('fee') as $item_id => $item) {
                            $fee_name = $item->get_name();
                            $order_date = $order->get_date_created()->date('Y-m-d');
                            
                            // เช็คชื่อ Fee ให้ตรงกับที่มึงแอดไว้ (แนะนำให้เช็คด้วย strpos กันเหนียว)
                            if (strpos($fee_name, 'ส่วนลดพิเศษ: Brand Privilege') !== false) {
                                $privilege_orders[] = [
                                    'order_id' => $order->get_id(),
                                    'fee_name' => $fee_name,
                                    'amount'   => $item->get_total(), // ยอดที่ลดไป
                                    'total'    => $order->get_formatted_order_total(),
                                    'total_amount'    => $order->get_total(),
                                    'date'     => $order_date
                                ];
                            }
                        }
                    }
                ?>
                <h1>จำนวนผู้ใช้สิทธิพิเศษ (<?=count($privilege_orders)?> คำสั่งซื้อ)</h1>
                <div style="padding: 25px;">
                    <?php
                    $combined = [];
                    foreach ($privilege_orders as $row) {
                        $date = $row['date'];
                        $amount = abs($row['amount']);
                        
                        if (!isset($combined[$date])) {
                            $combined[$date] = 0;
                        }
                        $combined[$date] += $amount;
                    }

                    // เรียงลำดับวันที่จาก เก่า -> ใหม่ ด้วย ksort
                    ksort($combined);

                    // แยก Key (วันที่) และ Value (ยอดเงิน) ออกมาเป็น Array
                    $chart_labels = array_keys($combined);
                    $chart_data   = array_values($combined);
                    ?>
                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

                    <canvas id="memberPrivilegeChart"></canvas>
                    <br><br>

                    <script>
                    const ctx = document.getElementById('memberPrivilegeChart').getContext('2d');

                    // ดึงข้อมูลจาก PHP มาใส่ JS
                    const labels = <?php echo json_encode($chart_labels); ?>;
                    const dataValues = <?php echo json_encode($chart_data); ?>;

                    new Chart(ctx, {
                        type: 'line', // ถ้าเป็นวันที่ กราฟเส้น (line) จะดูรู้เรื่องกว่ากราฟแท่ง
                        data: {
                            labels: <?php echo json_encode($chart_labels); ?>,
                            datasets: [{
                                label: 'ส่วนลด (บาท)',
                                data: <?php echo json_encode($chart_data); ?>,
                                borderColor: 'rgba(75, 192, 192, 1)',
                                tension: 0.1 // ทำให้เส้นมีความโค้งมน ดูสวยขึ้น
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                    </script>
                    <?php
                    // ก้อนที่ 1: รวมส่วนลด (amount)
                    $total_discount = array_sum(array_map(function($row) {
                        return abs($row['amount']);
                    }, $privilege_orders));

                    // ก้อนที่ 2: รวมยอดขาย (total)
                    $total_sales = array_sum(array_map(function($row) {
                        return abs($row['total_amount']);
                    }, $privilege_orders));
                    ?>

                    <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                        <div style="padding: 20px; background: #f8f8f8; border-radius: 8px;">
                            <h3>รวมยอดขายทั้งหมด</h3>
                            <p style="font-size: 24px; font-weight: bold; color: #2271b1;"><?= number_format($total_sales, 2) ?> บาท</p>
                        </div>
                        <div style="padding: 20px; background: #f8f8f8; border-radius: 8px;">
                            <h3>รวมส่วนลดที่ใช้ไป</h3>
                            <p style="font-size: 24px; font-weight: bold; color: #d63638;"><?= number_format($total_discount, 2) ?> บาท</p>
                        </div>
                    </div>
                    <h2>ประวัติการใช้ Member Privilege</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <th>หมายเลขคำสั่งซื้อ</th>
                            <th>วันที่</th>
                            <th>ส่วนลด</th>
                            <th>จำนวน</th>
                            <th>ยอดขาย</th>
                        </thead>
                        <tbody>
                            <?php
                                foreach($privilege_orders as $row) {
                                    $order_id = $row['order_id'];
                            ?>
                            <tr>
                                <td><a href="<?=admin_url("post.php?post=$order_id&action=edit")?>" target="_blank"><?=$row['order_id']?></a></td>
                                <td><?=$row['date']?></td>
                                <td><?=$row['fee_name']?></td>
                                <td><?=$row['amount'] * -1 ?></td>
                                <td><?=$row['total']?></td>
                            </tr>
                            <?php
                                }
                            ?>
                        </tbody>
                    </table>
                    <h2>ประวัติการแลกคะแนนเป็นส่วนลด</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <th>#</th>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Coupon Code</th>
                            <th>คะแนนที่แลก</th>
                            <th>เท่ากับ</th>
                            <th>แลกเมื่อ</th>
                            <th>สถานะ</th>
                        </thead>
                        <tbody>
                            <?php
                            global $wpdb;
                            $redeem_history_table = $wpdb->prefix . "redeem_history";
                            $user_table = $wpdb->users;
                            $redeem_history = $wpdb->get_results("SELECT r.id, u.display_name, r.coupon_code, r.points_used, r.status, r.created_at, r.user_id 
                            FROM $redeem_history_table as r JOIN $user_table as u ON u.ID = r.user_id ORDER BY r.created_at DESC");

                            foreach($redeem_history as $row) {
                            ?>
                            <tr>
                                <td><?=$row->id?></td>
                                <td><?=$row->user_id?></td>
                                <td><?=$row->display_name?></td>
                                <td><?=$row->coupon_code?></td>
                                <td><?=$row->points_used?></td>
                                <td><?=($row->points_used / (int)get_option("membership_baht_per_point"))?> บาท</td>
                                <td><?=$row->created_at?></td>
                                <td><?php if($row->status == "unused") { echo "<span style='color: red;'>ยังไม่ถูกใช้งาน</span>"; } else { echo "<span style='color: green;'>ใช้งานแล้ว</span>"; } ?></td>
                            </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <?php
                } else {
                ?>
                <h1>WooCommerce Membership Plugin</h1>
                <div style="padding: 0 25px 25px 25px;">
                    <h2>ระบบนี้คืออะไร ?</h2>
                    <p>ระบบ WooCommerce Membership คือระบบที่ออกแบบมาเพื่อรองรับการทำการตลาดบนเว็บไซต์ โดยลูกค้าสามารถสะสมคะแนนจากการซื้อสินค้าภายในเว็บไซต์ 
                        ลูกค้าสามารถรับส่วนลดทั้งตะกร้าตามระดับสมาชิกที่ถูกกำหนดตามเกณฑ์การคิดระดับสมาชิก ลูกค้าจะได้รับส่วนลดสำหรับ Brand พิเศษที่กำหนดไว้โดยคิดตามเกณฑ์ราคา 
                        ลูกค้าสามารถแลกคะแนนเป็นส่วนลด
                    </p>
                    <h2>วิธีการติดตั้ง</h2>
                    <p>
                        สามารถติดตั้งปลั้กอินนี้ได้โดยการดาวน์โหลดไฟล์นี้จาก Github หน้านี้ และอัพโหลดลงในหน้า /wp-admin/plugin-install.php หลังจากอัพโหลด 
                        และเปิดใช้งาน (Activate) ระบบจะทำการสร้างตารางและคอลัมน์ใหม่จากตารางเดิมโดยอัตโนมัติ
                    </p>
                </div>
                <?php
                }
                ?>
            </div>
        </div>
    </div>
    <?php
}

add_action('admin_init', 'membership_tier_settings_init');

function membership_tier_settings_init()
{
    register_setting('membership_settings_group_redeem_from_score', 'membership_enable_redeem');
    register_setting('membership_settings_group_redeem_from_score', 'membership_baht_per_point');
    // ลงทะเบียนค่าสำหรับแต่ละ Tier
    // Platinum
    register_setting('membership_settings_group_member_privilege', 'membership_point_per_baht');
    register_setting('membership_settings_group_member_privilege', 'ms_platinum_score');
    register_setting('membership_settings_group_member_privilege', 'ms_platinum_discount');
    // Gold
    register_setting('membership_settings_group_member_privilege', 'ms_gold_score');
    register_setting('membership_settings_group_member_privilege', 'ms_gold_discount');
    // Silver
    register_setting('membership_settings_group_member_privilege', 'ms_silver_score');
    register_setting('membership_settings_group_member_privilege', 'ms_silver_discount');
    register_setting('membership_settings_group_member_privilege', 'no_discount_self_pickup');

    //Member Privileges Discount
    register_setting('membership_settings_group_special_offers', 'membership_enable_member_privileges');
    register_setting('membership_settings_group_special_offers', 'member-privileges-slug');
    register_setting('membership_settings_group_special_offers', 'member-privileges-silver');
    register_setting('membership_settings_group_special_offers', 'member-privileges-gold');
    register_setting('membership_settings_group_special_offers', 'member-privileges-platinum');

    register_setting('membership_settings_group_member_privilege', 'ms_card_title');
    register_setting('membership_settings_group_member_privilege', 'ms_platinum_description_title');
    register_setting('membership_settings_group_member_privilege', 'ms_platinum_description_content');
    register_setting('membership_settings_group_member_privilege', 'ms_gold_description_title');
    register_setting('membership_settings_group_member_privilege', 'ms_gold_description_content');
    register_setting('membership_settings_group_member_privilege', 'ms_silver_description_title');
    register_setting('membership_settings_group_member_privilege', 'ms_silver_description_content');

    register_setting('membership_settings_group_member_privilege', 'member-privileges-silver-color');
    register_setting('membership_settings_group_member_privilege', 'member-privileges-gold-color');
    register_setting('membership_settings_group_member_privilege', 'member-privileges-platinum-color');

    register_setting('membership_settings_group_member_privilege', 'member-privileges-silver-single-color');
    register_setting('membership_settings_group_member_privilege', 'member-privileges-gold-single-color');
    register_setting('membership_settings_group_member_privilege', 'member-privileges-platinum-single-color');

    //Brand Privileges
    register_setting('membership_settings_group_brands_privilege', 'brands_privilege_enable');
    register_setting('membership_settings_group_brands_privilege', 'brands_privilege_list');

    register_setting('membership_settings_group_brands_privilege', 'brands_privilege_step_01_start');
    register_setting('membership_settings_group_brands_privilege', 'brands_privilege_step_01_end');
    register_setting('membership_settings_group_brands_privilege', 'brands_privilege_step_01_discount_percent');

    register_setting('membership_settings_group_brands_privilege', 'brands_privilege_step_02_start');
    register_setting('membership_settings_group_brands_privilege', 'brands_privilege_step_02_end');
    register_setting('membership_settings_group_brands_privilege', 'brands_privilege_step_02_discount_percent');

    register_setting('membership_settings_group_brands_privilege', 'brands_privilege_step_03_start');
    register_setting('membership_settings_group_brands_privilege', 'brands_privilege_step_03_end');
    register_setting('membership_settings_group_brands_privilege', 'brands_privilege_step_03_discount_percent');

    register_setting('membership_settings_group_brands_privilege', 'brands_privilege_step_04');
    register_setting('membership_settings_group_brands_privilege', 'brands_privilege_step_04_discount_percent');
}

// ฟังก์ชันคำนวณและเพิ่มคะแนนเมื่อออเดอร์เสร็จสมบูรณ์
add_action('woocommerce_order_status_completed', 'add_points_after_purchase', 10, 1);

function add_points_after_purchase($order_id)
{
    global $wpdb;

    $order = wc_get_order($order_id);

    // --- จุดที่ 1: กันคะแนนเบิ้ล ---
    // เช็คว่าออเดอร์นี้เคยให้คะแนนไปหรือยัง
    if ($order->get_meta('_points_added_to_score') === 'yes') {
        return;
    }

    // --- จุดที่ 2: หาตัวตนจาก Email (เผื่อซื้อแบบ Guest) ---
    $billing_email = $order->get_billing_email();
    $user = get_user_by('email', $billing_email);

    if (!$user)
        return; // ถ้าไม่มี User ในระบบเลยจริงๆ ถึงค่อยข้าม

    $user_id = $user->ID;
    $order_total = $order->get_total(); // หรือใช้ $order->get_subtotal() ถ้าไม่รวมค่าส่ง
    $points_earned = floor($order_total / get_option('membership_point_per_baht', 500));

    if ($points_earned > 0) {
        $table_name = $wpdb->prefix . 'users';

        // บังคับบวกคะแนน
        $wpdb->query($wpdb->prepare(
            "UPDATE $table_name SET score = score + %d WHERE ID = %d",
            $points_earned,
            $user_id
        ));

        // --- จุดที่ 3: บันทึกไว้ว่าให้คะแนนแล้ว ---
        $order->update_meta_data('_points_added_to_score', 'yes');
        $order->save();

        $order->add_order_note(sprintf('เพิ่มคะแนน %d ลงในคอลัมน์ score (User ID: %d)', $points_earned, $user_id));
    }
}

// แสดงคะแนนในหน้า My Account ของลูกค้า
add_action('woocommerce_before_my_account', 'display_customer_points');

function display_customer_points()
{
    $user_id = get_current_user_id();
    global $wpdb;
    $points = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT score FROM {$wpdb->prefix}users WHERE ID = %d",
        $user_id
    ));

    // คำนวณความกว้างของ Progress Bar (สมมติเป้าหมายสูงสุดที่ 30 คะแนน)
    $max_points = 30;
    $percentage = ($points / $max_points) * 100;
    if ($percentage > 100) $percentage = 100;

    // กำหนดสีตามช่วงคะแนน
    $bar_color = '#CCC';
    if ($points > 0) $bar_color = esc_attr(get_option('member-privileges-silver-single-color')); // Silver
    if ($points >= 10) $bar_color = esc_attr(get_option('member-privileges-gold-single-color')); // Gold
    if ($points >= 20) $bar_color = esc_attr(get_option('member-privileges-platinum-single-color')); // Platinum
    ?>
    <style>
        .progress-fill {
            height: 100%;
            background: <?php echo $bar_color; ?>;
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        .dot.active {
            border-color: <?php echo $bar_color; ?>;
        }
        .points-value {
            font-size: 28px;
            font-weight: bold;
            color: <?php echo $bar_color;?>;
        }
        #description-platinum {
            background: <?php echo esc_attr(get_option('member-privileges-platinum-color')); ?>;
            background: linear-gradient(0deg,rgba(162, 22, 46, 1) 0%, rgba(162, 22, 46, 1) 50%, rgba(110, 27, 27, 1) 100%);
            opacity: 0.2;
        }
        #description-gold {
            background: <?php echo esc_attr(get_option('member-privileges-gold-color')); ?>;
            background: linear-gradient(0deg,rgba(193, 172, 81, 1) 0%, rgba(193, 172, 81, 1) 56%, rgba(158, 138, 40, 1) 100%);
            opacity: 0.2;
        }
        #description-silver {
            background: <?php echo esc_attr(get_option('member-privileges-silver-color')); ?>;
            background: linear-gradient(0deg,rgba(87, 86, 86, 1) 0%, rgba(140, 137, 137, 1) 56%, rgba(179, 179, 179, 1) 100%);
            opacity: 0.2;
        }
    </style>
    <div class="accordion" id="membership">
        <div class="card">
            <div class="card-header"><button class="btn btn-link" data-toggle="collapse" data-target="#membershipAccordion" aria-expanded="true" aria-controls="membershipAccordion">👑 คะแนนสะสมและระดับ Membership</button></h5></div>
            <div id="membershipAccordion" class="collapse show" data-parent="#membership">
            <div class="card-body">
                <div class="rewards-container">
                    <div class="points-header">
                        <div>
                            <span style="display:block; color:#888; font-size:16px;">คะแนนสะสมและระดับ Membership</span>
                            <span style="display:block; color:#888; font-size:14px;">(ซื้อครบ <?=number_format(get_option('membership_point_per_baht', 500))?> บาท = 1 คะแนน)</span>
                            <span class="points-value" id="user_score"><?php echo number_format($points); ?></span> <small>คะแนน</small>
                        </div>
                        <div style="text-align: right; font-size: 14px; color: #aaa;"><?php
                                if($points < get_option('ms_silver_score', 10)) echo "อีก " . (10 - $points) . " คะแนนเพื่อเป็นระดับ Silver และรับส่วนลด 1% เมื่อซื้อสินค้าผ่านเว็บไซต์";
                                elseif($points < get_option('ms_gold_score', 20)) echo "อีก " . (20 - $points) . " คะแนนเพื่อเป็นระดับ Gold และรับส่วนลด 2% เมื่อซื้อสินค้าผ่านเว็บไซต์";
                                elseif($points < get_option('ms_platinum_score', 30)) echo "อีก " . (30 - $points) . " คะแนนเพื่อเป็นระดับ Platinum และรับส่วนลด 3% เมื่อซื้อสินค้าผ่านเว็บไซต์";
                                else echo "คุณอยู่ในระดับ Platinum เรียบร้อยแล้ว!";
                        ?></div></div>
            
                    <div class="progress-track">
                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%;"></div>
                        <div class="milestones">
                            <div class="dot active"><span class="dot-label">0</span></div>
                            <div class="dot <?php echo ($points >= get_option('ms_silver_score', 10)) ? 'active' : ''; ?>" style="left: 33.33%; position: absolute;"><span class="dot-label"><?=get_option('ms_silver_score', 30)?></span></div>
                            <div class="dot <?php echo ($points >= get_option('ms_gold_score', 20)) ? 'active' : ''; ?>" style="left: 66.66%; position: absolute;"><span class="dot-label"><?=get_option('ms_gold_score', 30)?></span></div>
                            <div class="dot <?php echo ($points >= get_option('ms_platinum_score', 30)) ? 'active' : ''; ?>" style="right: 0; position: absolute;"><span class="dot-label"><?=get_option('ms_platinum_score', 30)?>+</span></div>
                        </div>
                    </div>
                    <div class="membership-description">
                        <div class="card" id="description-silver" style="left: 0%; <?php if($points >= get_option('ms_silver_score', 10) && $points < get_option('ms_gold_score', 20)) { echo 'opacity: 1;'; } ?>">
                            <div class="card_header">
                                <span class="card_logo"><?=get_option('ms_card_title');?></span>
                                <h2 id="silver"><?=get_option('ms_silver_description_title');?></h2>
                            </div>
                            <span class="display_name"><?=wp_get_current_user()->display_name?></span><span class="card_number">1155 1854 7745</span>
                            <?=get_option('ms_silver_description_content');?>
                            <div class="card_points">
                                <span class="points"><?=get_option('ms_silver_score', 10)?></span><span class="text">Points</span>
                            </div>
                        </div>
                        <div class="card" id="description-gold" style="left: 0%; <?php if($points >= get_option('ms_gold_score', 20) && $points < get_option('ms_platinum_score', 30)) { echo 'opacity: 1;'; } ?>">
                            <div class="card_header">
                                <span class="card_logo"><?=get_option('ms_card_title');?></span>
                                <h2 id="gold"><?=get_option('ms_gold_description_title');?></h2>
                            </div>
                            <span class="display_name"><?=wp_get_current_user()->display_name?></span><span class="card_number">1155 1854 7745</span>
                            <?=get_option('ms_gold_description_content');?>
                            <div class="card_points">
                                <span class="points"><?=get_option('ms_gold_score', 20)?></span><span class="text">Points</span>
                            </div>
                        </div>
                        <div class="card" id="description-platinum" style="left: 0%; <?php if($points >= get_option('ms_platinum_score', 30)) { echo 'opacity: 1;'; } ?>">
                            <div class="card_header">
                                <span class="card_logo"><?=get_option('ms_card_title');?></span>
                                <h2 id="platinum"><?=get_option('ms_platinum_description_title');?></h2>
                            </div>
                            <span class="display_name"><?=wp_get_current_user()->display_name?></span><span class="card_number">1155 1854 7745</span>
                            <?=get_option('ms_platinum_description_content');?>
                            <div class="card_points">
                                <span class="points"><?=get_option('ms_platinum_score', 30)?></span><span class="text">Points</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * คำนวณส่วนลดตามคะแนน (Level) ของลูกค้าที่หน้า Checkout
 */
add_action('woocommerce_cart_calculate_fees', 'apply_tier_discount_based_on_score', 20, 1);

function apply_tier_discount_based_on_score($cart)
{
    if (is_admin() && !defined('DOING_AJAX'))
        return;

    // ดึง User ID ของคนที่กำลังจะซื้อ
    $user_id = get_current_user_id();
    if (!$user_id)
        return; // ถ้าเป็น Guest ไม่ได้ส่วนลด

    global $wpdb;

    $chosen_methods = WC()->session->get('chosen_shipping_methods');
    
    if(get_option('no_discount_self_pickup', "yes") == "yes") {
        if (isset($chosen_methods[0]) && strpos($chosen_methods[0], '_selfpickup') !== false) {
            return;
        }
    }

    // ดึงคะแนนจากคอลัมน์ score ในตาราง wpln_users
    $score = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT score FROM {$wpdb->prefix}users WHERE ID = %d",
        $user_id
    ));

    // กำหนดเงื่อนไขส่วนลด (Logic: 10 แต้ม = 1%, 20 แต้ม = 2%, 30 แต้ม = 3%)
    $discount_percentage = 0;

    $p_score = get_option('ms_platinum_score', 30);
    $p_discount = get_option('ms_platinum_discount', 3) / 100; // หาร 100 เพราะเก็บเป็นเลขจำนวนเต็ม

    $g_score = get_option('ms_gold_score', 20);
    $g_discount = get_option('ms_gold_discount', 2) / 100;

    $s_score = get_option('ms_silver_score', 10);
    $s_discount = get_option('ms_silver_discount', 1) / 100;

    // Logic การเช็คระดับ
    if ($score >= $p_score) {
        $discount_percentage = $p_discount;
        $level_name = "Platinum Membership (" . (get_option('ms_platinum_discount', 3)) . "%)";
    } elseif ($score >= $g_score) {
        $discount_percentage = $g_discount;
        $level_name = "Gold Membership (" . (get_option('ms_gold_discount', 2)) . "%)";
    } elseif ($score >= $s_score) {
        $discount_percentage = $s_discount;
        $level_name = "Silver Membership (" . (get_option('ms_silver_discount', 1)) . "%)";
    } else {
        $discount_percentage = 0;
        $level_name = "General Member";
    }

    // ถ้ามีส่วนลด ให้คำนวณยอดแล้วหักออก
    if ($discount_percentage > 0) {
        // คำนวณจากราคาสินค้ารวมในตะกร้า (ไม่รวมภาษี/ค่าส่ง หรือจะใช้ get_subtotal ก็ได้)
        $discount_amount = $cart->get_subtotal() * $discount_percentage;

        // บรรทัดนี้จะเพิ่มรายการส่วนลดเข้าไปในหน้า Checkout (ค่าติดลบเพื่อให้เป็นส่วนลด)
        $cart->add_fee(__('ส่วนลดพิเศษ: ' . $level_name, 'woocommerce'), -$discount_amount);
    }
}

// แลกคะแนนเป็นส่วนลด
add_action('woocommerce_before_my_account', 'redeem_form_in_my_account');

function redeem_form_in_my_account()
{
    if(get_option('membership_enable_redeem', 'no') === "no") return;

    global $wpdb;
    if (!is_user_logged_in())
        return;

    $user_id = get_current_user_id();
    // ดึงคะแนนจากตาราง users โดยตรง
    $score = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT score FROM {$wpdb->prefix}users WHERE ID = %d",
        $user_id
    ));

    ?>
    <div class="accordion" id="redeem">
        <div class="card">
            <div class="card-header"><button class="btn btn-link" data-toggle="collapse" data-target="#redeemAccordion" aria-expanded="true" aria-controls="redeemAccordion">🎁 แลกคะแนนเป็นส่วนลด</button>
            </div>
            <div id="redeemAccordion" class="collapse show" data-parent="#redeem">
            <div class="card-body">
                <div class="redeem-container" style="background:#fff; border-radius:8px; padding:25px; margin-bottom:30px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);">
                    <p style="color:#666; font-size:14px;">คะแนนปัจจุบัน: <strong id="current-user-score"
                            style="color:#27ae60; font-size:18px;"><?php echo number_format($score); ?></strong> คะแนน (1 คะแนน = <?=number_format(get_option('membership_baht_per_point', 1));?> บาท)
                    </p>

                    <div style="display:flex; gap:10px; margin-top:15px;">
                        <input type="number" id="redeem-amount" placeholder="ระบุจำนวนคะแนนที่ต้องการแลก" min="1" max="<?php echo $score; ?>"
                            style="flex-grow:1; padding:10px; border:1px solid #ddd; border-radius:4px;">
                        <button id="redeem-btn" class="button button-primary">แลกโค้ดส่วนลด</button>
                    </div>
                    <div id="redeem-result" style="margin-top:15px; display:none;"></div>

                    <?php
                    $history = $wpdb->get_results( $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}redeem_history WHERE user_id = %d ORDER BY created_at DESC",
                        $user_id
                    ) );

                    if ( $history ) { ?>
                    <div style="overflow: auto;">
                        <table style="width: 100%; border-collapse: collapse; margin-top:15px; white-space: nowrap;">
                            <thead>
                                <tr style="background:#f8f8f8;">
                                    <th style="padding:10px; border-bottom:1px solid #ddd;">วันที่</th>
                                    <th style="padding:10px; border-bottom:1px solid #ddd;">โค้ด</th>
                                    <th style="padding:10px; border-bottom:1px solid #ddd;">คะแนนที่ใช้</th>
                                    <th style="padding:10px; border-bottom:1px solid #ddd;">สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $row) : 
                                    // เช็คสถานะจริงจาก WooCommerce อีกทีเพื่อความชัวร์
                                    $c = new WC_Coupon($row->coupon_code);
                                    $display_status = ($c->get_usage_count() > 0) ? 'ใช้แล้ว' : 'ยังไม่ได้ใช้';
                                    $status_color = ($c->get_usage_count() > 0) ? '#999' : '#27ae60';
                                ?>
                                    <tr>
                                        <td style="padding:10px; border-bottom:1px solid #eee; text-align:center;"><?php echo date('j M Y', strtotime($row->created_at)); ?></td>
                                        <td style="padding:10px; border-bottom:1px solid #eee; text-align:center;"><code><?php echo $row->coupon_code; ?></code></td>
                                        <td style="padding:10px; border-bottom:1px solid #eee; text-align:center;"><?php echo number_format($row->points_used); ?></td>
                                        <td style="padding:10px; border-bottom:1px solid #eee; text-align:center; color:<?php echo $status_color; ?>;"><?php echo $display_status; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        jQuery(document).ready(function ($) {
            $('#redeem-btn').on('click', function (e) {
                e.preventDefault();
                var amount = $('#redeem-amount').val();
                if (!amount || amount <= 0) {
                    alert('กรุณากรอกจำนวนคะแนนที่ถูกต้อง');
                    return;
                }
                var btn = $(this);
                btn.text('กรุณารอสักครู่...').prop('disabled', true);
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'redeem_points',
                        amount: amount,
                        security: '<?php echo wp_create_nonce("redeem-nonce"); ?>'
                    },
                    success: function (res) {
                        // ตรวจสอบว่า res เป็น Object หรือไม่
                        if (res.success) {
                            var htmlResult =
                                `<div style="background:#e7fbd3; padding:15px; border-radius:4px; border:1px solid #2ecc71; color:#155724;">
                            สำเร็จ! โค้ดส่วนลดของคุณคือ: <strong style="font-size:18px;">${res.data.coupon}</strong>
                            <br>(ลด ${res.data.amount} บาท)
                        </div>`;
                            $('#redeem-result').html(htmlResult).fadeIn();
                            $('#current-user-score').text(res.data.new_score);
                            $('#user_score').text(res.data.new_score);
                            $('#user_score_header').text(res.data.new_score);
                            $('#redeem-amount').val('').attr('max', res.data.new_score_raw);
                        } else {
                            alert(res.data || 'เกิดข้อผิดพลาดในการแลกคะแนน');
                        }
                    },
                    error: function () {
                        alert('ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้');
                    },
                    complete: function () {
                        btn.text('แลกโค้ดส่วนลด').prop('disabled', false);
                    }
                });
            });
        });
    </script>
    <?php
}

add_action('wp_ajax_redeem_points', 'handle_ajax_redeem');

function handle_ajax_redeem()
{
    check_ajax_referer('redeem-nonce', 'security');

    global $wpdb;
    $user_id = get_current_user_id();
    $amount = intval($_POST['amount']);

    // ดึงคะแนนจากตาราง users เพื่อตรวจสอบ
    $current_score = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT score FROM {$wpdb->prefix}users WHERE ID = %d",
        $user_id
    ));

    if ($amount <= 0 || $current_score < $amount) {
        wp_send_json_error('คะแนนไม่เพียงพอ หรือระบุจำนวนไม่ถูกต้อง');
    }

    // สร้าง Coupon ใน WooCommerce
    $coupon_code = 'REDEEM-' . strtoupper(wp_generate_password(8, false));
    $coupon = new WC_Coupon();
    $coupon->set_code($coupon_code);
    $coupon->set_amount(($amount * get_option('membership_baht_per_point', 1)));
    $coupon->set_discount_type('fixed_cart');
    $coupon->set_individual_use(true);
    $coupon->set_usage_limit(1);
    $coupon->set_description('Redeemed via My Account by User ID: ' . $user_id);
    $coupon->save();

    // หักคะแนนในตาราง users
    $new_score = $current_score - $amount;
    $wpdb->update(
        "{$wpdb->prefix}users",
        array('score' => $new_score),
        array('ID' => $user_id),
        array('%d'),
        array('%d')
    );

    $wpdb->insert(
    "{$wpdb->prefix}redeem_history",
    array(
        'user_id'     => $user_id,
        'coupon_code' => $coupon_code,
        'points_used' => $amount,
        'status'      => 'unused'
    ), array('%d', '%s', '%d', '%s'));

    wp_send_json_success(array(
        'coupon' => $coupon_code,
        'amount' => number_format($amount),
        'new_score' => number_format($new_score),
        'new_score_raw' => $new_score
    ));
}

/**
 * อัปเดตสถานะในตาราง wp_redeem_history เมื่อคูปองถูกใช้งานในคำสั่งซื้อ
 */
add_action( 'woocommerce_order_status_completed', 'update_redeem_status_on_apply' );

function update_redeem_status_on_apply( $coupon_code ) {
    global $wpdb;
    
    // ทำความสะอาดชื่อคูปองก่อนค้นหา
    $coupon_code = sanitize_text_field( $coupon_code );
    $table_name = $wpdb->prefix . 'redeem_history';

    // อัปเดตสถานะเป็น 'used' เฉพาะแถวที่มี Code ตรงกัน
    $wpdb->update(
        $table_name,
        array( 'status' => 'used' ), // ข้อมูลที่ต้องการเปลี่ยน
        array( 'coupon_code' => $coupon_code ), // เงื่อนไข
        array( '%s' ), // Format ของข้อมูลใหม่
        array( '%s' )  // Format ของเงื่อนไข
    );
}

/**
 * สร้างสถานะสินค้าลดราคาสำหรับสมาชิก
 */
add_filter('woocommerce_product_is_on_sale', 'member_check_is_on_sale', 10, 2);
function member_check_is_on_sale($is_on_sale, $product) {
    if(get_option('membership_enable_member_privileges', 'no') === "no") return;

    if (!is_user_logged_in()) return $is_on_sale;

    $discount_rate = getUserLevel("percent");

    // ถ้าเรทเป็น 1 (คะแนนไม่ถึง 10) ห้ามบอกว่า On Sale เด็ดขาด
    if ($discount_rate >= 1) {
        return false; 
    }

    $special_categories = array(explode("\n", get_option('member-privileges-slug-name', 'member-privileges')));
    if (has_term($special_categories, 'product_cat', $product->get_id())) {
        return true;
    }
    return $is_on_sale;
}

function getUserLevel($option) {
    global $wpdb;
    $user_id = get_current_user_id();

    // 1. ดึงคะแนนจากตาราง users
    $user_score = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT score FROM {$wpdb->prefix}users WHERE ID = %d",
        $user_id
    ));

    // 2. เช็คระดับสมาชิก (เรียงจากระดับสูงสุดลงมา)
    if ($user_score >= get_option('ms_platinum_score', 30)) {
        $level_name = "platinum";
    } 
    elseif ($user_score >= get_option('ms_gold_score', 20)) {
        $level_name = "gold";
    } 
    elseif ($user_score >= get_option('ms_silver_score', 10)) {
        $level_name = "silver";
    } else {
        if($option == "percent") {
            return 1;
        } else {
            return null;
        }
    }

    $discount_percent = get_option("member-privileges-$level_name", 0);

    if($option == "percent") {
        return  1 - ($discount_percent / 100);
    } else {
        return $level_name;
    }
}

/**
 * 2. คำนวณราคาพิเศษ และแสดงผลแบบ ขีดฆ่า (Price HTML)
 */
add_filter('woocommerce_get_price_html', 'member_display_custom_price_html', 10, 2);
function member_display_custom_price_html($price_html, $product) {
    if(get_option('membership_enable_member_privileges', 'no') === "no") return $price_html;
    if (!is_user_logged_in() || is_admin()) return $price_html;
    
    // ตรวจสอบ ID (ถ้าเป็น Variation ให้ดึง Parent ID มาเช็คหมวดหมู่)
    $product_id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
    $special_categories = array('member-privileges'); // แก้เป็น Slug ที่คุณใช้งานจริง

    if (has_term($special_categories, 'product_cat', $product_id)) {
        
        $discount_rate = getUserLevel("percent");
        $level_name = getUserLevel('name');

        if ($discount_rate >= 1 || empty($level_name)) return $price_html;

        // --- กรณีที่ 1: สินค้ามีหลายราคา (Variable Product) ---
        if ($product->is_type('variable')) {
            $min_reg_price = $product->get_variation_regular_price('min');
            $max_reg_price = $product->get_variation_regular_price('max');
            
            $min_sale_price = $min_reg_price * $discount_rate;
            $max_sale_price = $max_reg_price * $discount_rate;

            // สร้าง HTML ขีดฆ่าช่วงราคาเดิม
            $original_price_html = wc_price($min_reg_price) . ' – ' . wc_price($max_reg_price);
            $new_price_html = wc_price($min_sale_price) . ' – ' . wc_price($max_sale_price);

            $price_html = '<del aria-hidden="true" style="color: #636363;">' . $original_price_html . '</del> ';
            $price_html .= '<ins style="text-decoration:none;">' . $new_price_html . '</ins>';
        } 
        // --- กรณีที่ 2: สินค้าราคาเดียว (Simple Product) ---
        else {
            $regular_price = (float)$product->get_regular_price();
            $sale_price = $regular_price * $discount_rate;
            
            if ($sale_price >= $regular_price) return $price_html;

            $price_html = '<del aria-hidden="true" style="color: #636363;">' . wc_price($regular_price) . '</del> ';
            $price_html .= '<ins style="text-decoration:none;">' . wc_price($sale_price) . '</ins>';
        }

        $price_html .= ' <span class="member-tag" style="font-size:12px; color:#27ae60; display:block; margin-top: 5px;">(ราคาสำหรับสมาชิก ' . ucfirst($level_name) . ')</span>';
    }

    return $price_html;
}

add_filter('woocommerce_available_variation', function($data, $product, $variation) {
    if(get_option('membership_enable_member_privileges', 'no') === "no") return $data;
    if (!is_user_logged_in()) return $data;

    $discount_rate = getUserLevel("percent");
    $level_name = getUserLevel('name');
    
    if ($discount_rate < 1 && has_term('member-privileges', 'product_cat', $product->get_id())) {
        $reg_price = (float)$variation->get_regular_price();
        $sale_price = $reg_price * $discount_rate;
        
        // แก้ไขการแสดงผลราคาใน JSON ที่ส่งไปหน้าบ้าน
        $data['price_html'] = '<del>' . wc_price($reg_price) . '</del> <ins>' . wc_price($sale_price) . '</ins>';
        $data['display_price'] = $sale_price;
    }
    return $data;
}, 10, 3);

/**
 * บังคับเปลี่ยนราคาในตะกร้าให้เป็นราคาสมาชิก
 */
add_action( 'woocommerce_before_calculate_totals', 'apply_member_cart_price', 999, 1 );

function apply_member_cart_price( $cart ) {
    if(get_option('membership_enable_member_privileges', 'no') === "no") return;
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    if ( ! is_user_logged_in() ) return;

    $discount_rate = getUserLevel("percent");
    if ( $discount_rate >= 1 ) return;

    foreach ( $cart->get_cart() as $cart_item ) {
        $product = $cart_item['data'];
        
        // เช็ค ID ตัวแม่ถ้าเป็น Variation
        $target_id = $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();

        if ( has_term( 'member-privileges', 'product_cat', $target_id ) ) {
            // ใช้ get_price() เพื่อให้ดึงราคาปัจจุบันของ Variation นั้นๆ
            $regular_price = (float)$product->get_regular_price();
            
            // ถ้า regular_price ว่าง (บางทีตั้งแค่ราคาขาย) ให้ใช้ get_price
            if(!$regular_price) $regular_price = (float)$product->get_price();

            $member_price = $regular_price * $discount_rate;
            $product->set_price( $member_price );
        }
    }
}

/**
 * บังคับให้แสดงป้ายเปอร์เซ็นต์ส่วนลดสมาชิก ในโครงสร้าง HTML ของธีม
 */
add_filter('woocommerce_sale_flash', 'member_custom_sale_badge', 10, 3);

function member_custom_sale_badge($html, $post, $product) {
    if(get_option('membership_enable_member_privileges', 'no') === "no") return;
    // 1. เช็คว่าล็อกอินไหม
    if (!is_user_logged_in()) return $html;

    // 2. กำหนดหมวดหมู่เป้าหมาย (ให้ตรงกับที่ใช้ในส่วนราคา)
    $target_slugs = array( 'สิทธิพิเศษสำหรับสมาชิก' ); 
    $discount_rate = getUserLevel('percent');
    if ($discount_rate >= 1) return $html;
    
    // 3. เช็คว่าอยู่ในหมวดไหม
    if (has_term($target_slugs, 'product_cat', $product->get_id())) {
        return '<div class="product-lable">
                    <div class="onsale">'.((1 - $discount_rate) * 100).'%</div>
                </div>';
    }

    return $html;
}

add_filter('woocommerce_product_is_on_sale', function($is_on_sale, $product) {
    if (!is_user_logged_in()) return $is_on_sale;
    $wcm_target_slugs = array( 'สิทธิพิเศษสำหรับสมาชิก' ); 
    return has_term($wcm_target_slugs, 'product_cat', $product->get_id()) ? true : $is_on_sale;
}, 10, 2);

/**
 * ซ่อนหมวดหมู่จาก Widget โดยระบุเป็น Slug Name
 */
add_filter( 'woocommerce_product_categories_widget_args', 'exclude_widget_category_by_slug' );

function exclude_widget_category_by_slug( $args ) {
    if (!is_user_logged_in() ) {
        $excluded_slugs = array(explode("\n", get_option('member-privileges-slug-name', 'member-privileges'))); 
        $excluded_ids = array();

        foreach ( $excluded_slugs as $slug ) {
            $term = get_term_by( 'slug', $slug, 'product_cat' );
            if ( $term ) {
                $excluded_ids[] = $term->term_id;
            }
        }

        if ( ! empty( $excluded_ids ) ) {
            $current_exclude = isset( $args['exclude'] ) ? (array) $args['exclude'] : array();
            $args['exclude'] = array_merge( $current_exclude, $excluded_ids );
        }

        return $args;
    } else {
        return $args;
    }
}

// บังคับให้ราคาทุกอย่างกลับมาเป็นราคาปกติ ถ้าคะแนนสมาชิกไม่ถึง
add_filter( 'woocommerce_product_get_price', 'force_clean_price', 999, 2 );
add_filter( 'woocommerce_product_get_sale_price', 'force_clean_price', 999, 2 );

function force_clean_price( $price, $product ) {
    if(get_option('membership_enable_member_privileges', 'no') === "no") return $price;
    if ( is_admin() ) return $price;

    $discount_rate = getUserLevel("percent");

    // ถ้าคะแนนไม่ถึง 10 (Rate = 1) 
    if ( $discount_rate >= 1 && has_term(get_option('member-privileges-slug-name', 'member-privileges'), 'product_cat', $product->get_id()) ) {
        // คืนค่าราคาปกติ (Regular Price) เท่านั้น เพื่อไม่ให้เกิดสถานะ Sale
        return $product->get_regular_price();
    }

    return $price;
}

// ปิดสถานะ On Sale แบบเด็ดขาด
add_filter( 'woocommerce_product_is_on_sale', function( $is_on_sale, $product ) {
    if(get_option('membership_enable_member_privileges', 'no') === "no") return false;

    if ( is_admin() ) return $is_on_sale;
    
    $discount_rate = getUserLevel("percent");
    if ( $discount_rate >= 1 && has_term(get_option('member-privileges-slug-name', 'member-privileges'), 'product_cat', $product->get_id()) ) {
        return false;
    }
    return $is_on_sale;
}, 999, 2 );

/**
 * สร้าง Shortcode [user_score] สำหรับแสดงคะแนนสมาชิก
 */
function user_score_shortcode() {
    // 1. เช็คก่อนว่าล็อกอินไหม ถ้าไม่ล็อกอินไม่ต้องโชว์อะไรเลย
    if (!is_user_logged_in()) {
        return '';
    }

    global $wpdb;
    $user_id = get_current_user_id();

    // 2. ดึงคะแนนจากฐานข้อมูล
    $score = $wpdb->get_var($wpdb->prepare(
        "SELECT score FROM {$wpdb->prefix}users WHERE ID = %d",
        $user_id
    ));

    $display_score = $score ? number_format($score) : '0';

    // 3. สร้าง HTML Output (สะสมค่าใส่ตัวแปรแล้วค่อย return)
    $output = '<div class="membership-score">';
    $output .= '    <span class="icon">⭐</span>';
    $output .= '    <span class="score-num" id="user_score_header">' . $display_score . '</span>';
    $output .= '    <span class="label">คะแนน</span>';
    $output .= '</div>';

    return $output;
}
add_shortcode('user_score', 'user_score_shortcode');

add_action('woocommerce_cart_calculate_fees', 'apply_tiered_brand_discount', 20);
function apply_tiered_brand_discount($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    if(get_option('brands_privilege_enable', 'no') == 'no') return;

    // --- 1. ตั้งค่าแบรนด์ (Slug ของ product_brand) และเงื่อนไข ---
    $target_brands = array(explode("\n", get_option('brands_privilege_list'))); //'CHASING', 'Dolphin'
    $eligible_amount = 0;

    // --- 2. คำนวณยอดรวมเฉพาะสินค้าในแบรนด์ที่กำหนด ---
    foreach ($cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        
        // เช็คว่าสินค้ามี Taxonomy 'product_brand' ตรงกับที่เรากำหนดไหม
        // ใช้ has_term( 'slug', 'taxonomy_name', 'product_id' )
        $is_target_brand = false;
        foreach ($target_brands as $brand_slug) {
            if (has_term($brand_slug, 'product_brand', $product_id)) {
                $is_target_brand = true;
                break;
            }
        }

        if ($is_target_brand) {
            // line_total คือราคาสินค้า x จำนวน หลังหักส่วนลดคูปองระดับสินค้าแล้ว
            $eligible_amount += $cart_item['line_total'];
        }
    }

    if ($eligible_amount <= 0) return;

    // --- 3. เช็ค Tier ส่วนลดตามยอดสะสมของแบรนด์ ---
    $discount_percent = 0;
    if ($eligible_amount >= (int) get_option('brands_privilege_step_01_start', 30000) && $eligible_amount <= (int) get_option('brands_privilege_step_01_end', 50000)) {
        $discount_percent = (int) get_option('brands_privilege_step_01_discount_percent', 2);
    } elseif ($eligible_amount >= (int) get_option('brands_privilege_step_02_start', 50001) && $eligible_amount <= (int) get_option('brands_privilege_step_02_end', 70000)) {
        $discount_percent = (int) get_option('brands_privilege_step_02_discount_percent', 3);
    } elseif ($eligible_amount >= (int) get_option('brands_privilege_step_03_start', 70001) && $eligible_amount <= (int) get_option('brands_privilege_step_03_end', 100000)) {
        $discount_percent = (int) get_option('brands_privilege_step_03_discount_percent', 4);
    } elseif ($eligible_amount > (int) get_option('brands_privilege_step_04', 100000)) {
        $discount_percent = (int) get_option('brands_privilege_step_04_discount_percent', 5);
    }

    // --- 4. สั่งลดราคา ---
    if ($discount_percent > 0) {
        $discount_total = ($eligible_amount * $discount_percent) / 100;
        
        // เพิ่มบรรทัดส่วนลดเข้าไป (ยอดติดลบ)
        // ใส่ชื่อแบรนด์รวมๆ หรือระบุว่า Discount ก็ได้ครับ
        $cart->add_fee(
            "ส่วนลดพิเศษ: Brand Privilege ($discount_percent%)", 
            -$discount_total, 
            false
        );
    }
}

add_action('woocommerce_cart_totals_after_order_total', 'display_combined_addon_and_tiered_discount');
add_action('woocommerce_review_order_after_order_total', 'display_combined_addon_and_tiered_discount');

function display_combined_addon_and_tiered_discount() {
    $total_discount = 0;

    // --- 1. ดึงส่วนลดจากคูปอง Add-on (D20-) ---
    $applied_coupons = WC()->cart->get_applied_coupons();
    if (!empty($applied_coupons)) {
        foreach ($applied_coupons as $code) {
            // ดึงยอดส่วนลดของคูปองใบนั้นๆ มาบวกได้เลย
            $total_discount += WC()->cart->get_coupon_discount_amount($code);
        }
    }

    // --- 2. ดึงส่วนลดจาก Tiered Brand Discount (ที่ส่งมาเป็น Fee) ---
    // ปกติ Fee ที่ลดราคามันจะเป็นค่าติดลบ เราเลยต้องเอามาบวกแบบค่าสัมบูรณ์ (abs)
    foreach (WC()->cart->get_fees() as $fee) {
        if ($fee->amount < 0) {
            $total_discount += abs($fee->amount);
        }
    }

    // --- 3. แสดงผลรวมทั้งหมดที่ประหยัดไปได้ ---
    if ($total_discount > 0) {
        ?>
        <div class="totals-discounts">
            <div class="title">
                <span>ประหยัดไปได้ทั้งหมด:</span> 
                <span class="totals" style="color: red;"><?php echo wc_price($total_discount); ?></span>
            </div>
        </div>
        <?php
    }
}

add_action('wp_footer', 'add_level_color_to_user_icon');

function add_level_color_to_user_icon() {
    if (!is_user_logged_in())
        return;
    ?>
    <style>
        .level_color {
            padding: 10px 8px;
            border-radius: 20px;
        }
        .silver.level_color {
            color: <?=get_option('member-privileges-silver-single-color')?>;
        }
        .gold.level_color {
            color: <?=get_option('member-privileges-gold-single-color')?>;   
        }
        .platinum.level_color {
            color: <?=get_option('member-privileges-platinum-single-color')?>;
        }
        .silver.level_color:hover {
            color: <?=get_option('member-privileges-silver-single-color')?> !important;
        }
        .gold.level_color:hover {
            color: <?=get_option('member-privileges-gold-single-color')?> !important;
        }
        .platinum.level_color:hover {
            color: <?=get_option('member-privileges-platinum-single-color')?> !important;
        }
        .user-level-badge {
            width: 65px;
            position: absolute;
            left: -11px;
            font-size: 11px;
            text-align: center;
            color: #fff;
            border-radius: 10px;
            text-transform: capitalize;
        }
        .sticky .user-level-badge {
            display: none;
        }
        .header-mobile .user-level-badge {
            display: none;
        }
        .silver.user-level-badge {
            background: <?=get_option('member-privileges-silver-color')?>;
        }
        .gold.user-level-badge {
            background: <?=get_option('member-privileges-gold-color')?>;
        }
        .platinum.user-level-badge {
            background: <?=get_option('member-privileges-platinum-color')?>;
        }
    </style>
    <script type="text/javascript">
        (function($){
            var target = $('.bwp-header .block-top-link > .widget .widget-custom-menu .widget-title');
            <?php 
            if(getUserLevel('name') == 'silver') {
            ?>
            target.addClass('level_color');
            target.addClass('silver');
            <?php
            }
            ?>
            <?php 
            if(getUserLevel('name') == 'gold') {
            ?>
            target.addClass('level_color');
            target.addClass('gold');
            <?php
            }
            ?>
            <?php 
            if(getUserLevel('name') == 'platinum') {
            ?>
            target.addClass('level_color');
            target.addClass('platinum');
            <?php
            }
            ?>
        })(jQuery);

        (function($){
            var target = $('.bwp-header .block-top-link > .widget .widget-custom-menu');
            
            var span = $('<span class="user-level-badge <?=getUserLevel('name')?>"></span>').text("<?=getUserLevel('name')?>");
            
            target.append(span);
        })(jQuery);
    </script>
    <?php
}

add_filter('nav_menu_item_title', 'add_level_text_to_user_icon', 10, 4);

function add_level_text_to_user_icon($title, $item, $args, $depth) {
    if (!is_user_logged_in())
        return $title;
    
    if (isset($args->container_class) && strpos($args->container_class, 'block-top-link') !== false) {
        $title .= ' <span class="custom-badge">'.getUserLevel('name').'</span>';
    }
    
    return $title;
}