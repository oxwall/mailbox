<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * @author Zarif Safiullin <zaph.work@gmail.com>
 * @package ow.ow_plugins.mailbox.components
 * @since 1.6.1
 */
class MAILBOX_CMP_Toolbar extends OW_Component
{
    private $useChat;

    public function __construct()
    {
        parent::__construct();

        $handlerAttributes = OW::getRequestHandler()->getHandlerAttributes();
        $event = new OW_Event('plugin.mailbox.on_plugin_init.handle_controller_attributes', array('handlerAttributes'=>$handlerAttributes));
        OW::getEventManager()->trigger($event);

        $handleResult = $event->getData();

        if ($handleResult === false)
        {
            $this->setVisible(false);
        }

        if ( !OW::getUser()->isAuthenticated() )
        {
            $this->setVisible(false);
        }
        else
        {
            if ( !BOL_UserService::getInstance()->isApproved() && OW::getConfig()->getValue('base', 'mandatory_user_approve') )
            {
                $this->setVisible(false);
            }

            $user = OW::getUser()->getUserObject();

            if (BOL_UserService::getInstance()->isSuspended($user->getId()))
            {
                $this->setVisible(false);
            }

            if ( (int) $user->emailVerify === 0 && OW::getConfig()->getValue('base', 'confirm_email') )
            {
                $this->setVisible(false);
            }

            $this->useChat = BOL_AuthorizationService::STATUS_AVAILABLE;

            $this->assign('useChat', $this->useChat);
            $this->assign('msg', '');
        }
    }

    public function render()
    {
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin("base")->getStaticJsUrl() . "jquery-ui.min.js");
        OW::getDocument()->addScript( OW::getPluginManager()->getPlugin('base')->getStaticJsUrl().'underscore-min.js', 'text/javascript', 3000 );
        OW::getDocument()->addScript( OW::getPluginManager()->getPlugin('base')->getStaticJsUrl().'backbone-min.js', 'text/javascript', 3000 );

        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('mailbox')->getStaticJsUrl() . 'audio-player.js');
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('mailbox')->getStaticJsUrl() . 'mailbox.js', 'text/javascript', 3000);
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('mailbox')->getStaticJsUrl() . 'contactmanager.js', 'text/javascript', 3001);

        OW::getDocument()->addStyleSheet( OW::getPluginManager()->getPlugin('mailbox')->getStaticCssUrl().'mailbox.css' );

        $conversationService = MAILBOX_BOL_ConversationService::getInstance();
        $userId = OW::getUser()->getId();
        $displayName = BOL_UserService::getInstance()->getDisplayName($userId);
        $avatarUrl = BOL_AvatarService::getInstance()->getAvatarUrl($userId);
        if ( empty($avatarUrl) )
        {
            $avatarUrl = BOL_AvatarService::getInstance()->getDefaultAvatarUrl();
        }
        $profileUrl = BOL_UserService::getInstance()->getUserUrl($userId);

        $jsGenerator = UTIL_JsGenerator::newInstance();
        $jsGenerator->setVariable('OWMailbox.documentTitle', OW::getDocument()->getTitle());
        $jsGenerator->setVariable('OWMailbox.soundEnabled', (bool) BOL_PreferenceService::getInstance()->getPreferenceValue('mailbox_user_settings_enable_sound', $userId));
        $jsGenerator->setVariable('OWMailbox.showOnlineOnly', (bool) BOL_PreferenceService::getInstance()->getPreferenceValue('mailbox_user_settings_show_online_only', $userId));
        $jsGenerator->setVariable('OWMailbox.showAllMembersMode', (bool)OW::getConfig()->getValue('mailbox', 'show_all_members') );
        $jsGenerator->setVariable('OWMailbox.soundSwfUrl', OW::getPluginManager()->getPlugin('mailbox')->getStaticUrl() . 'js/player.swf');
        $jsGenerator->setVariable('OWMailbox.soundUrl', OW::getPluginManager()->getPlugin('mailbox')->getStaticUrl() . 'sound/receive.mp3');
        $jsGenerator->setVariable('OWMailbox.defaultAvatarUrl', BOL_AvatarService::getInstance()->getDefaultAvatarUrl());
        $jsGenerator->setVariable('OWMailbox.serverTimezoneOffset', date('Z') / 3600);
        $jsGenerator->setVariable('OWMailbox.useMilitaryTime', (bool) OW::getConfig()->getValue('base', 'military_time'));
        $jsGenerator->setVariable('OWMailbox.getHistoryResponderUrl', OW::getRouter()->urlFor('MAILBOX_CTRL_Ajax', 'getHistory'));
        $jsGenerator->setVariable('OWMailbox.openDialogResponderUrl', OW::getRouter()->urlFor('MAILBOX_CTRL_Ajax', 'updateUserInfo'));
        $jsGenerator->setVariable('OWMailbox.attachmentsSubmitUrl', OW::getRouter()->urlFor('BASE_CTRL_Attachment', 'addFile'));
        $jsGenerator->setVariable('OWMailbox.attachmentsDeleteUrl',  OW::getRouter()->urlFor('BASE_CTRL_Attachment', 'deleteFile'));
        $jsGenerator->setVariable('OWMailbox.authorizationResponderUrl',  OW::getRouter()->urlFor('MAILBOX_CTRL_Ajax', 'authorization'));
        $jsGenerator->setVariable('OWMailbox.responderUrl', OW::getRouter()->urlFor("MAILBOX_CTRL_Mailbox", "responder"));
        $jsGenerator->setVariable('OWMailbox.userListUrl', OW::getRouter()->urlForRoute('mailbox_user_list'));
        $jsGenerator->setVariable('OWMailbox.convListUrl', OW::getRouter()->urlForRoute('mailbox_conv_list'));
        $jsGenerator->setVariable('OWMailbox.pingResponderUrl', OW::getRouter()->urlFor('MAILBOX_CTRL_Ajax', 'ping'));
        $jsGenerator->setVariable('OWMailbox.settingsResponderUrl', OW::getRouter()->urlFor('MAILBOX_CTRL_Ajax', 'settings'));
        $jsGenerator->setVariable('OWMailbox.userSearchResponderUrl', OW::getRouter()->urlFor('MAILBOX_CTRL_Ajax', 'rsp'));
        $jsGenerator->setVariable('OWMailbox.bulkOptionsResponderUrl', OW::getRouter()->urlFor('MAILBOX_CTRL_Ajax', 'bulkOptions'));

        $plugin_update_timestamp = 0;
        if ( OW::getConfig()->configExists('mailbox', 'plugin_update_timestamp') )
        {
            $plugin_update_timestamp = OW::getConfig()->getValue('mailbox', 'plugin_update_timestamp');
        }
        $jsGenerator->setVariable('OWMailbox.pluginUpdateTimestamp', $plugin_update_timestamp);

        $todayDate = date('Y-m-d', time());
        $jsGenerator->setVariable('OWMailbox.todayDate', $todayDate);
        $todayDateLabel = UTIL_DateTime::formatDate(time(), true);
        $jsGenerator->setVariable('OWMailbox.todayDateLabel', $todayDateLabel);

        $activeModeList = $conversationService->getActiveModeList();
        $chatModeEnabled = (in_array('chat', $activeModeList)) ? true : false;
        $this->assign('chatModeEnabled', $chatModeEnabled);
        $jsGenerator->setVariable('OWMailbox.chatModeEnabled', $chatModeEnabled);
        $jsGenerator->setVariable('OWMailbox.useChat', $this->useChat);

        $mailModeEnabled = (in_array('mail', $activeModeList)) ? true : false;
        $this->assign('mailModeEnabled', $mailModeEnabled);
        $jsGenerator->setVariable('OWMailbox.mailModeEnabled', $mailModeEnabled);

        $isAuthorizedSendMessage = OW::getUser()->isAuthorized('mailbox', 'send_message');
        $this->assign('isAuthorizedSendMessage', $isAuthorizedSendMessage);

        $configs = OW::getConfig()->getValues('mailbox');
//        if ( !empty($configs['enable_attachments']))
//        {
            OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('base')->getStaticJsUrl() . 'attachments.js');
//        }

        $this->assign('im_sound_url', OW::getPluginManager()->getPlugin('mailbox')->getStaticUrl() . 'sound/receive.mp3');

        /* DEBUG MODE */
        $debugMode = false;
        $jsGenerator->setVariable('im_debug_mode', $debugMode);
        $this->assign('debug_mode', $debugMode);

        $variables = $jsGenerator->generateJs();

        $details = array(
            'userId' => $userId,
            'displayName' => $displayName,
            'profileUrl' => $profileUrl,
            'avatarUrl' => $avatarUrl
        );
        OW::getDocument()->addScriptDeclaration("OWMailbox.userDetails = " . json_encode($details) . ";\n " . $variables);

        OW::getLanguage()->addKeyForJs('mailbox', 'find_contact');
        OW::getLanguage()->addKeyForJs('base', 'user_block_message');
        OW::getLanguage()->addKeyForJs('mailbox', 'send_message_failed');
        OW::getLanguage()->addKeyForJs('mailbox', 'confirm_conversation_delete');
        OW::getLanguage()->addKeyForJs('mailbox', 'silent_mode_off');
        OW::getLanguage()->addKeyForJs('mailbox', 'silent_mode_on');
        OW::getLanguage()->addKeyForJs('mailbox', 'show_all_users');
        OW::getLanguage()->addKeyForJs('mailbox', 'show_all_users');
        OW::getLanguage()->addKeyForJs('mailbox', 'show_online_only');
        OW::getLanguage()->addKeyForJs('mailbox', 'new_message');
        OW::getLanguage()->addKeyForJs('mailbox', 'mail_subject_prefix');
        OW::getLanguage()->addKeyForJs('mailbox', 'chat_subject_prefix');
        OW::getLanguage()->addKeyForJs('mailbox', 'new_message_count');
        OW::getLanguage()->addKeyForJs('mailbox', 'chat_message_empty');
        OW::getLanguage()->addKeyForJs('mailbox', 'text_message_invitation');

        $avatar_proto_data = array('url' => 1, 'src' => BOL_AvatarService::getInstance()->getDefaultAvatarUrl(), 'class' => 'talk_box_avatar');
        $this->assign('avatar_proto_data', $avatar_proto_data);

        $this->assign('defaultAvatarUrl', BOL_AvatarService::getInstance()->getDefaultAvatarUrl());
        $this->assign('online_list_url', OW::getRouter()->urlForRoute('base_user_lists', array('list' => 'online')));

        /**/

        $actionPromotedText = '';

        $isAuthorizedReplyToMessage = OW::getUser()->isAuthorized('mailbox', 'reply_to_chat_message');
        $isAuthorizedSendMessage = OW::getUser()->isAuthorized('mailbox', 'send_chat_message');
        $isAuthorized = $isAuthorizedReplyToMessage || $isAuthorizedSendMessage;
        if (!$isAuthorized)
        {
            $actionName = 'send_chat_message';
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('mailbox', $actionName);

            if ( $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED )
            {
                $actionPromotedText = $status['msg'];
            }
        }
        $this->assign('replyToMessageActionPromotedText', $actionPromotedText);
        $this->assign('isAuthorizedReplyToMessage', $isAuthorized);

        /**/

        $lastSentMessage = $conversationService->getLastSentMessage($userId);
        $lastMessageTimestamp = (int)($lastSentMessage ? $lastSentMessage->timeStamp : 0);

        if ($chatModeEnabled)
        {
            $countOnline = BOL_UserService::getInstance()->countOnline();
            if ($countOnline < 5)
            {
                $pingInterval = 5000;
            }
            else
            {
                if ($countOnline > 15)
                {
                    $pingInterval = 15000;
                }
                else
                {
                    $pingInterval = 5000; //TODO think about ping interval here
                }
            }
        }
        else
        {
            $pingInterval = 30000;
        }

        $applicationParams = array(
            'pingInterval'=>$pingInterval,
            'lastMessageTimestamp' => $lastMessageTimestamp
        );

        $js = UTIL_JsGenerator::composeJsString('OW.Mailbox = new OWMailbox.Application({$params});', array('params'=>$applicationParams));
        OW::getDocument()->addOnloadScript($js, 3003);


        $js = "
        OW.Mailbox.contactManager = new MAILBOX_ContactManager;
        OW.Mailbox.contactManagerView = new MAILBOX_ContactManagerView({model: OW.Mailbox.contactManager});";

        OW::getDocument()->addOnloadScript($js, 3009);

        return parent::render();
    }
}
