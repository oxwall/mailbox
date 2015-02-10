<?php
/**
 * @author Zarif Safiullin <zaph.work@gmail.com>
 * @package ow.ow_plugins.mailbox
 * @since 1.7.2
 */
$sql = "SELECT m.* FROM `".OW_DB_PREFIX."mailbox_message` AS `m`
LEFT JOIN `".OW_DB_PREFIX."base_user` AS `u` ON m.senderId = u.id
WHERE m.recipientRead=0 AND u.id IS NULL";

$list = Updater::getDbo()->queryForList($sql);

foreach($list as $message)
{
    $sql = "UPDATE `".OW_DB_PREFIX."mailbox_message` SET recipientRead=1 WHERE id=".$message['id'];
    Updater::getDbo()->query($sql);

    $sql = "UPDATE `".OW_DB_PREFIX."mailbox_conversation` SET `read`=3 WHERE `id`=".$message['conversationId'];
    Updater::getDbo()->query($sql);
}