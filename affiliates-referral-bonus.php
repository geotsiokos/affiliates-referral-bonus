<?php
/**
 * Plugin Name: Affiliates Referral Bonus
 * Plugin URI: http://www.netpad.gr
 * Description: Grant your affiliates a bonus coupon for their referral performance
 * Version: 1.0
 * Author: George Tsiokos
 * Author URI: http://www.netpad.gr
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright (c) 2015-2016 "gtsiokos" George Tsiokos www.netpad.gr
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ARB_DIR', 				plugin_dir_path( __FILE__ ) );
define( 'ARB_URL', 				plugin_dir_url( __FILE__ ) );
define( 'ARB_FILE', 			__FILE__ );
define( 'ARB_DIR_INCLUDES',		ARB_DIR . 'includes/' );
define( 'ARB_URL_INCLUDES',		ARB_URL . 'includes/' );
define( 'ARB_DOMAIN', 			'aff-referral-bonus' );
define( 'ARB_LANG', 			dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

require_once( ARB_DIR_INCLUDES . 'class-arb-admin.php' );
require_once( ARB_DIR_INCLUDES . 'class-arb-core.php' );