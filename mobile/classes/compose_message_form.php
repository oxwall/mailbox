<?php

class MAILBOX_MCLASS_ComposeMessageForm extends Form
{
    public function __construct($opponentId)
    {
        parent::__construct('composeMessageForm');

        $this->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);

        $field = new HiddenField('uid');
        $field->setValue( UTIL_HtmlTag::generateAutoId('mailbox_new_message_'.$opponentId) );
        $this->addElement($field);

        $field = new HiddenField('opponentId');
        $field->setValue($opponentId);
        $this->addElement($field);

        $field = new TextField('subject');
        $field->setInvitation(OW::getLanguage()->text('mailbox', 'subject'));
        $field->setHasInvitation(true);
        $field->setRequired();
        $this->addElement($field);

        $field = new Textarea('message');
        $field->setInvitation(OW::getLanguage()->text('mailbox', 'text_message_invitation'));
        $field->setHasInvitation(true);
        $field->setRequired();
        $this->addElement($field);

        $field = new HiddenField('attachment');
        $this->addElement($field);

        $submit = new Submit('sendBtn');
        $submit->setId('sendBtn');
        $submit->setValue(OW::getLanguage()->text('mailbox', 'add_button'));
        $this->addElement($submit);

        if ( !OW::getRequest()->isAjax() )
        {
            $js = UTIL_JsGenerator::composeJsString('
            owForms["composeMessageForm"].bind( "submit", function( r )
            {
                $("#newmessage-mail-send-btn").addClass("owm_preloader_circle");
            });');
            OW::getDocument()->addOnloadScript( $js );
        }
    }

    public function process()
    {
        $language = OW::getLanguage();
        $conversationService = MAILBOX_BOL_ConversationService::getInstance();
        $values = $this->getValues();
        $userId = OW::getUser()->getId();

        $actionName = 'send_message';
        $isAuthorized = OW::getUser()->isAuthorized('mailbox', $actionName);
        if ( !$isAuthorized )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('mailbox', $actionName);
            if ( $status['status'] != BOL_AuthorizationService::STATUS_AVAILABLE )
            {
                return array('result' => false, 'error'=> $language->text('mailbox', 'send_message_permission_denied'));
            }
        }

        $checkResult = $conversationService->checkUser($userId, $values['opponentId']);

        if ( $checkResult['isSuspended'] )
        {
            return array('result'=>false, 'error'=>$checkResult['suspendReasonMessage']);
        }

        $values['message'] = UTIL_HtmlTag::stripTags(UTIL_HtmlTag::stripJs($values['message']));

        $event = new OW_Event('mailbox.before_create_conversation', array(
            'senderId' => $userId,
            'recipientId' => $values['opponentId'],
            'message' => $values['message'],
            'subject' => $values['subject']
        ), array('result' => true, 'error' => '', 'message' => $values['message'],  'subject' => $values['subject'] ));
        OW::getEventManager()->trigger($event);

        $data = $event->getData();

        if ( empty($data['result']) )
        {
            return array('result'=>false, 'error' => $data['error']);
        }

        $values['subject'] = $data['subject'];
        $values['message'] = $data['message'];

        $conversation = $conversationService->createConversation($userId, $values['opponentId'], htmlspecialchars($values['subject']), $values['message']);
        $message = $conversationService->getLastMessage($conversation->id);

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

        return array('result' => true, 'conversationId'=>$message->conversationId);

    }
}