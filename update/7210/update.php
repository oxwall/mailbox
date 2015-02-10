<?php
/**
 * @author Zarif Safiullin <zaph.work@gmail.com>
 * @package ow.ow_plugins.mailbox
 * @since 1.7.2
 */
$sql = "ALTER TABLE `".OW_DB_PREFIX."mailbox_conversation`  ADD `lastMessageId` INT(11) NOT NULL,  ADD `lastMessageTimestamp` INT(11) NOT NULL";
Updater::getDbo()->query($sql);

$sql = "UPDATE `".OW_DB_PREFIX."mailbox_conversation` as mc
INNER JOIN `".OW_DB_PREFIX."mailbox_last_message` as lm ON mc.id = lm.conversationId
SET mc.lastMessageId = GREATEST(lm.initiatorMessageId, lm.interlocutorMessageId)";
Updater::getDbo()->query($sql);

$sql = "UPDATE `".OW_DB_PREFIX."mailbox_conversation` as mc
INNER JOIN `".OW_DB_PREFIX."mailbox_message` as m ON mc.lastMessageId = m.id
SET mc.lastMessageTimestamp = m.timeStamp";
Updater::getDbo()->query($sql);

$sql = "ALTER TABLE `".OW_DB_PREFIX."mailbox_conversation` ADD INDEX `lastMessageTimestamp` (`lastMessageTimestamp`)";
Updater::getDbo()->query($sql);

$sql = "ALTER TABLE `".OW_DB_PREFIX."mailbox_conversation` ADD INDEX `subject` (`subject`)";
Updater::getDbo()->query($sql);
