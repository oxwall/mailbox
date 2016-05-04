<?php

class MAILBOX_MCLASS_NewMailMessageForm extends Form
{
    public function __construct($conversationId, $opponentId)
    {
        parent::__construct('newMailMessageForm');

        $this->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);

        $field = new TextField('newMessageText');
        $field->setHasInvitation(true);
        $field->setInvitation(OW::getLanguage()->text('mailbox', 'text_message_invitation'));
        $field->setId('newMessageText');
        $this->addElement($field);

        $field = new HiddenField('attachment');
        $this->addElement($field);

        $field = new HiddenField('conversationId');
        $field->setValue($conversationId);
        $this->addElement($field);

        $field = new HiddenField('opponentId');
        $field->setValue($opponentId);
        $this->addElement($field);

        $field = new HiddenField('uid');
        $field->setValue( UTIL_HtmlTag::generateAutoId('mailbox_conversation_'.$conversationId.'_'.$opponentId) );
        $this->addElement($field);

        $submit = new Submit('newMessageSendBtn');
        $submit->setId('newMessageSendBtn');
        $submit->setName('newMessageSendBtn');
        $submit->setValue(OW::getLanguage()->text('mailbox', 'add_button'));
        $this->addElement($submit);

        if ( !OW::getRequest()->isAjax() )
        {
            $js = UTIL_JsGenerator::composeJsString('
            owForms["newMailMessageForm"].bind( "submit", function( r )
            {
                $("#newmessage-mail-send-btn").addClass("owm_preloader_circle");
            });');
            OW::getDocument()->addOnloadScript( $js );
        }

        $this->setAction( OW::getRouter()->urlFor('MAILBOX_MCTRL_Messages', 'newmessage') );
    }
}