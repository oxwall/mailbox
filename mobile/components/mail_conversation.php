<?php

class MAILBOX_MCMP_MailConversation extends OW_MobileComponent
{
    public function __construct($data)
    {
        $script = UTIL_JsGenerator::composeJsString('

        OWM.bind("mailbox.ready", function(readyStatus){
            if (readyStatus == 2){
                OWM.conversation = new MAILBOX_Conversation({$params});
                OWM.conversationView = new MAILBOX_MailConversationView({model: OWM.conversation});
            }
        });
        ', array('params' => $data));

        OW::getDocument()->addOnloadScript($script);

        OW::getLanguage()->addKeyForJs('mailbox', 'text_message_invitation');

        $form = new MAILBOX_MCLASS_NewMailMessageForm($data['conversationId'], $data['opponentId']);
        $this->addForm($form);

        $this->assign('data', $data);
        $this->assign('defaultAvatarUrl', BOL_AvatarService::getInstance()->getDefaultAvatarUrl());
        
        $firstMessage = MAILBOX_BOL_ConversationService::getInstance()->getFirstMessage($data['conversationId']);

        if (empty($firstMessage))
        {
            $actionName = 'send_message';
        }
        else
        {
            $actionName = 'reply_to_message';
        }

        $isAuthorized = OW::getUser()->isAuthorized('mailbox', $actionName);
        if ( !$isAuthorized )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('mailbox', $actionName);

            if ( $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED )
            {
                $this->assign('sendAuthMessage', $status['msg']);
            }
            elseif ( $status['status'] != BOL_AuthorizationService::STATUS_AVAILABLE )
            {
                $this->assign('sendAuthMessage', OW::getLanguage()->text('mailbox', $actionName.'_permission_denied'));
            }
        }
    }
}