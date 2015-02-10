<?php
/**
 * @author Zarif Safiullin <zaph.work@gmail.com>
 * @package ow.ow_plugins.mailbox
 * @since 1.7.2
 */

$sql = "UPDATE `".OW_DB_PREFIX."mailbox_conversation` as mc
INNER JOIN `".OW_DB_PREFIX."mailbox_message` as m ON mc.lastMessageId = m.id
SET mc.lastMessageTimestamp = m.timeStamp";
Updater::getDbo()->query($sql);