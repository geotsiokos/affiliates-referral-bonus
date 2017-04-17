<?php
if ( !defined( 'ABSPATH' ) ) {
	exit;
}
Class Affiliates_Referral_Bonus_Core {
	
	const PLUGIN_OPTIONS = 'arb-options';
	const REFERRALS_AMOUNT = 'reff-amount';
	const COUPON_AMOUNT = 'coupon-amount';
	const DISCOUNT_TYPE = 'discount-type';
	// const COUPON_EXPIRY_DATE = 'coupon-expiry-date';
	const DELETE_DATA = 'delete-data';
	
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'arb_check_dependencies' ) );
	}
	
	/**
	 * Check plugin dependencies
	 * if not met, print an admin notice
	 */
	public static function arb_check_dependencies () {
		$active_plugins = get_option( 'active_plugins', array() );
		$affiliates_is_active = in_array( 'affiliates/affiliates.php', $active_plugins ) || in_array( 'affiliates-pro/affiliates-pro.php', $active_plugins ) || in_array( 'affiliates-enterprise/affiliates-enterprise.php', $active_plugins );
		$woocommerce_is_active = in_array( 'woocommerce/woocommerce.php', $active_plugins );
	
		if ( !$affiliates_is_active ) {
			echo "<div class='error'>"; 
			_e( "<strong>Affiliates Referral Bonus</strong> plugin requires one of the <a href='http://wordpress.org/plugins/affiliates/'>Affiliates</a>, <a href='http://www.itthinx.com/shop/affiliates-pro/'>Affiliates Pro</a> or <a href='http://www.itthinx.com/shop/affiliates-enterprise/'>Affiliates Enterprise</a> plugins to be installed and activated.", ARB_DOMAIN );
			echo "</div>";
			// @todo don't deactivate for UX reasons 
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			deactivate_plugins( array( ARB_FILE ) );
		}else if ( !$woocommerce_is_active ) {
			echo "<div class='error'>";
			_e( "<strong>Affiliates Referral Bonus</strong> plugin requires <a href='http://wordpress.org/plugins/woocommerce/'>WooCommerce</a> plugin to be installed and activated.", ARB_DOMAIN );
			echo "</div>";
			// @todo don't deactivate for UX reasons
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			deactivate_plugins( array( ARB_FILE ) );
		} else {
			// dependencies met, move on
			add_action( 'affiliates_referral', array( __CLASS__, 'affiliates_referral_initial_bonus', 10, 2 ) );
			register_uninstall_hook( ARB_FILE, 	array( __CLASS__, 'arb_delete_data' ) );
		}		
	}
	
	/**
	 * Calculate the affiliate bonus
	 * 
	 * @param unknown_type $referral_id
	 * @param unknown_type $params
	 */
	public static function affiliates_referral_initial_bonus( $referral_id, $params ) {
		$options = (array) get_option( self::PLUGIN_OPTIONS );
		$aff_id = $params['affiliate_id'];		
		
		/*
		$post_id = $params['post_id'];
		$description = "Referral Bonus";
		*/
		//$currency_id = get_option( 'woocommerce_currency' );
		//$aff_default_referral_status = get_option( 'aff_default_referral_status' ) ? get_option( 'aff_default_referral_status' ) : "pending";
		//$type = "initial bonus for " . $referral_id;
		//$reference = "initial bonus for " . $referral_id;
		//$data = null;
		//$aff_id = $params['affiliate_id'];
		$total_referrals = affiliates_get_affiliate_referrals( $aff_id, $from_date = null , $thru_date = null, $status = $aff_default_referral_status, $precise = false );
	
		if ( $total_referrals < $options[ self::REFERRALS_AMOUNT ] ) {
			self::arb_add_bonus_coupon( $aff_id );
			
			/*if ( isset( $params['base_amount'] ) ) {
				$amount = bcmul( $bonus_rate, $params['base_amount'], 2 );
			} else {
				$amount = bcmul( $bonus_amount, 1, 2 );
			}
	
			affiliates_add_referral( $aff_id, $post_id, $description, $data, $amount, $currency_id, $aff_default_referral_status, $type, $reference );
			*/
		} else {
			return;
		}
	}
	
	/**
	 * Creates a WooCommerce coupon
	 * coupon parameters are set through the admin settings
	 * 
	 * @param int $affiliate_id
	 */
	public static function arb_add_bonus_coupon( $affiliate_id ) {
		
		$options = (array) get_option( self::PLUGIN_OPTIONS );
		// expiration date set to 1 month interval
		$expiry_date = date( 'Y-m-d', strtotime( '+1 month' ) );
		$author_id = self::set_coupon_author_id();
		
		if ( date( 'dmoGis' ) ) {
			$new_coupon_code = date( 'dmoGis' ); // 'UNIQUECODE' Code
		}
			
		$coupon = new WC_Coupon( $new_coupon_code );
		if ( !is_null( $coupon_post = get_post( $coupon->get_id() ) ) ) {			
			/*$coupon_data = array(
					'id' => $coupon->get_id(),
					'code' => $coupon->get_code()
			);*/
				
			if ( $new_coupon_code !== $coupon->get_code() ) {
		
				$new_coupon = array(
						'post_title' 	=> $coupon_code,	// $coupon_code
						'post_content' 	=> __('Affiliates Bonus Coupon', ARB_DOMAIN ),
						'post_status' 	=> 'publish',
						'post_author' 	=> $author_id,
						'post_type'		=> 'shop_coupon'
				);
					
				$new_coupon_id = wp_insert_post( $new_coupon );
					
				// Coupon meta
				if ( $new_coupon_id != 0 ) {
					update_post_meta( $new_coupon_id, 'discount_type', $options[ self::BONUS_DISCOUNT_TYPE ] );
					update_post_meta( $new_coupon_id, 'coupon_amount', $options[ self::BONUS_AMOUNT ] );
					update_post_meta( $new_coupon_id, 'individual_use', 'yes' );
					update_post_meta( $new_coupon_id, 'product_ids', '' );
					update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
					update_post_meta( $new_coupon_id, 'usage_limit', '1' );
					update_post_meta( $new_coupon_id, 'usage_limit_per_user', '1');
					update_post_meta( $new_coupon_id, 'expiry_date', $options[ self::COUPON_EXPIRY_DATE ] ); //YYYY-MM-DD
					update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
					update_post_meta( $new_coupon_id, 'free_shipping', 'no' );
					update_post_meta( $new_coupon_id, 'minimum_amount', $bonus_amount );
				}
			}
		}
	}
	
	/**
	 * Delete data upon plugin uninstall
	 *
	 */
	public static function arb_delete_data () {
		$options = (array) get_option( self::PLUGIN_OPTIONS );
		if ( isset( $ptions[ self::DELETE_DATA ] ) && $options[ self::DELETE_DATA ] == 'on' ) {
			//delete_option( self::PLUGIN_OPTIONS );
		}
	}
	
	/**
	 * Returns the author id for gift card product
	 * A fallback in case user_id 1 is not used
	 *
	 * @return int user_id
	 */
	public static function set_coupon_author_id () {
		$result = 1;	
		if ( !get_user_by( 'ID', 1 ) ) {
			$author_ids = get_users( array( 'role__in' => array( 'administrator', 'shop_manager' ), 'fields' => array( 'ID' ) ) );
			foreach( $author_ids as $author ) {
				$result = $author->ID;				
			}
		}	
		return $result;
	}
	
} Affiliates_Referral_Bonus_Core::init();