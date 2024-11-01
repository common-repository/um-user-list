<?php 
/**
 * UM User List Widget
 */
class UM_User_List_Widget extends WP_Widget {
 
	function __construct() {
			parent::__construct(
		 
			// Base ID of your widget
			'um_user_list_widget', 
		 
			// Widget name will appear in UI
			__('UM Users List', 'um-user-list'), 
		 
			// Widget description
			array( 'description' => __( 'Ultimate Member suggested users list in widget.', 'um-user-list' ), ) 
		);
	}
	 
	// Creating widget front-end
	 
	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', $instance['title'] );
		$count = $instance['count'];
		 
		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		 
		// This is where you run the code and display the output
		echo do_shortcode('[um_user_suggestions count="' . absint( $count ) . '"]');

		echo $args['after_widget'];
	}
	         
	// Widget Backend 
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'User Suggestions', 'um-user-list' );
		}
		if ( isset( $instance[ 'count' ] ) ) {
			$count = $instance[ 'count' ];
		}
		else {
			$count = 5;
		}
		// Widget admin form
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Amount to display:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" type="number" value="<?php echo esc_attr( $count ); ?>" min="1" />
		</p>
		<?php 
	}
     
	// Updating widget replacing old instances with new
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['count'] = ( ! empty( $new_instance['count'] ) ) ? strip_tags( $new_instance['count'] ) : '';
		return $instance;
	}
} // Class um_user_list_widget ends here

// Register and load the widget
function um_register_user_list_load_widget() {
    register_widget( 'UM_User_List_Widget' );
}
add_action( 'widgets_init', 'um_register_user_list_load_widget' );