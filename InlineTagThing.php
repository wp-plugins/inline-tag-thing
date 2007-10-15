<?php 
/*
Plugin Name: Inline Tag Thing
Plugin URI: http://www.neato.co.nz/wordpress-things/inline-tag-thing
Description: A thing for editing tags inline, using AJAX magic.
Version: beta 2
Author: Christine From The Internet
Author URI: http://www.neato.co.nz
*/

// AJAX Processing
if ($_POST['action'] && ($_POST['action'] == 'ITT_ProcessTag' || $_POST['action'] == 'ITT_ProcessRemoveTag') ) {
	require('../../../wp-blog-header.php');

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
	die( 'document.getElementById("assignedTags-' . $postid . '").innerHTML = "' . (ITT_GetSimpleTagList($postid)) . '"');
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
	wp_print_scripts( array( 'sack' ));

	  // Define custom JavaScript functions
	?>
	<script type="text/javascript">
	function Things_AddTagToPost(tag, postid)
	{
		var mysack = new sack("<?php bloginfo( 'wpurl' ); ?>/wp-content/plugins/InlineTagThing/InlineTagThing.php" );    
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
		var mysack = new sack("<?php bloginfo( 'wpurl' ); ?>/wp-content/plugins/InlineTagThing/InlineTagThing.php" );    
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

function ITT_ShowWidget($content) {
	global $post, $user_level;

	$postid = $post->ID;

	if (!current_user_can( 'edit_post', $postid )) return $content;
	
	$content .= "<div style=\"border-top:1px solid #bbc; border-bottom:1px solid #bbc; background:#efefff; padding:3px;\"><strong>Add Tags</strong>: <input type=\"text\" size=\"9\" id=\"soloAddTag-$postid\" /><input type=\"button\" value=\"+\" onClick=\"Things_AddTagToPost(document.getElementById('soloAddTag-$postid').value, '$postid')\" /> &nbsp; Currently Assigned: <span id=\"assignedTags-$postid\">" . ITT_GetSimpleTagList($postid) . '</span></div>';
	return $content;
} 

add_action('wp_head', 'ITT_ShowJavascript');
add_action('the_content', 'ITT_ShowWidget');
?>