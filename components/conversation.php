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
 * @package ow_plugin.mailbox.components
 * @since 1.6.1
 * */
class MAILBOX_CMP_Conversation extends OW_Component
{
    public function __construct()
    {
        parent::__construct();
    }

    public function render()
    {
        $defaultAvatarUrl = BOL_AvatarService::getInstance()->getDefaultAvatarUrl();
        $this->assign('defaultAvatarUrl', $defaultAvatarUrl);

        $js = "OW.Mailbox.conversationController = new MAILBOX_ConversationView();";

        OW::getDocument()->addOnloadScript($js, 3006);

        //TODO check this config
        $enableAttachments = OW::getConfig()->getValue('mailbox', 'enable_attachments');
        $this->assign('enableAttachments', $enableAttachments);

        $replyToMessageActionPromotedText = '';
        $isAuthorizedReplyToMessage = OW::getUser()->isAuthorized('mailbox', 'reply_to_message');
        $isAuthorizedReplyToMessage = $isAuthorizedReplyToMessage || OW::getUser()->isAuthorized('mailbox', 'send_chat_message');
        if (!$isAuthorizedReplyToMessage)
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('mailbox', 'reply_to_message');

            if ( $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED )
            {
                $replyToMessageActionPromotedText = $status['msg'];
            }
        }
        $this->assign('isAuthorizedReplyToMessage', $isAuthorizedReplyToMessage);

        $isAuthorizedReplyToChatMessage = OW::getUser()->isAuthorized('mailbox', 'reply_to_chat_message');
        if (!$isAuthorizedReplyToChatMessage)
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('mailbox', 'reply_to_chat_message');

            if ( $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED )
            {
                $replyToMessageActionPromotedText = $status['msg'];
            }
        }
        $this->assign('isAuthorizedReplyToChatMessage', $isAuthorizedReplyToChatMessage);

        $this->assign('replyToMessageActionPromotedText', $replyToMessageActionPromotedText);

        if ( $isAuthorizedReplyToMessage )
        {
            $text = new WysiwygTextarea('mailbox_message');
            $text->setId('conversationTextarea');
            $this->assign('mailbox_message', $text->renderInput());
        }

        return parent::render();
    }
}