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


$tblPrefix = OW_DB_PREFIX;
$db = Updater::getDbo();
$logger = Updater::getLogger();

$queryList = array(
    "ALTER TABLE  `{$tblPrefix}mailbox_message` ADD  `isSystem` TINYINT NOT NULL DEFAULT  '0'",
    "ALTER TABLE  `{$tblPrefix}mailbox_message` ADD  `wasAuthorized` TINYINT NOT NULL DEFAULT  '0'",
    "ALTER TABLE  `{$tblPrefix}mailbox_conversation` ADD  `initiatorDeletedTimestamp` INT( 10 ) NOT NULL DEFAULT  '0'",
    "ALTER TABLE  `{$tblPrefix}mailbox_conversation` ADD  `interlocutorDeletedTimestamp` INT( 10 ) NOT NULL DEFAULT  '0'",
    "UPDATE `{$tblPrefix}mailbox_conversation` SET `viewed`=3"
);

foreach ( $queryList as $query )
{
    try
    {
        $db->query($query);
    }
    catch ( Exception $e )
    {
        $logger->addEntry(json_encode($e));
    }
}

try
{
    $authorization = OW::getAuthorization();
    $groupName = 'mailbox';
    $authorization->addAction($groupName, 'reply_to_message');

    $authorization->addAction($groupName, 'read_chat_message');
    $authorization->addAction($groupName, 'send_chat_message');
    $authorization->addAction($groupName, 'reply_to_chat_message');
}
catch ( Exception $e )
{
    $logger->addEntry(json_encode($e));
}

try
{
    $preference = new BOL_Preference();

    $preference->key = 'mailbox_user_settings_enable_sound';
    $preference->defaultValue = true;
    $preference->sectionName = 'general';
    $preference->sortOrder = 1;

    BOL_PreferenceService::getInstance()->savePreference($preference);
}
catch ( Exception $e )
{
    $logger->addEntry(json_encode($e));
}

try
{
    $preference = new BOL_Preference();

    $preference->key = 'mailbox_user_settings_show_online_only';
    $preference->defaultValue = false;
    $preference->sectionName = 'general';
    $preference->sortOrder = 1;

    BOL_PreferenceService::getInstance()->savePreference($preference);
}
catch ( Exception $e )
{
    $logger->addEntry(json_encode($e));
}


$modes = array('mail', 'chat');
Updater::getConfigService()->addConfig('mailbox', 'active_modes', json_encode($modes));
Updater::getConfigService()->addConfig('mailbox', 'show_all_members', true);
Updater::getConfigService()->addConfig('mailbox', 'update_to_revision_7200', 1, '');
Updater::getConfigService()->addConfig('mailbox', 'last_updated_id', 0, '');
Updater::getConfigService()->addConfig('mailbox', 'updated_to_messages', 0, '');
Updater::getConfigService()->addConfig('mailbox', 'install_complete', 1, '');

Updater::getConfigService()->deleteConfig('mailbox', 'upload_max_file_size');

Updater::getLanguageService()->importPrefixFromZip(dirname(dirname(dirname(__FILE__))) . DS . 'langs.zip', 'mailbox');