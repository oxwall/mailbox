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
 * @package ow_plugin.mailbox.classes
 * @since 1.6.1
 * */
class MAILBOX_CLASS_NewMessageForm extends Form
{
    const DISPLAY_CAPTCHA_TIMEOUT = 20;

    public $displayCapcha = false;

    /**
     * Class constructor
     *
     */
    public function __construct(MAILBOX_CMP_NewMessage $component = null)
    {
        $language = OW::getLanguage();

        parent::__construct('mailbox-new-message-form');
        $this->setId('mailbox-new-message-form');
        $this->setAjax(true);
        $this->setAjaxResetOnSuccess(false);
        $this->setAction( OW::getRouter()->urlFor('MAILBOX_CTRL_Ajax', 'newMessage') );
        $this->setEmptyElementsErrorMessage('');

        $this->setEnctype('multipart/form-data');

        $subject = new TextField('subject');
//        $subject->setHasInvitation(true);
//        $subject->setInvitation($language->text('mailbox', 'subject'));
        $subject->addAttribute('placeholder', $language->text('mailbox', 'subject'));

        $requiredValidator = new RequiredValidator();
        $requiredValidator->setErrorMessage( $language->text('mailbox', 'subject_is_required') );
        $subject->addValidator($requiredValidator);

        $validatorSubject = new StringValidator(1, 2048);
        $validatorSubject->setErrorMessage($language->text('mailbox', 'message_too_long_error', array('maxLength' => 2048)));
        $subject->addValidator($validatorSubject);

        $this->addElement($subject);

        $validator = new StringValidator(1, MAILBOX_BOL_AjaxService::MAX_MESSAGE_TEXT_LENGTH);
        $validator->setErrorMessage($language->text('mailbox', 'message_too_long_error', array('maxLength' => MAILBOX_BOL_AjaxService::MAX_MESSAGE_TEXT_LENGTH)));

        $textarea = OW::getClassInstance("MAILBOX_CLASS_Textarea", "message");
        
        /* @var $textarea MAILBOX_CLASS_Textarea */
        $textarea->addValidator($validator);
        $textarea->setCustomBodyClass("mailbox");
//        $textarea->setHasInvitation(true);
//        $textarea->setInvitation($language->text('mailbox', 'message_invitation'));
        $textarea->addAttribute('placeholder', $language->text('mailbox', 'message_invitation'));
        $requiredValidator = new WyswygRequiredValidator();
        $requiredValidator->setErrorMessage( $language->text('mailbox', 'chat_message_empty') );
        $textarea->addValidator($requiredValidator);

        $this->addElement($textarea);

        $user = OW::getClassInstance("MAILBOX_CLASS_UserField", "opponentId");
        
        /* @var $user MAILBOX_CLASS_UserField */
//        $user->setHasInvitation(true);
//        $user->setInvitation($language->text('mailbox', 'to'));

        $requiredValidator = new RequiredValidator();
        $requiredValidator->setErrorMessage( $language->text('mailbox', 'recipient_is_required') );
        $user->addValidator($requiredValidator);

        $this->addElement($user);

        if (OW::getSession()->isKeySet('mailbox.new_message_form_attachments_uid'))
        {
            $uidValue = OW::getSession()->get('mailbox.new_message_form_attachments_uid');
        }
        else
        {
            $uidValue = UTIL_HtmlTag::generateAutoId('mailbox_new_message');
            OW::getSession()->set('mailbox.new_message_form_attachments_uid', $uidValue);
        }

        $uid = new HiddenField('uid');
        $uid->setValue($uidValue);
        $this->addElement($uid);

        $configs = OW::getConfig()->getValues('mailbox');
        if ( !empty($configs['enable_attachments']) && !empty($component) )
        {
            $attachmentCmp = new BASE_CLASS_FileAttachment('mailbox', $uidValue);
            $attachmentCmp->setInputSelector('#newMessageWindowAttachmentsBtn');
            $component->addComponent('attachments', $attachmentCmp);
        }

        $submit = new Submit("send");
        $submit->setValue($language->text('mailbox', 'send_button'));

        $this->addElement($submit);

        if ( !OW::getRequest()->isAjax() )
        {
            $this->initStatic();
        }
    }
    
    protected function initStatic()
    {
        $language = OW::getLanguage();
        $language->addKeyForJs('mailbox', 'close_new_message_window_confirmation');

        $messageError = $language->text('mailbox', 'create_conversation_fail_message');
        $messageSuccess = $language->text('mailbox', 'create_conversation_message');

        $js = "
        var newMessageFormModel = new OWMailbox.NewMessageForm.Model();
        OW.Mailbox.newMessageFormController = new OWMailbox.NewMessageForm.Controller(newMessageFormModel);

        OW.bind('mailbox.application_started', function(){
            OWMailbox.NewMessageForm.restoreForm();
        });

        owForms['mailbox-new-message-form'].bind( 'success',
        function( json )
        {
            var from = $('#mailbox-new-message-form');

            if ( json.result == 'permission_denied' )
            {
                if ( json.message != undefined )
                {
                    OW.error(json.message);
                }
                else
                {
                    OW.error(". json_encode($language->text('mailbox', 'write_permission_denied')).");
                }
            }
            else if ( json.result == true )
            {
                var attUid = $(owForms['mailbox-new-message-form'].elements.uid.input).val();
                var newUid = OWMailbox.uniqueId('mailbox_new_message_');
                OW.trigger('base.file_attachment', { 'uid': attUid, 'newUid': newUid });

                OW.Mailbox.lastMessageTimestamp = json.lastMessageTimestamp;

                owForms['mailbox-new-message-form'].resetForm();

                $(owForms['mailbox-new-message-form'].elements.uid.input).val(newUid);

                OW.trigger('mailbox.close_new_message_form');

                OW.info(json.message || '{$messageSuccess}');
            }
            else
            {
                OW.error(json.error);
            }
        } ); ";

        OW::getDocument()->addOnloadScript( $js, 3006 );
    }

    /**
     * Create new conversation
     *
     * @param MAILBOX_BOL_Conversation $conversation
     * @param int $userId
     * @return boolean
     */
    public function process()
    {
        $values = $this->getValues();
        $userId = OW::getUser()->getId();
        
        $language = OW::getLanguage();
        $conversationService = MAILBOX_BOL_ConversationService::getInstance();

        // Check if user can send message
        
        $error = null;
        
        $actionName = 'send_message';
        $userSendMessageIntervalOk = $conversationService->checkUserSendMessageInterval($userId);
        
        if (!$userSendMessageIntervalOk)
        {
            $send_message_interval = (int)OW::getConfig()->getValue('mailbox', 'send_message_interval');
            $error = array('result'=>false, 'error'=>$language->text('mailbox', 'feedback_send_message_interval_exceed', array('send_message_interval'=>$send_message_interval)));
        } 
        else if ( !OW::getUser()->isAuthorized('mailbox', $actionName) )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('mailbox', $actionName);
            if ( $status['status'] != BOL_AuthorizationService::STATUS_AVAILABLE )
            {
                $error = array('result' => false, 'error'=> $language->text('mailbox', $actionName.'_permission_denied'));
            }
        }
        
        $result = $error;
        
        if ( $error === null )
        {
            // Send message
            
            $files = BOL_AttachmentService::getInstance()->getFilesByBundleName('mailbox', $values['uid']);
            $result = $this->sendMessage($userId, $values["opponentId"], $values["subject"], $values["message"], $files);
        }
        
        OW::getSession()->delete('mailbox.new_message_form_attachments_uid');

        return $result;
    }
    
    protected function sendMessage( $userId, $opponentId, $subject, $message, $files = array() )
    {
        $conversationService = MAILBOX_BOL_ConversationService::getInstance();
        $checkResult = $conversationService->checkUser($userId, $opponentId);

        if ( $checkResult['isSuspended'] )
        {
            return array('result'=>false, 'error'=>$checkResult['suspendReasonMessage']);
        }

//        $message = UTIL_HtmlTag::stripTags(UTIL_HtmlTag::stripJs($message));
        $message = UTIL_HtmlTag::stripJs($message);
//        $message = nl2br($message);

        $event = new OW_Event('mailbox.before_create_conversation', array(
            'senderId' => $userId,
            'recipientId' => $opponentId,
            'message' => $message,
            'subject' => $subject
        ), array('result' => true, 'error' => '', 'message' => $message,  'subject' => $subject ));
        OW::getEventManager()->trigger($event);

        $data = $event->getData();

        if ( empty($data['result']) )
        {
            return array('result'=> 'permission_denied', 'message' => $data['error']);
        }

        if ( !trim(strip_tags($data['subject'])) )
        {
            return array('result'=>false, 'error' => OW::getLanguage()->text('mailbox', 'subject_is_required'));
        }

        $subject = $data['subject'];
        $message = $data['message'];

        $conversation = $conversationService->createConversation($userId, $opponentId, $subject, $message);
        $messageDto = $conversationService->getLastMessage($conversation->id);

        if (!empty($files))
        {
            $conversationService->addMessageAttachments($messageDto->id, $files);
        }

        BOL_AuthorizationService::getInstance()->trackAction('mailbox', 'send_message');

        $conversationService->resetUserLastData($userId);
        $conversationService->resetUserLastData($opponentId);

        return array('result' => true, 'lastMessageTimestamp'=>$messageDto->timeStamp);
    }
}