<?php
/**
 * Plugin Name: DLCOUPON
 * Plugin URI:
 * Description: A simple plugin to manage coupons to WP DB System
 * Version: 1.0.0
 * Author: D. Lev.
 * Author URI: http://e-cv.dograshvili.com/
 * Text Domain:
 *
 *
 */


 add_action('init', 'dlcoupon');

 function dlcoupon() {
     add_action('woocommerce_applied_coupon', 'handle_coupon_add');
     add_action('woocommerce_removed_coupon', 'handle_coupon_remove');
     add_action('rest_api_init', function() {
        register_rest_route('dlcoupon/', 'used/', [
            'methods'  => 'POST',
            'callback' => 'get_coupon_info_used'
        ]);
    });
 }

 function my_start_session () {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
 }

 function my_get_coupon_data($code = '') {
     global $woocommerce;
     $ret = [];
     if ($code) {
         $ret = new WC_Coupon($code);
     }
     return $ret;
 }

 function my_get_coupon_email($code = '') {
     $ret = '';
     if ($code) {
         $id = wc_get_coupon_id_by_code($code);
         if ($id) {
            $email = get_post_meta($id, 'dl_email_responsible', true);
            if ($email) {
                $ret = $email;
            }
         }
     }
     return $ret;
 }

 function handle_coupon_add($code = '') {
    my_start_session();
    $email = my_get_coupon_email($code);
    if ($email) {
        $_SESSION['DL_TEST_EXTRA_EMAILS_SEND'][] = [
            'email' => $email
        ];
    }
 }

 function handle_coupon_remove($code = '') {
    my_start_session();
    if (isset($_SESSION['DL_TEST_EXTRA_EMAILS_SEND'])) {
        unset($_SESSION['DL_TEST_EXTRA_EMAILS_SEND']);
    }
 }

 function get_coupon_info_used($req) {
    $params = $req->get_params();
	$ret = ['success' => false, 'msg' => 'GEN_ERR', 'data' => []];
	try {
		if ($params['coupons']) {
            $coupons = explode(',', $params['coupons']);
            if (!empty($coupons)) {
                $ret['success'] = true;
                $ret['msg'] = '';
                foreach ($coupons as $code) {
                    $id = wc_get_coupon_id_by_code($code);
                    $ret['data'][] = [
                        "code" => $code,
                        "id" => $id,
                        "used" => intval(get_post_meta($id, "usage_count", true))
                    ];
                }
            } else {
                $ret['msg'] = 'COUPONS_ARR_IS_EMPTY';
            }
        } else {
            $ret['msg'] = 'COUPONS_NOT_PROVIDED';
        }
	} catch (\Exception $e) {
		$ret = [
			'success' => false,
			'msg' => 'FATAL',
			'data' => [
				'fatal_msg' => $e->getMessage()
			]
		];
	}
	$response = new WP_REST_Response($ret);
	$response->set_status(200);
	return $response;
 }