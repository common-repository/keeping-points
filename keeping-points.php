<?php
/*
Plugin Name: Keeping Points 
Text Domain: keeping-points
Plugin URI: http://www.dlaugh.com/plugins/keeping-points/
Description: A great plugin to assist in keeping points for a church program or for employees in a rewards system.
Version: 1.2
Author: David Laughlin
Author URI: http://www.dlaugh.com
*/

/*  Copyright 2009 David Gwyer (email : d.v.gwyer@presscoders.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// ------------------------------------------------------------------------
// REQUIRE MINIMUM VERSION OF WORDPRESS:                                               
// ------------------------------------------------------------------------
// THIS IS USEFUL IF YOU REQUIRE A MINIMUM VERSION OF WORDPRESS TO RUN YOUR
// PLUGIN. IN THIS PLUGIN THE WP_EDITOR() FUNCTION REQUIRES WORDPRESS 3.3 
// OR ABOVE. ANYTHING LESS SHOWS A WARNING AND THE PLUGIN IS DEACTIVATED.                    
// ------------------------------------------------------------------------

function dl_keeping_points_requires_wordpress_version() {
	global $wp_version;
	$plugin = plugin_basename( __FILE__ );
	$plugin_data = get_plugin_data( __FILE__, false );

	if ( version_compare($wp_version, "4.4.2", "<" ) ) {
		if( is_plugin_active($plugin) ) {
			deactivate_plugins( $plugin );
			wp_die( "'".$plugin_data['Name']."' requires WordPress 3.3 or higher, and has been deactivated! Please upgrade WordPress and try again.<br /><br />Back to <a href='".admin_url()."'>WordPress admin</a>." );
		}
	}
}
add_action( 'admin_init', 'dl_keeping_points_requires_wordpress_version' );

// ------------------------------------------------------------------------
// PLUGIN PREFIX:                                                          
// ------------------------------------------------------------------------

// ------------------------------------------------------------------------
// REGISTER HOOKS & CALLBACK FUNCTIONS:
// ------------------------------------------------------------------------
// HOOKS TO SETUP DEFAULT PLUGIN OPTIONS, HANDLE CLEAN-UP OF OPTIONS WHEN
// PLUGIN IS DEACTIVATED AND DELETED, INITIALISE PLUGIN, ADD OPTIONS PAGE.
// ------------------------------------------------------------------------

// Set-up Action and Filter Hooks
register_activation_hook(__FILE__, 'dl_keeping_points_add_defaults');
add_action('admin_init', 'dl_keeping_points_init' );
add_action('admin_menu', 'dl_keeping_points_add_options_page');
add_filter( 'plugin_action_links', 'dl_keeping_points_plugin_action_links', 10, 2 );


// ------------------------------------------------------------------------------
// THIS FUNCTION RUNS WHEN THE PLUGIN IS ACTIVATED. IF THERE ARE NO THEME OPTIONS
// CURRENTLY SET, OR THE USER HAS SELECTED THE CHECKBOX TO RESET OPTIONS TO THEIR
// DEFAULTS THEN THE OPTIONS ARE SET/RESET.
//
// OTHERWISE, THE PLUGIN OPTIONS REMAIN UNCHANGED.
// ------------------------------------------------------------------------------

// Define default option settings
function dl_keeping_points_add_defaults() {
	$tmp = get_option('keeping_points_options');
    if(($tmp['chk_default_options_db']=='1')||(!is_array($tmp))) {
		delete_option('keeping_points_options'); // so we don't have to reset all the 'off' checkboxes too! (don't think this is needed but leave for now)
		$arr = array(	"chk_default_options_db" => ""
		);
		update_option('keeping_points_options', $arr);
	}
}



// ------------------------------------------------------------------------------
// THIS FUNCTION RUNS WHEN THE 'admin_init' HOOK FIRES, AND REGISTERS YOUR PLUGIN
// SETTING WITH THE WORDPRESS SETTINGS API. YOU WON'T BE ABLE TO USE THE SETTINGS
// API UNTIL YOU DO.
// ------------------------------------------------------------------------------

// Init plugin options to white list our options
function dl_keeping_points_init(){
	register_setting( 'keeping_points_plugin_options', 'keeping_points_options', 'dl_keeping_points_validate_options' );
}

// ------------------------------------------------------------------------------
// THIS FUNCTION RUNS WHEN THE 'admin_menu' HOOK FIRES, AND ADDS A NEW OPTIONS
// PAGE FOR YOUR PLUGIN TO THE SETTINGS MENU.
// ------------------------------------------------------------------------------

// Add menu page
function dl_keeping_points_add_options_page() {
		global $point_settings;
		$point_settings = add_options_page('Keeping Points Options Page', 'Keeping Points', 'manage_options', __FILE__, 'dl_keeping_points_render_form');
}

///LOAD AJAX TO MENU PAGE
function dl_keeping_points_load_scripts($hook) {
	global $point_settings;
	
	if(!$point_settings)
	return;
	wp_enqueue_script( 'points-ajax' , plugin_dir_url(__FILE__) . 'js/points-ajax.js', array('jquery'), '', true );
	
}
add_action('admin_enqueue_scripts', 'dl_keeping_points_load_scripts');

//TEST DISPLAY FOR AJAX
function dl_keeping_points_process_ajax() {
	$options = get_option('keeping_points_options');
	$name_num = $options['ppl_amount'];
	echo $name_num;
	exit();
}
add_action ( 'wp_ajax_points_action' , 'dl_keeping_points_process_ajax' );

//function to display point fields - ADDS BUTTONS TO ADMIN MENU DEPENDING ON 
//HOW MANY BUTTONS ARE NEEDED BY USER // ADD AJAX FUNCTIONALITY IN NEXT VERSION
function dl_keeping_points_loop_point_choice($number) {
	for($i=0;$i < $number; $i++) {
	$options = get_option('keeping_points_options');
	$point = "txt_" . $i;
	$point_num = $point . "_points";
	$name_build = "name_" . $i;
	?>
	  			<!-- Point Button Name -->
				<tr>
					<th scope="row"><?php _e( $options[$point] . ' Button', 'keeping-points' ); ?></th>
					<td>
						<input placeholder="Goal/Button Name" type="text" size="20" name="keeping_points_options[<?php echo $point ?>]" value="<?php _e($options[$point], 'keeping-points'); ?>" />
						<input placeholder="Points Amount" type="text" size="10" name="keeping_points_options[<?php echo $point_num?>]" value="<?php _e($options[$point_num], 'keeping-points'); ?>" />
					</td>
				</tr>
	<?php
	}?>
				<tr>
					<th scope="row"><?php _e('Subtract Button', 'keeping-points' ); ?></th>
					<td>
						<input placeholder="5" type="text" size="10" name="keeping_points_options[neg_btn]" value="<?php _e( $options["neg_btn"], 'keeping-points' ); ?>" /><span style="margin-left:20px;"><?php _e('Points to subtract from total points: (example) input 10 to subtract 10 from each persons total on each click', 'keeping-points' ); ?></span><br /><span style="margin-left: 10px";><?php _e('Use this to subtract points from each person.', 'keeping-points'); ?><span>
					</td>
				</tr>
	<?php

}

//FUNCTION TO DISPLAY TEXT BOXES TO ADD NAMES AND POINTS FOR PERSON IN POINT SYSTEM.
function dl_keeping_points_dlaugh_ppl_names() {
	
	$options = get_option('keeping_points_options');
	$name_num = $options['ppl_amount'];
	for($i=0;$i < $name_num; $i++) {

		$point = "txt_" . $i;
		$name_build = $point . "_name";
		$point_total = $point . "_point_total";
		$name_print = $options[$name_build];
		$changed_name = str_replace(' ', '_', $name_print);
		$point_build = "point_total_" . $changed_name;

		if ($options[$name_build] == "") {
			
			$options[$name_build] == null;
			$option[$point_total] = null;
			$options = array_filter( $options );
			update_option( 'keeping_points_options', $options );
		}
		
		
		if ($options[$name_build] == true) {
		
		?>
					<!-- Add Person Name -->
					<tr>
						<th scope="row">
						<?php if ($options[$name_build] == "" ) {
						    _e('Person ' . $i + 1, 'keeping-points'); ?>
							<?php } else
							 _e($options[$name_build], 'keeping-points');

							?>
							</th>
						<td>
							<input placeholder="Name" type="text" size="20" class="people" name="keeping_points_options[<?php echo $name_build ?>]" value="<?php _e( $options[$name_build], 'keeping-points'); ?>" />
							<input placeholder="Set Points - Not Required" type="text" size="10" name="keeping_points_options[<?php echo $point_build; ?>]" value="<?php _e( $options[$point_build], 'keeping-points'); ?>" />
						</td>
					</tr>
		<?php
		} 
	}
	
		for($i=0;$i < $name_num; $i++) {

		$point = "txt_" . $i;
		$name_build = $point . "_name";
		$point_total = $point . "_point_total";
		$name_print = $options[$name_build];
		$changed_name = str_replace(' ', '_', $name_print);
		$point_build = "point_total_" . $changed_name;
		
		if ($options[$name_build] == false && $options[$name_build] == null) {
		
		?>
					<!-- Add Person Name -->
					<tr>
						<th scope="row"><?php _e('Person' . ' ' . ($i + 1), 'keeping-points'); ?></th>
						<td>
							<input placeholder="Name" type="text" class="people" size="20" name="keeping_points_options[<?php echo $name_build ?>]" value="<?php _e( $options[$name_build], 'keeping-points'); ?>" />
							<input placeholder="Set Points - Not Required" type="hidden" name="keeping_points_options[<?php echo "point_total_" . $name_print ?>]" value="<?php _e( $options[$point_build], 'keeping-points'); ?>" />
						</td>
					</tr>
		<?php
		} 
		
	}
	

}


/* 
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
	<script>
	$(document).ready(function(){
		
			$(".ajax").click(function() {
				$.ajax({
					url: "../wp-content/plugins/plugin-options-starter-kit/scripts/textinput.php", 
					success: function(result){
						$(".ajax").after(result);
						}
			});
		});
	});
	</script>
	 */

// ------------------------------------------------------------------------------
// THIS FUNCTION IS SPECIFIED IN add_options_page() AS THE CALLBACK FUNCTION THAT
// ACTUALLY RENDER THE PLUGIN OPTIONS FORM AS A SUB-MENU UNDER THE EXISTING
// SETTINGS ADMIN MENU.
// ------------------------------------------------------------------------------
// Render the Plugin options form
function dl_keeping_points_render_form() {
	?>

	<div class="wrap">
		
		<!-- Display Plugin Icon, Header, and Description -->
		<div class="icon32" id="icon-options-general"><br></div>

		<div id="container">
	

		<h2><?php _e('Keeping Points - Options', 'keeping-points'); ?></h2>		
		<p class="ajax">Welcome to the Keeping Points plugin! I hope you enjoy. Be sure to check out my website at dlaugh.com and feel free to send me a message about how I might improve the plugin. Feel free to donate through my paypal account at the bottom if you are a thankful organization or person and have found this plugin useful. The basics of this plugin is to keep track of points for people. I used it to keep track of points for kids in church for their accomplishments. I've had people use this for schools or churches. Drop me a line and let me know what you think. Enjoy!</p>
		<p>
		</div>
		<!-- Beginning of the Plugin Options Form -->
		<form method="post" action="options.php">
			<?php settings_fields('keeping_points_plugin_options'); ?>
			<?php $options = get_option('keeping_points_options'); ?>

			<!-- Table Structure Containing Form Controls -->
			<!-- Each Plugin Option Defined on a New Table Row -->
			<table class="form-table">
			<!-- input to get amount of buttons -->
			<tr>
				<th scope="row"><?php _e ('How many point buttons do you need?', 'keeping-points' ); ?></th>
				<td>
					<input placeholder="5" type="text" size="15" name="keeping_points_options[button_amount]" value="<?php _e( $options['button_amount'], 'keeping-points' ); ?>" />
				</td>
			</tr>
			<!-- Select Drop-Down for button color -->
			<tr>
				<th scope="row">Select button color</th>
				<td>
					<select name='keeping_points_options[button_color]'>
						<option value='primary' <?php selected('primary', $options['button_color']); ?>><?php _e( 'primary', 'keeping-points' ); ?></option>
						<option value='success' <?php selected('success', $options['button_color']); ?>><?php _e( 'success', 'keeping-points' ); ?></option>
						<option value='info' <?php selected('info', $options['button_color']); ?>><?php _e( 'info', 'keeping-points' ); ?></option>
						<option value='warning' <?php selected('warning', $options['button_color']); ?>><?php _e( 'warning', 'keeping-points' ); ?></option>
						<option value='danger' <?php selected('danger', $options['button_color']); ?>><?php _e( 'danger', 'keeping-points' ); ?></option>
					</select>
					<span style="color:#666666;margin-left:2px;"><img style="margin-left:55px;" src="<?php echo plugins_url(); ?>/keeping-points/images/btn-colors.png" alt="image to show button colors" /></span>
				</td>
			</tr>
			
			<?php 
			$button_amount = $options["button_amount"];
			dl_keeping_points_loop_point_choice($button_amount); ?>
			
			<!-- input to get amount of people -->
			<tr>
				<th scope="row"><?php _e('How many people do you want to add?', 'keeping-points'); ?></th>
				<td>
					<input placeholder="5" type="text" id="ppl_amount" size="15" name="keeping_points_options[ppl_amount]" value="<?php _e($options['ppl_amount'], 'keeping-points' ); ?>" />
				</td>
			</tr>
			
			<?php dl_keeping_points_dlaugh_ppl_names(); ?>
			<!-- text to copy and put on page for button display -->
			<tr>
				<th scope="row"><?php _e('To display your buttons please copy this shortcode and place on any page.', 'keeping-points'); ?></th>
				<td>
					<input type="text" size="15" value="<?php _e('[my-keeping-points]', 'keeping-points' ); ?>" readonly />
				</td>
			</tr>
				<tr><td colspan="2"><div style="margin-top:10px;"></div></td></tr>
				<tr valign="top" style="border-top:#dddddd 1px solid;">
					<th scope="row">Database Options</th>
					<td>
						<label><input name="keeping_points_options[chk_default_options_db]" type="checkbox" value="1" <?php if (isset($options['chk_default_options_db'])) { checked('1', $options['chk_default_options_db']); } ?> /> Restore defaults upon plugin deactivation/reactivation</label>
						<br /><span style="color:#666666;margin-left:2px;">Only check this if you want to reset plugin settings upon Plugin reactivation</span>
					</td>
				</tr>
			</table>
			<p class="submit">
			<input id="ajaxsub" type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
		</form>

		<p style="margin-top:15px;">
		<span><a href="https://www.facebook.com/laughlin.david" title="Our Facebook page" target="_blank"><img style="border:1px #ccc solid;" src="<?php echo plugins_url(); ?>/keeping-points/images/facebook-icon.png" /></a></span>
			<p style="font-style: italic;font-weight: bold;color: #26779a;">If you have found this plugin at all useful, please consider making a donation below. Thanks!</p>
		</p>
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
		<input type="hidden" name="cmd" value="_s-xclick">
		<input type="hidden" name="hosted_button_id" value="HKLXF9M3F8B38">
		<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
		<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
		</form>
	</div>
	<?php	
}

// Sanitize and validate input. Accepts an array, return a sanitized array.
function dl_keeping_points_validate_options($input) {
	 // strip html from textboxes
	$options = get_option('keeping_points_options');
	$people_check = $options['ppl_amount'];
	for ($i=0; $i<$people_check;$i++) {
		$point = 'txt_' . $i;
		$name = $point . '_name';
			$input[$point] = wp_filter_nohtml_kses($input[$point]);
			$input[$name] = wp_filter_nohtml_kses($input[$name]);
	}
	return $input;
}

// Display a Settings link on the main Plugins page
function dl_keeping_points_plugin_action_links( $links, $file ) {

	if ( $file == plugin_basename( __FILE__ ) ) {
		$posk_links = '<a href="'.get_admin_url().'options-general.php?page=keeping-points/keeping-points.php">'.__('Settings').'</a>';
		// make the 'Settings' link appear first
		array_unshift( $links, $posk_links );
	}

	return $links;
}


/* <?php $my_greet = "What is up?"; ?>
<script type="text/javascript">  document.write("hi y'all..."); var greet = "<?php echo $my_greet; ?>"; document.write(greet);  </script>
 */

 ///AJAX FOR THE FRONT-END OF THE PLUGIN DISPLAY. THIS IS WHERE USER PASTES SHORTCODE TO THEIR PAGE.
function shortpoints_process_ajax() {
	$options = get_option('keeping_points_options');
	$btn_color = $options['button_color'];
	$btn_color_build = 'btn btn-' . $btn_color . ' ';
	$btnId = $_POST['name']; 
	$btnName = $_POST['btnName'];
	$btnClass = $_POST['btnClass'];
	$changed_class = str_replace( $btn_color_build, '', $btnClass);
	$changed_name = str_replace('_', ' ', $btnName);
	$num_start = $changed_class . "_points";
	$points = "point_total_" . $btnName;
	if ($btnId == $btnName . '_neg') {
	$btn_points = $options[$points] - $options['neg_btn'];
	} else {
	$btn_points = $options[$points] + $options[$num_start];
	}
	

	echo $changed_name . "'s " . "Total Points: " . $btn_points;
	
			$options[$points] = $btn_points;
			$options = array_filter( $options );
			update_option( 'keeping_points_options', $options );
	
	die();
}

add_action ( 'wp_ajax_shortpoints-ajax' , 'shortpoints_process_ajax' );
add_action ( 'wp_ajax_nopriv_shortpoints-ajax' , 'shortpoints_process_ajax' );


//THE FRONT-END OF THE PLUGIN DISPLAY. THIS IS WHERE USER PASTES SHORTCODE TO THEIR PAGE.
function dlaugh_keeping_points_plugin() 
{

	if ( is_user_logged_in() ) {
	wp_enqueue_script( 'shortpoints-ajax' , plugin_dir_url(__FILE__) . 'js/shortpoints-ajax.js', array('jquery'), '', true );

	wp_localize_script( 'shortpoints-ajax', 'postshortpoints', array(

			'ajax_url' => admin_url( 'admin-ajax.php' )

	));
	
	//add bootstrap last
	wp_enqueue_style( 'bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css' );	
   $output =  '<div class="container"><div class="row">';
	$options = get_option('keeping_points_options');
	$btn_color = $options['button_color'];
	$btn_color_build = 'btn btn-' . $btn_color . ' ';
	$number = $options['button_amount'];
	$ppl_num = $options['ppl_amount'];
	for($j=0;$j < $ppl_num; $j++) {
	$point = "txt_" . $j;
	$name_id = "txt_" . $j . '_name';
	if ($options[$name_id]) {
	$output .= '<section>';
	for($i=0;$i < $number; $i++) {
		$point = "txt_" . $i;
		$for_margin = $options[$point];
		$name_id = "txt_" . $j . '_name';
		$name_call = $options[$name_id];
		$changed_id = str_replace(' ', '_', $name_call);
		if ($options[$point] !== "") {
		$output.= '<div style="display:inline-block; margin:5px;">';
		$output.= '<button name="' . $changed_id . '"';
		$output.= 'type="button" class="' . $btn_color_build . $point . '" id = "' . ($for_margin . $changed_id) . '">';
		$output.= $options[$point] . '</button></div>';
		}
	}
	
	$name_id = "txt_" . $j . "_name";
	$name_call = $options[$name_id];
	$changed_id = str_replace(' ', '_', $name_call);
	$point_total = "point_total_" . $changed_id;
	if (!$options[$point_total]) {
		$options[$point_total] = 0;
	}
	$output.= '<div style="display:inline-block; margin:5px; width:50px;">';
	$output.= '<button name="' . $changed_id . '" type="button" class="' . $btn_color_build . $point . '"';
	$output.= 'id = "' . ($changed_id . "_neg") . '">-</button></div>';
	$output.= '<br /><div id ="' . ($changed_id . "result") . '">';
	$output.= $options[$name_id] . "'s Total Points: " . $options[$point_total] . '</div><br /> </section>';
		}
	}
	$output.= '</div></div>';

	return $output;
	
	} else {
	echo('<h3 style="color:red;">You must be a logged in user to enable point system</h3>');
	
		//add bootstrap last
	wp_enqueue_style( 'bootstrap', 'https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css' );	
   $output =  '<div class="container"><div class="row">';
	$options = get_option('keeping_points_options');
	$btn_color = $options['button_color'];
	$btn_color_build = 'btn btn-' . $btn_color . ' ';
	$number = $options['button_amount'];
	$ppl_num = $options['ppl_amount'];
	for($j=0;$j < $ppl_num; $j++) {
	$point = "txt_" . $j;
	$name_id = "txt_" . $j . '_name';
	if ($options[$name_id]) {
	$output .= '<section>';
	for($i=0;$i < $number; $i++) {
		$point = "txt_" . $i;
		$for_margin = $options[$point];
		$name_id = "txt_" . $j . '_name';
		$name_call = $options[$name_id];
		$changed_id = str_replace(' ', '_', $name_call);
		if ($options[$point] !== "") {
		$output.= '<div style="display:inline-block; margin:5px;">';
		$output.= '<button name="' . $changed_id . '"';
		$output.= 'type="button" class="' . $btn_color_build . $point . '" id = "' . ($for_margin . $changed_id) . '">';
		$output.= $options[$point] . '</button></div>';
		}
	}
	
	$name_id = "txt_" . $j . "_name";
	$name_call = $options[$name_id];
	$changed_id = str_replace(' ', '_', $name_call);
	$point_total = "point_total_" . $changed_id;
	if (!$options[$point_total]) {
		$options[$point_total] = 0;
	}
	$output.= '<div style="display:inline-block; margin:5px; width:50px;">';
	$output.= '<button name="' . $changed_id . '" type="button" class="' . $btn_color_build . $point . '"';
	$output.= 'id = "' . ($changed_id . "_neg") . '">-</button></div>';
	$output.= '<br /><div id ="' . ($changed_id . "result") . '">';
	$output.= $options[$name_id] . "'s Total Points: " . $options[$point_total] . '</div><br /> </section>';
		}
	}
	$output.= '</div></div>';

	return $output;
	}
}

add_shortcode('my-keeping-points','dlaugh_keeping_points_plugin');
?>