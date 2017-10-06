<?php 

/**
 * new WordPress Widget format
 * Wordpress 2.8 and above
 * @see http://codex.wordpress.org/Widgets_API#Developing_Widgets
 */
class HASHTAGGER_Widget extends WP_Widget {

	/**
	 * Constructor
	 *
	 * @return void
	 */
	function __construct() {
		$widget_ops = array( 'classname' => 'wp-hastagger emailsettings', 'description' => 'WP Hashtagger Email Registration' );
		parent::__construct( 'wp_hastagger', 'WP Hashtagger Email Registration', $widget_ops );
	}

	/**
	 * Outputs the HTML for this widget.
	 *
	 * @param array  An array of standard parameters for widgets in this theme
	 * @param array  An array of settings for this widget instance
	 * @return void Echoes it's output
	 */
	function widget( $args, $instance ) {
		echo $args['before_widget'];
		echo $args['before_title'];
		echo $instance['title']; // Can set this with a widget option, or omit altogether
		echo $args['after_title'];
		global $wpdb, $current_user;
		$prefix = $wpdb->prefix;
		$term_db = $prefix . 'tag_registration';
		
	    $currentUsr = get_currentuserinfo();
	    $cuserEmail = $currentUsr->user_email;
	    $cuserName 	= $currentUsr->display_name;
	    
		$r_tags = $wpdb->get_results("SELECT * FROM $term_db WHERE `email`='".$cuserEmail."'", OBJECT);

		/*echo '<pre>';
		print_r($r_tags);
		echo '</pre>';*/
	



		$output = '<div id="wp-hastagger">
			<div class="inner">
				<div class="rg_list">
			        <ul class="list-inline">';
			        foreach($r_tags as $tg) $output .= '<li><span class="itemN">'.$tg->tag_name.'</span><span data-item="'.$tg->tag_name.'" class="delete"><i class="fa fa-times-circle" aria-hidden="true"></i></span></li>';
			        $output .= '</ul>
		    	</div>
			</div>
				<div class="inputField"><div class="row">
					<form id="r_form" class="form-inline">
						<input type="hidden" name="r_user_id" value="'.get_current_user_id().'"/>
						<div class="form-group col-sm-9" style="padding-right:0px;">
							<input placeholder="start type with #" type="text" style="margin:0;" class="form-control" name="r_tag" id="registEmailinput" value=""/>
						</div>
						<div class="col-sm-3">
							<button style="min-width:unset; max-width:100%; padding:6px 8px;" type="submit" class="btn btn-primary has-go">Go</button>
						</div>
					</form>
				</div></div>
			</div>';
		echo $output;
		echo $args['after_widget'];
	}

	/**
	 * Deals with the settings when they are saved by the admin. Here is
	 * where any validation should be dealt with.
	 *
	 * @param array  An array of new settings as submitted by the admin
	 * @param array  An array of the previous settings
	 * @return array The validated and (if necessary) amended settings
	 */
	function update( $new_instance, $old_instance ) {

		// update logic goes here
		$updated_instance = $new_instance;
		return $updated_instance;
	}

	/**
	 * Displays the form for this widget on the Widgets page of the WP Admin area.
	 *
	 * @param array  An array of the current settings for this widget
	 * @return void Echoes it's output
	 */
	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array(
			   'title'   => 'Registration Your Hashtag'
		) );
		$title        = strip_tags($instance['title']);
	?>
		<p>
           <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'lawzone' ); ?>:</label>
           <input class="widefat"  name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
        </p>
	<?php

	}
}

function hastagger_register_widget() {
	register_widget( 'HASHTAGGER_Widget' );
}
add_action( 'widgets_init', 'hastagger_register_widget' );