<?php
/*
Plugin Name: wp Custom Post Field
Plugin URI: http://phpdevlopar.blogspot.in/
Description: Add Custom Field for Post
Author: Bhanderi Tushal
Version: 2.0.0
Author URI: http://phpdevlopar.blogspot.in
*/

// Define current version constant
define( 'WCP_VERSION', '2.0.0' );

// Define plugin URL constant
$WCP_URL = wcp_check_return( 'add' );

//load translated strings
add_action( 'init', 'wcp_load_textdomain' );

// create custom plugin settings menu
add_action( 'admin_menu', 'wcp_plugin_menu' );

//call delete post function
add_action( 'admin_init', 'wcp_delete_post_type' );

//call register settings function
add_action( 'admin_init', 'wcp_register_settings' );

//process custom taxonomies if they exist
add_action( 'init', 'wcp_create_custom_post_types', 0 );

add_action( 'admin_head', 'wcp_help_style' );

//flush rewrite rules on deactivation
register_deactivation_hook( __FILE__, 'wcp_deactivation' );

function wcp_deactivation() {
	// Clear the permalinks to remove our post type's rules
	flush_rewrite_rules();
}

function wcp_load_textdomain() {
	load_plugin_textdomain( 'wcp-plugin', false, basename( dirname( __FILE__ ) ) . '/languages' );
}

function wcp_plugin_menu() {
	//create custom post type menu
	add_menu_page( __( 'Custom Post', 'wcp-plugin' ), __( 'Custom Post', 'wcp-plugin' ), 'manage_options', 'wcp_main_menu', 'wcp_settings' );

	//create submenu items
	add_submenu_page( 'wcp_main_menu', __( 'Add New', 'wcp-plugin' ), __( 'Add New', 'wcp-plugin' ), 'manage_options', 'wcp_sub_add_new', 'wcp_add_new' );
	add_submenu_page( 'wcp_main_menu', __( 'Manage Post Types', 'wcp-plugin' ), __( 'Manage Post Types', 'wcp-plugin' ), 'manage_options', 'wcp_sub_manage_wcp', 'wcp_manage_wcp' );
	
}

if ( strpos( $_SERVER['REQUEST_URI'], 'wcp' ) > 0 ) {
	add_action( 'admin_head', 'wcp_wp_add_styles' );
}

// Add JS Scripts
function wcp_wp_add_styles() {

	wp_enqueue_script( 'jquery' ); ?>
<script type="text/javascript" >
			jQuery(document).ready(function($) {
				$(".comment_button").click(function() {
					var element = $(this), I = element.attr("id");
					$("#slidepanel"+I).slideToggle(300);
					$(this).toggleClass("active");

					return false;
				});
			});
		</script>
<?php
}

function wcp_create_custom_post_types() {
	//register custom post types
	$wcp_post_types = get_option('wcp_custom_post_types');

	//check if option value is an Array before proceeding
	if ( is_array( $wcp_post_types ) ) {
		foreach ($wcp_post_types as $wcp_post_type) {
			//set post type values
			$wcp_label              = ( !empty( $wcp_post_type["label"] ) ) ? esc_html( $wcp_post_type["label"] ) : esc_html( $wcp_post_type["name"] ) ;
			$wcp_singular           = ( !empty( $wcp_post_type["singular_label"] ) ) ? esc_html( $wcp_post_type["singular_label"] ) : esc_html( $wcp_label );
			$wcp_rewrite_slug       = ( !empty( $wcp_post_type["rewrite_slug"] ) ) ? esc_html( $wcp_post_type["rewrite_slug"] ) : esc_html( $wcp_post_type["name"] );
			$wcp_rewrite_withfront  = ( !empty( $wcp_post_type["rewrite_withfront"] ) ) ? true : get_disp_boolean( $wcp_post_type["rewrite_withfront"] ); //reversed because false is empty
			$wcp_menu_position      = ( !empty( $wcp_post_type["menu_position"] ) ) ? intval( $wcp_post_type["menu_position"] ) : null; //must be null
			$wcp_menu_icon          = ( !empty( $wcp_post_type["menu_icon"] ) ) ? esc_attr( $wcp_post_type["menu_icon"] ) : null; //must be null
			$wcp_taxonomies         = ( !empty( $wcp_post_type[1] ) ) ? $wcp_post_type[1] : array();
			$wcp_supports           = ( !empty( $wcp_post_type[0] ) ) ? $wcp_post_type[0] : array();

			//Show UI must be true
			if ( true == get_disp_boolean( $wcp_post_type["show_ui"] ) ) {
				//If the string is empty, we will need boolean, else use the string.
				if ( empty( $wcp_post_type['show_in_menu_string'] ) ) {
					$wcp_show_in_menu = ( $wcp_post_type["show_in_menu"] == 1 ) ? true : false;
				} else {
					$wcp_show_in_menu = $wcp_post_type['show_in_menu_string'];



				}
			} else {
				$wcp_show_in_menu = false;
			}

			//set custom label values
			$wcp_labels['name']             = $wcp_label;
			$wcp_labels['singular_name']    = $wcp_post_type["singular_label"];


			if ( isset ( $wcp_post_type[2]["menu_name"] ) ) {
				$wcp_labels['menu_name'] = ( !empty( $wcp_post_type[2]["menu_name"] ) ) ? $wcp_post_type[2]["menu_name"] : $wcp_label;
			}

			$wcp_has_archive                    = ( !empty( $wcp_post_type["has_archive"] ) ) ? get_disp_boolean( $wcp_post_type["has_archive"] ) : '';
			$wcp_exclude_from_search            = ( !empty( $wcp_post_type["exclude_from_search"] ) ) ? get_disp_boolean( $wcp_post_type["exclude_from_search"] ) : '';
			$wcp_labels['add_new']              = ( !empty( $wcp_post_type[2]["add_new"] ) ) ? $wcp_post_type[2]["add_new"] : 'Add ' .$wcp_singular;
			$wcp_labels['add_new_item']         = ( !empty( $wcp_post_type[2]["add_new_item"] ) ) ? $wcp_post_type[2]["add_new_item"] : 'Add New ' .$wcp_singular;
			$wcp_labels['edit']                 = ( !empty( $wcp_post_type[2]["edit"] ) ) ? $wcp_post_type[2]["edit"] : 'Edit';
			$wcp_labels['edit_item']            = ( !empty( $wcp_post_type[2]["edit_item"] ) ) ? $wcp_post_type[2]["edit_item"] : 'Edit ' .$wcp_singular;
			$wcp_labels['new_item']             = ( !empty( $wcp_post_type[2]["new_item"] ) ) ? $wcp_post_type[2]["new_item"] : 'New ' .$wcp_singular;
			$wcp_labels['view']                 = ( !empty( $wcp_post_type[2]["view"] ) ) ? $wcp_post_type[2]["view"] : 'View ' .$wcp_singular;
			$wcp_labels['view_item']            = ( !empty( $wcp_post_type[2]["view_item"] ) ) ? $wcp_post_type[2]["view_item"] : 'View ' .$wcp_singular;
			$wcp_labels['search_items']         = ( !empty( $wcp_post_type[2]["search_items"] ) ) ? $wcp_post_type[2]["search_items"] : 'Search ' .$wcp_label;
			$wcp_labels['not_found']            = ( !empty( $wcp_post_type[2]["not_found"] ) ) ? $wcp_post_type[2]["not_found"] : 'No ' .$wcp_label. ' Found';
			$wcp_labels['not_found_in_trash']   = ( !empty( $wcp_post_type[2]["not_found_in_trash"] ) ) ? $wcp_post_type[2]["not_found_in_trash"] : 'No ' .$wcp_label. ' Found in Trash';
			
			register_post_type( $wcp_post_type["name"], array(	'label' => __($wcp_label),
				'public' => get_disp_boolean($wcp_post_type["public"]),
				'singular_label' => $wcp_post_type["singular_label"],
				'description' => esc_html($wcp_post_type["description"]),
				'labels' => $wcp_labels
			) );
		}
	}
}


//delete custom post type or custom taxonomy
function wcp_delete_post_type() {
	global $WCP_URL;

	//check if we are deleting a custom post type
	if( isset( $_GET['deltype'] ) ) {

		//nonce security check
		check_admin_referer( 'wcp_delete_post_type' );

		$delType = intval( $_GET['deltype'] );
		$wcp_post_types = get_option( 'wcp_custom_post_types' );

		unset( $wcp_post_types[$delType] );

		$wcp_post_types = array_values( $wcp_post_types );

		update_option( 'wcp_custom_post_types', $wcp_post_types );

		if ( isset( $_GET['return'] ) ) {
			$RETURN_URL = wcp_check_return( esc_attr( $_GET['return'] ) );
		} else {
			$RETURN_URL = $WCP_URL;
		}

		wp_redirect( $RETURN_URL .'&wcp_msg=del' );
	}

	
}

function wcp_register_settings() {
	global $wcp_error, $WCP_URL;

	if ( isset( $_POST['wcp_edit'] ) ) {
		//edit a custom post type
		check_admin_referer( 'wcp_add_custom_post_type' );

		//custom post type to edit
		$wcp_edit = intval( $_POST['wcp_edit'] );

		//edit the custom post type
		$wcp_form_fields = $_POST['wcp_custom_post_type'];

		//add support checkbox values to array
		$wcp_supports = ( isset( $_POST['wcp_supports'] ) ) ? $_POST['wcp_supports'] : null;
		array_push($wcp_form_fields, $wcp_supports);

		//add label values to array
		array_push( $wcp_form_fields, $_POST['wcp_labels'] );

		//load custom posts saved in WP
		$wcp_options = get_option( 'wcp_custom_post_types' );

		if ( is_array( $wcp_options ) ) {

			unset( $wcp_options[$wcp_edit] );

			//insert new custom post type into the array
			array_push( $wcp_options, $wcp_form_fields );

			$wcp_options = array_values( $wcp_options );
			$wcp_options = stripslashes_deep( $wcp_options );

			//save custom post types
			update_option( 'wcp_custom_post_types', $wcp_options );

			if ( isset( $_GET['return'] ) ) {
				$RETURN_URL = wcp_check_return( esc_attr( $_GET['return'] ) );
			} else {
				$RETURN_URL = $WCP_URL;
			}

			wp_redirect( $RETURN_URL );

		}

	} elseif ( isset( $_POST['wcp_submit'] ) ) {
		//create a new custom post type

		//nonce security check
		check_admin_referer( 'wcp_add_custom_post_type' );

		//retrieve new custom post type values
		$wcp_form_fields = $_POST['wcp_custom_post_type'];

		if ( empty( $wcp_form_fields["name"] ) ) {
			if ( isset( $_GET['return'] ) ) {
				$RETURN_URL = wcp_check_return( esc_attr( $_GET['return'] ) );
			} else {
				$RETURN_URL = $WCP_URL;
			}

			wp_redirect( $RETURN_URL .'&wcp_error=1' );
			exit();
		}
		if ( false !== strpos( $wcp_form_fields["name"], '\'' ) ||
		     false !== strpos( $wcp_form_fields["name"], '\"' ) ||
		     false !== strpos( $wcp_form_fields["rewrite_slug"], '\'' ) ||
		     false !== strpos( $wcp_form_fields["rewrite_slug"], '\"' ) ) {
			if ( isset( $_GET['return'] ) ) {
				$RETURN_URL = wcp_check_return( esc_attr( $_GET['return'] ) );
			} else {
				$RETURN_URL = $WCP_URL;
			}

			wp_redirect( $RETURN_URL .'&wcp_error=4' );
			exit();
		}

		//add support checkbox values to array
		$wcp_supports = ( isset( $_POST['wcp_supports'] ) ) ? $_POST['wcp_supports'] : null;
		array_push( $wcp_form_fields, $wcp_supports );

		//add label values to array
		array_push( $wcp_form_fields, $_POST['wcp_labels'] );

		//load custom posts saved in WP
		$wcp_options = get_option( 'wcp_custom_post_types' );

		//check if option exists, if not create an array for it
		if ( !is_array( $wcp_options ) ) {
			$wcp_options = array();
		}

		//insert new custom post type into the array
		array_push( $wcp_options, $wcp_form_fields );
		$wcp_options = stripslashes_deep( $wcp_options );

		//save new custom post type array in the WCP option
		update_option( 'wcp_custom_post_types', $wcp_options );

		if ( isset( $_GET['return'] ) ) {
			$RETURN_URL = wcp_check_return( esc_attr( $_GET['return'] ) );
		} else {
			$RETURN_URL = $WCP_URL;
		}

		wp_redirect( $RETURN_URL .'&wcp_msg=1' );
	}

}

//main welcome/settings page
function wcp_settings() {
	global $WCP_URL, $wp_post_types;

	//flush rewrite rules
	flush_rewrite_rules();
?>

<div class="wrap">
  <?php screen_icon( 'plugins' ); ?>
  <h2>
    <?php _e( 'Custom Post', 'wcp-plugin' ); ?>
    <?php _e( 'version', 'wcp-plugin' ); ?>
    : <?php echo WCP_VERSION; ?></h2>
  <div class="cp-rss-widget">
    <table border="0">
        <tr>
        <td width="33%"><h3>
            <?php _e( 'About Plugin', 'wcp-plugin' ); ?>
          </h3></td>
      </tr>
      <tr>
        <td valign="top" width="33%"><p>
            <?php _e( 'This Plugin Created For Add Multiple Custom Post Type In Wordpress Site And Use Multiple Time with Single File code. 
			<br />
			On This Plugin You Create Multiple Post Filed And add Post Blog on That .If You Want To Display Post On Single Page Then You Can Do it With Use Post Type Name .
			<br>  ', 'wcp-plugin'); ?>


          </p></td>
      </tr>
      <tr>
			<td width="33%"><h3><?php _e( 'PayPal Donation', 'wcp-plugin' ); ?></h3></td>
			</tr>
			<tr>
			<td valign="top" width="33%">
				<p><?php _e( 'Please donate to the development<br />of Custom Post Field:', 'wcp-plugin'); ?>
				
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
				<input type="hidden" name="cmd" value="_donations">
				<input type="hidden" name="business" value="btushal304@gmail.com">
				<input type="hidden" name="lc" value="US">
				<input type="hidden" name="item_name" value="Wos">
				<input type="hidden" name="no_note" value="0">
				<input type="hidden" name="currency_code" value="USD">
				<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHostedGuest">
				<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
				<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
				</form>


				</p>
			</td>
    </table>
  </div>
</div>
<?php
//load footer
wcp_footer();
}

//manage custom post types page
function wcp_manage_wcp() {
	global $WCP_URL;

	$MANAGE_URL = wcp_check_return( 'add' );

?>
<div class="wrap">
  <?php
//check for success/error messages
if ( isset($_GET['wcp_msg'] ) && $_GET['wcp_msg'] == 'del' ) { ?>
  <div id="message" class="updated">
    <?php _e('Custom post type deleted successfully', 'wcp-plugin'); ?>
  </div>
  <?php
}
?>
  <?php screen_icon( 'plugins' ); ?>
  <h2>
    <?php _e('Manage Custom Post Types', 'wcp-plugin') ?>
  </h2>
  <p>
    <?php _e('Deleting custom post types will <strong>NOT</strong> delete any content into the database or added to those post types.  You can easily recreate your post types and the content will still exist.', 'wcp-plugin') ?>
  </p>
  <?php
	$wcp_post_types = get_option( 'wcp_custom_post_types', array() );

	if (is_array($wcp_post_types)) {
		?>
  <table width="100%" class="widefat">
    <thead>
      <tr>
        <th><?php _e('Action', 'wcp-plugin');?></th>
        <th><?php _e('Name', 'wcp-plugin');?></th>
        <th><?php _e('Label', 'wcp-plugin');?></th>
        <th><?php _e('Description', 'wcp-plugin');?></th>
        <th><?php _e('Shortcode', 'wcp-plugin');?></th>	   
        </tr>
    </thead>
    <tfoot>
      <tr>
        <th><?php _e('Action', 'wcp-plugin');?></th>
        <th><?php _e('Name', 'wcp-plugin');?></th>
        <th><?php _e('Label', 'wcp-plugin');?></th>
        <th><?php _e('Description', 'wcp-plugin');?></th>
         <th><?php _e('Shortcode', 'wcp-plugin');?></th>
        </tr>
    </tfoot>
    <?php
		$thecounter=0;
		$wcp_names = array();
		//Create urls for management
		foreach ( $wcp_post_types as $wcp_post_type ) {
			$del_url = wcp_check_return( 'wcp' ) .'&deltype=' .$thecounter .'&return=wcp';
			$del_url = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($del_url, 'wcp_delete_post_type') : $del_url;

			$edit_url = $MANAGE_URL .'&edittype=' .$thecounter .'&return=wcp';
			$edit_url = ( function_exists('wp_nonce_url') ) ? wp_nonce_url($edit_url, 'wcp_edit_post_type') : $edit_url;

			$wcp_counts = wp_count_posts($wcp_post_type["name"]);

			$rewrite_slug = ( $wcp_post_type["rewrite_slug"] ) ? $wcp_post_type["rewrite_slug"] : $wcp_post_type["name"];
		?>
    <tr>
      <td valign="top"><a href="<?php echo $del_url; ?>">
        <?php _e( 'Delete', 'wcp-plugin' ); ?>
        </a> / <a href="<?php echo $edit_url; ?>">
        <?php _e( 'Edit', 'wcp-plugin' ); ?>
        </a> 
        </td>
<!--       <td valign="top"><?php //echo stripslashes($wcp_post_type["id"]); ?></td> -->                    
      <td valign="top"><?php echo stripslashes($wcp_post_type["name"]); ?></td>
      <td valign="top"><?php echo stripslashes($wcp_post_type["label"]); ?></td>
      <td valign="top"><?php echo stripslashes($wcp_post_type["description"]); ?></td>
      <td valign="top"><?php echo '[WCPPOST postname="'.stripslashes($wcp_post_type["label"]).'"]'; ?></td>
    </tr>
    
    
	<?php  			
		$thecounter++;
		
		$wcp_names[] = strtolower( $wcp_post_type["name"] );
		}
		
			$args=array(
			  'public'   => true,
			  '_builtin' => false
			);
			$output = 'objects'; // or objects
			$post_types = get_post_types( $args, $output );
			$wcp_first = false;
			if ( $post_types ) {

				?>
  </table>
</div>
<?php
	

		//load footer
		wcp_footer();
	}
	}
}

//add new custom post type / taxonomy page
function wcp_add_new() {
	global $wcp_error, $WCP_URL;

	$RETURN_URL = ( isset( $_GET['return'] ) ) ? 'action="' . wcp_check_return( esc_attr( $_GET['return'] ) ) . '"' : '';

	//check if we are editing a custom post type or creating a new one
	if ( isset( $_GET['edittype'] ) && !isset( $_GET['wcp_edit'] ) ) {
		check_admin_referer('wcp_edit_post_type');

		//get post type to edit. This will reference array index for our option.
		$editType = intval($_GET['edittype']);

		//load custom posts saved in WP
		$wcp_options = get_option('wcp_custom_post_types');

		//load custom post type values to edit
		$wcp_post_type_name     = ( isset( $wcp_options[ $editType ]["name"] ) ) ? $wcp_options[ $editType ]["name"] : null;
		$wcp_label              = ( isset( $wcp_options[ $editType ]["label"] ) ) ? $wcp_options[ $editType ]["label"] : null;
		$wcp_singular_label     = ( isset( $wcp_options[ $editType ]["singular_label"] ) ) ? $wcp_options[ $editType ]["singular_label"] : null;
		$wcp_public             = ( isset( $wcp_options[ $editType ]["public"] ) ) ? $wcp_options[ $editType ]["public"] : null;
		$wcp_description        = ( isset( $wcp_options[ $editType ]["description"] ) ) ? $wcp_options[ $editType ]["description"] : null;
		$wcp_has_archive        = ( isset( $wcp_options[$editType]["has_archive"] ) ) ? $wcp_options[$editType]["has_archive"] : null;
		$wcp_exclude_from_search = ( isset( $wcp_options[$editType]["exclude_from_search"] ) ) ? $wcp_options[$editType]["exclude_from_search"] : null;

		$wcp_submit_name = __( 'Save Custom Post Type', 'wcp-plugin' );
	} else {
		$wcp_submit_name = __( 'Create Custom Post Type', 'wcp-plugin' );
	}

	

	//flush rewrite rules
	flush_rewrite_rules();

	/*
	BEGIN 'ADD NEW' PAGE OUTPUT
	 */
	?>
<div class="wrap">
  <?php
		//check for success/error messages
		if ( isset( $_GET['wcp_msg'] ) ) : ?>
  <div id="message" class="updated">
    <?php if ( $_GET['wcp_msg'] == 1 ) {
				_e( 'Custom post type created successfully. You may need to refresh to view the new post type in the admin menu.', 'wcp-plugin' );
				echo '<a href="' . wcp_check_return( 'wcp' ) . '"> ' . __( 'Manage custom post types', 'wcp-plugin') . '</a>';
			} ?>
  </div>
  <?php
		else :
			if ( isset( $_GET['wcp_error'] ) ) : ?>
  <div class="error">
    <?php if ( $_GET['wcp_error'] == 1 ) {
					_e( 'Post type name is a required field.', 'wcp-plugin' );
				}
				if ( $_GET['wcp_error'] == 3 ) {
					_e( 'You must assign your custom taxonomy to at least one post type.', 'wcp-plugin' );
				}
				if ( $_GET['wcp_error'] == 4 ) {
					_e( 'Please doe not use quotes in your post type slug or rewrite slug.', 'wcp-plugin' );
				}
				 ?>
  </div>
  <?php
			endif;
		endif;

		screen_icon( 'plugins' );

		if ( isset( $_GET['edittype'] ) || isset( $_GET['edittax'] ) ) { ?>
  <h2>
    <?php _e('Edit Custom Post Type', 'wcp-plugin') ?>
    &middot; <a href="<?php echo wcp_check_return( 'add' ); ?>">
    <?php _e('Reset', 'wcp-plugin');?>
    </a> </h2>
  <?php } else { ?>
  <h2>
    <?php _e('Create New Custom Post Type', 'wcp-plugin') ?>
    &middot; <a href="<?php echo wcp_check_return( 'add' ); ?>">
    <?php _e('Reset', 'wcp-plugin');?>
    </a> </h2>
  <?php } ?>
  <table border="0" cellspacing="10" class="widefat">
    <?php
			//BEGIN WCP HALF
			?>
    <tr>
      <td width="50%" valign="top"><p>
          <?php _e('Add the <strong>Post Type Name</strong> and <strong>Label</strong> fields and check which meta boxes to support. The other settings are set to the most common defaults for custom post types. Hover over the question mark for more details.', 'wcp-plugin'); ?>
        </p>
        <form method="post" <?php echo $RETURN_URL; ?>>
          <?php
						if ( function_exists( 'wp_nonce_field' ) )
							wp_nonce_field( 'wcp_add_custom_post_type' );
						?>
          <?php if ( isset( $_GET['edittype'] ) ) { ?>
          <input type="hidden" name="wcp_edit" value="<?php echo esc_attr( $editType ); ?>" />
          <?php } ?>
          <table class="form-table">
            <tr valign="top">
              <th scope="row"><?php _e('Post Type Name', 'wcp-plugin') ?>
                <span class="required">*</span> <a href="#" title="<?php esc_attr_e( 'The post type name.  Used to retrieve custom post type content.  Should be short and sweet', 'wcp-plugin'); ?>" class="help">?</a></th>
              <td><input type="text" name="wcp_custom_post_type[name]" tabindex="1" value="<?php if (isset($wcp_post_type_name)) { echo esc_attr($wcp_post_type_name); } ?>" maxlength="20" onblur="this.value=this.value.toLowerCase()" />
                <br />
                <p><strong>
                  <?php _e( 'Max 20 characters, can not contain capital letters or spaces. Reserved post types: post, page, attachment, revision, nav_menu_item.', 'wcp-plugin' ); ?>
                  </strong></p></td>
            </tr>
            <tr valign="top">
              <th scope="row"><?php _e('Label', 'wcp-plugin') ?>
                <a href="#" title="<?php esc_attr_e( 'Post type label.  Used in the admin menu for displaying post types.', 'wcp-plugin' ); ?>" class="help">?</a></th>
              <td><input type="text" name="wcp_custom_post_type[label]" tabindex="2" value="<?php if (isset($wcp_label)) { echo esc_attr($wcp_label); } ?>" /></td>
            </tr>
            <tr valign="top">
              <th scope="row"><?php _e('Singular Label', 'wcp-plugin') ?>
                <a href="#" title="<?php esc_attr_e( 'Custom Post Type Singular label.  Used in WordPress when a singular label is needed.', 'wcp-plugin' ); ?>" class="help">?</a></th>
              <td><input type="text" name="wcp_custom_post_type[singular_label]" tabindex="3" value="<?php if (isset($wcp_singular_label)) { echo esc_attr($wcp_singular_label); } ?>" /></td>
            </tr>
            <tr valign="top">
              <th scope="row"><?php _e('Description', 'wcp-plugin') ?>
                <a href="#" title="<?php esc_attr_e( 'Custom Post Type Description.  Describe what your custom post type is used for.', 'wcp-plugin' ); ?>" class="help">?</a></th>
              <td><textarea name="wcp_custom_post_type[description]" tabindex="4" rows="4" cols="40"><?php if (isset($wcp_description)) { echo esc_attr($wcp_description); } ?>
</textarea></td>
            </tr>
          </table>
          <div style="display:none;" id="slidepanel1">
            <p>
              <?php _e('Below are the advanced label options for custom post types.  If you are unfamiliar with these labels, leave them blank and the plugin will automatically create labels based off of your custom post type name', 'wcp-plugin'); ?>
            </p>
            <table class="form-table">
              <tr valign="top">
                <th scope="row"><?php _e('Menu Name', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'Custom menu name for your custom post type.', 'wcp-plugin' ); ?>" class="help">?</a></th>
                <td><input type="text" name="wcp_labels[menu_name]" tabindex="2" value="<?php if (isset($wcp_labels["menu_name"])) { echo esc_attr($wcp_labels["menu_name"]); } ?>" />
                  <br/></td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Add New', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'Post type label.  Used in the admin menu for displaying post types.', 'wcp-plugin' ); ?>" class="help">?</a></th>
                <td><input type="text" name="wcp_labels[add_new]" tabindex="2" value="<?php if (isset($wcp_labels["add_new"])) { echo esc_attr($wcp_labels["add_new"]); } ?>" />
                  <br/></td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Add New Item', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'Post type label.  Used in the admin menu for displaying post types.', 'wcp-plugin' ); ?>" class="help">?</a></th>
                <td><input type="text" name="wcp_labels[add_new_item]" tabindex="2" value="<?php if (isset($wcp_labels["add_new_item"])) { echo esc_attr($wcp_labels["add_new_item"]); } ?>" />
                  <br/></td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Edit', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'Post type label.  Used in the admin menu for displaying post types.', 'wcp-plugin' ); ?>" class="help">?</a></th>
                <td><input type="text" name="wcp_labels[edit]" tabindex="2" value="<?php if (isset($wcp_labels["edit"])) { echo esc_attr($wcp_labels["edit"]); } ?>" />
                  <br/></td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Edit Item', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'Post type label.  Used in the admin menu for displaying post types.', 'wcp-plugin' ); ?>" class="help">?</a></th>
                <td><input type="text" name="wcp_labels[edit_item]" tabindex="2" value="<?php if (isset($wcp_labels["edit_item"])) { echo esc_attr($wcp_labels["edit_item"]); } ?>" />
                  <br/></td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('New Item', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'Post type label.  Used in the admin menu for displaying post types.', 'wcp-plugin' ); ?>" class="help">?</a></th>
                <td><input type="text" name="wcp_labels[new_item]" tabindex="2" value="<?php if (isset($wcp_labels["new_item"])) { echo esc_attr($wcp_labels["new_item"]); } ?>" />
                  <br/></td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('View', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'Post type label.  Used in the admin menu for displaying post types.', 'wcp-plugin' ); ?>" class="help">?</a></th>
                <td><input type="text" name="wcp_labels[view]" tabindex="2" value="<?php if (isset($wcp_labels["view"])) { echo esc_attr($wcp_labels["view"]); } ?>" />
                  <br/></td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('View Item', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'Post type label.  Used in the admin menu for displaying post types.', 'wcp-plugin' ); ?>" class="help">?</a></th>
                <td><input type="text" name="wcp_labels[view_item]" tabindex="2" value="<?php if (isset($wcp_labels["view_item"])) { echo esc_attr($wcp_labels["view_item"]); } ?>" />
                  <br/></td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Search Items', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'Post type label.  Used in the admin menu for displaying post types.', 'wcp-plugin' ); ?>" class="help">?</a></th>
                <td><input type="text" name="wcp_labels[search_items]" tabindex="2" value="<?php if (isset($wcp_labels["search_items"])) { echo esc_attr($wcp_labels["search_items"]); } ?>" />
                  <br/></td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Not Found', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'Post type label.  Used in the admin menu for displaying post types.', 'wcp-plugin' ); ?>" class="help">?</a></th>
                <td><input type="text" name="wcp_labels[not_found]" tabindex="2" value="<?php if (isset($wcp_labels["not_found"])) { echo esc_attr($wcp_labels["not_found"]); } ?>" />
                  <br/></td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Not Found in Trash', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'Post type label.  Used in the admin menu for displaying post types.', 'wcp-plugin' ); ?>" class="help">?</a></th>
                <td><input type="text" name="wcp_labels[not_found_in_trash]" tabindex="2" value="<?php if (isset($wcp_labels["not_found_in_trash"])) { echo esc_attr($wcp_labels["not_found_in_trash"]); } ?>" />
                  <br/></td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Parent', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'Post type label.  Used in the admin menu for displaying post types.', 'wcp-plugin' ); ?>" class="help">?</a></th>
                <td><input type="text" name="wcp_labels[parent]" tabindex="2" value="<?php if (isset($wcp_labels["parent"])) { echo esc_attr($wcp_labels["parent"]); } ?>" />
                  <br/></td>
              </tr>
            </table>
          </div>
          <div style="display:none;" id="slidepanel2">
            <table class="form-table">
              <tr valign="top">
                <th scope="row"><?php _e('Public', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'Whether posts of this type should be shown in the admin UI', 'wcp-plugin' ); ?>" class="help">?</a></th>
                <td><select name="wcp_custom_post_type[public]" tabindex="4">
                    <option value="0" <?php if (isset($wcp_public)) { if ($wcp_public == 0 && $wcp_public != '') { echo 'selected="selected"'; } } ?>>
                    <?php _e( 'False', 'wcp-plugin' ); ?>
                    </option>
                    <option value="1" <?php if (isset($wcp_public)) { if ($wcp_public == 1 || is_null($wcp_public)) { echo 'selected="selected"'; } } else { echo 'selected="selected"'; } ?>>
                    <?php _e( 'True', 'wcp-plugin' ); ?>
                    </option>
                  </select>
                  <?php _e( '(default: True)', 'wcp-plugin' ); ?></td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Show UI', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'Whether to generate a default UI for managing this post type', 'wcp-plugin' ); ?>" class="help">?</a></th>
                <td><select name="wcp_custom_post_type[show_ui]" tabindex="5">
                    <option value="0" <?php if (isset($wcp_showui)) { if ($wcp_showui == 0 && $wcp_showui != '') { echo 'selected="selected"'; } } ?>>
                    <?php _e( 'False', 'wcp-plugin' ); ?>
                    </option>
                    <option value="1" <?php if (isset($wcp_showui)) { if ($wcp_showui == 1 || is_null($wcp_showui)) { echo 'selected="selected"'; } } else { echo 'selected="selected"'; } ?>>
                    <?php _e( 'True', 'wcp-plugin' ); ?>
                    </option>
                  </select>
                  <?php _e( '(default: True)', 'wcp-plugin' ); ?></td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Has Archive', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'Whether the post type will have a post type archive page', 'wcp-plugin' ); ?>" class="help">?</a></th>
                <td><select name="wcp_custom_post_type[has_archive]" tabindex="6">
                    <option value="0" <?php if (isset($wcp_has_archive)) { if ($wcp_has_archive == 0) { echo 'selected="selected"'; } } else { echo 'selected="selected"'; } ?>>
                    <?php _e( 'False', 'wcp-plugin' ); ?>
                    </option>
                    <option value="1" <?php if (isset($wcp_has_archive)) { if ($wcp_has_archive == 1) { echo 'selected="selected"'; } } ?>>
                    <?php _e( 'True', 'wcp-plugin' ); ?>
                    </option>
                  </select>
                  <?php _e( '(default: False)', 'wcp-plugin' ); ?></td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Exclude From Search', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'Whether the post type will be searchable', 'wcp-plugin' ); ?>" class="help">?</a></th>
                <td><select name="wcp_custom_post_type[exclude_from_search]" tabindex="6">
                    <option value="0" <?php if (isset($wcp_exclude_from_search)) { if ($wcp_exclude_from_search == 0) { echo 'selected="selected"'; } } else { echo 'selected="selected"'; } ?>>
                    <?php _e( 'False', 'wcp-plugin' ); ?>
                    </option>
                    <option value="1" <?php if (isset($wcp_exclude_from_search)) { if ($wcp_exclude_from_search == 1) { echo 'selected="selected"'; } } ?>>
                    <?php _e( 'True', 'wcp-plugin' ); ?>
                    </option>
                  </select>
                  <?php _e( '(default: False)', 'wcp-plugin' ); ?></td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Capability Type', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'The post type to use for checking read, edit, and delete capabilities', 'wcp-plugin' ); ?>" class="help">?</a></th>
                <td><input type="text" name="wcp_custom_post_type[capability_type]" tabindex="6" value="<?php if ( isset( $wcp_capability ) ) { echo esc_attr( $wcp_capability ); } else { echo 'post'; } ?>" /></td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Hierarchical', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'Whether the post type can have parent-child relationships', 'wcp-plugin' ); ?>" class="help">?</a></th>
                <td><select name="wcp_custom_post_type[hierarchical]" tabindex="8">
                    <option value="0" <?php if (isset($wcp_hierarchical)) { if ($wcp_hierarchical == 0) { echo 'selected="selected"'; } } else { echo 'selected="selected"'; } ?>>
                    <?php _e( 'False', 'wcp-plugin' ); ?>
                    </option>
                    <option value="1" <?php if (isset($wcp_hierarchical)) { if ($wcp_hierarchical == 1) { echo 'selected="selected"'; } } ?>>
                    <?php _e( 'True', 'wcp-plugin' ); ?>
                    </option>
                  </select>
                  <?php _e( '(default: False)', 'wcp-plugin' ); ?></td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Rewrite', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'Triggers the handling of rewrites for this post type', 'wcp-plugin' ); ?>" class="help">?</a></th>
                <td><select name="wcp_custom_post_type[rewrite]" tabindex="9">
                    <option value="0" <?php if (isset($wcp_rewrite)) { if ($wcp_rewrite == 0 && $wcp_rewrite != '') { echo 'selected="selected"'; } } ?>>
                    <?php _e( 'False', 'wcp-plugin' ); ?>
                    </option>
                    <option value="1" <?php if (isset($wcp_rewrite)) { if ($wcp_rewrite == 1 || is_null($wcp_rewrite)) { echo 'selected="selected"'; } } else { echo 'selected="selected"'; } ?>>
                    <?php _e( 'True', 'wcp-plugin' ); ?>
                    </option>
                  </select>
                  <?php _e( '(default: True)', 'wcp-plugin' ); ?></td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Custom Rewrite Slug', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'Custom slug to use instead of the default.' ,'wcp-plugin' ); ?>" class="help">?</a></th>
                <td><input type="text" name="wcp_custom_post_type[rewrite_slug]" tabindex="10" value="<?php if (isset($wcp_rewrite_slug)) { echo esc_attr($wcp_rewrite_slug); } ?>" />
                  <?php _e( '(default: post type name)', 'wcp-plugin' ); ?></td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('With Front', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'Should the permastruct be prepended with the front base.', 'wcp-plugin' ); ?>" class="help">?</a></th>
                <td><select name="wcp_custom_post_type[rewrite_withfront]" tabindex="4">
                    <option value="0" <?php if (isset($wcp_rewrite_withfront)) { if ($wcp_rewrite_withfront == 0 && $wcp_rewrite_withfront != '') { echo 'selected="selected"'; } } ?>>
                    <?php _e( 'False', 'wcp-plugin' ); ?>
                    </option>
                    <option value="1" <?php if (isset($wcp_rewrite_withfront)) { if ($wcp_rewrite_withfront == 1 || is_null($wcp_rewrite_withfront)) { echo 'selected="selected"'; } } else { echo 'selected="selected"'; } ?>>
                    <?php _e( 'True', 'wcp-plugin' ); ?>
                    </option>
                  </select>
                  <?php _e( '(default: True)', 'wcp-plugin' ); ?></td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Query Var', 'wcp-plugin') ?>
                  <a href="#" title="" class="help">?</a></th>
                <td><select name="wcp_custom_post_type[query_var]" tabindex="10">
                    <option value="0" <?php if (isset($wcp_query_var)) { if ($wcp_query_var == 0 && $wcp_query_var != '') { echo 'selected="selected"'; } } ?>>
                    <?php _e( 'False', 'wcp-plugin' ); ?>
                    </option>
                    <option value="1" <?php if (isset($wcp_query_var)) { if ($wcp_query_var == 1 || is_null($wcp_query_var)) { echo 'selected="selected"'; } } else { echo 'selected="selected"'; } ?>>
                    <?php _e( 'True', 'wcp-plugin' ); ?>
                    </option>
                  </select>
                  <?php _e( '(default: True)', 'wcp-plugin' ); ?></td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Menu Position', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'The position in the menu order the post type should appear. show_in_menu must be true.', 'wcp-plugin' ); ?>" class="help">?</a>
                  <p>
                    <?php _e( 'See <a href="http://codex.wordpress.org/Function_Reference/register_post_type#Parameters">Available options</a> in the "menu_position" section. Range of 5-100', 'wcp-plugin' ); ?>
                  </p>
                </th>
                <td><input type="text" name="wcp_custom_post_type[menu_position]" tabindex="11" size="5" value="<?php if (isset($wcp_menu_position)) { echo esc_attr($wcp_menu_position); } ?>" /></td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Show in Menu', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'Whether to show the post type in the admin menu and where to show that menu. Note that show_ui must be true', 'wcp-plugin' ); ?>" class="help">?</a>
                  <p>
                    <?php _e( '"Show UI" must be "true". If an existing top level page such as "tools.php" is indicated for second input, post type will be sub menu of that.', 'wcp-plugins' ); ?>
                  </p>
                </th>
                <td><p>
                    <select name="wcp_custom_post_type[show_in_menu]" tabindex="10">
                      <option value="0" <?php if (isset($wcp_show_in_menu)) { if ($wcp_show_in_menu == 0) { echo 'selected="selected"'; } } ?>>
                      <?php _e( 'False', 'wcp-plugin' ); ?>
                      </option>
                      <option value="1" <?php if (isset($wcp_show_in_menu)) { if ($wcp_show_in_menu == 1 || is_null($wcp_show_in_menu)) { echo 'selected="selected"'; } } else { echo 'selected="selected"'; } ?>>
                      <?php _e( 'True', 'wcp-plugin' ); ?>
                      </option>
                    </select>
                  </p>
                  <p>
                    <input type="text" name="wcp_custom_post_type[show_in_menu_string]" tabindex="12" size="20" value="<?php if (isset($wcp_show_in_menu_string)) { echo esc_attr($wcp_show_in_menu_string); } ?>" />
                  </p></td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Menu Icon', 'wcp-plugin') ?>
                  <a href="#" title="<?php esc_attr_e( 'URL to image to be used as menu icon.', 'wcp-plugin' ); ?>" class="help">?</a> </th>
                <td><input type="text" name="wcp_custom_post_type[menu_icon]" tabindex="11" size="20" value="<?php if (isset($wcp_menu_icon)) { echo esc_attr($wcp_menu_icon); } ?>" />
                  (Full URL for icon)</td>
              </tr>
              <tr valign="top">
                <th scope="row"><?php _e('Supports', 'wcp-plugin') ?></th>
                <td><input type="checkbox" name="wcp_supports[]" tabindex="11" value="title" <?php if (isset($wcp_supports) && is_array($wcp_supports)) { if (in_array('title', $wcp_supports)) { echo 'checked="checked"'; } } elseif (!isset($_GET['edittype'])) { echo 'checked="checked"'; } ?> />
                  &nbsp;
                  <?php _e( 'Title' , 'wcp-plugin' ); ?>
                  <a href="#" title="<?php esc_attr_e( 'Adds the title meta box when creating content for this custom post type', 'wcp-plugin' ); ?>" class="help">?</a> <br/ >
                  <input type="checkbox" name="wcp_supports[]" tabindex="12" value="editor" <?php if (isset($wcp_supports) && is_array($wcp_supports)) { if (in_array('editor', $wcp_supports)) { echo 'checked="checked"'; } } elseif (!isset($_GET['edittype'])) { echo 'checked="checked"'; } ?> />
                  &nbsp;
                  <?php _e( 'Editor' , 'wcp-plugin' ); ?>
                  <a href="#" title="<?php esc_attr_e( 'Adds the content editor meta box when creating content for this custom post type', 'wcp-plugin' ); ?>" class="help">?</a> <br/ >
                  <input type="checkbox" name="wcp_supports[]" tabindex="13" value="excerpt" <?php if (isset($wcp_supports) && is_array($wcp_supports)) { if (in_array('excerpt', $wcp_supports)) { echo 'checked="checked"'; } } elseif (!isset($_GET['edittype'])) { echo 'checked="checked"'; } ?> />
                  &nbsp;
                  <?php _e( 'Excerpt' , 'wcp-plugin' ); ?>
                  <a href="#" title="<?php esc_attr_e( 'Adds the excerpt meta box when creating content for this custom post type', 'wcp-plugin' ); ?>" class="help">?</a> <br/ >
                  <input type="checkbox" name="wcp_supports[]" tabindex="14" value="trackbacks" <?php if (isset($wcp_supports) && is_array($wcp_supports)) { if (in_array('trackbacks', $wcp_supports)) { echo 'checked="checked"'; } } elseif (!isset($_GET['edittype'])) { echo 'checked="checked"'; } ?> />
                  &nbsp;
                  <?php _e( 'Trackbacks' , 'wcp-plugin' ); ?>
                  <a href="#" title="<?php esc_attr_e( 'Adds the trackbacks meta box when creating content for this custom post type', 'wcp-plugin' ); ?>" class="help">?</a> <br/ >
                  <input type="checkbox" name="wcp_supports[]" tabindex="15" value="custom-fields" <?php if (isset($wcp_supports) && is_array($wcp_supports)) { if (in_array('custom-fields', $wcp_supports)) { echo 'checked="checked"'; } } elseif (!isset($_GET['edittype'])) { echo 'checked="checked"'; }  ?> />
                  &nbsp;
                  <?php _e( 'Custom Fields' , 'wcp-plugin' ); ?>
                  <a href="#" title="<?php esc_attr_e( 'Adds the custom fields meta box when creating content for this custom post type', 'wcp-plugin' ); ?>" class="help">?</a> <br/ >
                  <input type="checkbox" name="wcp_supports[]" tabindex="16" value="comments" <?php if (isset($wcp_supports) && is_array($wcp_supports)) { if (in_array('comments', $wcp_supports)) { echo 'checked="checked"'; } } elseif (!isset($_GET['edittype'])) { echo 'checked="checked"'; }  ?> />
                  &nbsp;
                  <?php _e( 'Comments' , 'wcp-plugin' ); ?>
                  <a href="#" title="<?php esc_attr_e( 'Adds the comments meta box when creating content for this custom post type', 'wcp-plugin' ); ?>" class="help">?</a> <br/ >
                  <input type="checkbox" name="wcp_supports[]" tabindex="17" value="revisions" <?php if (isset($wcp_supports) && is_array($wcp_supports)) { if (in_array('revisions', $wcp_supports)) { echo 'checked="checked"'; } } elseif (!isset($_GET['edittype'])) { echo 'checked="checked"'; }  ?> />
                  &nbsp;
                  <?php _e( 'Revisions' , 'wcp-plugin' ); ?>
                  <a href="#" title="<?php esc_attr_e( 'Adds the revisions meta box when creating content for this custom post type', 'wcp-plugin' ); ?>" class="help">?</a> <br/ >
                  <input type="checkbox" name="wcp_supports[]" tabindex="18" value="thumbnail" <?php if (isset($wcp_supports) && is_array($wcp_supports)) { if (in_array('thumbnail', $wcp_supports)) { echo 'checked="checked"'; } } elseif (!isset($_GET['edittype'])) { echo 'checked="checked"'; }  ?> />
                  &nbsp;
                  <?php _e( 'Featured Image' , 'wcp-plugin' ); ?>
                  <a href="#" title="<?php esc_attr_e( 'Adds the featured image meta box when creating content for this custom post type', 'wcp-plugin' ); ?>" class="help">?</a> <br/ >
                  <input type="checkbox" name="wcp_supports[]" tabindex="19" value="author" <?php if (isset($wcp_supports) && is_array($wcp_supports)) { if (in_array('author', $wcp_supports)) { echo 'checked="checked"'; } } elseif (!isset($_GET['edittype'])) { echo 'checked="checked"'; }  ?> />
                  &nbsp;
                  <?php _e( 'Author' , 'wcp-plugin' ); ?>
                  <a href="#" title="<?php esc_attr_e( 'Adds the author meta box when creating content for this custom post type', 'wcp-plugin' ); ?>" class="help">?</a> <br/ >
                  <input type="checkbox" name="wcp_supports[]" tabindex="20" value="page-attributes" <?php if (isset($wcp_supports) && is_array($wcp_supports)) { if (in_array('page-attributes', $wcp_supports)) { echo 'checked="checked"'; } } elseif (!isset($_GET['edittype'])) { echo 'checked="checked"'; }  ?> />
                  &nbsp;
                  <?php _e( 'Page Attributes' , 'wcp-plugin' ); ?>
                  <a href="#" title="<?php esc_attr_e( 'Adds the page attribute meta box when creating content for this custom post type', 'wcp-plugin' ); ?>" class="help">?</a> <br/ >
                  <input type="checkbox" name="wcp_supports[]" tabindex="21" value="post-formats" <?php if (isset($wcp_supports) && is_array($wcp_supports)) { if (in_array('post-formats', $wcp_supports)) { echo 'checked="checked"'; } } elseif (!isset($_GET['edittype'])) { echo 'checked="checked"'; }  ?> />
                  &nbsp;
                  <?php _e( 'Post Formats' , 'wcp-plugin' ); ?>
                  <a href="#" title="<?php esc_attr_e( 'Adds post format support', 'wcp-plugin' ); ?>" class="help">?</a> <br/ ></td>
              </tr>
              
            </table>
          </div>
          <p class="submit">
            <input type="submit" class="button-primary" tabindex="21" name="wcp_submit" value="<?php echo $wcp_submit_name; ?>" />
          </p>
        </form></td>
    </tr>
  </table>
</div>
<?php
//load footer
wcp_footer();
}

function wcp_footer() {
	?>
<hr />
<p class="cp_about"><a target="_blank" href="#">
  <?php _e( 'Custom Post', 'wcp-plugin' ); ?>
  </a>
 </p>
<?php
}

function wcp_check_return( $return ) {
	global $WCP_URL;

	if ( $return == 'wcp' ) {
		return ( isset( $_GET['return'] ) ) ? admin_url( 'admin.php?page=wcp_sub_manage_wcp&return=wcp' ) : admin_url( 'admin.php?page=wcp_sub_manage_wcp' );
	}elseif ( $return == 'add' ) {
		return admin_url( 'admin.php?page=wcp_sub_add_new' );
	} else {
		return admin_url( 'admin.php?page=wcp_sub_add_new' );
	}
}

function get_disp_boolean($booText) {
	if ( empty( $booText ) || $booText == '0') {
		return false;
	}

	return true;
}

function disp_boolean($booText) {
	if ( empty( $booText ) || $booText == '0' ) {
		return 'false';
	}

	return 'true';
}

add_shortcode( 'WCPPOST', 'my_custom_post_feature1' );
function my_custom_post_feature1($atts) {

extract(shortcode_atts(array(
      'post_type' => $postnames,
   ), $atts));
    

    $query = new WP_Query( array(
        'post_type' => $atts['postname'],
        'color' => 'blue',
        'posts_per_page' => -1,
        'order' => 'ASC',
        'orderby' => 'title',
    ) );
    if ( $query->have_posts() ) { ?>
        <ul class="postname-listing">
            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
            <li id="post-<?php the_ID(); ?>"  <?php post_class('postnameli') ; ?>>
              <div class="post-titles">
		      <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			 </div>
			 <div class="post-cont">
			<?php the_content('Read more...'); ?>.
			</div>
            </li>
            <?php endwhile;
            wp_reset_postdata(); ?>
        </ul>
    <?php $myvariable = ob_get_clean();
    return $myvariable;
    }
}

function wcp_help_style() { ?>
<style>
		.help:hover {
			font-weight: bold;
		}
		.required { color: rgb(255,0,0); }

	</style>
<?php
}
?>
<style>.postnameli {
    border: 1px solid #000;
    border-radius: 10px;
    list-style: outside none none;
    margin: 20px 0;
    padding: 10px 20px;
    text-align: left;
}
.post-titles {
    background: none repeat scroll 0 0 #73b2e4;
    padding: 10px 0 10px 20px;
}
.post-cont {
    padding: 10px 20px;
}
.post-titles > a {
    color: #fff;
    font-size: 18px;
}</style>
