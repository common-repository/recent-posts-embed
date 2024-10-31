<?php
/*
Plugin Name: Recent Posts Embed
Plugin URI: http://dev.berthiau.com/wordpress/recent-posts-embed-plugin-for-wordpress.html
Description: Embed a list of recent posts in a page or a post.
Version: 1.4.2.1
Author: Sebastien Berthiau
Author URI: http://dev.berthiau.com/wordpress/
*/

/*  Copyright 2008  Sebastien Berthiau (email : sberthiau -at- free.fr)

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

$recent_posts_embed_version = "1.4.2.1";
static $depth=0;
$max_depth=20; //Max tags in a page (avoid crash if a tag appears in an excerpt)

if (!function_exists('add_filter'))
	die ("add filter doesnt exist");

add_filter('the_content', 'recent_posts_embed');


function recent_posts_embed ($content) {
	global $post;
	global $depth;
	global $depth;
	global $max_depth;

	if (!strstr($content, "[recent posts"))
		return $content;
	else {
		$tag_start = strpos ( $content , "[recent posts" );
		$tag_end = strpos ( $content , "]" , $tag_start );
		$tag = substr ( $content , $tag_start , $tag_end-$tag_start+1 );

		$arg_start = strpos ( $tag , "=" );
		if ($arg_start) {
			$arg_start+=1;
			$arg_len = strlen($tag)-$arg_start-1;
			$arg = substr ( $tag , $arg_start , $arg_len );
			$arg = preg_replace ('# #','',$arg);
		}

		$content=str_replace($tag,get_recent_posts_embed($arg),$content);
	}
	if (strstr($content, "[recent posts") && $depth++<$max_depth) {
		$content=recent_posts_embed ($content);
	}
	return $content;
}




function get_recent_posts_embed ($arg) {
	global $recent_posts_embed_version;
	global $recent_posts_list;
	global $post;
	global $wpdb;
	global $id;

	if (get_option('rpe_number_posts')=='') update_option( 'rpe_number_posts', '5');
	if (get_option('rpe_date_format')=='') update_option( 'rpe_date_format', 'j F Y :');
	if (get_option('rpe_display_date')=='') update_option( 'rpe_display_date', 'on');
	if (get_option('rpe_display_comments')=='') update_option( 'rpe_display_comments', 'on');
	if (get_option('rpe_display_excerpt')=='') update_option( 'rpe_display_excerpt', 'on');
	if (get_option('rpe_before_excerpt')=='') update_option( 'before_excerpt', '<br><blockquote>');
	if (get_option('rpe_after_excerpt')=='') update_option( 'after_excerpt', '</blockquote><br>');
	if (get_option('rpe_len_excerpt')=='') update_option( 'len_excerpt', '30');
	if (get_option('rpe_more_excerpt')=='') update_option( 'more_excerpt', '[More...]');
	if (get_option('rpe_comments_zero')=='') update_option( 'rpe_comments_zero', '(No Comments)');
	if (get_option('rpe_comments_one')=='') update_option( 'rpe_comments_one', '(1 Comment)');
	if (get_option('rpe_comments_more')=='') update_option( 'rpe_comments_more', '(% Comments)');

	$rpe_number_posts=get_option('rpe_number_posts');
	$rpe_date_format=get_option('rpe_date_format');
	$rpe_display_date=get_option('rpe_display_date');
	$rpe_display_comments=get_option('rpe_display_comments');
	$rpe_display_excerpt=get_option('rpe_display_excerpt');
	$rpe_before_excerpt=get_option('rpe_before_excerpt');
	$rpe_after_excerpt=get_option('rpe_after_excerpt');
	$rpe_len_excerpt=get_option('rpe_len_excerpt');
	$rpe_more_excerpt=get_option('rpe_more_excerpt');
	$rpe_comments_zero=get_option('rpe_comments_zero');
	$rpe_comments_one=get_option('rpe_comments_one');
	$rpe_comments_more=get_option('rpe_comments_more');
	$rpe_leave_comment=get_option('rpe_leave_comment');


	$recent_posts_list="<!-- Recent Posts Embed - Version ".$recent_posts_embed_version." - Sebastien Berthiau -->\n";
	$recent_posts_list=$recent_posts_list."<ul>\n";
 	$postslist = get_posts('numberposts='.$rpe_number_posts.'&order=DESC&orderby=post_date&category='.$arg);
	$sav_post=$post;
 	foreach ($postslist as $post) :
	    setup_postdata($post);
	    $recent_posts_list=$recent_posts_list."<li>";
	    if ($rpe_display_date=='on')
			$recent_posts_list=$recent_posts_list.get_the_time($rpe_date_format);
		$recent_posts_list=$recent_posts_list." <a href=\"".get_permalink($post->ID,'',false)."\">".the_title('','',false)."</a> ";
	    if ($rpe_display_comments=='on')
			$recent_posts_list=$recent_posts_list.$rpe_comments_before.rpe_comments_number($rpe_comments_zero,$rpe_comments_one,$rpe_comments_more,'',false).$rpe_comments_after;
	    if ($rpe_leave_comment!='')
			$recent_posts_list=$recent_posts_list." <a href=\"".get_permalink($post->ID,'',false)."#respond\"> ".$rpe_leave_comment."</a> ";
	    if ($rpe_display_excerpt=='on')
			$recent_posts_list=$recent_posts_list.$rpe_before_excerpt.rpe_get_the_excerpt($rpe_len_excerpt,$rpe_more_excerpt).$rpe_after_excerpt;
	    $recent_posts_list=$recent_posts_list."</li>\n";

	endforeach;
	$post=$sav_post;
	setup_postdata($post);

	$recent_posts_list=$recent_posts_list.'</ul>';

    return $recent_posts_list;
}


add_action('admin_menu', 'rpe_add_pages');

function rpe_add_pages() {
    add_options_page('Recent Posts', 'Recent Posts', 8, 'recentposts', 'rpe_options_page');
}

function rpe_options_page() {

	if(function_exists('load_plugin_textdomain'))
		load_plugin_textdomain('recent-posts-embed', 'wp-content/plugins/recent-posts-embed/languages/');

    $hidden_field_name = 'rpe_submit_hidden';

	if (get_option('rpe_number_posts')=='') update_option( 'rpe_number_posts', '5');
	if (get_option('rpe_date_format')=='') update_option( 'rpe_date_format', 'j F Y :');
	if (get_option('rpe_display_date')=='') update_option( 'rpe_display_date', 'on');
	if (get_option('rpe_display_comments')=='') update_option( 'rpe_display_comments', 'on');
	if (get_option('rpe_comments_zero')=='') update_option( 'rpe_comments_zero', '(No Comments)');
	if (get_option('rpe_comments_one')=='') update_option( 'rpe_comments_one', '(1 Comment)');
	if (get_option('rpe_comments_more')=='') update_option( 'rpe_comments_more', '(% Comments)');
	if (get_option('rpe_display_excerpt')=='') update_option( 'rpe_display_excerpt', 'on');
	if (get_option('rpe_before_excerpt')=='') update_option( 'rpe_before_excerpt', '<br><blockquote>');
	if (get_option('rpe_after_excerpt')=='') update_option( 'rpe_after_excerpt', '</blockquote><br>');
	if (get_option('rpe_len_excerpt')=='') update_option( 'rpe_len_excerpt', '30');
	if (get_option('rpe_more_excerpt')=='') update_option( 'rpe_more_excerpt', '[More...]');


	$rpe_number_posts=get_option('rpe_number_posts');
	$rpe_date_format=get_option('rpe_date_format');
	$rpe_display_date=get_option('rpe_display_date');
	$rpe_display_excerpt=get_option('rpe_display_excerpt');
	$rpe_before_excerpt=get_option('rpe_before_excerpt');
	$rpe_after_excerpt=get_option('rpe_after_excerpt');
	$rpe_len_excerpt=get_option('rpe_len_excerpt');
	$rpe_more_excerpt=get_option('rpe_more_excerpt');
	$rpe_display_comments=get_option('rpe_display_comments');
	$rpe_comments_zero=get_option('rpe_comments_zero');
	$rpe_comments_one=get_option('rpe_comments_one');
	$rpe_comments_more=get_option('rpe_comments_more');
	$rpe_leave_comment=get_option('rpe_leave_comment');

	if($rpe_display_date == 'off')
		$display_date_selected = "";
	else
		$display_date_selected = " checked=\"checked\"";
	if($rpe_display_comments == 'off')
		$display_comments_selected = "";
	else
		$display_comments_selected = " checked=\"checked\"";
	if($rpe_display_excerpt == 'off')
		$display_excerpt_selected = "";
	else
		$display_excerpt_selected = " checked=\"checked\"";

    if( $_POST[ $hidden_field_name ] == 'Y' ) {
        $rpe_number_posts = $_POST[ 'rpe_number_posts' ];
        $rpe_date_format = $_POST[ 'rpe_date_format' ];
        $rpe_comments_zero = $_POST[ 'rpe_comments_zero' ];
        $rpe_comments_one = $_POST[ 'rpe_comments_one' ];
        $rpe_comments_more = $_POST[ 'rpe_comments_more' ];
        $rpe_before_excerpt = $_POST[ 'rpe_before_excerpt' ];
        $rpe_after_excerpt = $_POST[ 'rpe_after_excerpt' ];
        $rpe_len_excerpt = $_POST[ 'rpe_len_excerpt' ];
        $rpe_more_excerpt = $_POST[ 'rpe_more_excerpt' ];
        $rpe_leave_comment = $_POST[ 'rpe_leave_comment' ];
		if ($_POST[ 'rpe_display_date' ]=='on') {
			$display_date_selected = " checked=\"checked\"";
			update_option( 'rpe_display_date', 'on' );
		}
		else {
			$display_date_selected = "";
			update_option( 'rpe_display_date', 'off' );
		}
		if ($_POST[ 'rpe_display_comments' ]=='on') {
			$display_comments_selected = " checked=\"checked\"";
			update_option( 'rpe_display_comments', 'on' );
		}
		else {
			$display_comments_selected = "";
			update_option( 'rpe_display_comments', 'off' );
		}
		if ($_POST[ 'rpe_display_excerpt' ]=='on') {
			$display_excerpt_selected = " checked=\"checked\"";
			update_option( 'rpe_display_excerpt', 'on' );
		}
		else {
			$display_excerpt_selected = "";
			update_option( 'rpe_display_excerpt', 'off' );
		}

        update_option( 'rpe_number_posts', $rpe_number_posts );
        update_option( 'rpe_date_format', $rpe_date_format );
        update_option( 'rpe_comments_zero', $rpe_comments_zero );
        update_option( 'rpe_comments_one', $rpe_comments_one );
        update_option( 'rpe_comments_more', $rpe_comments_more );
        update_option( 'rpe_before_excerpt', $rpe_before_excerpt );
        update_option( 'rpe_after_excerpt', $rpe_after_excerpt );
        update_option( 'rpe_len_excerpt', $rpe_len_excerpt );
        update_option( 'rpe_more_excerpt', $rpe_more_excerpt );
        update_option( 'rpe_leave_comment', $rpe_leave_comment );

?>
<div class="updated"><p><strong><?php _e('Options saved.', 'recent-posts-embed' ); ?></strong></p></div>
<?php

    }

    echo '<div class="wrap">';

    echo "<h2>" . __( 'Recent posts embed plugin options', 'recent-posts-embed' ) . "</h2>";

    ?>

<form name="form1" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

<p><?php _e("Number of posts : ", 'recent-posts-embed' ); ?>
<input type="text" name="<?php echo 'rpe_number_posts'; ?>" value="<?php echo $rpe_number_posts; ?>" size="2">
</p>
<hr>
<p><?php _e("Display date : ", 'recent-posts-embed' ); ?>
<input type="checkbox" id="rpe_display_date" name="rpe_display_date"<?php echo $display_date_selected ?> />
</p>
<p><?php _e("Date/time format : ", 'recent-posts-embed' ); ?>
<input type="text" name="<?php echo 'rpe_date_format'; ?>" value="<?php echo $rpe_date_format; ?>" size="20">
</p>
<hr>
<p><?php _e("Display comments : ", 'recent-posts-embed' ); ?>
<input type="checkbox" id="rpe_display_comments" name="rpe_display_comments"<?php echo $display_comments_selected ?> />
</p>
<p><?php _e("No comments : ", 'recent-posts-embed' ); ?>
<input type="text" name="<?php echo 'rpe_comments_zero'; ?>" value="<?php echo $rpe_comments_zero; ?>" size="20">
</p>
<p><?php _e("1 comment : ", 'recent-posts-embed' ); ?>
<input type="text" name="<?php echo 'rpe_comments_one'; ?>" value="<?php echo $rpe_comments_one; ?>" size="20">
</p>
<p><?php _e("More comments : ", 'recent-posts-embed' ); ?>
<input type="text" name="<?php echo 'rpe_comments_more'; ?>" value="<?php echo $rpe_comments_more; ?>" size="20">
</p>
<p><?php _e("Leave a comment : ", 'recent-posts-embed' ); ?>
<input type="text" name="<?php echo 'rpe_leave_comment'; ?>" value="<?php echo $rpe_leave_comment; ?>" size="20">
</p>
<hr>
<p><?php _e("Display excerpt : ", 'recent-posts-embed' ); ?>
<input type="checkbox" id="rpe_display_excerpt" name="rpe_display_excerpt"<?php echo $display_excerpt_selected ?> />
</p>
<p><?php _e("HTML before : ", 'recent-posts-embed' ); ?>
<input type="text" name="<?php echo 'rpe_before_excerpt'; ?>" value="<?php echo $rpe_before_excerpt; ?>" size="20">
</p>
<p><?php _e("HTML after : ", 'recent-posts-embed' ); ?>
<input type="text" name="<?php echo 'rpe_after_excerpt'; ?>" value="<?php echo $rpe_after_excerpt; ?>" size="20">
</p>
<p><?php _e("Length : ", 'recent-posts-embed' ); ?>
<input type="text" name="<?php echo 'rpe_len_excerpt'; ?>" value="<?php echo $rpe_len_excerpt; ?>" size="3">
</p>
<p><?php _e("Read more : ", 'recent-posts-embed' ); ?>
<input type="text" name="<?php echo 'rpe_more_excerpt'; ?>" value="<?php echo $rpe_more_excerpt; ?>" size="20">
</p>
<hr>

<p>
Formatting Date and Time :
<ul><li> <code>l</code> = Full name for day of the week (lower-case L).
</li><li> <code>F</code> = Full name for the month.
</li><li> <code>M</code> = A short textual representation of a month, three letters.
</li><li> <code>m</code> = Numeric representation of a month, with leading zeros.
</li><li> <code>j</code> = The day of the month.
</li><li> <code>Y</code> = The year in 4 digits. (lower-case y gives the year's last 2 digits)
</li><li> <code>H</code> = 24-hour format of an hour with leading zeros
</li><li> <code>h</code> = 12-hour format of an hour with leading zeros
</li><li> <code>a</code> = Lowercase Ante meridiem and Post meridiem
</li><li> <code>i</code> = Minutes with leading zeros
</li><li> You can use the <a href="http://php.net/date" class="external text" title="http://php.net/date">table of date format characters on the PHP website</a> as a reference for building date format strings for use in WordPress.
</li></ul>
</p>

<p class="submit">
<input type="submit" name="Submit" value="<?php _e('Update Options', 'recent-posts-embed' ) ?>" />
</p>

</form>
</div>

<?php

}



/**
 * comments_number() - Display the language string for the number of comments the current post has
 *
 * @since 0.71 modified for php use instead of display
 * @uses $id
 * @uses apply_filters() Calls the 'comments_number' hook on the output and number of comments respectively.
 *
 * @param string $zero Text for no comments
 * @param string $one Text for one comment
 * @param string $more Text for more than one comment
 * @param string $deprecated Not used.
 * @param string $echo (boolean) Display the number(TRUE), or return the number to be used in PHP (FALSE). Defaults to TRUE.
 */
function rpe_comments_number( $zero = false, $one = false, $more = false, $deprecated = '', $echo = true) {
	global $id;
	$number = get_comments_number($id);

	if ( $number > 1 )
		$output = str_replace('%', $number, ( false === $more ) ? __('% Comments') : $more);
	elseif ( $number == 0 )
		$output = ( false === $zero ) ? __('No Comments') : $zero;
	else // must be one
		$output = ( false === $one ) ? __('1 Comment') : $one;

	if ( $echo )
	         echo apply_filters('comments_number', $output, $number);
         else
	         return apply_filters('comments_number', $output, $number);
}

function rpe_get_the_excerpt($len,$more) {
	$text = get_the_content('');
	$text = strip_shortcodes( $text );
	$text = strip_tags($text);
	$words = explode(' ', $text, $len+ 1);
	if (count($words)>$len) {
		array_pop($words);
		array_push($words, '<a href="'.get_permalink().'">'.$more.'</a>');
		$text = implode(' ', $words);
	}
	return $text;
}



?>