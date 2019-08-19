<?php

namespace MyListing\Ext\Messages;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use \Exception as Exception;

class Messages {
    use \MyListing\Src\Traits\Instantiatable;

    /**
     * The number of user return in a recipient search
     * @var integer
     */
    protected $recipient_search_limit = 50;

    /**
     * Load Messages on page scroll
     * @var boolean
     */
    protected $infinite_scroll = true;

    /**
     * Limit the number of messages to display on first load
     * @var integer
     */
    protected $onload_message_limit = 30;

    /**
     * Limit the number of messages to load on each scroll
     * @var integer
     */
    protected $onscroll_message_limit = 20;

    /**
     * Connection open long-polling time in seconds
     * 20 seconds are fair enough
     * @var array
     */
    protected $long_polling_time = 20;

    /**
     * Maximum character limit for a message
     * default 2000
     *
     * @var integer
     */
    protected $max_char_limit = 2000;

    /**
     * Javascript message delay in miliseconds
     * The send button will be disabled for miliseconds
     * default = 1 second
     * @var integer
     */
    protected $js_send_delay = 1000;

    /**
     * Limit the number of messages in a time span
     * default 30 seconds
     *
     * @var integer
     */
    protected $max_messages_duration = 30;

    /**
     * Disable user if the sent messages are higher
     * than this limit during the max_messages_duration time
     *
     * @var integer
     */
    protected $max_messages_limit = 10;

    /**
     * Lock user if the maximum messages limit is reached
     * default 60 seconds
     *
     * @var integer
     */
    protected $user_lock_time = 60;

    /**
     * Only trigger email notifications after a set time has
     * passed since the last message.
     *
     * @var integer delay in seconds
     */
    protected $notification_send_delay = 30 * MINUTE_IN_SECONDS;

    /**
     * Stores the message ID after it's been stored in database.
     * @var integer
     */
    protected $inserted_message_id = null;

    /**
     * Message sender information
     * @var array
     */
    protected $sender = [];

    /**
     * Message Receiver Information
     * @var array
     */
    protected $receiver = [];

    /**
     * User Message
     * @var string
     */
    protected $message = null;

    /**
     * Is sender blocked by receiver
     * @var boolen
     */
    protected $is_sender_blocked = false;

    /**
     * Message Table Name
     * @var string
     */
    private $_message_table = null;

    /**
     * Database version option name
     * @var string
     */
    private $_db_option_name = 'mylisting_msg_db_version';
    /**
     * Current database version
     * @var int
     */
    private $_database_version = '1.0';

    /**
     * Constructor to bind call events
     */
    public function __construct() {
        // setup ACF settings page
        add_action( 'mylisting/init', [ $this, 'setup_options_page' ] );

        // Do nothing if the message system is disabled
        if ( c27()->get_setting( 'messages_enabled', true ) === false ) {
            return;
        }

        $this->max_char_limit = absint( c27()->get_setting( 'messages_max_length', 2000 ) ) ?: 2000;

        // Set table name
        $this->_message_table = $GLOBALS['wpdb']->prefix . 'mylisting_messages';

        $db_version = get_option( $this->_db_option_name );

        if ( $db_version != $this->_database_version ) {
            $this->_upgrade_database();
        }

        // WordPress AJAX hooks
        // Sync Messages
        add_action('wp_ajax_mylisting_sync_messages', [&$this, 'sync_activities'] );

        // send message
        add_action('wp_ajax_mylisting_send_message', [&$this, 'send_message'] );

        // delete message
        add_action('wp_ajax_mylisting_delete_message', [&$this, 'delete_message']);

        // read conversation
        add_action('wp_ajax_mylisting_read_conversation', [&$this, 'read_conversation']);

        // Delete Conversation
        add_action('wp_ajax_mylisting_delete_conversation', [&$this, 'delete_conversation']);

        // Recipient List
        add_action('wp_ajax_mylisting_recipients', [&$this, 'get_recipients_list']);

        // Archive the user display name if the account is deleted
        add_action('delete_user', [&$this, 'archive_user_display_name']);

        // Do not load script files if the user is not logged in
        if ( ! is_user_logged_in() ) {
            return null;
        }

        // Enqueue Assets
        add_action('wp_enqueue_scripts', [&$this, 'enqueue_scripts'], 501);
        add_action( 'mylisting/get-footer', [ $this, 'load_templates' ] );
    }

    public function __get( $name ) {
        if ( property_exists( $this, $name ) ) {
            return $this->{ $name };
        }

        throw new
            Exception(
                esc_html__(
                    sprintf('The property %s is not accessible.', $name ),
                'my-listing'),
            1 );
    }

    /**
     * Return the list of available recipients based on AJAX request
     * @return void
     */
    public function get_recipients_list() {
        global $wpdb;

        // Fetch the list of users starting from the POST request
        try {
            $this->_is_user_logged_in();
        } catch ( Exception $error ) {
            wp_send_json_error( $error->getMessage() );
        }

        // @TODO: nonce verification
        // We need atleast one character to search the user
        $search_query = "SELECT a.ID, a.user_nicename, a.display_name, b.meta_value as block_list
                            FROM {$wpdb->users} a LEFT join {$wpdb->usermeta} b
                            ON a.ID = b.user_id AND b.meta_key = '_ml_user_block_list'";

        if ( ! empty( $_POST['term'] ) ) {
            $search_term = $wpdb->esc_like( wp_kses_data( $_POST['term'] ) )  . '%';
            $search_query .= $wpdb->prepare(
                " WHERE ( a.user_login LIKE %s OR a.user_nicename LIKE %s OR a.display_name LIKE %s )",
                    $search_term,
                    $search_term,
                    $search_term
                );
        } elseif ( ! empty( $_POST['user_id'] ) ) {
            $search_query .= $wpdb->prepare( ' WHERE a.ID = %d ', absint( $_POST['user_id'] ) );
        }

        $offset = 0;
        if ( isset( $_POST['offset'] ) && intval( $_POST['offset'] ) ) {
            $offset = absint( $_POST['offset'] );
        }

        $search_query .= " LIMIT $offset, $this->recipient_search_limit";
        $users = $wpdb->get_results( $search_query );

        if ( ! $users ) {
            wp_send_json_success([]);
        }

        $return_list = [];

        foreach ( $users as $user ) {
            $user_block_list = maybe_unserialize( $user->block_list );

            // Do not include user if blocked by receipent or self
            if ( $user->ID == $this->sender->ID || $user_block_list && in_array( $this->sender->ID, (array) $user_block_list ) ) {
                continue;
            }

            $return_list[ $user->ID ] = [
                'id'    => $user->ID,
                'name'  => wp_specialchars_decode( $user->display_name ? $user->display_name : $user->user_nicename ),
                'avatar'=> get_avatar( $user->ID ) ?: '',
            ];
        }

        wp_send_json_success( $return_list );
    }

    /**
     * Sync recent activities
     * @return string
     */
    public function sync_activities() {
        // @TODO: nonce verification
        foreach( ['st', 'et'] as $time ) {
            $$time = 0;
            if ( isset( $_REQUEST[ $time ] ) && is_numeric( $_REQUEST[ $time ] ) && $this->is_valid_timestamp( $_REQUEST[ $time ] ) ) {
                $$time = date('Y-m-d H:i:s', $_REQUEST[ $time ]);
            }
        }

        // Mark all messages are read if the opponent id is opened on client end
        if ( ! empty( $_POST['opponent_id'] ) ) {
            $this->mark_as_seen();
        }

        // Long-polling to avoid too many requests to server
        // 20 seconds are fair enough for one request
        @set_time_limit(0);
        $end_time = strtotime("+{$this->long_polling_time} seconds");
        while( time() < $end_time ) {
            // @TODO: Flush messages and keep the connection open
            $messages = $this->read_messages( $st );

            // Send instant reply if this is first request
            if ( $messages['ml'] || ! $st ) {
                wp_send_json_success( $messages );
            }

            sleep(2);
        }

        wp_send_json_success( $messages );
    }

    /**
     * Read messages for current user
     *
     * @return [type] [description]
     */
    public function read_messages( $start_time = 0 ) {
        try {
            $this->_is_user_logged_in();
            return Reply::read_messages( get_current_user_id(), $start_time );
        } catch ( Exception $error ) {
            wp_send_json_error( $error->getMessage() );
        }
    }

    public function read_conversation() {
        try {
            $this->_is_user_logged_in();
            $opponent_id = $this->get_opponent_id();
            $offset = $this->get_offset_value();

            $messages = Reply::read_conversation([
                'opponent_id' => $opponent_id,
                'offset'      => $offset
            ]);

        } catch ( Exception $error ) {
            wp_send_json_error( $error->getMessage() );
        }

        if ( isset( $_POST['mark_all_read'] ) && $_POST['mark_all_read'] ) {
            $this->mark_as_seen();
        }

        // Prepare output
        wp_send_json_success( $messages );
    }

    public function delete_message() {
        // Nonce verification
        $invalid_request = esc_html__('You do not have permission to delete this message', 'my-listing');

        // User have permission to delete this message
        if ( ! isset( $_POST['message_id'] ) || ! intval( $_POST['message_id'] ) || ! isset( $_POST['seckey'] )
            || ! wp_verify_nonce( $_POST['seckey'], "delete-message-{$_POST['message_id']}" ) ) {
            wp_send_json_error( $invalid_request );
        }

        try {
            Reply::delete_message( $_POST['message_id'] );
        } catch ( Exception $error ) {
            wp_send_json_error( $error->getMessage() );
        }

        wp_send_json_success( true );
    }

    public function delete_conversation() {
        try {
            $opponent_id = $this->get_opponent_id();
            // User have permission to delete this message
            if ( ! isset( $_POST['seckey'] ) || ! wp_verify_nonce( $_POST['seckey'], "delete-conversation-{$opponent_id}" ) ) {
                throw new Exception(
                    esc_html__('You do not have permission to delete this conversation.', 'my-listing')
                );
            }

            Reply::delete_conversation( $opponent_id );
        } catch ( Exception $error ) {
            wp_send_json_error( $error->getMessage() );
        }

        wp_send_json_success( true );
    }

    /**
     * Publish new message
     * @return [type] [description]
     */
    public function send_message() {
        try {
            // Validate form entries
            $this->_validate_form();
        } catch ( Exception $error ) {
            wp_send_json_error( $error->getMessage() );
        }

        $this->_max_messages_limit();
        $this->_write_message();
        $this->_send_alert();

        wp_send_json_success( true );
    }

    // public function mark_all_seen() {
    //     try {
    //         $this->_is_user_logged_in();
    //         Reply::mark_all_seen();
    //     } catch ( Exception $error ) {
    //         wp_send_json_error( $error->getMessage() );
    //     }

    //     wp_send_json_success( true );
    // }

    public function mark_as_seen() {
        try {

            $this->_is_user_logged_in();
            $opponent_id = $this->get_opponent_id();

            Reply::mark_as_seen( $opponent_id );

        } catch ( Exception $error ) {
            wp_send_json_error( $error->getMessage() );
        }

        return true;
    }

    public function archive_user_display_name( $user_id ) {
        global $wpdb;

        $user = get_user_by( 'id', $user_id );

        $user_data = maybe_serialize([
            'ID'            => $user_id,
            'display_name'  => esc_sql( $user->display_name ),
            'email'         => esc_sql( $user->user_email )
        ]);

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->_message_table} SET
                    receiver_data = CASE WHEN receiver_id = %d THEN %s ELSE receiver_data END,
                    sender_data = CASE WHEN sender_id = %d THEN %s ELSE sender_data END
                    WHERE sender_id = %d OR receiver_id = %d
                ",

                $user_id,
                $user_data,
                $user_id,
                $user_data,
                $user_id,
                $user_id
            )
        );
    }

    public function get_table_name() {
        return $this->_message_table;
    }

    public function get_execution_time() {
        $max_execution_time = @ini_get('max_execution_time');
        if ( ! $max_execution_time || ! intval( $max_execution_time ) || $max_execution_time > $this->long_polling_time ) {
            $max_execution_time = $this->long_polling_time;
        }

        return $max_execution_time;
    }

    public function is_valid_timestamp( $timestamp ) {
        return ctype_digit( $timestamp ) && strlen( $timestamp ) >= 10 && strtotime( date('Y-m-d H:i:s', $timestamp) ) === (int) $timestamp;
    }

    /**
     * Enqueue Resources
     * @return void
     */
    public function enqueue_scripts() {
        $current_user = wp_get_current_user();
        $suffix = is_rtl() ? '-rtl' : '';
        wp_enqueue_script( 'vuejs' ); // @todo: load conditionally, only after clicking on the messages icon
        wp_enqueue_style( 'mylisting-messages', get_template_directory_uri() . '/assets/dist/messages'.$suffix.'.css', ['mylisting-frontend'], CASE27_THEME_VERSION );
        wp_enqueue_script( 'mylisting-messages', get_template_directory_uri() . '/assets/dist/messages.js', ['jquery'], CASE27_THEME_VERSION, true );
        // wp_enqueue_script('mylisting-messages', get_template_directory_uri() . '/includes/extensions/messages/assets/build.js', ['jquery'], 1.0, true);
        wp_localize_script('mylisting-messages', 'ml_msg', [
            'sd'        => $this->js_send_delay, // disable send button after each hit
            'bt'        => $this->user_lock_time, // User block time
            'mcl'       => $this->max_char_limit, // maximum character limit for a message
            'smn'       => wp_create_nonce('mylisting-message'),
            'strings'   => [
                'selectUser'    => esc_html_x('Select a User', 'mylisting messages system', 'my-listing'),
                'notAvailable'  => esc_html_x('User not available.', 'mylisting messages system', 'my-listing'),
            ],
            // Current User Info
            'cu' => [
                'id'    => $current_user->ID,
                'name'  => wp_specialchars_decode( $current_user->display_name ),
                'avatar'=> get_avatar( $current_user->ID ) ?: '',
            ],
        ]);
    }

    public function load_templates() {
        if ( is_user_logged_in() ) {
            require locate_template( 'includes/extensions/messages/views/messages.php' );
        }
    }

    protected function get_opponent_id() {
        if ( ! isset( $_POST['opponent_id'] ) || ! intval( $_POST['opponent_id'] ) ) {
            throw new Exception(
                esc_html__('Invalid request, please try again.', 'my-listing')
            );
        }

        return absint( $_POST['opponent_id'] );
    }

    protected function get_offset_value() {
        $offset = 0;

        if ( isset( $_POST['offset'] ) && intval( $_POST['offset'] ) ) {
            $offset = absint( $_POST['offset'] );
        }

        return $offset;
    }

    private function _validate_form() {
        $this->_is_user_logged_in();
        $this->_is_valid_request();
        $this->_is_valid_message();

        $this->_is_self_messaging();
        $this->_is_sender_blocked();
    }

    private function _is_user_logged_in() {
        if ( ! is_user_logged_in() ) {
            throw new Exception(
                esc_html__('You must login to read messages.', 'my-listing')
                , 1
            );
        }

        $this->sender = wp_get_current_user();
    }

    private function _is_valid_request() {
        // Validate form entries
        if ( ! isset( $_POST['receiver_id'] ) || ! intval( $_POST['receiver_id'] ) || ! isset( $_POST['seckey'] ) || ! wp_verify_nonce( $_POST['seckey'], 'mylisting-message' ) ) {
            throw new Exception(
                esc_html__('Invalid request, please refresh page and try again.', 'my-listing'),
            1 );
        }

        $this->receiver = \get_user_by( 'id', $_POST['receiver_id'] );

        if ( ! $this->receiver ) {
            throw new Exception(
                esc_html__('The receiver is no longer available.', 'my-listing'),
            1);
        }
    }

    private function _is_valid_message() {
        $message = null;
        if ( isset( $_POST['message'] ) ) {
            $message = trim( $_POST['message'] );
        }

        if ( ! $message ) {
            throw new Exception(
                esc_html__('The message field is required.', 'my-listing'),
            1);
        }

        $this->message = substr( wp_kses_post( $_POST['message'] ), 0, $this->max_char_limit );
    }

    private function _is_self_messaging() {
        if ( $this->sender->ID == $this->receiver->ID ) {
            throw new Exception(
                esc_html__('You can\'t message this user.', 'my-listing'),
            1);
        }
    }

    private function _is_sender_blocked() {
        $receiver_block_list = get_user_meta( $this->receiver->ID, '_ml_user_block_list', true );

        if ( ! $receiver_block_list ) {
            $receiver_block_list = [];
        }

        $this->is_sender_blocked = in_array( $this->sender->ID, $receiver_block_list );
    }

    private function _max_messages_limit() {
        $new_message_data = [
            'st' => time(), // start time
            'et' => time() + $this->max_messages_duration, // end time
            'mc' => 0 // message counts
        ];

        $message_data = get_user_meta( $this->sender->ID, '_ml_message_data', true );
        if ( ! $message_data || $message_data['et'] < time() ) {
            $message_data = [];
        }

        $message_data = array_merge( $new_message_data, $message_data );
        if ( $message_data['mc'] > $this->max_messages_limit ) {
            wp_send_json_error(
                sprintf(
                    esc_html__(
                        'You are sending messages too fast and blocked for %d seconds, please slow down your speed to avoid permanent block.',
                        'my-listing'
                    ),

                    $this->user_lock_time
                )
            );
        }

        $message_data['mc'] = $message_data['mc'] + 1;
        if ( ! add_user_meta( $this->sender->ID, '_ml_message_data', $message_data, true ) ) {
            update_user_meta( $this->sender->ID, '_ml_message_data', $message_data );
        }
    }

    private function _write_message() {
        global $wpdb;

        $message_data = [
            'sender_id'     => $this->sender->ID,
            'receiver_id'   => $this->receiver->ID,
            'message'       => $this->message
        ];

        $message_format = ['%d', '%d', '%s'];

        // Do not display this message to receiver if the sender is blocked
        if ( $this->is_sender_blocked ) {
            $message_data['receiver_status'] = 0;
            $message_format[] = '%d';
        }

        $wpdb->insert(
            $this->_message_table,
            $message_data,
            $message_format
        );

        $this->inserted_message_id = $wpdb->insert_id;
    }

    private function _send_alert() {
        // skip alerts if the sender was blocked by the receiver
        if ( $this->is_sender_blocked ) {
            return null;
        }

        global $wpdb;

        /**
         * Check if another message has been sent in the last 30 minutes ($this->notification_send_delay).
         * If not, then an email notification should be sent.
         */
        $previous_message = $wpdb->get_row( $wpdb->prepare( "
            SELECT a.message_id FROM {$this->_message_table} a
                WHERE message_id != %d
                AND sender_id = %d AND receiver_id = %d
                AND sender_delete_status = 0 AND receiver_delete_status = 0
                AND send_time > DATE_SUB( CURRENT_TIMESTAMP, INTERVAL %d SECOND )
                LIMIT 1
        ", $this->inserted_message_id, $this->sender->ID, $this->receiver->ID, $this->notification_send_delay ) );

        if ( $previous_message === null ) {
            do_action( 'mylisting/messages/send-notification', $this );
        }
    }

    private function _upgrade_database() {
        global $wpdb;

        $table_name = $this->get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "
            CREATE TABLE IF NOT EXISTS $table_name (
              message_id bigint(20) NOT NULL AUTO_INCREMENT,
              sender_id bigint(20) NOT NULL,
              sender_data varchar(300) NOT NULL,
              sender_delete_status tinyint(1) NOT NULL,
              receiver_id bigint(20) NOT NULL,
              receiver_data varchar(300) NOT NULL,
              receiver_delete_status tinyint(1) NOT NULL,
              seen tinyint(1) NOT NULL DEFAULT '0',
              send_time timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              message text NOT NULL,
              PRIMARY KEY ( message_id )
            ) $charset_collate;
        ";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        update_option( $this->_db_option_name, $this->_database_version );
    }

    /**
     * Setup message options page in WP Admin > Theme Options > Direct Messages.
     *
     * @since 2.0
     */
    public function setup_options_page() {
        acf_add_options_sub_page( [
            'page_title'    => _x( 'Direct Messages', 'Direct Messages page title in WP Admin', 'my-listing' ),
            'menu_title'    => _x( 'Direct Messages', 'Direct Messages menu title in WP Admin', 'my-listing' ),
            'menu_slug'     => 'theme-messages-settings',
            'capability'    => 'manage_options',
            'redirect'      => false,
            'parent_slug'   => 'case27/tools.php',
        ] );
    }
}
