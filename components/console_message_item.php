<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2009, Skalfa LLC
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
class MAILBOX_CMP_ConsoleMessageItem extends OW_Component
{
    /**
     *
     * @var BASE_CMP_ConsoleListItem
     */
    protected $consoleItem;

    protected $convId, $opponentId, $avatarUrl = '', $profileUrl = '', $text = '', $displayName = '', $url = '', $mode = '', $dateLabel = '', $unreadMessageCount = 0;

    public function __construct( $conversationData )
    {
        parent::__construct();

        $this->consoleItem = new BASE_CMP_ConsoleListItem();

        $this->convId = $conversationData['conversationId'];

        $userId = OW::getUser()->getId();
        $conversationService = MAILBOX_BOL_ConversationService::getInstance();

        $this->opponentId = $conversationData['opponentId'];
        $avatarUrl = BOL_AvatarService::getInstance()->getAvatarUrl($this->opponentId);
        $this->avatarUrl = $avatarUrl ? $avatarUrl : BOL_AvatarService::getInstance()->getDefaultAvatarUrl();
        $this->profileUrl = BOL_UserService::getInstance()->getUserUrl($this->opponentId);
        $this->displayName = BOL_UserService::getInstance()->getDisplayName($this->opponentId);
        $this->mode = $conversationData['mode'];
        $this->text = $conversationData['previewText'];
        $this->dateLabel = $conversationData['dateLabel'];
        $this->unreadMessageCount = $conversationService->countUnreadMessagesForConversation($this->convId, $userId);

        if ($this->mode == 'mail')
        {
            $this->url = $conversationService->getConversationUrl($this->convId);
            $this->addClass('ow_mailbox_request_item ow_cursor_default');
        }

        if ($this->mode == 'chat')
        {
            $this->url = 'javascript://';
            $this->addClass('ow_chat_request_item ow_cursor_default');


            $js = "$('.consoleChatItem#mailboxConsoleMessageItem{$this->convId}').bind('click', function(){
        var convId = $(this).data('convid');
        var opponentId = $(this).data('opponentid');
        OW.trigger('mailbox.open_dialog', {convId: convId, opponentId: opponentId, mode: 'chat', isSelected: true});
        OW.Console.getItem('mailbox').hideContent();
    });";

            OW::getDocument()->addOnloadScript($js);
        }

        if ( $conversationData['conversationRead'] == 0 )
        {
            $this->addClass('ow_console_new_message');
        }
    }

    public function setMode( $mode )
    {
        $this->mode = $mode;
    }

    public function setKey( $key )
    {
        $this->consoleItem->setKey($key);
    }

    public function getKey()
    {
        return $this->consoleItem->getKey();
    }

    public function setIsHidden( $hidden = true )
    {
        $this->consoleItem->setIsHidden($hidden);
    }

    public function getIsHidden()
    {
        return $this->consoleItem->getIsHidden();
    }

    public function addClass( $class )
    {
        $this->consoleItem->addClass($class);
    }

    public function setAvatarUrl( $avatarUrl )
    {
        $this->avatarUrl = $avatarUrl;
    }

    public function setProfileUrl( $profileUrl )
    {
        $this->profileUrl = $profileUrl;
    }

    public function setText( $text )
    {
        $this->text = $text;
    }

    public function setDisplayName( $displayName )
    {
        $this->displayName = $displayName;
    }

    public function setUrl( $url )
    {
        $this->url = $url;
    }

    public function onBeforeRender()
    {
        parent::onBeforeRender();

        $this->assign('convId', $this->convId);
        $this->assign('opponentId', $this->opponentId);
        $this->assign('mode', $this->mode);
        $this->assign('avatarUrl', $this->avatarUrl);
        $this->assign('profileUrl', $this->profileUrl);
        $this->assign('displayName', $this->displayName);
        $this->assign('text', $this->text);
        $this->assign('url', $this->url);
        $this->assign('dateLabel', $this->dateLabel);
        $this->assign('unreadMessageCount', $this->unreadMessageCount);
    }

    public function render()
    {
        $this->consoleItem->setContent(parent::render());

        return $this->consoleItem->render();
    }
}