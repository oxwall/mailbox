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
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_plugins.mailbox
 * @since 1.0
 */

if ( !OW::getConfig()->configExists('mailbox', 'results_per_page') )
{
    OW::getConfig()->addConfig('mailbox', 'results_per_page', 10, 'Conversations number per page');
}

if ( !OW::getConfig()->configExists('mailbox', 'enable_attachments') )
{
    OW::getConfig()->addConfig('mailbox', 'enable_attachments', true, 'Enable file attachments');
}

$sql = "CREATE TABLE IF NOT EXISTS `" . OW_DB_PREFIX . "mailbox_conversation` (
  `id` int(10) NOT NULL auto_increment,
  `initiatorId` int(10) NOT NULL default '0',
  `interlocutorId` int(10) NOT NULL default '0',
  `subject` varchar(100) NOT NULL default '',
  `read` tinyint(3) NOT NULL default '1' COMMENT 'bitmap, values: 0 - none, 1 - read by initiator, 2 - read by interlocutor, 3 - read all',
  `deleted` tinyint(3) NOT NULL default '0' COMMENT 'bitmap, values: 0 - none, 1 - deleted by initiator, 2 - deleted by interlocutor.',
  `viewed` tinyint(3) NOT NULL default '1' COMMENT 'bitmap, is user viewed conversation in console, values: 0 - none, 1 - viewed by initiator, 2 - viewed by interlocutor, 3 - viewed all',
  `notificationSent` tinyint(3) NOT NULL default '0' COMMENT 'int flag, was notification about this letter sent to user',
  `createStamp` int(10) default '0',
  `initiatorDeletedTimestamp` INT( 10 ) NOT NULL DEFAULT  '0',
  `interlocutorDeletedTimestamp` INT( 10 ) NOT NULL DEFAULT  '0',
  `lastMessageId` int(11) NOT NULL,
  `lastMessageTimestamp` int(11) NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `initiatorId` (`initiatorId`),
  KEY `interlocutorId` (`interlocutorId`),
  KEY `lastMessageTimestamp` (`lastMessageTimestamp`),
  KEY `subject` (`subject`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8";

OW::getDbo()->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS `" . OW_DB_PREFIX . "mailbox_last_message` (
  `id` int(10) NOT NULL auto_increment,
  `conversationId` int(10) NOT NULL default '0',
  `initiatorMessageId` int(10) NOT NULL default '0',
  `interlocutorMessageId` int(10) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `conversationId` (`conversationId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

OW::getDbo()->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS `" . OW_DB_PREFIX . "mailbox_message` (
  `id` int(10) NOT NULL auto_increment,
  `conversationId` int(10) NOT NULL default '0',
  `timeStamp` bigint(10) NOT NULL default '0',
  `senderId` int(10) NOT NULL default '0',
  `recipientId` int(10) NOT NULL default '0',
  `text` mediumtext NOT NULL,
  `recipientRead` TINYINT NOT NULL DEFAULT '0',
  `isSystem` TINYINT NOT NULL DEFAULT  '0',
  `wasAuthorized` TINYINT NOT NULL DEFAULT  '0',
  PRIMARY KEY  (`id`),
  KEY `senderId` (`senderId`),
  KEY `recipientId` (`recipientId`),
  KEY `conversationId` (`conversationId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

OW::getDbo()->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS `" . OW_DB_PREFIX . "mailbox_attachment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `messageId` int(11) NOT NULL,
  `hash` varchar(13) NOT NULL,
  `fileName` varchar(255) NOT NULL,
  `fileSize` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `messageId` (`messageId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

OW::getDbo()->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS `" . OW_DB_PREFIX . "mailbox_attachment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `messageId` int(11) NOT NULL,
  `hash` varchar(13) NOT NULL,
  `fileName` varchar(255) NOT NULL,
  `fileSize` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `messageId` (`messageId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

OW::getDbo()->query($sql);

$sql = "CREATE TABLE `" . OW_DB_PREFIX . "mailbox_user_last_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userId` int(11) NOT NULL,
  `data` longtext,
  PRIMARY KEY (`id`),
  KEY `userId` (`userId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8";

OW::getDbo()->query($sql);

//
//$authorization = OW::getAuthorization();
//$groupName = 'mailbox';
//$authorization->addGroup($groupName, 0);
//$authorization->addAction($groupName, 'read_message');
//$authorization->addAction($groupName, 'send_message');
//$authorization->addAction($groupName, 'reply_to_message');
//
//$authorization->addAction($groupName, 'read_chat_message');
//$authorization->addAction($groupName, 'send_chat_message');
//$authorization->addAction($groupName, 'reply_to_chat_message');

require_once OW_DIR_PLUGIN . 'mailbox' . DS . 'alloc.php';

$preference = BOL_PreferenceService::getInstance()->findPreference('mailbox_create_conversation_stamp');

if ( empty($preference) )
{
    $preference = new BOL_Preference();
}

$preference->key = 'mailbox_create_conversation_stamp';
$preference->sectionName = 'general';
$preference->defaultValue = 0;
$preference->sortOrder = 1;

BOL_PreferenceService::getInstance()->savePreference($preference);

$preference = BOL_PreferenceService::getInstance()->findPreference('mailbox_create_conversation_display_capcha');

if ( empty($preference) )
{
    $preference = new BOL_Preference();
}

$preference->key = 'mailbox_create_conversation_display_capcha';
$preference->sectionName = 'general';
$preference->defaultValue = false;
$preference->sortOrder = 1;

BOL_PreferenceService::getInstance()->savePreference($preference);

OW::getLanguage()->importPluginLangs(OW::getPluginManager()->getPlugin('mailbox')->getRootDir() . 'langs.zip', 'mailbox');

OW::getPluginManager()->addPluginSettingsRouteName('mailbox', 'mailbox_admin_config');

$preference = BOL_PreferenceService::getInstance()->findPreference('mailbox_user_settings_enable_sound');

if ( empty($preference) )
{
    $preference = new BOL_Preference();
}

$preference->key = 'mailbox_user_settings_enable_sound';
$preference->defaultValue = true;
$preference->sectionName = 'general';
$preference->sortOrder = 1;

BOL_PreferenceService::getInstance()->savePreference($preference);

$preference = BOL_PreferenceService::getInstance()->findPreference('mailbox_user_settings_show_online_only');

if ( empty($preference) )
{
    $preference = new BOL_Preference();
}

$preference->key = 'mailbox_user_settings_show_online_only';
$preference->defaultValue = true;
$preference->sectionName = 'general';
$preference->sortOrder = 1;

BOL_PreferenceService::getInstance()->savePreference($preference);

$modes = array('mail', 'chat');
OW::getConfig()->addConfig('mailbox', 'active_modes', json_encode($modes));
OW::getConfig()->addConfig('mailbox', 'show_all_members', false);
OW::getConfig()->addConfig('mailbox', 'updated_to_messages', 1);
OW::getConfig()->addConfig('mailbox', 'install_complete', 0);
OW::getConfig()->addConfig('mailbox', 'last_attachment_id', 0);
OW::getConfig()->addConfig('mailbox', 'plugin_update_timestamp', 0);
OW::getConfig()->addConfig('mailbox', 'send_message_interval', 60);