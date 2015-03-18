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
 * @package ow_plugin.mailbox.controllers
 * @since 1.6.1
 * */
class MAILBOX_CTRL_Messages extends OW_ActionController
{
    public function index( $params )
    {
        if (!OW::getUser()->isAuthenticated())
        {
            throw new AuthenticateException();
        }

        $this->setPageHeading(OW::getLanguage()->text('mailbox', 'page_heading_messages'));

        $conversationService = MAILBOX_BOL_ConversationService::getInstance();

        $listParams = array();
        if (!empty($params['convId']))
        {
            $listParams['conversationId'] = $params['convId'];

            $conversation = $conversationService->getConversation($params['convId']);
            if (empty($conversation))
            {
                throw new Redirect404Exception();
            }

            /*$conversationMode = $conversationService->getConversationMode($params['convId']);
            if ($conversationMode != 'mail')
            {
                throw new Redirect404Exception();
            }*/
        }

        $listParams['activeModeList'] = $conversationService->getActiveModeList();

        //Conversation list
        $conversationList = new MAILBOX_CMP_ConversationList($listParams);
        $this->addComponent('conversationList', $conversationList);

        $conversationContainer = new MAILBOX_CMP_Conversation();
        $this->addComponent('conversationContainer', $conversationContainer);

        $activeModeList = $conversationService->getActiveModeList();
        $mailModeEnabled = (in_array('mail', $activeModeList)) ? true : false;
        $this->assign('mailModeEnabled', $mailModeEnabled);

        $actionName = 'send_message';

        $event = new OW_Event('mailbox.show_send_message_button', array(), false);
        OW::getEventManager()->trigger($event);
        $showSendMessage = $event->getData();

        $isAuthorizedSendMessage = $showSendMessage && OW::getUser()->isAuthorized('mailbox', $actionName);
        if (!$isAuthorizedSendMessage)
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('mailbox', $actionName);

            if ( $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED )
            {
                $script = '$("#newMessageBtn").click(function(){
                    OW.authorizationLimitedFloatbox('.json_encode($status['msg']).');
                });';
                OW::getDocument()->addOnloadScript($script);
                $isAuthorizedSendMessage = true; //the service is promoted
            }
        }

        $this->assign('isAuthorizedSendMessage', $isAuthorizedSendMessage);

        $chatModeEnabled = (in_array('chat', $activeModeList)) ? true : false;
        $this->assign('chatModeEnabled', $chatModeEnabled);

    }

    public function chatConversation( $params ){
        $this->redirect(OW::getRouter()->urlForRoute('mailbox_messages_default'));
    }

    public function conversation($params)
    {
//        pv($_REQUEST);

        exit('1');
    }

    public function conversations($params)
    {
        if (!OW::getUser()->isAuthenticated())
        {
            exit(array());
        }

        $userId = OW::getUser()->getId();
        $conversationService = MAILBOX_BOL_ConversationService::getInstance();

        if ($_SERVER['REQUEST_METHOD'] == 'GET'){
            $list = $conversationService->getConversationListByUserId($userId);
            exit(json_encode($list));
        }
        else
        {
            exit(json_encode('todo'));
        }
    }
}