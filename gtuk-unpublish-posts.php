<?php
/**
 * Plugin Name: Gtuk unpublish posts
 * Description: A plugin to add an expire date to pages, posts and custom post types.
 * Version: 1.0.0
 * Author: Gtuk
 * Author URI: http://gtuk.me
 * License: GPLv2
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

add_action( 'plugins_loaded', array ( GtukUnpublishPosts::get_instance(), 'plugin_setup' ) );

class GtukUnpublishPosts {

	/**
	 * Plugin instance
	 */
	protected static $instance = null;

	/**
	 * URL to this plugin's directory
	 */
	public $plugin_url = '';

	/**
	 * Path to this plugin's directory
	 */
	public $plugin_path = '';

	/**
	 * Name of the text domain
	 */
	public $text_domain = 'gtuk-unpublish-posts';

	/**
	 * Access the plugin’s working instance
	 *
	 * @return  object of this class
	 */
	public static function get_instance() {
		null === self::$instance and self::$instance = new self;
		return self::$instance;
	}

	/**
	 * Plugin setup
	 *
	 * @return  void
	 */
	public function plugin_setup() {
		$this->plugin_url    = plugins_url( '/', __FILE__ );
		$this->plugin_path   = plugin_dir_path( __FILE__ );

		$this->load_language( $this->text_domain );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		if ( is_admin() ) {
			add_action( 'post_submitbox_misc_actions', array( $this, 'edit_unpublish_box' ) );
			add_action( 'save_post', array( $this, 'modify_post_content' ) );
		}

		add_action( 'unpublish_post',array( $this, 'unpublish_post' ) );
	}

	/**
	 * Constructor
	 */
	public function __construct() {}

	/**
	 * Load the translation file
	 *
	 * @param   string $domain
	 *
	 * @return  void
	 */
	public function load_language( $domain ) {
		load_plugin_textdomain(
				$domain,
				FALSE,
				$this->plugin_path . '/languages'
		);
	}

	/**
	 * Enqueue admin scripts and styles
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'gtuk-unpublish-posts', plugins_url( 'js/unpublish-posts.js', __FILE__ ), array( 'jquery' ), '', true );
		wp_enqueue_style( 'gtuk-unpublish-posts', plugins_url( 'css/unpublish-posts.css', __FILE__ ) );
	}

	/**
	 * Show unpublish box in post edit
	 */
	public function edit_unpublish_box() {
		global $post;

		if ( 'publish' != $post->post_status ) {
			return;
		}

		$timestamp = get_post_meta( $post->ID, '_unpublish_datetime', true );
		$post_meta = $timestamp;

		if ( empty( $timestamp ) ) {
			$timestamp = current_time( 'Y-m-d H:i:s' );
		}

		$monthList = array(
				'01' => '01-Jan',
				'02' => '02-Feb',
				'03' => '03-Mrz',
				'04' => '04-Apr',
				'05' => '05-Mai',
				'06' => '06-Jun',
				'07' => '07-Jul',
				'08' => '08-Aug',
				'09' => '09-Sep',
				'10' => '10-Okt',
				'11' => '11-Nov',
				'12' => '12-Dez',
		);

		$day = date( 'd', strtotime( $timestamp ) );
		$month = date( 'm', strtotime( $timestamp ) );
		$year = date( 'Y', strtotime( $timestamp ) );
		$hour = date( 'H', strtotime( $timestamp ) );
		$minute = date( 'i', strtotime( $timestamp ) );

		?>
		<div class="misc-pub-section curtime">
			<span id="timestamp"><?php _e( 'Unpublish', 'gtuk-unpublish-posts' ); ?>:</span>
			<span>
				<b>
					<?php
					if ( ! empty( $post_meta ) ) {
						$datef = __( 'M j, Y @ H:i' );
						echo date_i18n( $datef, strtotime( $timestamp ) );
					} else {
						_e( 'Never', 'gtuk-unpublish-posts' );
					}
					?>
				</b>
			</span>
			<a href="#edit_unpublish" class="edit-unpublish hide-if-no-js"><span aria-hidden="true"><?php _e( 'Edit', 'gtuk-unpublish-posts' ); ?></span> <span class="screen-reader-text"><?php _e( 'Edit unpublish date', 'gtuk-unpublish-posts' ); ?></span></a>
			<div id="gtuk-unpublish" class="hide-if-js">
				<div>
					<label for="jj" class="screen-reader-text"><?php _e( 'Day', 'gtuk-unpublish-posts' ); ?></label>
					<input type="text" id="jj" name="unpublish[day]" value="<?php echo $day; ?>" size="2" maxlength="2" autocomplete="off">
					<label for="mm" class="screen-reader-text"><?php _e( 'Month', 'gtuk-unpublish-posts' ); ?></label>
					<select id="mm" name="unpublish[month]">
						<?php foreach ( $monthList as $key => $currentMonth ) { ?>
							<option <?php echo ( $key == $month ? 'selected' : '' ) ?> value="<?php echo $key; ?>"><?php echo $currentMonth; ?></option>
						<?php } ?>
					</select>
					<label for="aa" class="screen-reader-text"><?php _e( 'Year', 'gtuk-unpublish-posts' ); ?></label>
					<input type="text" id="aa" name="unpublish[year]" value="<?php echo $year; ?>" size="4" maxlength="4" autocomplete="off">,
					<label for="hh" class="screen-reader-text"><?php _e( 'Hour', 'gtuk-unpublish-posts' ); ?></label>
					<input type="text" id="hh" name="unpublish[hour]" value="<?php echo $hour; ?>" size="2" maxlength="2" autocomplete="off"> :
					<label for="mn" class="screen-reader-text"><?php _e( 'Minute', 'gtuk-unpublish-posts' ); ?></label>
					<input type="text" id="mn" name="unpublish[minute]" value="<?php echo $minute; ?>" size="2" maxlength="2" autocomplete="off">
				</div>
				<div>
					<a class="gtuk-cancel-unpublish hide-if-no-js button-cancel" href="#edit_unpublish"><?php _e( 'Cancel', 'gtuk-unpublish-posts' ); ?></a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * If post is saved
	 *
	 * @param $post_id
	 */
	public function modify_post_content( $post_id ) {
		if ( null === $post_id ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( isset( $_POST['unpublish'] )
				&& ! empty( $_POST['unpublish']['day'] )
				&& ! empty( $_POST['unpublish']['month'] )
				&& ! empty( $_POST['unpublish']['year'] )
				&& ! empty( $_POST['unpublish']['hour'] )
				&& ! empty( $_POST['unpublish']['minute'] )
		) {
			$current_date = current_time( 'Y-m-d H:i:s' );
			$unpublish_date = $_POST['unpublish']['year'].'-'.$_POST['unpublish']['month'].'-'.$_POST['unpublish']['day'].' '.$_POST['unpublish']['hour'].':'.$_POST['unpublish']['minute'].':00';

			if ( $unpublish_date > $current_date ) {
				$timestamp = get_gmt_from_date( $unpublish_date, 'U' );
				update_post_meta( $post_id, '_unpublish_datetime', $unpublish_date );
				$this->schedule_unpublish( $post_id, $timestamp );
			} else {
				$this->unschedule_unpublish( $post_id );
				delete_post_meta( $post_id, '_unpublish_datetime' );
			}
		}
	}

	/**
	 * Unpublish post
	 *
	 * @param $post_id
	 */
	public function unpublish_post( $post_id ) {
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );

		delete_post_meta( $post_id, '_unpublish_datetime' );
	}

	/**
	 * Schedule event to unpublish post
	 *
	 * @param $post_id
	 * @param $timestamp
	 */
	private function schedule_unpublish( $post_id, $timestamp ) {
		$this->unschedule_unpublish( $post_id );
		wp_schedule_single_event( $timestamp, 'unpublish_post', array( $post_id ) );
	}

	/**
	 * Unschedule event to unpublish post
	 *
	 * @param $post_id
	 */
	private function unschedule_unpublish( $post_id ) {
		if ( wp_next_scheduled( 'unpublish_post', array( $post_id ) ) !== false ) {
			wp_clear_scheduled_hook( 'unpublish_post', array( $post_id ) );
		}
	}
}
