<?php
if ( !defined( 'ABSPATH' ) ) {
	exit;
}
Class Affiliates_Referral_Bonus_Core {
	
	const PLUGIN_OPTIONS 	= 'arb-options';
	const REFERRALS_AMOUNT 	= 'reff-amount';
	const COUPON_AMOUNT 	= 'coupon-amount';
	const DISCOUNT_TYPE 	= 'discount-type';
	const BONUS_CONDITION 	= 'bonus-condition';
	// const COUPON_EXPIRY_DATE = 'coupon-expiry-date';
	const DELETE_DATA 		= 'delete-data';
	
	public static function init() {		
		if ( self::arb_check_dependencies() ) {
			add_action( 'affiliates_referral',	array( __CLASS__, 'affiliates_referral_bonus' ), 10, 2 );
			register_uninstall_hook( ARB_FILE, 	array( __CLASS__, 'arb_delete_data' ) );
		}
	}
	
	/**
	 * Check plugin dependencies.
	 * If not met, print an admin notice
	 * 
	 * @return boolean
	 */
	public static function arb_check_dependencies() {
		$result = false;
		$active_plugins = get_option( 'active_plugins', array() );
		$affiliates_is_active = in_array( 'affiliates/affiliates.php', $active_plugins ) || in_array( 'affiliates-pro/affiliates-pro.php', $active_plugins ) || in_array( 'affiliates-enterprise/affiliates-enterprise.php', $active_plugins );
		$woocommerce_is_active = in_array( 'woocommerce/woocommerce.php', $active_plugins );		
		
		if ( !$affiliates_is_active ) {
			echo "<div class='error'>"; 
			_e( "<strong>Affiliates Referral Bonus</strong> plugin requires one of the <a href='http://wordpress.org/plugins/affiliates/'>Affiliates</a>, <a href='http://www.itthinx.com/shop/affiliates-pro/'>Affiliates Pro</a> or <a href='http://www.itthinx.com/shop/affiliates-enterprise/'>Affiliates Enterprise</a> plugins to be installed and activated.", ARB_DOMAIN );
			echo "</div>";			
		}else if ( !$woocommerce_is_active ) {
			echo "<div class='error'>";
			_e( "<strong>Affiliates Referral Bonus</strong> plugin requires <a href='http://wordpress.org/plugins/woocommerce/'>WooCommerce</a> plugin to be installed and activated.", ARB_DOMAIN );
			echo "</div>";
		} else {
			$result = true;
		}		
		
		return $result;
	}
		
	/**
	 * Calculate the affiliate bonus
	 * 
	 * @param int $referral_id
	 * @param array $params
	 */
	public static function affiliates_referral_bonus( $referral_id, $params ) {
		$options = (array) get_option( self::PLUGIN_OPTIONS );
		$aff_id = $params[ 'affiliate_id' ];
		$total_referrals = affiliates_get_affiliate_referrals( $aff_id, $from_date = null , $thru_date = null, $status = 'accepted', $precise = false );
		$condition = $options[ self::BONUS_CONDITION ];
		
		switch ( $condition ) {
			case 'every' :
				if ( ( $total_referrals % $options[ self::REFERRALS_AMOUNT ] == 0 ) && $total_referrals >= $options[ self::REFERRALS_AMOUNT ] ) {
					if ( $coupon_code = self::arb_add_bonus_coupon( $aff_id ) ) {
						self::arb_send_coupon( $aff_id, $coupon_code );
					}
				} else {
					break;
				}
				break;
				
			case 'after' :
				if ( $total_referrals == $options[ self::REFERRALS_AMOUNT ] ) {
					if ( $coupon_code = self::arb_add_bonus_coupon( $aff_id ) ) {
						self::arb_send_coupon( $aff_id, $coupon_code );
					}
				} else {
					break;
				}
				break;
		}
	}
	
	/**
	 * Creates a WooCommerce coupon.
	 * Coupon parameters are retrieved from the admin settings
	 * 
	 * @param int $affiliate_id
	 * @return WC Coupon code on success, false on failure
	 */
	public static function arb_add_bonus_coupon( $affiliate_id ) {
		$result = false;
		$options = (array) get_option( self::PLUGIN_OPTIONS );
		$expiry_date = date( 'Y-m-d', strtotime( '+1 month' ) );
		$new_coupon_code = date( 'dmoGis' );		
		$author_id = self::get_admin_id();	
			
		$new_coupon = array(
				'post_title' 	=> $new_coupon_code,
				'post_excerpt'	=> __('Affiliates Bonus Coupon', ARB_DOMAIN ),
				'post_content' 	=> __('Affiliates Bonus Coupon', ARB_DOMAIN ),
				'post_status' 	=> 'publish',
				'post_author' 	=> $author_id,
				'post_type'		=> 'shop_coupon'
		);			
		$new_coupon_id = wp_insert_post( $new_coupon );			
		if ( $new_coupon_id != 0 ) {
			update_post_meta( $new_coupon_id, 'discount_type', $options[ self::DISCOUNT_TYPE ] );
			update_post_meta( $new_coupon_id, 'coupon_amount', $options[ self::COUPON_AMOUNT ] );
			update_post_meta( $new_coupon_id, 'individual_use', 'yes' );
			update_post_meta( $new_coupon_id, 'product_ids', '' );
			update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
			update_post_meta( $new_coupon_id, 'usage_limit', '1' );
			update_post_meta( $new_coupon_id, 'usage_limit_per_user', '1');
			update_post_meta( $new_coupon_id, 'expiry_date', $expiry_date );
			update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
			update_post_meta( $new_coupon_id, 'free_shipping', 'no' );
			$result = $new_coupon_code;
		}		
		
		return $result;
	}
	
	/**
	 * Delete data upon plugin uninstall
	 *
	 */
	public static function arb_delete_data () {
		$options = (array) get_option( self::PLUGIN_OPTIONS );
		if ( isset( $ptions[ self::DELETE_DATA ] ) && $options[ self::DELETE_DATA ] == 'on' ) {
			delete_option( self::PLUGIN_OPTIONS );
		}
	}
	
	/**
	 * Returns the user id for administrator or 
	 * shop manager, in case user_id 1 is not used
	 *
	 * @return int user_id
	 */
	private static function get_admin_id () {
		$result = 1;	
		if ( !get_user_by( 'ID', 1 ) ) {
			$author_ids = get_users( array( 'role__in' => array( 'administrator', 'shop_manager' ), 'fields' => array( 'ID' ) ) );
			foreach( $author_ids as $author ) {
				$result = $author->ID;				
			}
		}	
		return $result;
	}
	
	/**
	 * Send coupon by email to the affiliate
	 * 
	 * @param int $affiliate_id, string $coupon_code
	 * @return boolean
	 */
	public static function arb_send_coupon( $affiliate_id, $coupon_code ) {
		$result = false;
		$user_email = '';
		$subject = '';
		$message = '';
		
		if ( function_exists( 'affiliates_get_affiliate_user' ) ) {
			$user_id = affiliates_get_affiliate_user( $affiliate_id );
			if ( $user = get_user_by( 'ID', $user_id ) ) {
				$user_email = $user->user_email;
				$subject = 'You got a bonus coupon on '. get_bloginfo( 'name' );
				$message = 'You got a bonus coupon for your referral performance on '. get_bloginfo( 'name' ) . '\n'; 
				$message .= 'Here is your Coupon code: '. $coupon_code . '\n';
			} else {
				$user_id = self::get_admin_id();
				$user = get_user_by( 'ID', $user_id );
				$user_email = $user->user_email;
				$subject = 'There is a bonus coupon for affiliate with id ' . $affiliate_id;
				$message = 'There is a bonus coupon for affiliate with id ' . $affiliate_id . 'but there is no registered email address.';
				$message .= 'The Coupon code is: '. $coupon_code;
			}				

			if ( wp_mail( $user_email, $subject, $message ) ) {
				$result = true;
			}			
		}
		
		return $result;
	}
	
} Affiliates_Referral_Bonus_Core::init();