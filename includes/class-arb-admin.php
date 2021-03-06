<?php
class Affiliates_Referral_Bonus_Admin {	
	
	const PLUGIN_OPTIONS = 'arb-options';
	
	public function __construct() {
			self::init();
	}	

	public function init() {
		add_action( 'admin_menu', array( $this, 'arb_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'arb_admin_init' ) );
	}
		
	/**
	 * The admin options page
	 * 
	 */ 
	public function arb_admin_menu() {
		add_submenu_page( 
				'affiliates-admin',
				'Affiliates Referral Bonus Settings',
				'Affiliates Bonus',
				'manage_options',
				'arb-settings',
				array( $this, 'arb_settings' )
		);
	}
	
	/**
	 * The admin options page contents
	 */
	public function arb_settings()	{		
        echo '<div class="wrap">';
        echo '<h2>Affiliates Referral Bonus Settings</h2>';            
        echo '<form method="post" action="options.php">';
        	settings_fields( 'arb_settings' );   
        	do_settings_sections( 'arb-settings' );
        	echo '<hr>';
        	submit_button( __( 'Save', ARB_DOMAIN ) );
        echo '</form>';
        echo '</div>';
    }
    
    /**
     * Sections and form fields
     */
    public function arb_admin_init() {  
    	register_setting ( 
    			'arb_settings', 
    			self::PLUGIN_OPTIONS, 		
    			array( $this, 'settings_validation' ) 
    	);
    	// Referrals bounus section
    	add_settings_section( 
    			'referrals_bonus_section', 
    			__( 'Choose the amount of referrals', ARB_DOMAIN ), 
    			array( $this, 'referrals_bonus_section' ), 
    			'arb-settings' 
    	);   
    	add_settings_field(
    			'bonus_condition',
    			__( 'Referral Bonus Condition', ARB_DOMAIN ),
    			array( $this, 'referrals_bonus_fields' ),
    			'arb-settings',
    			'referrals_bonus_section',
    			array ( 'field' => 'condition' )
    	);
    	add_settings_field( 
    			'referrals_amount_field', 
    			__( 'Referrals Amount Field', ARB_DOMAIN ), 
    			array( $this, 'referrals_bonus_fields' ), 
    			'arb-settings', 
    			'referrals_bonus_section',
    			array ( 'field' => 'amount' )
    	);    	
    	// Coupon section
    	add_settings_section( 
    			'coupon_settings_section', 
    			__( 'Choose the settings for the bonus coupon', ARB_DOMAIN ), 
    			array( $this, 'coupon_settings_section' ), 
    			'arb-settings' 
    	); 
    	add_settings_field( 
    			'discount_type',
    			'Discount type',
    			array( $this, 'coupon_fields' ),
    			'arb-settings',
    			'coupon_settings_section',
    			array ( 'field' => 'discount_type' )
    	);
    	add_settings_field(
    			'coupon_amount',
    			'Coupon amount',
    			array( $this, 'coupon_fields' ),
    			'arb-settings',
    			'coupon_settings_section',
    			array( 'field' => 'coupon_amount' )
    	);    	
    	// Data persistence
    	add_settings_section( 
    			'data_persistence_section', 
    			__( 'Delete plugin data on deactivation', ARB_DOMAIN ), 
    			array( $this, 'data_persistence_section' ), 
    			'arb-settings' 
    	);
    	add_settings_field( 
    			'delete_data', 
    			'Delete data', 
    			array( $this, 'delete_data' ), 
    			'arb-settings', 
    			'data_persistence_section' 
    	);
    }
    
    /**
     * Validates input data.   
     *
     * @param array $input 
     * @return array
     */ 
    public function settings_validation( $input ) {    	
    	if ( isset( $input[ 'reff-amount' ] ) ) {
    		if ( !is_numeric( $input[ 'reff-amount' ] ) ) {
    			$input[ 'reff-amount' ] = preg_replace( '/[^0-9]/', '', $input[ 'reff-amount' ] );
    		}
    	} else {
    		$input[ 'reff-amount' ] = 0;
    	}
    	
    	if ( isset( $input[ 'coupon-amount' ] ) ) {
    		if ( !is_numeric( $input[ 'coupon-amount' ] ) ) {
    			$input[ 'coupon-amount' ] = self::validate_amount( $input[ 'coupon-amount' ] );
    		}    		
    	} else {
    		$input[ 'coupon-amount' ] = 0;
    	}
    	
    	if ( !isset( $input[ 'discount-type' ] ) ) {
    		$input[ 'discount-type' ] = 'prcnt_discount';
    	}
    	
    	if ( !isset( $input[ 'delete-data' ] ) ) {
    		$input[ 'delete-data' ] = '';
    	}
    	
    	return $input;
    }
    
    /**
     * Referrals amount section
     */
    public function referrals_bonus_section() {
    	_e( 'The number entered here will be used as limit when to grant the affiliate the bonus. ', ARB_DOMAIN );
    	_e( 'The value can range between: ', ARB_DOMAIN );
    	echo '<strong>0-999</strong>';
    	echo '<br />';
    	_e( 'When this limit is reached, the affiliate will be granted with a bonus coupon.', ARB_DOMAIN );    	
    	echo '<p><strong>';
    	_e( 'Examples: ', ARB_DOMAIN );
    	echo '</strong><br />';
    	_e( 'Indicate <strong>0</strong> for no affiliate bonus.', ARB_DOMAIN );
    	echo '<br />';
    	_e( 'Indicate <strong>Every</strong> and <strong>2</strong> to grant the affiliate a bonus for every two referrals they have recorded.', ARB_DOMAIN );
    	echo '<br />';
    	_e( 'Indicate <strong>After</strong> and <strong>2</strong> to grant the affiliate a bonus after two referrals they have recorded.', ARB_DOMAIN );
    	echo '</p>';
    }
    
    /**
     * Referral bonus fields form
     * 
     * @param array 
     */
    public function referrals_bonus_fields( $args ) {
    	$options = (array) get_option( self::PLUGIN_OPTIONS );
    	$conditions = array( 
    			'every' => 'Every',
    			'after' => 'After'
    	);
    	
    	switch ( $args[ 'field' ] ) {
    		case 'condition':
    			$arb_bonus_condition = isset( $options[ 'bonus-condition' ] ) ? $options[ 'bonus-condition' ] : '';
    			echo '<select name="'. self::PLUGIN_OPTIONS .'[bonus-condition]">';
    			foreach( $conditions as $condition => $label ) {
    				$selected = '';
    				if ( $arb_bonus_condition == $condition ) {
    					$selected = 'selected';
    				}
    				echo '<option value="'. $condition .'" '. $selected .'>'. $label .'</option>';
    			}
    			break;
    			
    		case 'amount':
    			$arb_reff_amount = isset( $options[ 'reff-amount' ] ) ? $options[ 'reff-amount' ] : '';    			
    			echo	'<input id="referrals_amount_field" type="text" class="referrals_amount_field" name="'. self::PLUGIN_OPTIONS .'[reff-amount]" value="'.$arb_reff_amount.'" maxlength="3" size="3" />';
    			break;
    	}    	    	
    }
    
    /**
     * Coupon settings section
     */
    public function coupon_settings_section() {
    	_e( 'Choose the options for the generated coupon', ARB_DOMAIN );
    }
    
    /**
     * Coupon form fields
     * 
     * @param array $args
     */
    public function coupon_fields( $args ) {
    	$options = (array) get_option( self::PLUGIN_OPTIONS );
    	$discount_types = array( 
    			'prcnt_discount'			=> 'Percentage Discount', 
    			'fixed_cart_discount'		=> 'Fixed Cart Discount', 
    			'fixed_product_discount'	=> 'Fixed Product Discount',
    	);
    	
    	switch ( $args[ 'field' ] ) {
    		case 'discount_type' :
    			$arb_discount_type = isset( $options[ 'discount-type' ] ) ? $options[ 'discount-type' ] : '';
    			echo '<select name="'. self::PLUGIN_OPTIONS .'[discount-type]">';
    			foreach( $discount_types as $type => $label ) {
    				$selected = '';
    				if ( $arb_discount_type == $type ) {
    					$selected = 'selected';
    				}
    				echo	'<option value="'. $type .'" '. $selected .'>'. $label .'</option>';
    			}
    			echo '</select>';
    			break;
    			
    		case 'coupon_amount' :
    			$arb_coupon_amount = isset( $options[ 'coupon-amount' ] ) ? $options[ 'coupon-amount' ] : '';    			 
    			echo	'<input id="coupon_amount" name="'. self::PLUGIN_OPTIONS .'[coupon-amount]" type="text" value="' . $arb_coupon_amount . '" />';
    			break;
    	}    	
    }
    
    /**
     * Data persistence section
     */
    public function data_persistence_section() {
    	_e( 'If you select this option, all saved settings will be deleted once the plugin is deleted.', ARB_DOMAIN );
    	_e( '<br />Once deleted, data cannot be recovered, so you should use it with caution.', ARB_DOMAIN );
    }
    
    /**
     * Data persistence callback
     */
    public function delete_data() {
    	$options = (array) get_option( self::PLUGIN_OPTIONS );
    	$arb_delete_data = isset( $options[ 'delete-data' ] ) ? $options[ 'delete-data' ] : '';    	 
    	echo	'<input id="delete_data" name="'. self::PLUGIN_OPTIONS .'[delete-data]" type="checkbox" '. ( $arb_delete_data == 'on' ? ' checked="checked" ' : '' ) .' />';
    }
    
    /**
     * Validates input amount
     * extracts the float out of a string
     * 
     * @param string
     * @return float
     */
    private function validate_amount( $input_string ) {
    	$result = $input_string;
    	$input_string = preg_replace( '/[^0-9.]+/', '', $input_string );
    	$count = preg_match_all( "(\.)", $input_string, $matches );
    	if ( $count > 1 ) {
    		$first_occur = strpos( $input_string, "." );
    		$first_piece = substr( $input_string, 0, $first_occur );
    		$second_piece = substr( $input_string, $first_occur + 1 );
    		$second_piece = str_replace( '.', '', $second_piece );
    		$result = $first_piece . "." .$second_piece;
    	}    	
    	return $result;
    }
}
if( is_admin() ) {
	new Affiliates_Referral_Bonus_Admin();
}