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

class MAILBOX_MCLASS_EventHandler
{
    const CONSOLE_ITEM_KEY = 'mailbox';
    const CONSOLE_PAGE_KEY = 'convers';

    public function init()
    {
        OW::getEventManager()->bind(MBOL_ConsoleService::EVENT_COLLECT_CONSOLE_PAGES, array($this, 'onConsolePagesCollect'));
        OW::getEventManager()->bind(BASE_MCMP_ProfileActionToolbar::EVENT_NAME, array($this, "onCollectProfileActions"));

        OW::getEventManager()->bind('mailbox.renderOembed', array($this, 'onRenderOembed'));

//        OW::getEventManager()->bind(MBOL_ConsoleService::EVENT_COUNT_CONSOLE_PAGE_NEW_ITEMS, array($this, 'countNewItems'));
    }

    public function onCollectProfileActions( BASE_CLASS_EventCollector $event )
    {
        $params = $event->getParams();
        $userId = $params['userId'];

        if ( !OW::getUser()->isAuthenticated() || OW::getUser()->getId() == $userId )
        {
            return;
        }

        $activeModes = MAILBOX_BOL_ConversationService::getInstance()->getActiveModeList();
        if (!in_array('mail', $activeModes))
        {
            return;
        }

        $linkId = uniqid('send_message');

        $script = UTIL_JsGenerator::composeJsString('
            $("#' . $linkId . '").click(function()
            {
                if ( {$isBlocked} )
                {
                    OWM.error({$blockError});
                    return false;
                }
            });
        ', array(
                'isBlocked' => BOL_UserService::getInstance()->isBlocked(OW::getUser()->getId(), $userId),
                'blockError' => OW::getLanguage()->text('base', 'user_block_message')
        ));

        OW::getDocument()->addOnloadScript($script);

        $event->add(array(
            "label" => OW::getLanguage()->text('mailbox', 'send_message'),
            "href" => OW::getRouter()->urlForRoute('mailbox_compose_mail_conversation', array('opponentId'=>$userId)),
            "id" => $linkId
        ));
    }

    public function onConsolePagesCollect(BASE_CLASS_EventCollector $event)
    {
        OW::getDocument()->addScript( OW::getPluginManager()->getPlugin('base')->getStaticJsUrl().'underscore-min.js', 'text/javascript', 3000 );
        OW::getDocument()->addScript( OW::getPluginManager()->getPlugin('base')->getStaticJsUrl().'backbone-min.js', 'text/javascript', 3000 );
//        OW::getDocument()->addScript( OW::getPluginManager()->getPlugin('base')->getStaticJsUrl().'backbone.js', 'text/javascript', 3000 );

        OW::getDocument()->addScript( OW::getPluginManager()->getPlugin('mailbox')->getStaticJsUrl().'mobile_mailbox.js', 'text/javascript', 3000 );

        $userListUrl = OW::getRouter()->urlForRoute('mailbox_user_list');
        $convListUrl = OW::getRouter()->urlForRoute('mailbox_conv_list');
        $authorizationResponderUrl = OW::getRouter()->urlFor('MAILBOX_CTRL_Ajax', 'authorization');
        $pingResponderUrl = OW::getRouter()->urlFor('MAILBOX_CTRL_Ajax', 'ping');
        $getHistoryResponderUrl = OW::getRouter()->urlFor('MAILBOX_CTRL_Ajax', 'getHistory');
        $userId = OW::getUser()->getId();
        $displayName = BOL_UserService::getInstance()->getDisplayName($userId);
        $avatarUrl = BOL_AvatarService::getInstance()->getAvatarUrl($userId);
        if ( empty($avatarUrl) )
        {
            $avatarUrl = BOL_AvatarService::getInstance()->getDefaultAvatarUrl();
        }
        $profileUrl = BOL_UserService::getInstance()->getUserUrl($userId);
        $lastSentMessage = MAILBOX_BOL_ConversationService::getInstance()->getLastSentMessage($userId);
        $lastMessageTimestamp = (int)($lastSentMessage ? $lastSentMessage->timeStamp : 0);

        $params = array(
            'getHistoryResponderUrl' => $getHistoryResponderUrl,
            'pingResponderUrl' => $pingResponderUrl,
            'authorizationResponderUrl' => $authorizationResponderUrl,
            'userListUrl' => $userListUrl,
            'convListUrl' => $convListUrl,
            'pingInterval' => 5000,
            'lastMessageTimestamp' => $lastMessageTimestamp,
            'user'=>array(
                'userId' => $userId,
                'displayName' => $displayName,
                'profileUrl' => $profileUrl,
                'avatarUrl' => $avatarUrl
            )
        );

        $js = UTIL_JsGenerator::composeJsString('OWM.Mailbox = new MAILBOX_Mobile({$params});', array('params'=>$params));
        OW::getDocument()->addOnloadScript($js, 'text/javascript', 3000);

        $event->add(array(
            'key' => 'convers',
            'cmpClass' => 'MAILBOX_MCMP_ConsoleConversationsPage',
            'order' => 2
        ));
    }

    public function onRenderOembed( OW_Event $event )
    {
        $params = $event->getParams();

        $tempCmp = new MAILBOX_CMP_OembedAttachment($params['message'], $params);
        $content = $tempCmp->render();
        $event->setData('<a href="'.$params['href'].'" target="_blank">'.$params['href']."</a>");
    }

//    public function countNewItems( OW_Event $event )
//    {
//        $params = $event->getParams();
//
//        if ( $params['page'] == self::CONSOLE_PAGE_KEY )
//        {
//            $event->add(
//                array('mailbox' => 12)
//            );
//        }
//    }
}

