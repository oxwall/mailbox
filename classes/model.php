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
 * @package ow.ow_plugins.mailbox
 * @since 1.7
 */
class MAILBOX_CLASS_Model
{
    private $userId;
    private $response = array();

    /**
     * @var MAILBOX_BOL_ConversationService
     */
    private $conversationService;

    public function __construct()
    {
        $this->userId = OW::getUser()->getId();
        $this->conversationService = MAILBOX_BOL_ConversationService::getInstance();
    }

    public function updateWithData($params)
    {
        if (!isset($params['lastRequestTimestamp']))
        {
            return;
        }

        if ((int)$params['lastRequestTimestamp'] == 0)
        {
            $params['lastRequestTimestamp'] = time();
        }

        /***************************************************************************************************************/

        if (!empty($params['readMessageList']))
        {
            $readMessageIdList = array();
            foreach ($params['readMessageList'] as $message)
            {
                $readMessageIdList[] = $message["id"];
            }
            $this->conversationService->markMessageIdListRead($readMessageIdList);
        }

        /***************************************************************************************************************/

        $ignoreMessageList = array();
        if (!empty($params['ignoreMessageList']))
        {
            foreach ($params['ignoreMessageList'] as $message)
            {
                $ignoreMessageList[] = $message["id"];
            }
        }
        $m = $this->conversationService->findUnreadMessagesForApi($this->userId, $ignoreMessageList, $params['lastRequestTimestamp']);
        $this->setObject('messageList', $m);

        /***************************************************************************************************************/

        if (!isset($params['conversationListLength']))
        {
            $params['conversationListLength'] = 0;
        }

//        $count = $this->conversationService->countConversationListByUserId($this->userId);
//
//        if ((int)$params['conversationListLength'] != $count)
//        {
//            $list = $this->conversationService->getConversationListByUserId($this->userId);
//            $this->setObject('conversationList', $list);
//        }
        if (count($m) > 0)
        {
        $list = $this->conversationService->getChatUserList($this->userId, 0, 10); //TODO specify limits
        $this->setObject('conversationList', $list);
        }

        $this->setObject('lastRequestTimestamp', time());
    }

    private function setObject($key, $value)
    {
        $this->response[$key] = $value;
    }

    public function getResponse()
    {
        return $this->response;
    }
}