<?php
/**
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
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
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,               
 * MA 02110-1301 USA                                                    
 *
*/


class MS_Model_Settings extends MS_Model_Option {
	
	protected static $CLASS_NAME = __CLASS__;
	
	protected static $instance;
	
	const SPECIAL_PAGE_NO_ACCESS = 'no_access';
	const SPECIAL_PAGE_ACCOUNT = 'account';
	const SPECIAL_PAGE_WELCOME = 'welcome';
	const SPECIAL_PAGE_SIGNUP = 'signup';
	
	protected $id =  'ms_plugin_settings';
	
	protected $name = 'Plugin settings';
	
	/** Current db version */
	protected $version;
	
	protected $plugin_enabled = false;
	
	protected $initial_setup;
	
	protected $pages;
	
	protected $default_membership_enabled;
	
	protected $currency = 'USD';
	
	protected $tax;
	
	protected $invoice_sender_name;

	/** For extensions settings.*/
	protected $custom;
	
	/**
	 * Shortcode protection message.
	 * 
	 * @var $protection_message
	 */
	protected $protection_message = array( 'content', 'shortcode', 'more_tag' );

	protected $downloads = array(
		'protection_type' => MS_Model_Rule_Media::PROTECTION_TYPE_DISABLED,
		'masked_url' => 'downloads',
	);
	
	public function __construct() {
		$this->add_action( 'wp_loaded', 'initial_setup' );	
	}
		
	public function initial_setup() {
		MS_Model_Membership::get_visitor_membership();
		if( ! $this->initial_setup ) {
// 			$this->create_initial_pages();
		}
	}

	public function create_page_no_access() {
		$content = '<p>' . __( 'The content you are trying to access is only available to members. Sorry.', MS_TEXT_DOMAIN ) . '</p>';
		$pagedetails = array( 'post_title' => __( 'Protected Content', MS_TEXT_DOMAIN ), 'post_name' => 'protected', 'post_status' => 'publish', 'post_type' => 'page', 'ping_status' => 'closed', 'comment_status' => 'closed' , 'post_content' => $content);
		$id = wp_insert_post( $pagedetails );
		$this->pages[ self::SPECIAL_PAGE_NO_ACCESS ] = $id;
	}
	
	public function create_page_account() {
		$content = '';
		$pagedetails = array( 'post_title' => __( 'Account', MS_TEXT_DOMAIN ), 'post_name' => 'account', 'post_status' => 'publish', 'post_type' => 'page', 'ping_status' => 'closed', 'comment_status' => 'closed' , 'post_content' => $content);
		$id = wp_insert_post( $pagedetails );
		$this->pages[ self::SPECIAL_PAGE_ACCOUNT ] = $id;
	}
	
	public function create_page_welcome() {
		$content = '';
		$pagedetails = array( 'post_title' => __( 'Welcome', MS_TEXT_DOMAIN ), 'post_name' => 'welcome', 'post_status' => 'publish', 'post_type' => 'page', 'ping_status' => 'closed', 'comment_status' => 'closed' , 'post_content' => $content);
		$id = wp_insert_post( $pagedetails );
		$this->pages[ self::SPECIAL_PAGE_WELCOME ] = $id;
	}

	public function create_page_signup() {
		$content = '';
		$pagedetails = array( 'post_title' => __( 'Signup', MS_TEXT_DOMAIN ), 'post_name' => 'signup', 'post_status' => 'publish', 'post_type' => 'page', 'ping_status' => 'closed', 'comment_status' => 'closed' , 'post_content' => $content);
		$id = wp_insert_post( $pagedetails );
		$this->pages[ self::SPECIAL_PAGE_SIGNUP ] = $id;
	}
	
	public function create_special_page( $type ) {
		$create_method = "create_page_{$type}";
		if( in_array( $type, self::get_special_page_types() ) && method_exists( $this, $create_method ) ) {
			$this->$create_method();
			$this->save();
			return $this->pages[ $type ];
		}
	}
	
	public function get_special_page( $type ) {
		$page_id = null;
		if( in_array( $type, self::get_special_page_types() ) ) {
			if( empty( $this->pages[ $type ] ) ){
				$page_id = $this->create_special_page( $type );
			}
			else{
				$page_id = $this->pages[ $type ];
			}
		}
		return $page_id;		
	}
	
	public static function get_special_page_types() {
		return apply_filters( 'ms_model_settings_get_special_page_types', array( 
				self::SPECIAL_PAGE_NO_ACCESS,
				self::SPECIAL_PAGE_ACCOUNT,
				self::SPECIAL_PAGE_WELCOME,
				self::SPECIAL_PAGE_SIGNUP,
			)
		);
		
	}
	public function get_pages( $args = null ) {
		$defaults = array(
				'posts_per_page' => -1,
				'offset'      => 0,
				'orderby'     => 'post_date',
				'order'       => 'DESC',
				'post_type'   => 'page',
				'post_status' => array('publish'), 
		);
		$args = wp_parse_args( $args, $defaults );
		
		$contents = get_posts( $args );
		$cont = array();
		foreach( $contents as $content ) {
			$cont[ $content->ID ] = $content->post_title;
		}
		return $cont;
	}
		
	public function is_special_page( $page_id = null, $special_page_type = null ) {
		
		$is_special_page = false;
		
		if( empty( $page_id ) ) {
			if( is_page() ) {
				$page_id = get_the_ID();
			}
		}
	
		if( ! empty( $page_id ) ) {
			if( ! empty( $special_page_type ) ) {
				if( isset( $this->pages[ $special_page_type ] ) && $page_id == $this->pages[ $special_page_type ] ) {
					$is_special_page = $special_page_type;
				}
			}
			else {
				foreach( $this->pages as $special_page_type => $special_page_id ) {
					if( $page_id == $special_page_id ) {
						$is_special_page = $special_page_type;
					}
				}
			}
		}
			
		return $is_special_page;
	}
	
	public function get_custom_settings( $group, $name ) {
		$setting = '';
		if( ! empty( $this->custom[ $group ][ $name ] ) ) {
			$setting = $this->custom[ $group ][ $name ];
		}
		return apply_filters( 'ms_model_settings_get_custom_settings', $setting, $group, $name );
	}
	
	public static function get_currencies() {
		return apply_filters( 'ms_model_settings_get_currencies', array(
				'AUD' => __( 'AUD - Australian Dollar', MS_TEXT_DOMAIN ),
				'BRL' => __( 'BRL - Brazilian Real', MS_TEXT_DOMAIN ),
				'CAD' => __( 'CAD - Canadian Dollar', MS_TEXT_DOMAIN ),
				'CHF' => __( 'CHF - Swiss Franc', MS_TEXT_DOMAIN ),
				'CZK' => __( 'CZK - Czech Koruna', MS_TEXT_DOMAIN ),
				'DKK' => __( 'DKK - Danish Krone', MS_TEXT_DOMAIN ),
				'EUR' => __( 'EUR - Euro', MS_TEXT_DOMAIN ),
				'GBP' => __( 'GBP - Pound Sterling', MS_TEXT_DOMAIN ),
				'HKD' => __( 'HKD - Hong Kong Dollar', MS_TEXT_DOMAIN ),
				'HUF' => __( 'HUF - Hungarian Forint', MS_TEXT_DOMAIN ),
				'ILS' => __( 'ILS - Israeli Shekel', MS_TEXT_DOMAIN ),
				'JPY' => __( 'JPY - Japanese Yen', MS_TEXT_DOMAIN ),
				'MYR' => __( 'MYR - Malaysian Ringgits', MS_TEXT_DOMAIN ),
				'MXN' => __( 'MXN - Mexican Peso', MS_TEXT_DOMAIN ),
				'NOK' => __( 'NOK - Norwegian Krone', MS_TEXT_DOMAIN ),
				'NZD' => __( 'NZD - New Zealand Dollar', MS_TEXT_DOMAIN ),
				'PHP' => __( 'PHP - Philippine Pesos', MS_TEXT_DOMAIN ),
				'PLN' => __( 'PLN - Polish Zloty', MS_TEXT_DOMAIN ),
				'RUB' => __( 'RUB - Russian Ruble', MS_TEXT_DOMAIN ),
				'SEK' => __( 'SEK - Swedish Krona', MS_TEXT_DOMAIN ),
				'SGD' => __( 'SGD - Singapore Dollar', MS_TEXT_DOMAIN ),
				'TWD' => __( 'TWD - Taiwan New Dollars', MS_TEXT_DOMAIN ),
				'THB' => __( 'THB - Thai Baht', MS_TEXT_DOMAIN ),
				'USD' => __( 'USD - U.S. Dollar', MS_TEXT_DOMAIN ),
				'ZAR' => __( 'ZAR - South African Rand', MS_TEXT_DOMAIN),
		) );
	}
	
	/**
	 * Set specific property.
	 *
	 * @since 4.0
	 *
	 * @access public
	 * @param string $property The name of a property to associate.
	 * @param mixed $value The value of a property.
	 */
	public function __set( $property, $value ) {
		if ( property_exists( $this, $property ) ) {
			switch( $property ) {
				case 'currency':
					if( array_key_exists( $value, self::get_currencies() ) ) {
						$this->$property = $value;
					}
					break;
				case 'protection_message':
					$this->$property = wp_kses_post( $value );
					break;
				case 'invoice_sender_name':
					$this->$property = sanitize_text_field( $value );
					break;
				case 'plugin_enabled':
				case 'initial_setup':
				case 'show_default_membership':
					$this->$property = $this->validate_bool( $value );
					break;
				default:
					$this->$property = $value;
					break;
			}
		}
	}
}