<?php
/**
 * UM User List Core.
 *
 * @since   1.0.0
 * @package UM_User_List
 */

/**
 * UM User List Core.
 *
 * @since 1.0.0
 */
class UM_User_List_Core {
	/**
	 * Parent plugin class.
	 *
	 * @since 1.0.0
	 *
	 * @var   UM_User_List
	 */
	protected $plugin = null;

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 *
	 * @param  UM_User_List $plugin Main plugin object.
	 */
	public function __construct( $plugin ) {
		$this->plugin = $plugin;
		$this->hooks();
	}

	/**
	 * Initiate our hooks.
	 *
	 * @since  1.0.0
	 */
	public function hooks() {
		add_shortcode( 'um_user_suggestions', array( $this, 'um_user_suggestions_handler' ) );
		add_shortcode( 'um_user_list', array( $this, 'um_user_list_handler' ) );
		// Ajax function to fetch next users list to loggedin user after refresh.
		add_action( 'wp_ajax_umul_refresh_user_connection', array( $this, 'umul_refresh_user_connection_callback' ) );
		add_action( 'wp_ajax_nopriv_umul_refresh_user_connection', array( $this, 'umul_refresh_user_connection_callback' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_assets' ) );
	}

	/**
	 * Add Assets.
	 *
	 * @return void
	 */
	public function add_assets() {
		wp_enqueue_style( 'umul-style-css', um_user_list()->url( 'assets/css/um-user-list.css' ) );
		wp_enqueue_script( 'um-user-list-script', um_user_list()->url( 'assets/js/um-user-list.js' ), array( 'jquery' ) );
		wp_localize_script( 'um-user-list-script', 'umulfAjax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
	}

	/**
	 * Add random ordering
	 *
	 * @param array $query
	 */
	public function randomize_order( $query = array() ) {
		$query->query_orderby = 'ORDER by RAND()';
	}
	public function um_user_suggestions_handler( $atts = array() ) {
		global $wpdb, $user;
		$params = shortcode_atts( array(
			'count'     => 5,
			'orderby'   => 'user_login',
			'order'     => 'ASC',
			'more_text' => __( 'Show others', 'um-user-list' ),
		), $atts );

		$user_count = $params['count'];

		$data = '<div class="umul-member-list-container"><div class="umul-member-outer">';

		if ( is_user_logged_in() ) {
			// Loggedin user id.
			$user_id = get_current_user_id();
			$has_ids = $this->umul_has_connection_ids_callback();

			// WP_User_Query arguments.
			$args = array(
				'number'  => $user_count,
				'order'   => 'ASC',
				'orderby' => 'user_login',
				'exclude' => $has_ids,
			);
			if ( 'random' === $params['orderby'] ) {
				add_filter( 'pre_user_query', array( $this, 'randomize_order' ) );
			}
			// The User Query.
			$user_query = new WP_User_Query( $args );

			if ( 'random' === $params['orderby'] ) {
				remove_filter( 'pre_user_query', array( $this, 'randomize_order' ) );
			}
			// The User Loop.
			if ( ! empty( $user_query->results ) ) {
				foreach ( $user_query->results as $user ) {
					um_fetch_user( $user->ID );
					$user_id2 = absint( $user->ID );
					$name     = um_user( 'display_name' );
					$uname    = $user->user_login;
					$url      = um_user_profile_url( $user_id2 );
					$avatar   = um_get_user_avatar_url( um_user( 'ID' ) );
					$data    .= '<div class="umul-member-wrap"><div class="umul-member-avatar"><img src="' . esc_attr( $avatar ) . '" alt="' . esc_attr( $name ) . '"></div><div class="umul-member-info"><div class="umul-member-name"><a href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a></div><div class="umul-member-uname"><a href="' . esc_url( $url ) . '">@' . esc_html( $uname ) . '</a></div></div></div>';
					um_reset_user();
				}
				$data .= '</div><div class="umul-member-list-refresh-wrap"><a href="javascript:void(0)" class="umul-member-list-refresh" data-count="' . esc_attr( $user_count ) . '" data-skip="' . esc_attr( $user_count ) . '" data-order="' . esc_attr( $params['orderby'] ) . '">' . esc_html( $params['more_text'] ) . '</a></div>';
			} else {
				$data .= '<div class="umul-member-wrap">' . esc_html__( 'No more suggestions found.', 'um-user-list' ) . '</div>';
			}
		} else {
			$data .= '<div class="umul-member-wrap">' . esc_html__( 'You have to logged in to see suggestions.', 'um-user-list' ) . '</div>';
		}
		$data .= '</div>';
		return $data;
	}

	
	public function umul_has_connection_ids_callback() {
		global $wpdb;
		
		$data = array();

		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			
			// Check if followers plugin is installed.
			if ( class_exists( 'UM_Followers_API' ) ) {
				$follow_table = $wpdb->prefix . 'um_followers';
				$followers    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$follow_table} WHERE user_id2 = %d", $user_id ), ARRAY_A );
				if ( count( $followers ) > 0 ) {
					foreach ( $followers as $key => $follower ) {
						$fid = absint( $follower['user_id1'] );
						if ( $fid != $user_id ) {
							$data[] = $fid;
						}
					}
				}
			}

			// Check if Friends plugin installed.
			if ( class_exists( 'UM_Friends_API' ) ) {
				$friend_table = $wpdb->prefix . 'um_friends';
				$friends      = $wpdb->get_results( "SELECT * FROM {$friend_table} WHERE status = 1 AND user_id2 = {$user_id} OR user_id1 = {$user_id}", ARRAY_A );
				if ( count( $friends ) > 0 ) {
					foreach ( $friends as $key => $friend ) {
						$fid1 = absint( $friend['user_id1'] );
						if ( $fid1 > 0 ) {
							$data[] = $fid1;
						}
						$fid2 = absint( $friend['user_id2'] );
						if ( $fid2 > 0 ) {
							$data[] = $fid2;
						}
					}
				}
			}
		}
		return array_unique( $data );
	}

	/**
	 * Ajax callback.
	 *
	 * @return json
	 */
	public function umul_refresh_user_connection_callback() {
		global $wpdb, $user;

		if ( is_user_logged_in() ) {
			$data = "";

			$count = isset( $_POST['count'] ) ? sanitize_text_field( $_POST['count'] ) : 0;
			$skip  = isset( $_POST['skip'] ) ? sanitize_text_field( $_POST['skip'] ) : 0;
			$order = isset( $_POST['orderby'] ) ? sanitize_text_field( $_POST['orderby'] ) : 'user_login';
			$offset  = $count + $skip;

			if ( $count > 0 ) {
				// Loggedin user id
				$user_id = get_current_user_id();

				// Fetch friends and followers of logged in user
				$has_ids = $this->umul_has_connection_ids_callback();

				// WP_User_Query arguments
				$args = array(
					'number'         => $count,
					'offset'		 => $skip,
					'order'          => 'ASC',
					'orderby'        => 'user_login',
					'exclude'		 => $has_ids,
				);
				
				if ( 'random' === $order ) {
					add_filter( 'pre_user_query', array( $this, 'randomize_order' ) );
				}
				// The User Query
				$user_query = new WP_User_Query( $args );

				if ( 'random' === $order ) {
					remove_filter( 'pre_user_query', array( $this, 'randomize_order' ) );
				}
				// The User Loop
				if ( ! empty( $user_query->results ) ) {
					foreach ( $user_query->results as $user ) {
						um_fetch_user( $user->ID );
						$user_id2 = absint( $user->ID );
						$name     = um_user( 'display_name' );
						$uname    = $user->user_login;
						$url      = um_user_profile_url( $user_id2 );
						$avatar   = um_get_user_avatar_url( um_user( 'ID' ) );
						$data .= '<div class="umul-member-wrap"><div class="umul-member-avatar"><img src="' . esc_attr( $avatar ) . '" alt="' . esc_attr( $name ) . '"></div><div class="umul-member-info"><div class="umul-member-name"><a href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a></div><div class="umul-member-uname"><a href="' . esc_url( $url ) . '">@' . esc_html( $uname ) . '</a></div></div></div>';
						um_reset_user();
					}
				} else {
					$data .= '<div class="umul-member-wrap">' . esc_html__( 'No more suggestions found.', 'um-user-list' ) . '</div>';
					$offset = 0;
				}
			}

			if ( $offset > 0 ) {
				wp_send_json( array(
					'txt'  => wp_kses_post( $data ),
					'skip' => absint( $offset ),
				)
				);
			} else {
				wp_send_json(
					array(
						'txt'  => wp_kses_post( $data ),
						'skip' => 0,
					)
				);
			}		
		} else {
			$login_err_msg = '<div class="umul-member-wrap">' . esc_html__( 'You have to logged in to see suggestions.', 'um-user-list' ) . '</div>';
			wp_send_json( array(
				'txt'  => $login_err_msg,
				'skip' => 0,
			)
			);
		}
	}

	public function um_user_list_handler($atts = array() ) {
		global $wpdb, $user;
		$params = shortcode_atts( array(
			'count'     => 5,
			'orderby'   => 'user_login',
			'order'     => 'ASC',
			'more_text' => __( 'Show others', 'um-user-list' ),
		), $atts );
		
	}
}
