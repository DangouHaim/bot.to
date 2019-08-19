<?php $compose_enabled = c27()->get_setting( 'messages_enable_compose', true ) !== false; ?>
<div id="ml-messages-modal" class="modal modal-27" role="dialog">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
            <div class="sign-in-box">
                <div class="messaging-center" id="ml-message-btn">
                    <transition-group name="vopacity">
                        <compose v-if="chat.mode === 'compose'" :chat="chat" :key="'compose'"></compose>
                        <inbox v-else-if="chat.mode === 'inbox' || !chat.mode" :chat="chat" :key="'inbox'"></inbox>
                        <conversation v-else-if="chat.mode === 'conversation'" :chat="chat" :conversation="chat.conversation" :key="'conversation'"></conversation>
                    </transition-group>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/html" id="ml-opponent-list">
    <select name="test">
        <slot></slot>
    </select>
</script>
<script type="text/html" id="ml-compose-message">
    <div id="compose-message" class="compose-message">
        <div class="inbox-header">
            <a href="#" class="go-back-btn" @click.prevent="inbox"><i class="material-icons">arrow_back</i></a>
            <h4><?php esc_html_e('Compose', 'my-listing'); ?></h4>
            <div class="clearfix"></div>
        </div>

        <div class="compose-contents">
            <div class="select-user" v-if="!opponentId">
                <select2 :chat="chat" :options="options" v-model="selected" :url="url">
                    <option disabled value="0"><?php esc_html_e('Select one', 'my-listing'); ?></option>
                </select2>
            </div>
        </div>
    </div>
</script>

<script type="text/html" id="ml-conversation-messages">
    <ul class="messages-list">
        <li v-for="message in messages" :class="conversationClass( message )">
            <div class="chat-text">
                <span class="chat-date">{{ timestamp( message.utime ) }}</span>
                <a href="#" class="avatar-img" v-html="senderAvatar(message)"></a>
                <p v-html="linkify( message )"></p>
                <a href="#" class="delete-chat" @click.stop.prevent="deleteMsg(message)" v-if="!message.loading">
                    <i class="material-icons">delete_outline</i>
                </a>
            </div>

            <div class="chat-loader" v-if="message.loading"><i class="fa fa-refresh"></i></div>

            <div class="delete-confirm-overlay" v-if="isDelete(message)">
                <div class="action-controllers">
                    <a href="#" @click.stop.prevent="deleteMsg(message)">
                        <i class="material-icons">check</i> <?php esc_html_e('Yes', 'my-listing'); ?>
                    </a>
                    <a href="#" @click.stop.prevent="cancelDelete(message)">
                        <i class="material-icons">close</i> <?php esc_html_e('No', 'my-listing'); ?>
                    </a>
                </div>
            </div>
            <div class="clearfix"></div>
        </li>

        <li class="avatar-container" v-if="!isMessages">
            <?php esc_html_e( 'Say hello to', 'my-listing' ) ?> {{ opponent.name }}
            <a href="#" class="avatar-img" v-html="opponent.avatar"></a>
        </li>
    </ul>
</script>
<script type="text/html" id="ml-conversation">
    <div id="message-inbox-chat" class="message-inbox-chat">
        <div class="inbox-header">
            <a href="#" class="go-back-btn" @click.prevent="inbox()"><i class="material-icons">arrow_back</i></a>
            <div class="inbox-avatar">
                <transition-group name="vopacity">
                    <div class="avatar-container" v-if="!init" :key="'loaded-convo'">
                        <a href="#" class="avatar-img" v-html="opponent.avatar"></a>
                        <h6><a href="#">{{ opponent.name }}</a></h6>
                    </div>
                </transition-group>
            </div>
            <div class="inbox-actions">
                <a href="#" class="delete-chat" @click.prevent="deleteConversation( chat.opponentId )">
                    <i class="material-icons">delete_outline</i>
                </a>
            </div>
            <div class="clearfix"></div>
        </div>
        <div :class="{'inbox-chat-contents':true, '_loading': isLoading}">
            <transition name="vopacity">
                <div class="loading-more-messages" v-if="isLoading">
                    <div class="inner">
                        <i class="fa fa-refresh fa-spin"></i> <?php esc_html_e('Loading conversation', 'my-listing'); ?>
                    </div>
                </div>
            </transition>
            <messages :conversation="conversation" :opponent="opponent" :chat="chat"></messages>
            <div class="clearfix"></div>
            <form @submit.prevent="send">
                <textarea cols="30" :rows="rows" placeholder="<?php esc_html_e('Post a reply', 'my-listing'); ?>" v-model="message" :maxlength="maxLength" @keyup.enter="send($event)" :disabled="init" id="ml-conv-textarea"></textarea>
                <button class="btn" @click.stop.prevent="send" :disabled="disable"><i class="material-icons">send</i></button>
            </form>
            <div class="clearfix"></div>
        </div>

        <div class="delete-confirm-overlay" v-if="isDelete(opponent.id)">
            <div class="action-controllers">
                <a href="#" @click.stop.prevent="deleteConversation()">
                    <i class="material-icons">check</i> <?php esc_html_e('Yes', 'my-listing'); ?>
                </a>
                <a href="#" @click.stop.prevent="cancelDelete(message)">
                    <i class="material-icons">close</i> <?php esc_html_e('No', 'my-listing'); ?>
                </a>
            </div>
        </div>
    </div>
</script>

<script type="text/html" id="ml-inbox-messages">
    <ul>
        <li v-for="message in messageList" @click.prevent="open(message.data)" :class="{'unread-message': !message.seen}">
            <div class="inbox-avatar">
                <a href="#" v-html="message.data.op.avatar"></a>
            </div>
            <div class="message">
                <h6><a href="#">{{ opponentInfo( message.data ) }}</a></h6>
                <p>{{ message.data.message }}</p>
            </div>
            <div class="date-action">
                <p class="date">{{ timestamp( message.data.utime ) }}</p>
                <a href="#" class="action" @click.stop.prevent="deleteConversation(message.data, $event)"><i class="material-icons">
                    delete_outline
                </i></a>
            </div>
            <div class="delete-confirm-overlay" v-if="isDelete(message)">
                <div class="action-controllers">
                    <a href="#" @click.stop.prevent="deleteConversation(message.data)">
                        <i class="material-icons">check</i> <?php esc_html_e('Yes', 'my-listing'); ?>
                    </a>
                    <a href="#" @click.stop.prevent="cancelDelete(message)"><i class="material-icons">close</i> <?php esc_html_e('No', 'my-listing'); ?></a>
                </div>
            </div>
        </li>
    </ul>
</script>

<script type="text/html" id="ml-inbox">
    <div id="message-inbox" class="message-inbox">
        <div class="inbox-header">
            <h4><?php esc_html_e('Messages', 'my-listing'); ?></h4>
            <?php if ( $compose_enabled ): ?>
                <a href="#" class="compose-btn btn-primary" @click.prevent="compose"><?php esc_html_e('Compose', 'my-listing'); ?></a>
            <?php endif ?>
            <div class="clearfix"></div>
        </div>
        <div class="inbox-contents">
            <messages :chat="chat" :isLoading="isLoading" v-if="isMessages"></messages>
            <div class="inbox-contents empty-inbox" v-else>
                <p v-if="isLoading"><i class="fa fa-refresh"></i><?php esc_html_e('Loading Inbox', 'my-listing'); ?></p>
                <p v-else><?php esc_html_e('No messages available. To start a conversation, use compose button', 'my-listing'); ?></p>
                <div class="clearfix"></div>
            </div>
        </div>
        <?php if ( $compose_enabled ): ?>
            <a href="#" class="compose-btn compose-btn-mobile btn-primary" @click.prevent="compose"><?php esc_html_e('Compose', 'my-listing'); ?></a>
        <?php endif ?>
    </div>
</script>
