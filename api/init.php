<?php

$eventHandler = new MAILBOX_CLASS_EventHandler();
$eventHandler->genericInit();

OW::getRouter()->addRoute(new OW_Route('mailbox_chat_conversation', 'messages/chat/:userId', 'MAILBOX_MCTRL_Messages', 'chatConversation'));
OW::getRouter()->addRoute(new OW_Route('mailbox_mail_conversation', 'messages/mail/:convId', 'MAILBOX_MCTRL_Messages', 'mailConversation'));