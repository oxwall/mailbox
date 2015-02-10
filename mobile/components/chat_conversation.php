<?php

class MAILBOX_MCMP_ChatConversation extends OW_MobileComponent
{
    public function __construct($data)
    {
        $script = UTIL_JsGenerator::composeJsString('
        OWM.conversation = new MAILBOX_Conversation({$params});
        OWM.conversationView = new MAILBOX_ConversationView({model: OWM.conversation});
        ', array('params' => $data));

        OW::getDocument()->addOnloadScript($script);

        OW::getLanguage()->addKeyForJs('mailbox', 'text_message_invitation');

        $form = new MAILBOX_MCLASS_NewMessageForm($data['conversationId'], $data['opponentId']);
        $this->addForm($form);

        $this->assign('data', $data);
        $this->assign('defaultAvatarUrl', BOL_AvatarService::getInstance()->getDefaultAvatarUrl());
    }
}
