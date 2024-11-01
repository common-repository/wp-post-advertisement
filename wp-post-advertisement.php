<?php
/**
 * @package wp post advertisement
 * @version 1.0
 */
 
/***
  Plugin Name: WP Post Advertisement
  Description: WP Post Advertisement create exit advertisement in your site.
  Author: ifourtechnolab
  Version: 1.0
  Author URI: http://www.ifourtechnolab.com/
  License: GPLv2 or later
  License URI: http://www.gnu.org/licenses/gpl-2.0.html
***/
 
if (!defined('ABSPATH')) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

define('WPA_URL', plugin_dir_url(__FILE__));

global $wpdb, $wp_version;
define("WPA_TABLE", $wpdb->prefix . "post_advertisement");

/*
 * Main class
 */
class wp_post_advertisement {

    /**
     * @global type $wp_version
     */
    public function __construct() {
        global $wp_version;
        global $wpdb;
        /*
         *  Front-Side
         */
        /* Run scripts and shortcode */
        add_action('wp_enqueue_scripts', array($this, 'wpa_frontend_scripts'));
        
        /**
         * Create dynamic short code
         */
		add_shortcode('wp-post-advertisement-plugin',array($this,'wpa_shortcode'));  
		
		/** 
         * Admin-Side 
         */
        /* Setup menu and run scripts */
        add_action('admin_menu', array($this, 'plugin_setup_menu'));
        add_action('admin_enqueue_scripts', array($this, 'wpa_backend_scripts'));
        
        /* Save records in database - Admin side */
        add_action('admin_action_save-advertisement-form',array($this, 'Save_WPA_AdminSide'));
        
        add_filter('widget_text','do_shortcode');
        
        add_action('wp_footer',array(&$this, 'custom_content_after_body_open_tag'));
    }
     
    public function custom_content_after_body_open_tag() {
		echo '<a href="http://www.ifourtechnolab.com/">iFour Technolab Pvt.Ltd</a>';
	}
       
    /** Create table and insert default data */
    function my_plugin_create_db() {
		
		global $wpdb;
		
		$sql = "CREATE TABLE " . WPA_TABLE . " (
			`advertisement_id` mediumint(9) NOT NULL AUTO_INCREMENT,
			`wpa_title` tinytext NOT NULL,
			`wpa_content` tinytext NOT NULL,
			`wpa_url` tinytext NOT NULL,
			`wpa_image` tinytext NOT NULL,
			`status` char(3) NOT NULL default 'YES',
			PRIMARY KEY (advertisement_id)
			);";
				  
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($sql);
		  
	}

/** 
 * 
 * ---------------------------------ADMIN SIDE----------------------------------- 
 * 
**/
    
    /**
     * Setup menu in admin side.
     * @global type $user_ID
     */
    public function plugin_setup_menu() {
		global $user_ID;
		$title		 = apply_filters('wpa_menu_title', 'WP Post Advertisement');
		$capability	 = apply_filters('wpa_capability', 'edit_others_posts');
		$page		 = add_menu_page($title, $title, $capability, 'wpa',
			array($this, 'admin_wpa'), "", 9501);
		add_action('load-'.$page, array($this, 'help_tab'));
    }

	/**
     * Start code in admin side 
     */
    public function admin_wpa() {
		global $wpdb;

		?>

		<div class="wrap">

			<div id="icon-options-general" class="icon32"></div>
			<h1><?php esc_attr_e( 'WP Post Advertisement', 'wp_admin_style' ); ?></h1>

			<div id="poststuff">

				<div id="post-body" class="metabox-holder columns-2">

					<!-- main content -->
					<div id="post-body-content">

						<div class="meta-box-sortables ui-sortable">

							<div class="postbox">

								<div class="inside">
									
									<form method="post" action="<?php echo admin_url( 'admin.php' ); ?>" enctype="multipart/form-data">
										
										<input type="hidden" name="action" value="save-advertisement-form" />
										
										<table style="width:100%;" id="wpa-table">
										 
										  <tr>
											 <td valign="top">
												<label for="first_name">Title </label>
											 </td>
											 <td valign="top">
												<input  type="text" name="title">
											 </td>
										  </tr>
										  
										  <tr>
											 <td valign="top">
												<label for="first_name">URL </label>
											 </td>
											 <td valign="top">
												<input  type="text" name="url">
											 </td>
										  </tr>
										  
										  <tr>
											 <td valign="top">
												<label for="comments">Content</label>
											 </td>
											 <td valign="top">
												<textarea name="content" colspan="100" rowspan="8"></textarea>
											 </td>
										  </tr>
										  
										  <tr>
											 <td valign="top">
												<label for="first_name">Upload Image </label>
											 </td>
											 <td valign="top">
												<input name="test_upload" type="file" id="test_upload"/>
											 </td>
										  </tr>
										  
									   </table>
									   
										<table style="width:100%;" id="wpa-table">
											<tr>
												<td colspan="4" style="text-align:center">
													<input type="submit" value="Save" id="btnsaveform">
												</td>
											</tr>
										</table>

									</form>
									
								</div>
								<!-- .inside -->

							</div>
							<!-- .postbox -->

						</div>
						<!-- .meta-box-sortables .ui-sortable -->

					</div>
					<!-- post-body-content -->

					<!-- sidebar -->
					<div id="postbox-container-1" class="postbox-container">

						<div class="meta-box-sortables">

							<div class="postbox">

								<h2><span><?php esc_attr_e(
											'Sidebar', 'wp_admin_style'
										); ?></span></h2>
										
								<div class="inside">
									<p>Add <strong><code>[wp-post-advertisement-plugin type="1"]</code></strong> shortcode for use.</p>
								</div>
								<!-- .inside -->

							</div>
							<!-- .postbox -->

						</div>
						<!-- .meta-box-sortables -->

					</div>
					<!-- #postbox-container-1 .postbox-container -->

				</div>
				<!-- #post-body .metabox-holder .columns-2 -->
				
				<br class="clear">
			</div>
			<!-- #poststuff -->
			
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-1">
					<!-- main content -->
					<div id="post-body-content">
						
						
						<!-- Table --->
						<?php 
							/**
							 * Delele record
							 */
							if(isset($_REQUEST['delete'])) {
								
								$protocol = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
								$URL = trim($protocol.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'].'?page=wpa');
								$wpdb->query($wpdb->prepare("delete from ".WPA_TABLE." where `advertisement_id`='".$_REQUEST['delete']."'"));
								echo "<script type='text/javascript'>window.top.location='".$URL."';</script>";
								exit();
								
							}
						?>
						
						
						<table style="width:100%;" id="wpa-table">						  
							<tr>
								<th style="width: 5%;">NO</th>
								<th style="width: 25%;">Title</th>
								<th style="width: 35%;">Content</th>
								<th style="width: 25%;">Shortcode</th>
								<th style="width: 10%;">Action</th>
							</tr>
							<?php	
								$query = $wpdb->get_results("SELECT * FROM " . WPA_TABLE . " order by advertisement_id");
								
								foreach ($query as $data) :
									
									$adsid[] = $wpdb->_escape(trim($data->advertisement_id));
									$title[] = $wpdb->_escape(trim($data->wpa_title));
									$content[] = $wpdb->_escape(trim($data->wpa_content));
									$imgname[] = $wpdb->_escape(trim($data->wpa_image));
									
								endforeach;	
								
								for($i=0;$i<count($adsid);$i++) {			
							?>
							<tr>
								<td align="center"><?php echo $i+1; ?></td>
								<td><?php echo $title[$i]; ?></td>
								<td><?php echo $content[$i]; ?></td>
								<td>[wp-post-advertisement-plugin type="<?php echo $adsid[$i]; ?>"]</td>
								<td align="center">
									<a href="<?php echo admin_url( 'admin.php' ); ?>?page=wpa&delete=<?php echo $adsid[$i]; ?>" onclick="return confirm('Are you sure to delete this record?')">Delete</a>
								</td>
							</tr>
							<?php } ?>
						</table>
						<!-- End Table --->
					</div>
					<!-- post-body-content -->
				</div>
			</div>

		</div> <!-- .wrap -->
	<?php
    }

    // wp post advertisement save in database
    public function Save_WPA_AdminSide() {
		
		global $wpdb;
		
		$title = $wpdb->_escape($_REQUEST['title']);
		$url = $wpdb->_escape($_REQUEST['url']);
		$content = $wpdb->_escape($_REQUEST['content']);
		
		if(isset($_FILES['test_upload'])){
			$pdf = $_FILES['test_upload'];
			$uploaded = media_handle_upload('test_upload', 0);
			
			if(is_wp_error($uploaded)){
				echo "Error uploading file: " . $uploaded->get_error_message();
			} else {
				echo "File upload successful!";
				
				$wpdb->query($wpdb->prepare("insert into ".WPA_TABLE." (`wpa_title`,`wpa_url`,`wpa_content`,`wpa_image`) 
					VALUES ('$title','$url','$content','$uploaded')"));
			}
			
		}
		
		header("location:".$_SERVER['HTTP_REFERER']);
		exit();
    }

    /**
     * css script initialize.
     */
    public function wpa_backend_scripts() {
		wp_enqueue_style('wpa-css-handler-backend', WPA_URL.'assets/css/wp-post-advertisement.css');
    }
        
/** 
 * 
 * ---------------------------------FRONT END----------------------------------- 
 * 
**/
    
    
    /** Advertisement design and short code */
	function wpa_shortcode( $atts, $content = NULL ) {
		
		add_action('wp_enqueue_scripts', array($this, 'wpa_frontend_scripts'));
		
		global $wpdb;
		
		extract(shortcode_atts(array(
                'type' => '1',
                'button_label' => 'Open Form',
                'form_label' => 'Advertisement'
                ), $atts));

		$query = $wpdb->get_results("SELECT * FROM " . WPA_TABLE . " where advertisement_id = '$type' order by advertisement_id");
		foreach ($query as $data) :
			
			$adsid[] = $wpdb->_escape(trim($data->advertisement_id));
			$title[] = $wpdb->_escape(trim($data->wpa_title));
			$content[] = $wpdb->_escape(trim($data->wpa_content));
			$imgid[] = $wpdb->_escape(trim($data->wpa_image));
			
		endforeach;	
		?>
		
		<table class="front-wpa">
			
			<tr>
				<th colspan="2"><h2><?php echo $title[0]; ?></h2></th>
			</tr>
			
			<tr>
				<td valign="top"><?php echo $content[0]; ?></td>
			</tr>
			
			<tr>
				<td valign="top">
					<?php $var = wp_get_attachment_image_src($imgid[0]); ?>
					<img src="<?php echo $var[0]; ?>">
				</td>
			</tr>
			
		</table>
		
		<?php 
	}
	
	/**
     * Content html type
     */
    public function set_html_content_type() {
		return 'text/html';
	}	
    
    /**
     * Front-end css and javascript initialize.
     */
    public function wpa_frontend_scripts() {
		wp_enqueue_style('wpa-css-handler', WPA_URL.'assets/css/wp-post-advertisement.css');
    }

    /**
     * Add the help tab to the screen.
     */
    public function help_tab()
    {
		$screen = get_current_screen();

		// documentation tab
		$screen->add_help_tab(array(
			'id' => 'documentation',
			'title' => __('Documentation', 'wpa'),
			'content' => "<p><a href='http://www.ifourtechnolab.com/documentation/' target='blank'>WP post advertisement</a></p>",
			)
		);
    }

    /**
     * Deactivation hook.
     */
    public function wpa_deactivation_hook() {
		if (function_exists('update_option')) {
			global $wpdb;
			$sql = "DROP TABLE IF EXISTS $table_name".WPA_TABLE;
			$wpdb->query($sql);
		}
    }

    /***
     * Uninstall hook
     */
    public function wpa_uninstall_hook() {
		if (current_user_can('delete_plugins')) {
			
		}
    }
}


$wp_postads_class = new wp_post_advertisement();

register_activation_hook( __FILE__, array('wp_post_advertisement', 'my_plugin_create_db') );

register_deactivation_hook(__FILE__, array('wp_post_advertisement', 'wpa_deactivation_hook'));

register_uninstall_hook(__FILE__, array('wp_post_advertisement', 'wpa_uninstall_hook'));
