<?php
/*
Plugin Name: WP One Post Widget
Plugin URI: http://wordpress.org/extend/plugins/wp-one-post-widget
Description: This plugin show only one post in the sidebar or widgetzed area
Version: 2.1
Author: Rafael Tavares
Author URI: 
*/ 

load_plugin_textdomain('wponepostwidget', false, dirname(plugin_basename(__FILE__)).'/language/');

function wp_one_post_admin_scripts() {
  wp_register_style( 'jquery-ui-css', "http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css");
  wp_enqueue_style( 'jquery-ui-css' );

  wp_register_script( 'jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.5/jquery.min.js');
  wp_enqueue_script( 'jquery' );

  wp_register_script( 'jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/jquery-ui.min.js');
  wp_enqueue_script( 'jquery-ui' );

  wp_register_style( 'wp-one-post-admin', plugins_url('css/wp-one-post-admin.css', __FILE__));
  wp_enqueue_style( 'wp-one-post-admin' );
}    
add_action('admin_enqueue_scripts', 'wp_one_post_admin_scripts');

function wp_one_post_scripts() {
  wp_register_style( 'wp-one-post-widget', plugins_url('css/wp-one-post-widget.css', __FILE__));
  wp_enqueue_style( 'wp-one-post-widget' );
}
add_action('wp_enqueue_scripts', 'wp_one_post_scripts');


add_action('init', 'wp_one_post_widget_register');
function wp_one_post_widget_register() {
	
	$prefix = 'wp-one-post-widget';
	$name = __('WP One Post Widget');
	$widget_ops = array('classname' => 'wp_one_post_widget', 'description' => __('Add content specific to your site.'));
	$control_ops = array('width' => 200, 'height' => 200, 'id_base' => $prefix);
	
	$options = get_option('wp_one_post_widget');
  
	if(isset($options[0])) unset($options[0]);
	
	if(!empty($options)){
		foreach(array_keys($options) as $widget_number){
			wp_register_sidebar_widget($prefix.'-'.$widget_number, $name, 'wp_one_post_widget', $widget_ops, array( 'number' => $widget_number ));
			wp_register_widget_control($prefix.'-'.$widget_number, $name, 'wp_one_post_widget_control', $control_ops, array( 'number' => $widget_number ));
		}
	} else{
		$options = array();
		$widget_number = 1;
		wp_register_sidebar_widget($prefix.'-'.$widget_number, $name, 'wp_one_post_widget', $widget_ops, array( 'number' => $widget_number ));
		wp_register_widget_control($prefix.'-'.$widget_number, $name, 'wp_one_post_widget_control', $control_ops, array( 'number' => $widget_number ));
	}
}

function wp_one_post_widget($args, $vars = array()) {
  extract($args);
  $widget_number = (int)str_replace('wp-one-post-widget-', '', @$widget_id);
  $options = get_option('wp_one_post_widget');
  if(!empty($options[$widget_number])){
    $vars = $options[$widget_number];
  }

  global $post;
  global $wpdb;

  $querystr = "
    SELECT $wpdb->posts.* 
    FROM $wpdb->posts
    WHERE $wpdb->posts.post_title = '".$vars['title']."'
    AND $wpdb->posts.post_status = 'publish' 
    AND $wpdb->posts.post_type = 'post'
  ";

  $pageposts = $wpdb->get_results($querystr, OBJECT);
  
  foreach ($pageposts as $post):
    setup_postdata($post);
    $title = $post->post_title;
    $excerpt = $post->post_excerpt; 
    if(!$excerpt): $excerpt = substr($post->post_content,0,100); endif;
    if(!$vars['thumbnail_position']): $vars['thumbnail_position'] = 'left'; endif;
		$thumb = get_the_post_thumbnail($post->ID, 'thumbnail', array('class' => $vars['thumbnail_position']));
		$link = get_permalink($post->ID);
  endforeach;

  echo $before_widget;

	if(empty($vars['custom_title'])): echo $before_title . $vars['title'] . $after_title; else: echo $before_title . $vars['custom_title'] . $after_title; endif; 

  if($vars['use_thumbnail'] == 'yes'):
    $content_widget = $thumb.'<p>'.$excerpt.' <a href="'.$link.'">'.$vars['readmore'].'</a></p>';
  else:
    $content_widget = '<p>'.$excerpt.' <a href="'.$link.'">'.$vars['readmore'].'</a></p>';
  endif;

  echo $content_widget;

  echo $after_widget;
}

function wp_one_post_widget_control($args) {

	$prefix = 'wp-one-post-widget';
	
	$options = get_option('wp_one_post_widget');
	if(empty($options)) $options = array();
	if(isset($options[0])) unset($options[0]);
		
	if(!empty($_POST[$prefix]) && is_array($_POST)){
		foreach($_POST[$prefix] as $widget_number => $values){
			if(empty($values) && isset($options[$widget_number]))
				continue;
			
			if(!isset($options[$widget_number]) && $args['number'] == -1){
				$args['number'] = $widget_number;
				$options['last_number'] = $widget_number;
			}
			$options[$widget_number] = $values;
		}
		
		if($args['number'] == -1 && !empty($options['last_number'])){
			$args['number'] = $options['last_number'];
		}

		$options = multiwidget_update($prefix, $options, $_POST[$prefix], $_POST['sidebar'], 'wp_one_post_widget');
	}
	
	$number = ($args['number'] == -1)? '%i%' : $args['number'];

	$opts = @$options[$number];
	$title = @$opts['title'];
	$custom_title = @$opts['custom_title'];
	$readmore = @$opts['readmore'];
	$thumbnail_position = @$opts['thumbnail_position'];
	$use_thumbnail = @$opts['use_thumbnail'];
	 
	?>
  <p><?php _e('Custom Title', 'wponepostwidget') ?></p>
  <p><input type="text" id="custom_title" name="<?php echo $prefix; ?>[<?php echo $number; ?>][custom_title]" value="<?php echo $custom_title; ?>"/></p> 
  <p><?php _e('Search the content for the keyword and select', 'wponepostwidget') ?></p>
  <p><input type="text" id="autocomplete" name="<?php echo $prefix; ?>[<?php echo $number; ?>][title]" placeholder="<?php _e('keyword...','wponepostwidget'); ?>" value="<?php echo $title; ?>"/></p> 
  <p><?php _e('Label Read More', 'wponepostwidget') ?></p>
	<p><input type="text" id="readmore" name="<?php echo $prefix; ?>[<?php echo $number; ?>][readmore]" value="<?php echo $readmore; ?>"/></p>
  <p><?php _e('Use Thumbnail?', 'wponepostwidget') ?></p>
	<p><input type="radio" id="use_thumbnail" name="<?php echo $prefix; ?>[<?php echo $number; ?>][use_thumbnail]" <?php if($use_thumbnail == 'yes'): echo 'checked="checked"'; endif;?> value="yes"/><?php _e('Yes', 'wponepostwidget') ?>
  <input type="radio" id="use_thumbnail" name="<?php echo $prefix; ?>[<?php echo $number; ?>][use_thumbnail]" <?php if($use_thumbnail == 'no'): echo 'checked="checked"'; endif;?> value="no"/><?php _e('No', 'wponepostwidget') ?></p>
  <p><?php _e('Thumbnail Position', 'wponepostwidget') ?></p>
	<p><input type="radio" id="thumbnail_position" name="<?php echo $prefix; ?>[<?php echo $number; ?>][thumbnail_position]" <?php if($thumbnail_position == 'left'): echo 'checked="checked"'; endif;?> value="left"/><?php echo _e('Left', 'wponepostwidget') ?>
	<input type="radio" id="thumbnail_position" name="<?php echo $prefix; ?>[<?php echo $number; ?>][thumbnail_position]" <?php if($thumbnail_position == 'right'): echo 'checked="checked"'; endif;?> value="right"/><?php echo _e('Right', 'wponepostwidget') ?>
  <input type="radio" id="thumbnail_position" name="<?php echo $prefix; ?>[<?php echo $number; ?>][thumbnail_position]" <?php if($thumbnail_position == 'top'): echo 'checked="checked"'; endif;?> value="top"/><?php echo _e('Top', 'wponepostwidget') ?></p>

  <script type="text/javascript">
    jQuery(document).ready(function($) {
      $("input#autocomplete").autocomplete({
        source: function(request, response) {
					  $.ajax({
              url: "<?php echo plugins_url('data.php', __FILE__);?>",
						  dataType: "json",
						  data: {
							  term : request.term,
							  autocompletar : $("#autocomplete").val()
						  },
						  success: function(data) {
							  response(data);
						  }
					  });
				  },
				  minLength: 1
      });
    });
  </script>

	<?
}

if(!function_exists('multiwidget_update')){
	function multiwidget_update($id_prefix, $options, $post, $sidebar, $option_name = ''){
		global $wp_registered_widgets;
		static $updated = false;

		$sidebars_widgets = wp_get_sidebars_widgets();
		if ( isset($sidebars_widgets[$sidebar]) )
			$this_sidebar =& $sidebars_widgets[$sidebar];
		else
			$this_sidebar = array();
		
		foreach ( $this_sidebar as $_widget_id ) {
			if(preg_match('/'.$id_prefix.'-([0-9]+)/i', $_widget_id, $match)){
				$widget_number = $match[1];
				
				if(!in_array($match[0], $_POST['widget-id'])){
					unset($options[$widget_number]);
				}
			}
		}
		
		if(!empty($option_name)){
			update_option($option_name, $options);
			$updated = true;
		}
		
		return $options;
	}
}

/**
* PressTrends Plugin API
*/
	function presstrends_WPOnePostWidget_plugin() {

		// PressTrends Account API Key
		$api_key = 'm269xyyh9z7ewolnfm6y4bup070fu4np1r8b';
		$auth    = 'lr1puahqgb9zdfgk58wzjt4qhku46lwhn';

		// Start of Metrics
		global $wpdb;
		$data = get_transient( 'presstrends_cache_data' );
		if ( !$data || $data == '' ) {
			$api_base = 'http://api.presstrends.io/index.php/api/pluginsites/update/auth/';
			$url      = $api_base . $auth . '/api/' . $api_key . '/';

			$count_posts    = wp_count_posts();
			$count_pages    = wp_count_posts( 'page' );
			$comments_count = wp_count_comments();

			// wp_get_theme was introduced in 3.4, for compatibility with older versions, let's do a workaround for now.
			if ( function_exists( 'wp_get_theme' ) ) {
				$theme_data = wp_get_theme();
				$theme_name = urlencode( $theme_data->Name );
			} else {
				$theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' );
				$theme_name = $theme_data['Name'];
			}

			$plugin_name = '&';
			foreach ( get_plugins() as $plugin_info ) {
				$plugin_name .= $plugin_info['Name'] . '&';
			}
			// CHANGE __FILE__ PATH IF LOCATED OUTSIDE MAIN PLUGIN FILE
			$plugin_data         = get_plugin_data( __FILE__ );
			$posts_with_comments = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type='post' AND comment_count > 0" );
			$data                = array(
				'url'             => stripslashes( str_replace( array( 'http://', '/', ':' ), '', site_url() ) ),
				'posts'           => $count_posts->publish,
				'pages'           => $count_pages->publish,
				'comments'        => $comments_count->total_comments,
				'approved'        => $comments_count->approved,
				'spam'            => $comments_count->spam,
				'pingbacks'       => $wpdb->get_var( "SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_type = 'pingback'" ),
				'post_conversion' => ( $count_posts->publish > 0 && $posts_with_comments > 0 ) ? number_format( ( $posts_with_comments / $count_posts->publish ) * 100, 0, '.', '' ) : 0,
				'theme_version'   => $plugin_data['Version'],
				'theme_name'      => $theme_name,
				'site_name'       => str_replace( ' ', '', get_bloginfo( 'name' ) ),
				'plugins'         => count( get_option( 'active_plugins' ) ),
				'plugin'          => urlencode( $plugin_name ),
				'wpversion'       => get_bloginfo( 'version' ),
			);

			foreach ( $data as $k => $v ) {
				$url .= $k . '/' . $v . '/';
			}
			wp_remote_get( $url );
			set_transient( 'presstrends_cache_data', $data, 60 * 60 * 24 );
		}
	}

// PressTrends WordPress Action
add_action('admin_init', 'presstrends_WPOnePostWidget_plugin');
?>
