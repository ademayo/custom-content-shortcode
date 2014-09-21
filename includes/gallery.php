<?php

/*====================================================================================================
 *
 * Gallery field
 *
 *====================================================================================================*/

new CCS_Gallery_Field;

class CCS_Gallery_Field {

	function __construct() {

		add_action( 'admin_init', array($this, 'register_settings_page') );
		add_action( 'admin_head', array($this, 'admin_css') );

		add_action( 'add_meta_boxes', array($this, 'add_meta_boxes') );
		add_action( 'save_post', array($this, 'save_post') );

		add_action( 'admin_menu', array($this, 'gallery_field_settings') );
	}


	/*========================================================================
	*
	* CSS for admin
	*
	*=======================================================================*/

	function admin_css() {
		?>
		<style>
		.attachment.details .check div {
			background-position: -60px 0;
		}

		.attachment.details .check:hover div {
			background-position: -60px 0;
		}

		.gallery_images .details.attachment {
			/* margin: 0; */
			box-shadow: none;
		}

		.eig-metabox-sortable-placeholder {
			background: #DFDFDF;
		}

		.gallery_images .attachment.details > div {
			width: 150px; /* 150px */
			height: 150px;
			box-shadow: none;
		}

		.gallery_images .attachment-preview .thumbnail {
			cursor: move;
		}

		.attachment.details div:hover .check {
			display:block;
		}

		.gallery_images:after,
		#gallery_images_container:after {
			content: "."; display: block; height: 0; clear: both; visibility: hidden; }

		ul.gallery_images {
			/* max-width: 665px; */
			margin: 0 auto;
		}
		.gallery_images > li {
			float: left;
			width: 150px;
			height: 150px;
			margin: 8px;
			padding: 0;
		}
		.gallery_images li.image {
			cursor: move;
		}
		.gallery_images li.image img {
			width: 100%; /*150px*/
			height: auto;
 		}

		.add_gallery_images a, .add_gallery_images a:active, .add_gallery_images a:focus {
			outline: 0;
		}
		.add_gallery_images {
			background: #f0f0f0;
		}
		.add_gallery_images:hover {
			background: #eaeaea;
		}
		.add_gallery_images a {
			text-decoration: none;
			width: 150px;
			height: 150px;
			line-height: 150px;
			display: block;
			color: #bbb;
		}
		.add_gallery_images a:hover {
			color: #999;
			/* color: #0074a2;  #2ea2cc */
		}
		.add_gallery_images a .dashicons {
			font-size: 50px;
			height: 150px;
			line-height: 150px;
			text-align: center;
			vertical-align: middle;
			width: 100%;
		}
		</style>
		<?php
	}


	/*========================================================================
	 *
	 * Add meta boxes to selected post types
	 *
	 *=======================================================================*/
	
	function add_meta_boxes() {

	    $post_types = $this->enabled_post_types();

	    if ( ! $post_types )
	        return;

	    foreach ( $post_types as $post_type => $status ) {
	        add_meta_box( 'ccs_gallery_field', 'Gallery', array($this, 'metabox'), $post_type, 'normal', 'low' );
	    }
	}


	/*========================================================================
	 *
	 * Render gallery metabox
	 *
	 *=======================================================================*/

	function metabox() {

	    global $post;
		?>
	    <div id="gallery_images_container">
	        <ul class="gallery_images">
	    	<?php
	    		$image_gallery = get_post_meta( $post->ID, '_custom_gallery', true );
			    $attachments = array_filter( explode( ',', $image_gallery ) );

			    if ( $attachments ) {
			        foreach ( $attachments as $attachment_id ) {
			            echo '<li class="image attachment details" data-attachment_id="'
			            	. $attachment_id
			            	. '"><div class="attachment-preview"><div class="thumbnail">'
			            	. wp_get_attachment_image( $attachment_id, 'thumbnail' )
			            	. '</div><a href="#" class="delete check" title="'
			            	. __( 'Remove image', 'custom-gallery' )
			            	. '"><div class="media-modal-icon"></div></a></div></li>';
	        		}
	        	}
			?>

	    <li class="add_gallery_images hide-if-no-js">
	        <a href="#"><div class="dashicons dashicons-plus"></div></a>
	    </li>


	        </ul>

	        <input type="hidden" id="image_gallery" name="image_gallery"
	        	value="<?php echo esc_attr( $image_gallery ); ?>" />
	        <?php wp_nonce_field( 'custom_gallery', 'custom_gallery' ); ?>

	    </div>

	    <?php
/*
	    <p class="add_gallery_images hide-if-no-js">
	        <a href="#"><?php echo 'Add images'; ?></a>
	    </p>

 */


		// If options don't exist yet, set to checked by default

    	if ( ! get_post_meta( get_the_ID(), '_custom_gallery_link_images', true ) )
	        $checked = ' checked="checked"';
    	else
        	$checked = $this->has_linked_images() ? checked( get_post_meta( get_the_ID(), '_custom_gallery_link_images', true ), 'on', false ) : '';


        /*========================================================================
         *
         * Image order and remove actions
         *
         *=======================================================================*/

		?>
	    <script type="text/javascript">
	        jQuery(document).ready(function($){

	            var image_gallery_frame;

	            var $gallery_images_wrap = $('#gallery_images_container');
	            var $image_gallery_ids = $('#image_gallery');
	            var $gallery_images = $('#gallery_images_container ul.gallery_images');



	            function adjust_gallery_width() {

					// Get the total width and center the images

					var mw = $gallery_images_wrap.width();
					var e = 170; // each image
					var nume = Math.floor(mw / e); // round down

					var fitw = nume * e;
					$gallery_images.width(fitw); console.log(fitw);

	            }

				$(window).resize(function() {
					adjust_gallery_width();
				});
				adjust_gallery_width();

	            $('.add_gallery_images').on( 'click', 'a', function( event ) {

	                var $el = $(this);
	                var attachment_ids = $image_gallery_ids.val();

	                event.preventDefault();

	                // If the media frame already exists, reopen it.
	                if ( image_gallery_frame ) {
	                    image_gallery_frame.open();
	                    return;
	                }

	                // Create the media frame.
	                image_gallery_frame = wp.media.frames.downloadable_file = wp.media({
	                    // Set the title of the modal.
	                    title: '<?php _e( 'Add Images to Gallery', 'custom-gallery' ); ?>',
	                    button: {
	                        text: '<?php _e( 'Add to gallery', 'custom-gallery' ); ?>',
	                    },
	                    multiple: true
	                });

	                // When an image is selected, run a callback.
	                image_gallery_frame.on( 'select', function() {

	                    var selection = image_gallery_frame.state().get('selection');

	                    selection.map( function( attachment ) {

	                        attachment = attachment.toJSON();

	                        if ( attachment.id ) {
	                            attachment_ids = attachment_ids ? attachment_ids + "," + attachment.id : attachment.id;

	                             $gallery_images.append('\
	                                <li class="image attachment details" data-attachment_id="' + attachment.id + '">\
	                                    <div class="attachment-preview">\
	                                        <div class="thumbnail">\
	                                            <img src="' + attachment.url + '" />\
	                                        </div>\
	                                       <a href="#" class="delete check" title="<?php _e( 'Remove image', 'custom-gallery' ); ?>"><div class="media-modal-icon"></div></a>\
	                                    </div>\
	                                </li>');

	                        }

						// Move "add image" icon to the end
	                    $gallery_images.find('.add_gallery_images').appendTo($gallery_images);

	                    } );

	                    $image_gallery_ids.val( attachment_ids );
	                });

	                // Finally, open the modal.
	                image_gallery_frame.open();
	            });

	            // Image ordering
	            $gallery_images.sortable({
	                items: 'li.image',
	                cursor: 'move',
	                scrollSensitivity:40,
	                forcePlaceholderSize: true,
	                forceHelperSize: true, // false
	                helper: 'clone',
	                opacity: 0.65,
	                placeholder: 'eig-metabox-sortable-placeholder',
	                start:function(event,ui){
	                    ui.item.css('background-color','#f6f6f6');
	                },
	                stop:function(event,ui){
	                    ui.item.removeAttr('style');
	                },
	                update: function(event, ui) {
	                    var attachment_ids = '';

	                    $('#gallery_images_container ul li.image').css('cursor','default').each(function() {
	                        var attachment_id = jQuery(this).attr( 'data-attachment_id' );
	                        attachment_ids = attachment_ids + attachment_id + ',';
	                    });

	                    $image_gallery_ids.val( attachment_ids );
	                }
	            });

	            // Remove images
	            $('#gallery_images_container').on( 'click', 'a.delete', function() {

	                $(this).closest('li.image').remove();

	                var attachment_ids = '';

	                $('#gallery_images_container ul li.image').css('cursor','default').each(function() {
	                    var attachment_id = $(this).attr( 'data-attachment_id' );
	                    attachment_ids = attachment_ids + attachment_id + ',';
	                });

	                $image_gallery_ids.val( attachment_ids );

	                return false;
	            } );

	        });
	    </script>
	    <?php
	}



	/*========================================================================
	 *
	 * Metabox save function
	 *
	 *=======================================================================*/

	function save_post( $post_id ) {

	    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
	        return;

	    $post_types = $this->enabled_post_types();

	    // check user permissions
	/*    if ( isset( $_POST[ 'post_type' ] ) && !array_key_exists( $_POST[ 'post_type' ], $post_types ) ) {
	        if ( !current_user_can( 'edit_page', $post_id ) )
	            return;
	    } */
        if ( !current_user_can( 'edit_post', $post_id ) )
            return;

	    if ( ! isset( $_POST[ 'custom_gallery' ] ) || ! wp_verify_nonce( $_POST[ 'custom_gallery' ], 'custom_gallery' ) )
	        return;

	    if ( isset( $_POST[ 'image_gallery' ] ) && !empty( $_POST[ 'image_gallery' ] ) ) {
	        $attachment_ids = sanitize_text_field( $_POST['image_gallery'] );
	        $attachment_ids = explode( ',', $attachment_ids ); // turn comma separated values into array
	        $attachment_ids = array_filter( $attachment_ids  ); // clean the array
	        $attachment_ids =  implode( ',', $attachment_ids ); // return back to comma separated list with no trailing comma. This is common when deleting the images
	        update_post_meta( $post_id, '_custom_gallery', $attachment_ids );
	    } else {
	        delete_post_meta( $post_id, '_custom_gallery' );
	    }

	    // link to larger images
	    if ( isset( $_POST[ 'custom_gallery_link_images' ] ) )
	        update_post_meta( $post_id, '_custom_gallery_link_images', $_POST[ 'custom_gallery_link_images' ] );
	    else
	        update_post_meta( $post_id, '_custom_gallery_link_images', 'off' );

	}


	/*========================================================================
	 *
	 * Settings page
	 *
	 *=======================================================================*/

	function gallery_field_settings() {
		add_options_page( 'Gallery Fields', 'Gallery Fields', 'manage_options', 'custom-gallery', array($this, 'settings_page') );
	}


	/*
	 * Admin page
	 *
	 */

	function settings_page() {
		?>
	    <div class="wrap">
	        <h2><?php echo 'Gallery Fields'; ?></h2>

	        <form action="options.php" method="POST">
	            <?php settings_fields( 'ccs-gallery-field-settings-group' ); ?>
	            <?php do_settings_sections( 'ccs-gallery-field-settings' ); ?>
	            <?php submit_button(); ?>
	        </form>
			<div style="padding-left:5px;">
				<a href="options-general.php?page=ccs_content_shortcode_help&tab=gallery"><em>Reference: Custom Content Shortcode</em></a>
			</div>
	    </div>
	<?php
	}



	/*========================================================================
	 *
	 * Register settings page
	 *
	 *=======================================================================*/

	function register_settings_page() {

		register_setting( 'ccs-gallery-field-settings-group', 'custom-gallery', array($this, 'sanitize') );
		add_settings_section( 'general',  '', '', 'ccs-gallery-field-settings' );
		add_settings_field( 'post-types', '<b>Select post types</b>', array($this, 'post_types_callback'), 'ccs-gallery-field-settings', 'general' );
	}

	/*
	 * Post Types callback
	 */

	function post_types_callback() {

		$settings = (array) get_option( 'custom-gallery', $default = false );

		 foreach ( $this->get_available_post_types() as $key => $label ) {
			$post_types = isset( $settings['post_types'][ $key ] ) ? esc_attr( $settings['post_types'][ $key ] ) : '';

			?><p>
				<input type="checkbox" id="<?php echo $key; ?>" name="custom-gallery[post_types][<?php echo $key; ?>]" <?php checked( $post_types, 'on' ); ?>/><label for="<?php echo $key; ?>"> <?php echo $label; ?></label>
			</p><?php
		} 
	}


	function sanitize( $input ) {

		// Create our array for storing the validated options
		$output = array();

		// post types
		$post_types = isset( $input['post_types'] ) ? $input['post_types'] : '';

		// only loop through if there are post types in the array
		if ( $post_types ) {
			foreach ( $post_types as $post_type => $value )
				$output[ 'post_types' ][ $post_type ] = isset( $input[ 'post_types' ][ $post_type ] ) ? 'on' : '';	
		}
		
		return $output;
	}















/*========================================================================
 *
 * Helper functions
 *
 *=======================================================================*/

	function has_linked_images() {

		$link_images = get_post_meta( get_the_ID(), '_custom_gallery_link_images', true );
		if ( 'on' == $link_images ) return true;
	}


	/*========================================================================
	 *
	 * List of post types for checkboxes on the settings page
	 *
	 *=======================================================================*/

	function get_available_post_types() {

		$args = array( 'public' => true	);

		$post_types = get_post_types( $args );

		// remove attachment
		unset( $post_types[ 'attachment' ] );

		return apply_filters( 'ccs_gallery_field_post_types', $post_types );
	}



	/*========================================================================
	 *
	 * Get enabled post types from options
	 *
	 *=======================================================================*/

	function enabled_post_types() {

		// get the allowed post types

		$settings = ( array ) get_option( 'custom-gallery', $default = false );
		$post_types = isset( $settings['post_types'] ) ? $settings['post_types'] : '';

		// post types don't exist, bail
		if ( ! $post_types )
			return;

		return $post_types;
	}




/*========================================================================
 *
 * Unused
 *
 *=======================================================================*/

	function has_gallery() {
		$attachment_ids = get_post_meta( get_the_ID(), '_custom_gallery', true );
		if ( $attachment_ids )
			return true;
	}

	function has_shortcode( $shortcode = '' ) {
		global $post;
		$found = false;

		if ( !$shortcode ) {
			return $found;
		}
		if (  is_object( $post ) && stripos( $post->post_content, '[' . $shortcode ) !== false ) {
			$found = true; // we have found the short code
		}
		return $found;
	}

	/*
	 * Is the currently viewed post type allowed?
	 */

	function is_enabled_post_type() {

		// get currently viewed post type
		$post_type = ( string ) get_post_type();

		//echo $post_type; exit; // download

		// get the allowed post type from the DB
		$settings = ( array ) get_option( 'custom-gallery', $defaults );
		$post_types = isset( $settings['post_types'] ) ? $settings['post_types'] : '';

		// post types don't exist, bail
		if ( ! $post_types )
			return;

		// check the two against each other
		if ( array_key_exists( $post_type, $post_types ) )
			return true;
	}

	/*========================================================================
	 *
	 * Retrieve attachment IDs
	 *
	 *=======================================================================*/

	function get_image_ids() {

		if( empty(self::$state['current_gallery_id']) ) {

			global $post;
			if( ! isset( $post->ID) )
				return;
			$attachment_ids = get_post_meta( $post->ID, '_custom_gallery', true );

		} else {
			$attachment_ids = get_post_meta( self::$state['current_gallery_id'], '_custom_gallery', true );
		}

		$attachment_ids = explode( ',', $attachment_ids );

		return array_filter( $attachment_ids );
	}


	/*========================================================================
	 *
	 * Count number of images
	 *
	 *=======================================================================*/

	function count_images() {

		$images = get_post_meta( get_the_ID(), '_custom_gallery', true );

		$images = explode(',', $images );
		$number = count( $images );

		return $number;
	}



}