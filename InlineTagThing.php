<?php 
/*
Plugin Name: Inline Tag Thing
Plugin URI: http://www.neato.co.nz/wordpress-things/inline-tag-thing
Description: A thing for editing tags inline, using AJAX magic.
Version: 1.2
Author: Christine From The Internet
Author URI: http://www.neato.co.nz
*/

// If you change this to false, the tag thing will no longer be automatically included at the end of posts.  You'll need
// to use the ITT_ShowWidget() template tag, inside the loop, to get it to show.
$automagicEmbed = true;

// If you change this to true, any existing tags will be included as a dropdown list along after the text box.
$showExistingTags = false;

// windows slashes go the other way.
strstr( PHP_OS, "WIN") ? $slash = "\\" : $slash = "/";
$pluginDirectory = $slash . 'wp-content' . $slash . 'plugins';


// AJAX Processing
if ($_POST['action'] && ($_POST['action'] == 'ITT_ProcessTag' || $_POST['action'] == 'ITT_ProcessRemoveTag') ) {
	if (substr(dirname(__FILE__), strlen($pluginDirectory) * -1) == $pluginDirectory) {
		require('../../wp-blog-header.php');
	} else {
		// blog header is a level lower if the plugin is in a subdirectory.
		require('../../../wp-blog-header.php');
	}
	
	if (!current_user_can( 'edit_post', $postid )) die("alert('You do not have the correct permissions to manipulate tags')");

	$error = false;
	$tag = $_POST['tag'];
	$postid = $_POST['postid'];
	
	$taxonomy = 'post_tag';
	
	// Check values, then process.
	if (!$postid || $postid == '') {
		$error .= 'No post id was provided\n';
	} else if (!$tag || $tag == '') {
		$error .= 'No tag was provided\n';
	} else {
		if ($_POST['action'] == 'ITT_ProcessTag') {
			$tags = explode(',', $tag);
			
			foreach ($tags as $solotag) {
				$solotag = trim($solotag);
				$check = is_term($solotag, $taxonomy);
				if (is_null($check)) {
					$args =array();
					$tag = wp_insert_term($solotag,$taxonomy);
					$tagid = $tag['term_id'];
				} else {
					$tagid = $check['term_id'];
				}
				
				wp_set_object_terms($postid, ($tagid*1) ,$taxonomy,true);
			}
		} else if ($_POST['action'] == 'ITT_ProcessRemoveTag') {
			$terms = wp_get_object_terms($postid, $taxonomy);
			$keep = array();
			foreach ($terms as $term) {
				if ($term->term_id != $tag) {
					array_push($keep, $term->slug);
				}
			}

			wp_set_object_terms($postid, $keep, $taxonomy, false);
		}
	}

	if( $error ) {
	   die( "alert('$error')" );
	} 
	
	// Compose JavaScript for return
	die( 'document.getElementById("soloAddTag-' . $postid . '").value=""; document.getElementById("assignedTags-' . $postid . '").innerHTML = "' . (ITT_GetSimpleTagList($postid)) . '"');
}

function ITT_GetSimpleTagList($postid) {
	$terms = wp_get_object_terms($postid, 'post_tag');
	$termstr = '';
	$first = true;
	foreach ($terms as $term) {
		if (!$first) $termstr .= ', ';
		$termstr .= $term->name ."[<a href='#' onClick='javascript:Things_RemoveTagFromPost(" . $term->term_id . "," . $postid . ")'>-</a>]";
		
		$first = false;
	}
	return $termstr;
}

function ITT_ShowJavascript() {
	global $pluginDirectory;
	wp_print_scripts( array( 'sack' ));

	  // Define custom JavaScript functions
	?>
	<script type="text/javascript">
	function Things_AddTagToPost(tag, postid)
	{
		var foo = "$pluginDirectory";
		var mysack = new sack("<?php bloginfo( 'wpurl' ); ?><?php echo str_replace($slash, '/', substr(__FILE__, strpos(__FILE__, $pluginDirectory))) ?>" );    
		if (tag != "" && postid != "") {
			mysack.execute = 1;
			mysack.method = 'POST';
			mysack.setVar( "action", "ITT_ProcessTag" );
			mysack.setVar( "tag", tag);
			mysack.setVar( "postid", postid);			
			mysack.encVar( "cookie", document.cookie, false );
			mysack.onError = function() { alert('AJAX error saving tag' )};
			mysack.runAJAX();
		} 
		return true;
	}
	

	function Things_RemoveTagFromPost(tag, postid)
	{
		var mysack = new sack("<?php bloginfo( 'wpurl' ); ?><?php echo str_replace($slash, '/', substr(__FILE__, strpos(__FILE__, $pluginDirectory))) ?>" );    
		if (tag != "" && postid != "") {
			mysack.execute = 1;
			mysack.method = 'POST';
			mysack.setVar( "action", "ITT_ProcessRemoveTag" );
			mysack.setVar( "tag", tag);
			mysack.setVar( "postid", postid);			
			mysack.encVar( "cookie", document.cookie, false );
			mysack.onError = function() { alert('AJAX error removing tag' )};
			mysack.runAJAX();
		} 
		return true;
	}
	
	</script>
	<?php
}

function ITT_EmbedWidget($content) {
	global $post, $user_level;

	$postid = $post->ID;

	if (!current_user_can( 'edit_post', $postid )) return $content;
	
	$content .= ITT_GetWidget();
	return $content;
}

function ITT_ShowWidget() {
	echo ITT_GetWidget();
}

function ITT_GetWidget() {
	global $post, $user_level, $showExistingTags;

	$postid = $post->ID;
	if (!current_user_can( 'edit_post', $postid )) return "";

	$existingTagsWidget = "";
	
	if ($showExistingTags) {
		$existingTagsWidget .= "<strong>Existing Tag</strong>: <select id=\"existingAddTag-$postid\">";
		$tags = (array) get_terms('post_tag','get=all');
		
		foreach ($tags as $tag) {
			$existingTagsWidget .= "<option value='$tag->slug'>$tag->name</option>";
		}
		
		$existingTagsWidget .= "</select><input type=\"button\" value=\"+\" onClick=\"Things_AddTagToPost(document.getElementById('existingAddTag-$postid').value, '$postid')\" />";
	}

	return "<div class=\"itt_tagBox\" style=\"border-top:1px solid #bbc; border-bottom:1px solid #bbc; background:#efefff; padding:3px;\"><strong>Add Tags</strong>: <input type=\"text\" size=\"9\" id=\"soloAddTag-$postid\" /><input type=\"button\" value=\"+\" onClick=\"Things_AddTagToPost(document.getElementById('soloAddTag-$postid').value, '$postid')\" /> &nbsp; Currently Assigned: <span id=\"assignedTags-$postid\">" . ITT_GetSimpleTagList($postid) . "</span><br />$existingTagsWidget</div>";
}

add_action('wp_head', 'ITT_ShowJavascript');
if ($automagicEmbed) {
	add_action('the_content', 'ITT_EmbedWidget');
}
?>