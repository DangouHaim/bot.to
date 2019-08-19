<?php

namespace MyListing\Ext\Messages;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Reply {
    use \MyListing\Src\Traits\Instantiatable;

    /**
     * Sender and receivers lists
     * @var array
     */
    static $users_list = [];

    /**
     * Message sender information
     * @var object
     */
    protected $sender = [];

    /**
     * Message receiver information
     * @var object
     */
    protected $receiver = [];

    /**
     * Current user data
     * @var object
     */
    protected $current_user = [];

    /**
     * Current user id
     * @var integer
     */
    protected $current_user_id = 0;
    /**
     * Weather the current user is sender or receiver
     * @var boolen|string
     */
    protected $current_user_role = false;

    /**
     * Current message data
     * @var object
     */
    private $_message = [];

    /**
     * Message table name
     * @var string
     */
    private $_message_table = '';

    /**
     * Constructor to bind call events
     */
    public function __construct( $message ) {
        if ( ! is_object( $message ) && ! intval( $message ) ) {
            throw new \Exception(
                esc_html__('Invalid message', 'my-listing')
            );
        }

        $this->_message_table = Reply::get_table_name();
        $this->_message = $message;

        // Fetch message data
        $this->_fetch();

        // Fetch current user information
        $this->current_user_id = get_current_user_id();

        // Set current user role
        $this->_set_current_user_role();

        $this->current_user = $this->_normalize_user_data( $this->current_user_id );

        // Normalize Users data
        $this->sender = $this->_normalize_user_data( $this->_message->sender_id );
        $this->receiver = $this->_normalize_user_data( $this->_message->receiver_id );

        return $this;
    }

    public function __get( $name ) {
        switch( $name ) {
            case 'sender_id' :
                return $this->_message->sender_id;
            break;

            case 'receiver_id' :
                return $this->_message->receiver_id;
            break;

            default :

                if ( isset( $this->_message->{$name} ) ) {
                    return $this->_message->{$name};
                }

                throw new \Exception(
                    sprintf(
                        esc_html__('The property %s is not accessible.', 'my-listing')
                    )
                );
            break;
        }
    }

    public function set_sender_data() {
        $sender_user = get_user_by( 'id');
    }

    protected function _delete() {
        global $wpdb;

        // Authorization Test
        if ( ! $this->current_user_role ) {
            throw new Exception(
                esc_html__('You are not authorized to delete this message.', 'my-listing')
            );
        }

        // Do nothing if already deleted for this user
        $delete_column = $this->current_user_role . '_delete_status';
        if ( $this->{ $delete_column } ) {
            return true;
        }

        // Delete message permanently if deleted for both users
        $toggle_user_role = ( 'sender' == $this->current_user_role ) ? 'receiver' : 'sender';
        $deleted_for_all = $this->{$toggle_user_role . '_delete_status'};

        if ( $deleted_for_all ) {
            return $wpdb->delete( $this->_message_table, ['message_id' => $this->message_id], ['%d'] );
        }

        return $wpdb->update( $this->_message_table, [ $delete_column => '1' ], [ 'message_id' => $this->message_id ] );
    }

    private function _fetch() {
        global $wpdb;

        if ( is_object( $this->_message ) ) {
            return true;
        }

        $this->_message = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT message_id, sender_id, sender_data, sender_delete_status,
                        receiver_id, receiver_data, receiver_delete_status, seen,
                        send_time, CONVERT_TZ( send_time, @@session.time_zone, '+00:00' ) AS `utc_datetime`, message
                        FROM {$this->_message_table} WHERE message_id = %d",
                $this->_message
            )
        );

        if ( ! $this->_message ) {
            throw new \Exception(
                esc_html__('The message is no longer available.', 'my-listing')
            );
        }
    }

    private function _normalize_user_data( $user_id ) {
        // Fetch user only if it's not available in cache
        if ( ! isset( Reply::$users_list[ $user_id ] ) ) {
            $user = get_user_by( 'id', $user_id );

            if ( ! $user ) {

                $user_role = 'sender';

                if ( $this->_message->receiver_id === $user_id ) {
                    $user_role = 'receiver';
                }

                $user = (object) apply_filters('mylisting/messages/deleted_user',
                    wp_parse_args(
                        maybe_unserialize( $this->_message->{$user_role . '_data'} ),
                        [
                            'ID'           => $user_id,
                            'display_name' => esc_html__('USER DELETED', 'my-listing'),
                            'email'        => false
                        ]
                    )
                );
            }

            Reply::$users_list[ $user_id ] = $user;
        }

        return Reply::$users_list[ $user_id ];
    }

    private function _set_current_user_role() {
        if ( $this->sender_id != $this->current_user_id && $this->receiver_id != $this->current_user_id ) {
            return false;
        }

        $this->current_user_role = ( $this->sender_id == $this->current_user_id ) ? 'sender' : 'receiver';
    }

    public static function delete_message( $message_id ) {
        return ( new Reply( $message_id ) )->_delete();
    }

    public static function delete_conversation( $opponent_id, $current_user_id = 0 ) {
        global $wpdb;

        if ( ! $current_user_id ) {
            $current_user_id = get_current_user_id();
        }

        $table_name = Reply::get_table_name();

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table_name} SET
                    receiver_delete_status = CASE WHEN receiver_id = %d THEN 1 ELSE receiver_delete_status END,
                    sender_delete_status = CASE WHEN sender_id = %d THEN 1 ELSE sender_delete_status END
                    WHERE ( receiver_id = %d AND sender_id = %d ) OR ( sender_id = %d AND receiver_id = %d )
                ",

                $current_user_id,
                $current_user_id,
                $current_user_id,
                $opponent_id,
                $current_user_id,
                $opponent_id
            )
        );

        // DELETE GARBAGE
        return $wpdb->query(
            "DELETE FROM {$table_name} WHERE sender_delete_status = 1 AND receiver_delete_status = 1"
        );
    }

    public static function unread_messages( $user_id, $time = '' ) {
        global $wpdb;

        $table_name = Reply::get_table_name();

        $messages = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT message_id, sender_id, sender_data, sender_delete_status,
                        receiver_id, receiver_data, receiver_delete_status, seen,
                        send_time, CONVERT_TZ( send_time, @@session.time_zone, '+00:00' ) AS `utc_datetime`, message
                        FROM {$table_name} WHERE receiver_id = %d AND seen = 0
                        ORDER BY seen ASC, send_time DESC",
                    $user_id
                )
            );

        return Reply::_normalize_messages( $messages );
    }

    public static function read_messages( $user_id, $start_time = null ) {
        global $wpdb;

        $table_name = Reply::get_table_name();

        $time_query = '';
        if ( $start_time ) {
            $time_query .= " AND send_time > %s";
        }

        $query_args = [
            "SELECT a.message_id, a.sender_id, a.sender_data, a.sender_delete_status,
                        a.receiver_id, a.receiver_data, a.receiver_delete_status, a.seen,
                        a.send_time, CONVERT_TZ( send_time, @@session.time_zone, '+00:00' ) AS `utc_datetime`, a.message
                    FROM {$table_name} a
                    INNER JOIN (
                        SELECT MAX( message_id ) as message_id
                            FROM {$table_name}
                            WHERE ( sender_id = %d OR receiver_id = %d ) AND
                            CASE
                                WHEN receiver_id = %d THEN receiver_delete_status=0
                                WHEN sender_id = %d THEN sender_delete_status=0
                                ELSE TRUE
                            END {$time_query}
                            GROUP BY LEAST( sender_id, receiver_id ), GREATEST( sender_id, receiver_id )

                    ) b on a.message_id = b.message_id
                    ORDER BY seen ASC, send_time DESC",
            $user_id,
            $user_id,
            $user_id,
            $user_id,
        ];

        if ( $time_query ) {
            $query_args[] = $start_time;
        }

        $messages = $wpdb->get_results(
            call_user_func_array([$wpdb, 'prepare'], $query_args)
        );

        // @TODO: multiple ajax requests to load more messages - if
        // in the worst case the messages are more than 500
        return Reply::_normalize_messages( $messages );
    }

    public static function read_conversation( $args ) {
        global $wpdb;

        // Normalize Query data
        $args = apply_filters('mylisting/messages/read_conversation', wp_parse_args( $args, [
            'opponent_id'       => 0,
            'current_user_id'   => get_current_user_id(),
            'offset'            => 0,
            'onload_limit'      => \MyListing\Ext\Messages\Messages::instance()->onload_message_limit,
            'onscroll_limit'    => \MyListing\Ext\Messages\Messages::instance()->onscroll_message_limit
        ]) );

        $load_message_limit = $args['onload_limit'];

        if ( intval( $args['offset'] ) ) {
            $offset = absint( $args['offset'] );
            $load_message_limit = $args['onscroll_limit'];
        }

        $table_name = Reply::get_table_name();

        $messages = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT message_id, sender_id, sender_data, sender_delete_status,
                        receiver_id, receiver_data, receiver_delete_status, seen,
                        send_time, CONVERT_TZ( send_time, @@session.time_zone, '+00:00' ) AS `utc_datetime`, message
                        FROM {$table_name} WHERE
                    ( sender_id = %d AND receiver_id = %d AND sender_delete_status = 0 )
                    OR ( receiver_id = %d AND sender_id = %d AND receiver_delete_status = 0 )
                    ORDER BY message_id DESC
                    LIMIT %d, %d",
                $args['current_user_id'],
                $args['opponent_id'],
                $args['current_user_id'],
                $args['opponent_id'],
                $args['offset'],
                $load_message_limit
            )
        );

        return Reply::_normalize_messages( $messages );
    }

    public static function mark_all_seen( $user_id = 0 ) {
        global $wpdb;

        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }

        $table_name = Reply::get_table_name();
        return $wpdb->update(
            $table_name,
            ['seen' => 1],
            ['receiver_id' => $user_id],
            ['%d'],
            ['%d']
        );
    }

    public static function mark_as_seen( $opponent_id, $user_id = 0 ) {
        global $wpdb;

        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }

        $table_name = Reply::get_table_name();
        return $wpdb->update(
            $table_name,
            [ 'seen' => 1 ],
            [ 'sender_id' => $opponent_id, 'receiver_id' => $user_id ],
            ['%d', '%d'],
            ['%d']
        );
    }

    public static function get_table_name() {
        return \MyListing\Ext\Messages\Messages::instance()->get_table_name();
    }

    private static function _normalize_messages( $messages ) {
        // All messages are within a time-frame so take the first and
        // last message time for this range
        $messages_list = [
            'st' => 0, // start time
            'et' => 0, // end time
            'ml' => [] // messages list
        ];

        foreach ( $messages as $index => $message ) {
            $this_message = new Reply( $message );
            $opponent = 'receiver' === $this_message->current_user_role
                            ? $this_message->sender
                            : $this_message->receiver;

            $messages_list['ml'][ $this_message->message_id ] = [
                'id' => $this_message->message_id,
                // Opponent User Info
                'op' => [
                    'id'    => $opponent->ID,
                    'name'  => wp_specialchars_decode( $opponent->display_name ),
                    'avatar'=> get_avatar( $opponent->ID ) ?: '',
                ],
                'sender' => $this_message->sender_id,
                'seen'   => $this_message->seen,
                'utime'  => strtotime($this_message->utc_datetime),
                // 'utime'  => strtotime($this_message->send_time), // Message time
                //'time' => $this_message->send_time,
                'message' => wp_kses( stripslashes( $this_message->message ), ['br', 'a'] ),

                // Nonce
                'dm'      => wp_create_nonce("delete-message-{$this_message->message_id}"), // Delete Message
                'dcn'     => wp_create_nonce("delete-conversation-{$opponent->ID}"), // Delete Conversation
            ];
        }

        if ( $messages ) {
            $messages_list['st'] = strtotime( $messages[0]->send_time );

            // Last message time - same if there is only one message
            end( $messages );
            $messages_list['et'] = strtotime( current( $messages )->send_time );
        }

        return $messages_list;
    }

}
