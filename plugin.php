<?php
/*
Plugin Name: WordPress Upload Custom Meta Box 
Plugin URI: http://github.com/tommcfarlin/WordPress-Upload-Custom-Meta-Box/
Description: An example plugin for how to include a metabox for attaching files to your WordPress posts outside of the media uploader.
Version: 0.2
Author: Tom McFarlin
Author URI: http://tommcfarlin.com
Author Email: tom@tommcfarlin.com
License:

  Copyright 2012 - 2013 Tom McFarlin (tom@tommcfarlin.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as 
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
  
*/

if( ! defined( 'UMB_VERSION' ) ) {
	define( 'UMB_VERSION', 0.2 );
} // end if

class Upload_Meta_Box {

	/*--------------------------------------------*
	 * Attributes
	 *--------------------------------------------*/
	 
	 // A reference to the single instance of this class
	 private static $instance = null;

	 // Represents the nonce value used to save the post media
	 private $nonce = 'wp_upm_media_nonce';

	/*--------------------------------------------*
	 * Constructor
	 *--------------------------------------------*/

	 /**
	  * Provides access to a single instance of this class.
	  *
	  * @return	object	A single instance of this class.
	  */
	 public static function get_instance() {
		 
		 if( null == self::$instance ) {
			 self::$instance = new self;
		 } // end if
		 
		 return self::$instance;
		 
	 } // end get_instance;

	 /**
	  * Initializes localiztion, sets up JavaScript, and displays the meta box for saving the file
	  * information.
	  */
	 private function __construct() {
	 
	 	// Localization, Styles, and JavaScript
	 	add_action( 'init', array( $this, 'plugin_textdomain' ) );
	 	add_action( 'admin_enqueue_scripts', array( $this, 'register_admin_scripts' ) );
	 
	 	// Setup the meta box hooks
	 	add_action( 'add_meta_boxes', array( $this, 'add_file_meta_box' ) );
	 	add_action( 'save_post', array( $this, 'save_custom_meta_data' ) );

	 } // end construct
	 
	/*--------------------------------------------*
	 * Localization, Styles, and JavaScript
	 *--------------------------------------------*/
	
	/**
	 * Defines the text domain for localization.
	 */
	public function plugin_textdomain() {
		load_plugin_textdomain( 'umb', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
	} // end plugin_textdomain
	
	/**
	 * Addings the admin JavaScript
	 */
	public function register_admin_scripts() {
		wp_enqueue_script( 'umb-admin', plugins_url( 'WordPress-Upload-Meta-Box/js/admin.js' ), array( 'jquery'), UMB_VERSION );
	} // end register_scripts
	
	/*--------------------------------------------*
	 * Hooks
	 *--------------------------------------------*/
	
	/**
	 * Introduces the file meta box for uploading the file to this post.
	 */ 
	public function add_file_meta_box() {
	
		add_meta_box(
			'post_media',
			__( 'Media', 'umb' ),
			array( $this, 'post_media_display' ),
			'post',
			'side'
		);
	
	} // add_file_meta_box
	
	/**
	 * Adds the file input box for the post meta data.
	 *
	 * @param		object	$post	The post to which this information is going to be saved.
	 */
	public function post_media_display( $post ) {
	
		wp_nonce_field( plugin_basename( __FILE__ ), $this->nonce );
	
		$html = '<input id="post_media" type="file" name="post_media" value="" size="25" />';
		
		$html .= '<p class="description">';
		if( '' == get_post_meta( $post->ID, 'umb_file', true ) ) {
			$html .= __( 'You have no file attached to this post.', 'umb' );
		} else {
			$html .= get_post_meta( $post->ID, 'umb_file', true );
		} // end if
		$html .= '</p><!-- /.description -->';
		
		echo $html;
	
	} // end post_media
	
	/**
	 * Determines whether or not the current user has the ability to save meta data associated with this post.
	 *
	 * @param		int		$post_id	The ID of the post being save
	 * @param		bool				Whether or not the user has the ability to save this post.
	 */
	public function save_custom_meta_data( $post_id ) {
	
		// First, make sure the user can save the post
		if( $this->user_can_save( $post_id, $this->nonce ) ) { 

			// If the user uploaded an image, let's upload it to the server
			if( ! empty( $_FILES ) && isset( $_FILES['post_media'] ) ) {
			
				// Upload the goal image to the uploads directory, resize the image, then upload the resized version
				$goal_image_file = wp_upload_bits( $_FILES['post_media']['name'], null, wp_remote_get( $_FILES['post_media']['tmp_name'] ) );

				// Set post meta about this image. Need the comment ID and need the path.
				if( false == $goal_image_file['error'] ) {
				
					// Since we've already added the key for this, we'll just update it with the file.
					update_post_meta( $post_id, 'umb_file', $goal_image_file['url'] );
		
				} // end if/else

			} // end if
	
		} // end if
	
	} // end update_data

	/*--------------------------------------------*
	 * Helper Functions
	 *--------------------------------------------*/

	/**
	 * Determines whether or not the current user has the ability to save meta data associated with this post.
	 *
	 * @param		int		$post_id	The ID of the post being save
	 * @param		bool				Whether or not the user has the ability to save this post.
	 */
	function user_can_save( $post_id, $nonce ) {
		
	    $is_autosave = wp_is_post_autosave( $post_id );
	    $is_revision = wp_is_post_revision( $post_id );
	    $is_valid_nonce = ( isset( $_POST[ $nonce ] ) && wp_verify_nonce( $_POST[ $nonce ], plugin_basename( __FILE__ ) ) );
	    
	    // Return true if the user is able to save; otherwise, false.
	    return ! ( $is_autosave || $is_revision ) && $is_valid_nonce;
	 
	} // end user_can_save

} // end class

// Get an instance of the class
Upload_Meta_Box::get_instance();