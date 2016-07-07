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
 * @package ow_plugin.mailbox.mobile.controllers
 * @since 1.6.1
 * */
class MAILBOX_MCTRL_Messages extends OW_MobileActionController
{
    public function chatConversation($params)
    {
        if (!OW::getUser()->isAuthenticated())
        {
            throw new AuthenticateException();
        }

        $userId = OW::getUser()->getId();
        $opponentId = (int)$params['userId'];

        /* $actionName = 'use_chat';

        $isAuthorized = OW::getUser()->isAuthorized('mailbox', $actionName);
        if ( !$isAuthorized )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('mailbox', $actionName);
            if ( $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED )
            {
                throw new AuthorizationException($status['msg']);
            }
            else
            {
                throw new AuthorizationException();
            }
        } */

        $conversationService = MAILBOX_BOL_ConversationService::getInstance();

        $conversationId = $conversationService->getChatConversationIdWithUserById($userId, $opponentId);
        if ( empty($conversationId) )
        {
            $conversation = $conversationService->createChatConversation($userId, $opponentId);

            $conversationId = $conversation->getId();
        }

        $data = $conversationService->getConversationDataAndLog($conversationId, 0, 16);

        $cmp = new MAILBOX_MCMP_ChatConversation($data);

        $this->addComponent('cmp', $cmp);
    }

    public function mailConversation($params)
    {
        if (!OW::getUser()->isAuthenticated())
        {
            throw new AuthenticateException();
        }

        $userId = OW::getUser()->getId();
        $conversationId = (int)$params['convId'];

        $conversationService = MAILBOX_BOL_ConversationService::getInstance();

        $conversation = $conversationService->getConversation($conversationId);
        if (empty($conversation))
        {
            throw new Redirect404Exception();
        }

        $data = $conversationService->getConversationDataAndLog($conversationId);

        $cmp = new MAILBOX_MCMP_MailConversation($data);

        $this->addComponent('cmp', $cmp);
    }

    public function composeMailConversation($params)
    {
        if (!OW::getUser()->isAuthenticated())
        {
            throw new Redirect404Exception();
        }

        $actionName = 'send_message';
        $isAuthorized = OW::getUser()->isAuthorized('mailbox', $actionName);
        if ( !$isAuthorized )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('mailbox', $actionName);
            if ( $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED )
            {
                throw new AuthorizationException($status['msg']);
            }
            else
            {
                throw new AuthorizationException();
            }
        }

        $conversationService = MAILBOX_BOL_ConversationService::getInstance();

        $userSendMessageIntervalOk = $conversationService->checkUserSendMessageInterval(OW::getUser()->getId());
        if (!$userSendMessageIntervalOk)
        {
            $send_message_interval = (int)OW::getConfig()->getValue('mailbox', 'send_message_interval');
            $this->echoOut(
                array('error'=>OW::getLanguage()->text('mailbox', 'feedback_send_message_interval_exceed', array('send_message_interval'=>$send_message_interval)))
            );
        }

        $this->assign('defaultAvatarUrl', BOL_AvatarService::getInstance()->getDefaultAvatarUrl());
        $opponentId = $params['opponentId'];

        $profileDisplayname = BOL_UserService::getInstance()->getDisplayName($opponentId);
        $this->assign('displayName', empty($profileDisplayname) ? BOL_UserService::getInstance()->getUserName($opponentId) : $profileDisplayname);

        $profileUrl = BOL_UserService::getInstance()->getUserUrl($opponentId);
        $this->assign('profileUrl', $profileUrl);

        $avatarUrl = BOL_AvatarService::getInstance()->getAvatarUrl($opponentId);
        $this->assign('avatarUrl', empty($avatarUrl) ? BOL_AvatarService::getInstance()->getDefaultAvatarUrl() : $avatarUrl);

        $this->assign('status', $conversationService->getUserStatus($opponentId));

        $params = array(
            'profileUrl' => $profileUrl
        );

        $js = UTIL_JsGenerator::composeJsString(' OWM.composeMessageForm = new MAILBOX_ComposeMessageFormView({$params})', array('params'=>$params));
        OW::getDocument()->addOnloadScript($js, 3001);

        $form = new MAILBOX_MCLASS_ComposeMessageForm($opponentId);

        if (OW::getRequest()->isPost())
        {
            if ($form->isValid($_POST))
            {
                $result = $form->process();
                if ($result['result'])
                {
                    $this->redirect(OW::getRouter()->urlForRoute('mailbox_mail_conversation', array('convId'=>$result['conversationId'])));
                }
                else
                {
                    OW::getFeedback()->error($result['error']);
                    $this->addForm($form);
                }
            }
            else
            {
                exit(json_encode(array($form->getErrors())));
            }
        }
        else
        {
            $this->addForm($form);
        }
    }

    private function echoOut( $out )
    {
        echo '<script>window.parent.OWM.conversation.afterAttachment(' . json_encode($out) . ');</script>';
        exit;
    }

    public function attachment($params)
    {
        if ( empty($_FILES['attachment']["tmp_name"]) || empty($_POST['conversationId']) || empty($_POST['opponentId']) || empty($_POST['uid']) )
        {
            $this->echoOut(array(
                "error" => OW::getLanguage()->text('base', 'form_validate_common_error_message')
            ));
        }

        if ( !OW::getUser()->isAuthenticated() )
        {
            $this->echoOut(array(
                "error" => "You need to sign in to send attachment."
            ));
        }

        $conversationService = MAILBOX_BOL_ConversationService::getInstance();

//        $userSendMessageIntervalOk = $conversationService->checkUserSendMessageInterval(OW::getUser()->getId());
//        if (!$userSendMessageIntervalOk)
//        {
//            $send_message_interval = (int)OW::getConfig()->getValue('mailbox', 'send_message_interval');
//            $this->echoOut(
//                array('error'=>OW::getLanguage()->text('mailbox', 'feedback_send_message_interval_exceed', array('send_message_interval'=>$send_message_interval)))
//            );
//        }

        if ( !empty($_FILES['attachment']["tmp_name"]) )
        {
            $attachmentService = BOL_AttachmentService::getInstance();

            $conversationId = $_POST['conversationId'];
            $userId = OW::getUser()->getId();
            $uid = $_POST['uid'];

            try
            {
                $maxUploadSize = OW::getConfig()->getValue('base', 'attch_file_max_size_mb');
                $validFileExtensions = json_decode(OW::getConfig()->getValue('base', 'attch_ext_list'), true);

                $dtoArr = $attachmentService->processUploadedFile('mailbox', $_FILES['attachment'], $uid, $validFileExtensions, $maxUploadSize);
            }
            catch ( Exception $e )
            {
                $this->echoOut(array(
                    "error" => $e->getMessage()
                ));
            }

            $files = $attachmentService->getFilesByBundleName('mailbox', $uid);

            if (!empty($files))
            {
                $conversation = $conversationService->getConversation($conversationId);
                try
                {
                    $message = $conversationService->createMessage($conversation, $userId, OW::getLanguage()->text('mailbox', 'attachment'));
                    $conversationService->addMessageAttachments($message->id, $files);

                    $this->echoOut( array('message'=>$conversationService->getMessageData($message)) );
                }
                catch(InvalidArgumentException $e)
                {
                    $this->echoOut(array(
                        "error" => $e->getMessage()
                    ));
                }
            }
        }
    }

    public function newmessage($params)
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            $this->echoOut(array(
                "error" => "You need to sign in to send message."
            ));
        }
        
        $conversationService = MAILBOX_BOL_ConversationService::getInstance();
        
//        $userSendMessageIntervalOk = $conversationService->checkUserSendMessageInterval(OW::getUser()->getId());
//        if (!$userSendMessageIntervalOk)
//        {
//            $send_message_interval = (int)OW::getConfig()->getValue('mailbox', 'send_message_interval');
//            $this->echoOut(
//                array('error'=>OW::getLanguage()->text('mailbox', 'feedback_send_message_interval_exceed', array('send_message_interval'=>$send_message_interval)))
//            );
//        }
        
        if ( empty($_POST['conversationId']) || empty($_POST['opponentId']) || empty($_POST['uid']) || empty($_POST['newMessageText']) )
        {
            $this->echoOut(array(
                "error" => OW::getLanguage()->text('base', 'form_validate_common_error_message')
            ));
        }
        
        $conversationId = $_POST['conversationId'];
        $userId = OW::getUser()->getId();
        
        $actionName = 'reply_to_message';
        $isAuthorized = OW::getUser()->isAuthorized('mailbox', $actionName);
        
        if ( !$isAuthorized )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('mailbox', $actionName);
            
            if ( $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED )
            {
                $this->echoOut(array(
                    "error" => OW::getLanguage()->text('mailbox', $actionName.'_promoted')
                ));
            }
            else if ( $status['status'] != BOL_AuthorizationService::STATUS_AVAILABLE )
            {
                $this->echoOut(array(
                    "error" => OW::getLanguage()->text('mailbox', $actionName.'_permission_denied')
                ));
            }
        }

        $checkResult = $conversationService->checkUser($userId, $_POST['opponentId']);

        if ( $checkResult['isSuspended'] )
        {
            $this->echoOut(array(
                "error" => $checkResult['suspendReasonMessage']
            ));
        }

        $conversation = $conversationService->getConversation($conversationId);
        try
        {
            $message = $conversationService->createMessage($conversation, $userId, $_POST['newMessageText']);

            if ( !empty($_FILES['attachment']["tmp_name"]) )
            {
                $attachmentService = BOL_AttachmentService::getInstance();
                $uid = $_POST['uid'];

                $maxUploadSize = OW::getConfig()->getValue('base', 'attch_file_max_size_mb');
                $validFileExtensions = json_decode(OW::getConfig()->getValue('base', 'attch_ext_list'), true);
                $dtoArr = $attachmentService->processUploadedFile('mailbox', $_FILES['attachment'], $uid, $validFileExtensions, $maxUploadSize);

                $files = $attachmentService->getFilesByBundleName('mailbox', $uid);

                if (!empty($files))
                {
                    $conversationService->addMessageAttachments($message->id, $files);
                }
            }
            
            BOL_AuthorizationService::getInstance()->trackAction('mailbox', $actionName);
            $this->echoOut( array('message'=>$conversationService->getMessageData($message)) );
        }
        catch(InvalidArgumentException $e)
        {
            $this->echoOut(array(
                "error" => $e->getMessage()
            ));
        }
    }
}
