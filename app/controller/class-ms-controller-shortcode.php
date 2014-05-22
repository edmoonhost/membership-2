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

class MS_Controller_Shortcode extends MS_Controller {
	
	public function __construct() {
		add_shortcode( 'ms-membership-form', array( $this, 'membership_form' ) );
		add_shortcode( 'ms-membership-title', array( $this, 'membership_title' ) );
		add_shortcode( 'ms-membership-details', array( $this, 'membership_details' ) );
		add_shortcode( 'ms-membership-price', array( $this, 'membership_price' ) );
		add_shortcode( 'ms-membership-button', array( $this, 'membership_button' ) );
		
		add_shortcode( 'ms-membership-login', array( $this, 'membership_login' ) );
		
	}

	public function membership_form( $atts ) {
		$data = apply_filters( 
				'ms_controller_shortcode_membership_form_atts', 
				shortcode_atts( 
					array(
						'title' => '',
						MS_Model_Membership_Relationship::MEMBERSHIP_ACTION_SIGNUP . '_text' =>  __( 'Signup', MS_TEXT_DOMAIN ),
						MS_Model_Membership_Relationship::MEMBERSHIP_ACTION_MOVE . '_text' => __( 'Signup', MS_TEXT_DOMAIN ),
						MS_Model_Membership_Relationship::MEMBERSHIP_ACTION_CANCEL . '_text' => __( 'Cancel', MS_TEXT_DOMAIN ),
					), 
				$atts
			) 
		);
		$data['member'] = MS_Model_Member::get_current_member();
		$not_in = $data['member']->membership_ids;
		$not_in = array_merge( $not_in, array( MS_Model_Membership::get_visitor_membership()->id, MS_Model_Membership::get_default_membership()->id ) );
		$args = array( 'post__not_in' => $not_in );
		$data['memberships'] = MS_Model_Membership::get_memberships( $args );
		$view = apply_filters( 'ms_view_shortcode_membership_form', new MS_View_Shortcode_Membership_Form() );
		$view->data = $data;
		return $view->to_html();
	}
	
	public function membership_title() {
		
	}
	
	public function membership_details() {
		
	}
	
	public function membership_price() {
		
	}
	
	public function membership_button() {
		
	}
	
	public function membership_login( $atts ) {
		$data = apply_filters(
				'ms_controller_shortcode_membership_login_atts',
				shortcode_atts(
						array(
							"holder"        => '',
							"holderclass"   => '',
							"item"          => '',
							"itemclass"     => '',
							"postfix"       => '',
							"prefix"        => '',
							"wrapwith"      => '',
							"wrapwithclass" => '',
							"redirect"      => filter_input( INPUT_GET, 'redirect_to', FILTER_VALIDATE_URL ),
							"lostpass"      => '',
						),
						$atts
				)
		);
		$view = apply_filters( 'ms_view_shortcode_membership_login', new MS_View_Shortcode_Membership_Login() );
		$view->data = $data;
		return $view->to_html();
	}
}