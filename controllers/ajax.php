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
class MAILBOX_CTRL_Ajax extends OW_ActionController
{
    private $conversationService;

    public function init()
    {
        if (!OW::getRequest()->isAjax())
        {
            throw new Redirect404Exception();
        }

        if (!OW::getUser()->isAuthenticated())
        {
            echo json_encode('User is not authenticated');
            exit;
        }

        $this->conversationService = MAILBOX_BOL_ConversationService::getInstance();
    }

    public function getHistory()
    {
        $userId = OW::getUser()->getId();
        $conversationId = (int)$_POST['convId'];
        $beforeMessageId = (int)$_POST['messageId'];

        $data = $this->conversationService->getConversationHistory($conversationId, $beforeMessageId);

        exit(json_encode($data));
    }

    public function newMessage()
    {
        $form = OW::getClassInstance("MAILBOX_CLASS_NewMessageForm");
        /* @var $user MAILBOX_CLASS_NewMessageForm */

        if ($form->isValid($_POST))
        {
            $result = $form->process();
            exit(json_encode($result));
        }
        else
        {
            exit(json_encode(array($form->getErrors())));
        }
    }

    public function updateUserInfo()
    {
        //DDoS check
        if ( empty($_SESSION['lastUpdateRequestTimestamp']) )
        {
            $_SESSION['lastUpdateRequestTimestamp'] = time();
        }
        else if ( (time() - (int) $_SESSION['lastUpdateRequestTimestamp']) < 3 )
        {
            exit('{error: "Too much requests"}');
        }

        $_SESSION['lastUpdateRequestTimestamp'] = time();

        $conversationService = MAILBOX_BOL_ConversationService::getInstance();

        if ($errorMessage = $conversationService->checkPermissions())
        {
            exit(json_encode(array('error'=>$errorMessage)));
        }

        /* @var BOL_User $user */
        $user = null;

        if ( !empty($_POST['userId']) )
        {
            $user = BOL_UserService::getInstance()->findUserById($_POST['userId']);

            if (!$user)
            {
                $info = array(
                    'warning' => true,
                    'message' => 'User not found',
                    'type' => 'error'
                );
                exit(json_encode($info));
            }

            if ( !OW::getAuthorization()->isUserAuthorized($user->getId(), 'mailbox', 'reply_to_chat_message') )
            {
                $status = BOL_AuthorizationService::getInstance()->getActionStatus('mailbox', 'reply_to_chat_message', array('userId'=>$user->getId()));

                if ($status['status'] == BOL_AuthorizationService::STATUS_DISABLED)
                {
                    $info = array(
                        'warning' => true,
                        'message' => OW::getLanguage()->text('mailbox', 'user_is_not_authorized_chat', array('username' => BOL_UserService::getInstance()->getDisplayName($user->getId()))),
                        'type' => 'warning'
                    );
                    exit(json_encode($info));
                }
            }

            $eventParams = array(
                'action' => 'mailbox_invite_to_chat',
                'ownerId' => $user->getId(),
                'viewerId' => OW::getUser()->getId()
            );

            try
            {
                OW::getEventManager()->getInstance()->call('privacy_check_permission', $eventParams);
            }
            catch ( RedirectException $e )
            {
                //TODOS return message that has been set in a privacy value
                $info = array(
                    'warning' => true,
                    'message' => OW::getLanguage()->text('mailbox', 'warning_user_privacy_friends_only', array('displayname' => BOL_UserService::getInstance()->getDisplayName($user->getId()))),
                    'type' => 'warning'
                );
                exit(json_encode($info));
            }

            if ( BOL_UserService::getInstance()->isBlocked(OW::getUser()->getId(), $user->getId()) )
            {
                $errorMessage = OW::getLanguage()->text('base', 'user_block_message');
                $info = array(
                    'warning' => true,
                    'message' => $errorMessage,
                    'type' => 'error'
                );
                exit(json_encode($info));
            }

            if (empty( $_POST['checkStatus'] ) || $_POST['checkStatus'] != 2)
            {
                $onlineStatus = BOL_UserService::getInstance()->findOnlineStatusForUserList(array($user->getId()));
                if (!$onlineStatus[$user->getId()])
                {
                    $displayname = BOL_UserService::getInstance()->getDisplayName($user->getId());
                    $info = array(
                        'warning' => true,
                        'message' => OW::getLanguage()->text('mailbox', 'user_went_offline', array('displayname'=>$displayname)),
                        'type' => 'warning'
                    );
                    exit(json_encode($info));
                }
            }

            $info = $conversationService->getUserInfo($user->getId());
            exit(json_encode($info));
        }

        exit();
    }

    public function settings()
    {
        if (isset($_POST['soundEnabled']))
        {
            $_POST['soundEnabled'] = $_POST['soundEnabled'] === 'false' ? false : true;

            BOL_PreferenceService::getInstance()->savePreferenceValue('mailbox_user_settings_enable_sound', $_POST['soundEnabled'], OW::getUser()->getId());
        }

        if (isset($_POST['showOnlineOnly']))
        {
            $_POST['showOnlineOnly'] = $_POST['showOnlineOnly'] === 'false' ? false : true;
            BOL_PreferenceService::getInstance()->savePreferenceValue('mailbox_user_settings_show_online_only', $_POST['showOnlineOnly'], OW::getUser()->getId());

        }

        exit('true');
    }

    public function authorization(){
        $result = MAILBOX_BOL_AjaxService::getInstance()->authorizeAction($_POST);
        exit(json_encode($result));
    }

    public function ping()
    {
        $params = json_decode($_POST['request'], true);

        $event = new OW_Event('mailbox.ping', array('params'=>$params, 'command'=>'mailbox_ping'));
        OW::getEventManager()->trigger($event);

        exit( json_encode($event->getData()) );
    }

    public function rsp()
    {
        if ( !OW::getRequest()->isAjax() )
        {
            throw new Redirect403Exception;
        }

        if ( !OW::getUser()->isAuthenticated() )
        {
            echo json_encode(array());
            exit;
        }

        $kw = empty($_GET['term']) ? null : $_GET['term'];
        $idList = empty($_GET['idList']) ? null : $_GET['idList'];

        $context = empty($_GET["context"]) ? 'user' : $_GET["context"];
        $userId = OW::getUser()->getId();

        $entries = MAILBOX_BOL_AjaxService::getInstance()->getSuggestEntries($userId, $kw, $idList, $context);

        echo json_encode($entries);
        exit;
    }

    /**
     * Deprecated see AjaxService / bulkActions
     */
    public function bulkOptions()
    {
        $userId = OW::getUser()->getId();

        switch($_POST['actionName'])
        {
            case 'markUnread':
                $count = MAILBOX_BOL_ConversationService::getInstance()->markConversation($_POST['convIdList'], $userId, MAILBOX_BOL_ConversationService::MARK_TYPE_UNREAD);
                $message = OW::getLanguage()->text('mailbox', 'mark_unread_message', array('count'=>$count));
                break;
            case 'markRead':
                $count = MAILBOX_BOL_ConversationService::getInstance()->markConversation($_POST['convIdList'], $userId, MAILBOX_BOL_ConversationService::MARK_TYPE_READ);
                $message = OW::getLanguage()->text('mailbox', 'mark_read_message', array('count'=>$count));
                break;
            case 'delete':
                $count = MAILBOX_BOL_ConversationService::getInstance()->deleteConversation($_POST['convIdList'], $userId);
                $message = OW::getLanguage()->text('mailbox', 'delete_message', array('count'=>$count));
                break;
        }

        exit(json_encode(array('count'=>$count, 'message'=>$message)));
    }
}