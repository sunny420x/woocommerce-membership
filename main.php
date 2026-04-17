<?php
/**
 * Plugin Name: Membership System For WooCommerce
 * Description: มอบคะแนนสะสมให้ลูกค้า 1 คะแนน ต่อทุกยอดซื้อ 500 บาท สำหรับแลกคูปองส่วนลด
 * Version: 1.0
 * Author: Jirakit Pawnsakunrungrot
 * Author URI: https://www.linkedin.com/in/sunny-jirakit
 * Plugin URI: https://github.com/sunny420x/woocommerce-membership
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('admin_menu', 'worldchem_membership_menu');

function worldchem_membership_menu() {
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

function woocommerce_membership_setting_page() {
    ?>
    <div class="wrap">
        <h1>ตั้งค่าระดับ Membership</h1>
        <hr>
        <form action="options.php" method="post">
            <?php
            settings_fields('membership_settings_group');
            ?>
            <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th>Membership Level</th>
                        <th>Minimum Score (คะแนนขั้นต่ำ)</th>
                        <th>Discount Percentage (%)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Platinum Membership</strong></td>
                        <td><input type="number" name="ms_platinum_score" value="<?php echo esc_attr(get_option('ms_platinum_score', 30)); ?>" /></td>
                        <td><input type="number" step="0.01" name="ms_platinum_discount" value="<?php echo esc_attr(get_option('ms_platinum_discount', 3)); ?>" /> %</td>
                    </tr>
                    <tr>
                        <td><strong>Gold Membership</strong></td>
                        <td><input type="number" name="ms_gold_score" value="<?php echo esc_attr(get_option('ms_gold_score', 20)); ?>" /></td>
                        <td><input type="number" step="0.01" name="ms_gold_discount" value="<?php echo esc_attr(get_option('ms_gold_discount', 2)); ?>" /> %</td>
                    </tr>
                    <tr>
                        <td><strong>Silver Membership</strong></td>
                        <td><input type="number" name="ms_silver_score" value="<?php echo esc_attr(get_option('ms_silver_score', 10)); ?>" /></td>
                        <td><input type="number" step="0.01" name="ms_silver_discount" value="<?php echo esc_attr(get_option('ms_silver_discount', 1)); ?>" /> %</td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button('บันทึกเกณฑ์คะแนน'); ?>
            <hr>
            <?php
            function getMemberShipLevel($score) {
                if($score >= (int) get_option('ms_platinum_score', 30)) {
                    return "Platinum";
                } else if($score >= (int) get_option('ms_gold_score', 20)) {
                    return "Gold";
                } else if($score >= (int) get_option('ms_silver_score', 10)) { 
                    return "Silver";
                } else {
                    return "-";
                }
            }
            ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Display Name</th>
                        <th>Email</th>
                        <th>Score</th>
                        <th>Level</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    global $wpdb;
                    $results = $wpdb->get_results( "SELECT display_name,user_email,ID,score FROM {$wpdb->prefix}users WHERE score > 0 ORDER BY score DESC" );

                    foreach ( $results as $row ) {
                    ?>
                    <tr>
                        <td><?=$row->ID;?></td>
                        <td><?=$row->display_name;?></td>
                        <td><a href="/wp-admin/edit.php?s=<?=$row->user_email?>&post_status=all&post_type=shop_order&action=-1&m=0&_created_via&_customer_user&paged=1&action2=-1" target="_blank"><?=$row->user_email;?></a></td>
                        <td><?=$row->score;?></td>
                        <td><?=getMemberShipLevel($row->score);?></td>
                    </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
            <hr>
            <p>Github Repository: <a href="https://github.com/sunny420x/woocommerce-membership" target="_blank">github.com/sunny420x/woocommerce-membership</a></p>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'membership_tier_settings_init');

function membership_tier_settings_init() {
    // ลงทะเบียนค่าสำหรับแต่ละ Tier
    // Platinum
    register_setting('membership_settings_group', 'ms_platinum_score');
    register_setting('membership_settings_group', 'ms_platinum_discount');
    // Gold
    register_setting('membership_settings_group', 'ms_gold_score');
    register_setting('membership_settings_group', 'ms_gold_discount');
    // Silver
    register_setting('membership_settings_group', 'ms_silver_score');
    register_setting('membership_settings_group', 'ms_silver_discount');
}

// ฟังก์ชันคำนวณและเพิ่มคะแนนเมื่อออเดอร์เสร็จสมบูรณ์
add_action( 'woocommerce_order_status_completed', 'add_points_after_purchase', 10, 1 );

function add_points_after_purchase( $order_id ) {
    global $wpdb;
    
    $order = wc_get_order( $order_id );
    
    // --- จุดที่ 1: กันคะแนนเบิ้ล ---
    // เช็คว่าออเดอร์นี้เคยให้คะแนนไปหรือยัง
    if ( $order->get_meta('_points_added_to_score') === 'yes' ) {
        return;
    }

    // --- จุดที่ 2: หาตัวตนจาก Email (เผื่อซื้อแบบ Guest) ---
    $billing_email = $order->get_billing_email();
    $user = get_user_by( 'email', $billing_email );

    if ( ! $user ) return; // ถ้าไม่มี User ในระบบเลยจริงๆ ถึงค่อยข้าม

    $user_id = $user->ID;
    $order_total = $order->get_total(); // หรือใช้ $order->get_subtotal() ถ้าไม่รวมค่าส่ง
    $points_earned = floor( $order_total / 500 );

    if ( $points_earned > 0 ) {
        $table_name = $wpdb->prefix . 'users';
        
        // บังคับบวกคะแนน
        $wpdb->query( $wpdb->prepare(
            "UPDATE $table_name SET score = score + %d WHERE ID = %d",
            $points_earned,
            $user_id
        ));

        // --- จุดที่ 3: บันทึกไว้ว่าให้คะแนนแล้ว ---
        $order->update_meta_data( '_points_added_to_score', 'yes' );
        $order->save();

        $order->add_order_note( sprintf( 'เพิ่มคะแนน %d ลงในคอลัมน์ score (User ID: %d)', $points_earned, $user_id ) );
    }
}

// (Optional) แสดงคะแนนในหน้า My Account ของลูกค้า
add_action( 'woocommerce_before_my_account', 'display_customer_points' );

function display_customer_points() {
    $user_id = get_current_user_id();
    global $wpdb;
    $points = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT score FROM {$wpdb->prefix}users WHERE ID = %d",
        $user_id
    ) );

    // คำนวณความกว้างของ Progress Bar (สมมติเป้าหมายสูงสุดที่ 30 คะแนน)
    $max_points = 30;
    $percentage = ($points / $max_points) * 100;
    if ($percentage > 100) $percentage = 100;

    // กำหนดสีตามช่วงคะแนน
    $bar_color = '#CCC';
    if ($points > 0) $bar_color = '#1D9DD8'; // Silver
    if ($points >= 10) $bar_color = '#FF9900'; // Gold
    if ($points >= 20) $bar_color = '#106DBA'; // Platinum

    ?>
    <style>
        .rewards-container {
            font-family: 'Kanit';
            margin-bottom: 30px;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .points-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 15px;
        }
        .points-value {
            font-size: 28px;
            font-weight: bold;
            color: <?php echo $bar_color; ?>;
        }
        .progress-track {
            position: relative;
            width: 100%;
            height: 12px;
            background: #eee;
            border-radius: 10px;
            margin: 25px 0;
        }
        .progress-fill {
            height: 100%;
            background: <?php echo $bar_color; ?>;
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        .milestones {
            position: absolute;
            top: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: space-between;
        }
        .dot {
            width: 18px;
            height: 18px;
            background: #fff;
            border: 3px solid #eee;
            border-radius: 50%;
            margin-top: -3px;
            position: relative;
        }
        .dot.active {
            border-color: <?php echo $bar_color; ?>;
        }
        .dot-label {
            position: absolute;
            top: 25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 12px;
            font-weight: bold;
            color: #666;
        }
    </style>

    <div class="rewards-container">
        <div class="points-header">
            <div>
                <span style="display:block; color:#888; font-size:14px;">คะแนนสะสมของคุณ (ซื้อครบ 500 บาท = 1 คะแนน)</span>
                <span class="points-value"><?php echo number_format($points); ?></span> <small>คะแนน</small>
            </div>
            <div style="text-align: right; font-size: 12px; color: #aaa;">
            <?php 
            if($points < 10) echo "อีก " . (10 - $points) . " คะแนนเพื่อเป็นระดับ Silver และรับส่วนลด 1% เมื่อซื้อสินค้าผ่านเว็บไซต์";
            elseif($points < 20) echo "อีก " . (20 - $points) . " คะแนนเพื่อเป็นระดับ Gold และรับส่วนลด 2% เมื่อซื้อสินค้าผ่านเว็บไซต์";
            elseif($points < 30) echo "อีก " . (30 - $points) . " คะแนนเพื่อเป็นระดับ Platinum และรับส่วนลด 3% เมื่อซื้อสินค้าผ่านเว็บไซต์";
            else echo "คุณอยู่ในระดับสูงสุด Platinum เรียบร้อยแล้ว!";
            ?>
            </div>
        </div>

        <div class="progress-track">
            <div class="progress-fill" style="width: <?php echo $percentage; ?>%;"></div>
            <div class="milestones">
                <div class="dot active"><span class="dot-label">0</span></div>
                <div class="dot <?php echo ($points >= 10) ? 'active' : ''; ?>" style="left: 33.33%; position: absolute;"><span class="dot-label">10</span></div>
                <div class="dot <?php echo ($points >= 20) ? 'active' : ''; ?>" style="left: 66.66%; position: absolute;"><span class="dot-label">20</span></div>
                <div class="dot <?php echo ($points >= 30) ? 'active' : ''; ?>" style="right: 0; position: absolute;"><span class="dot-label">30+</span></div>
            </div>
        </div>
        <div style="margin-top: 40px; font-size: 13px; color: #666; text-align: center;">

        </div>
    </div>
    <?php
}

/**
 * คำนวณส่วนลดตามคะแนน (Level) ของลูกค้าที่หน้า Checkout
 */
add_action( 'woocommerce_cart_calculate_fees', 'apply_tier_discount_based_on_score', 20, 1 );

function apply_tier_discount_based_on_score( $cart ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

    // ดึง User ID ของคนที่กำลังจะซื้อ
    $user_id = get_current_user_id();
    if ( ! $user_id ) return; // ถ้าเป็น Guest ไม่ได้ส่วนลด

    global $wpdb;
    
    // 1. ดึงคะแนนจากคอลัมน์ score ในตาราง wpln_users
    $score = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT score FROM {$wpdb->prefix}users WHERE ID = %d",
        $user_id
    ) );

    // 2. กำหนดเงื่อนไขส่วนลด (Logic: 10 แต้ม = 1%, 20 แต้ม = 2%, 30 แต้ม = 3%)
    $discount_percentage = 0;

    $p_score    = get_option('ms_platinum_score', 30);
    $p_discount = get_option('ms_platinum_discount', 3) / 100; // หาร 100 เพราะเก็บเป็นเลขจำนวนเต็ม

    $g_score    = get_option('ms_gold_score', 20);
    $g_discount = get_option('ms_gold_discount', 2) / 100;

    $s_score    = get_option('ms_silver_score', 10);
    $s_discount = get_option('ms_silver_discount', 1) / 100;

    // Logic การเช็คระดับ
    if ( $score >= $p_score ) {
        $discount_percentage = $p_discount;
        $level_name = "Platinum Membership (" . (get_option('ms_platinum_discount', 3)) . "%)";
    } elseif ( $score >= $g_score ) {
        $discount_percentage = $g_discount;
        $level_name = "Gold Membership (" . (get_option('ms_gold_discount', 2)) . "%)";
    } elseif ( $score >= $s_score ) {
        $discount_percentage = $s_discount;
        $level_name = "Silver Membership (" . (get_option('ms_silver_discount', 1)) . "%)";
    } else {
        $discount_percentage = 0;
        $level_name = "General Member";
    }

    // 3. ถ้ามีส่วนลด ให้คำนวณยอดแล้วหักออก
    if ( $discount_percentage > 0 ) {
        // คำนวณจากราคาสินค้ารวมในตะกร้า (ไม่รวมภาษี/ค่าส่ง หรือจะใช้ get_subtotal ก็ได้)
        $discount_amount = $cart->get_subtotal() * $discount_percentage;

        // บรรทัดนี้จะเพิ่มรายการส่วนลดเข้าไปในหน้า Checkout (ค่าติดลบเพื่อให้เป็นส่วนลด)
        $cart->add_fee( __( 'ส่วนลดพิเศษ: ' . $level_name, 'woocommerce' ), -$discount_amount );
    }
}