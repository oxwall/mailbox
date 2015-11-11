<?php

/**
 * Copyright (c) 2014, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com) and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

$langService = Updater::getLanguageService();

$keys = array('reply_to_chat_message_promoted', 'reply_to_message_promoted', 'send_chat_message_promoted', 'send_message_promoted');

foreach ($keys as $key)
{
    $langService->deleteLangKey('mailbox', $key);
}

$langService->importPrefixFromZip( dirname(__FILE__) . DS . 'langs.zip', 'mailbox' );
