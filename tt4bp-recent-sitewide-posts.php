<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/*
Plugin Name: TT4BP Recent SiteWide Posts Widget
Plugin URI: http://themestown.com/groups/tt4bp-recent-sitewide-posts-widget/
Description: Basically just creates a new Recent Sitewide Posts for BuddyPress that allows you to exclude certain blogs from having their excerpts show up in the list.
Version: 1.0
Author: A Lewis
Author URI: http://www.themestown.com
*/
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/*  Copyright 2010  A. Lewis  (email : themestown@gmail.com)

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
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

if ( !defined('WP_CONTENT_DIR') )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' ); // no trailing slash, full paths only - WP_CONTENT_URL is defined further down

if ( !defined('WP_CONTENT_URL') )
	define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content'); // no trailing slash, full paths only - WP_CONTENT_URL is defined further down

$wpcontenturl=WP_CONTENT_URL;
$wpcontentdir=WP_CONTENT_DIR;



$ttrspbp_plugin_path = WP_CONTENT_DIR.'/plugins/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
$ttrspbp_plugin_url = WP_CONTENT_URL.'/plugins/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));

$ttrspbpdb_version = "1.0";




////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// Add actions and filters etc
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	add_action('init', 'ttrspbpinstall');
	add_action("plugins_loaded", "init_ttrswpsbarwidget");


function ttrspbpinstall()
{

	global $wpdb,$ttrspbpdb_version;
	
	$installed_ver = get_option( "ttrspbpdb_version" );


    	if(!isset($installed_ver) || empty($installed_ver)){add_option("ttrspbpdb_version", $ttrspbpdb_version);}
    	else{update_option("ttrspbpdb_version", $ttrspbpdb_version);}
}


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// START FUNCTION: The sidebar widget to show recent sitewide posts
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
### Function: Init TT4BP Recent Sitewide Posts
function init_ttrswpsbarwidget() {
	if (!function_exists('register_sidebar_widget')) {
		return;
	}

	### Function: TT Recent Sitewide Posts Buddypress
	function widget_ttrspbpposts($args) {
		$output = '';
		extract($args);
		$limit=$args[0];
		$title=$args[1];
		$exclude=$args[2];

		$options = get_option('widget_ttrspbpposts');
		if(!isset($limit))
		{
			$limit = htmlspecialchars(stripslashes($options['hlimit']));
		}
		if(!isset($title))
		{
			$title = htmlspecialchars(stripslashes($options['title']));
		}
		if(!isset($exclude))
		{
			$exclude = htmlspecialchars(stripslashes($options['exclude']));
		}
		global $wpdb;

	//Get the blog ids
	$allblogids=$wpdb->get_col("SELECT blog_id FROM $wpdb->blogs WHERE
	public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0'");	
		
	// Create an array from the blog IDs to exclude
	$exclblogids=explode(",",$exclude);
	$exclublogidsarr=array();

	for ($i=0;isset($exclblogids[$i]);++$i) {
		$exclublogidsarr[]=$exclblogids[$i];
	}
	
	foreach($allblogids as $blogid)
	{
		if(!in_array($blogid,$exclublogidsarr))
		{
			$includetheseblogids[]=$blogid;
		}
	}
	
	$primaryblogids='';
	
	$excludeblogid_lastid=end($includetheseblogids);
							
	foreach($includetheseblogids as $blogidtoinclude)
	{
		$primaryblogids.="$blogidtoinclude";
		if($excludeblogid_lastid != $blogidtoinclude )
		{
			$primaryblogids.=",";
		}								
	}	

	if ( bp_has_activities('object=blogs&action=new_blog_post&primary_id='.$primaryblogids.'&max='.$limit) ) : ?>


	<h2><?php echo $title;?></h2>
	<div id="activity-stream" class="activity-list item-list">

	<?php while ( bp_activities() ) : bp_the_activity(); ?>


			<div class="activity-content">

				<div class="activity-header">
					<?php bp_activity_action() ?>
				</div>

				<?php if ( bp_get_activity_content_body() ) : ?>
					<div class="activity-inner">
						<?php bp_activity_content_body() ?>
					</div>
				<?php endif; ?>

				<?php do_action( 'bp_activity_entry_content' ) ?>

			</div>

	<?php endwhile; ?>

	</div>

<?php else : ?>
	<div id="message" class="info">
		<p><?php _e( 'Sorry, there was no activity found. Please try a different filter.', 'buddypress' ) ?></p>
	</div>
<?php endif; 

	}

	### Function: TT4BP Recent Sitewide Posts
	function widget_ttrspbpposts_options() {
		$output = '';
		$options = get_option('widget_ttrspbpposts');
		if (!is_array($options)) {
			$options = array('hlimit' => '5', 'title' => __('Recent Sitewide Posts', 'ttrswp'), 'exclude' => '');
		}
		if ($_POST['ttrspbpposts-submit']) {
			$options['hlimit'] = intval($_POST['ttrswpwid-limit']);
			$options['title'] = strip_tags($_POST['ttrswpwid-title']);
			$options['exclude'] = $_POST['ttrswpwid-exclude'];

			update_option('widget_ttrspbpposts', $options);
		}
		$output .= '<p><label for="ttrswpwid-title">'.__('Widget Title', 'ttrswp').':</label>&nbsp;&nbsp;&nbsp;<input type="text" id="ttrswpwid-title" size="35" name="ttrswpwid-title" value="'.htmlspecialchars(stripslashes($options['title'])).'" />';
		$output .= '<p><label for="ttrswpwid-limit">'.__('Number of Items to Show', 'ttrswp').':</label>&nbsp;&nbsp;&nbsp;<input type="text" size="5" id="ttrswpwid-limit" name="ttrswpwid-limit" value="'.htmlspecialchars(stripslashes($options['hlimit'])).'" />';
		$output .= '<p><label for="ttrswpwid-exclude">'.__('Blog IDs to exclude', 'ttrswp').':</label>&nbsp;&nbsp;&nbsp;<input type="text" id="ttrswpwid-exclude" name="ttrswpwid-exclude" value="'. $options['exclude'].'" />';
		$output .= '<input type="hidden" id="ttrspbpposts-submit" name="ttrspbpposts-submit" value="1" />'."\n";
		//Echo ok here:
		echo $output;
	}
	// Register Widgets
	register_sidebar_widget('TT4BP Recent Sitewide Posts', 'widget_ttrspbpposts');
	register_widget_control('TT4BP Recent Sitewide Posts', 'widget_ttrspbpposts_options', 350, 120);

}

