<?php

class MAILBOX_MCLASS_NewMessageForm extends Form
{
    public function __construct($conversationId, $opponentId)
    {
        parent::__construct('newMessageForm');

        $this->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);

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

        $this->setAction( OW::getRouter()->urlFor('MAILBOX_MCTRL_Messages', 'attachment') );
    }
}