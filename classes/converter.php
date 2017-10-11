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

class MAILBOX_CLASS_Converter{

    /**
    * @var OW_Database
    */
    private $db;

    public function __construct()
    {
        $this->db = OW::getDbo();
    }

    public function start()
    {
        $this->alterMessagesTable();

        $sql = "select id, initiatorId, interlocutorId from `".MAILBOX_BOL_ConversationDao::getInstance()->getTableName()."` where subject != 'mailbox_chat_conversation'";
        $list = $this->db->queryForList($sql);

        foreach ($list as $conversation)
        {
            $chatId = $this->findChat($conversation);

            if ($chatId === false)
            {
                $this->createChat($conversation['id']);
            }
            else
            {
                $this->moveMessages($conversation, $chatId);
                $this->deleteConversation($conversation);
            }
        }

        $sql = "select id, initiatorId, interlocutorId from `".MAILBOX_BOL_ConversationDao::getInstance()->getTableName()."` where subject = 'mailbox_chat_conversation'";
        $list = $this->db->queryForList($sql);

        foreach ($list as $conversation)
        {
            $this->addLastMessages($conversation);
        }

    }

    private function alterMessagesTable()
    {


        $sql = "SELECT * FROM information_schema.COLUMNS WHERE 
    TABLE_SCHEMA = '".OW_DB_NAME."'
    AND TABLE_NAME = '".MAILBOX_BOL_MessageDao::getInstance()->getTableName()."'
    AND COLUMN_NAME = 'originalConversationId'";
        $result = $this->db->queryForRow($sql);

        if (empty($result))
        {
            $sql = "ALTER TABLE `".MAILBOX_BOL_MessageDao::getInstance()->getTableName()."` ADD `originalConversationId` INT(10) NULL DEFAULT NULL AFTER `conversationId`";
            $this->db->query($sql);
        }
    }

    private function addLastMessages($conversation)
    {
        $sql = "select `id`, `timeStamp` from `".MAILBOX_BOL_MessageDao::getInstance()->getTableName()."` where `senderId` = ? and `conversationId` = ? order by `timeStamp` DESC LIMIT 1";
        $initiatorLastMessage = $this->db->queryForRow($sql, array($conversation['initiatorId'], $conversation['id']));
        $initiatorLastMessage = empty($initiatorLastMessage) ? array('id' => 0, 'timeStamp' => 0) : $initiatorLastMessage;

        $sql = "select `id`, `timeStamp` from `".MAILBOX_BOL_MessageDao::getInstance()->getTableName()."` where `senderId` = ? and `conversationId` = ? order by `timeStamp` DESC LIMIT 1";
        $interlocutorLastMessage = $this->db->queryForRow($sql, array($conversation['interlocutorId'], $conversation['id']));
        $interlocutorLastMessage = empty($interlocutorLastMessage) ? array('id' => 0, 'timeStamp' => 0) : $interlocutorLastMessage;

        $sql = "select `id` from `".MAILBOX_BOL_LastMessageDao::getInstance()->getTableName()."` where `conversationId` = ?";
        $lastMessageConversation = $this->db->queryForRow($sql, array($conversation['id']));

        if (empty($lastMessageConversation))
        {
            $sql = "insert into `".MAILBOX_BOL_LastMessageDao::getInstance()->getTableName()."` 
                        (`conversationId`, `initiatorMessageId`, `interlocutorMessageId`) values (?, ?, ?)";
            $this->db->query($sql, array($conversation['id'], (int) $initiatorLastMessage['id'], (int) $interlocutorLastMessage['id']));
        }
        else
        {
            $sql = "UPDATE `".MAILBOX_BOL_LastMessageDao::getInstance()->getTableName()."` 
                    SET `initiatorMessageId` = ?, `interlocutorMessageId` = ? WHERE `conversationId` = ?";
            $this->db->query($sql, array((int) $initiatorLastMessage['id'], (int) $interlocutorLastMessage['id'], $conversation['id']));
        }

        $lastMessage = $initiatorLastMessage['timeStamp'] > $interlocutorLastMessage['timeStamp'] ? $initiatorLastMessage : $interlocutorLastMessage;

        $sql = "UPDATE `".MAILBOX_BOL_ConversationDao::getInstance()->getTableName()."` 
                    SET `lastMessageId` = ?, `lastMessageTimestamp` = ? WHERE `id` = ?";
        $this->db->query($sql, array($lastMessage['id'], $lastMessage['timeStamp'], $conversation['id']));
    }

    private function deleteConversation($conversation)
    {
        $sql = "delete from `".MAILBOX_BOL_ConversationDao::getInstance()->getTableName()."` where `id` = ?";
        $this->db->query($sql, array($conversation['id']));
    }

    private function findChat($conversation)
    {
        $sql = "select `id` from `".MAILBOX_BOL_ConversationDao::getInstance()->getTableName()."` where subject = 'mailbox_chat_conversation' and ((initiatorId = ? and interlocutorId = ?) or (initiatorId = ? and interlocutorId = ?))";
        $result = $this->db->queryForRow($sql, array($conversation['initiatorId'], $conversation['interlocutorId'], $conversation['interlocutorId'], $conversation['initiatorId']));
        if (!$result)
        {
            return false;
        }
        return $result['id'];
    }

    private function createChat($conversationId)
    {
        $sql = "update `".MAILBOX_BOL_ConversationDao::getInstance()->getTableName()."` set `subject` = 'mailbox_chat_conversation' where `id` = ?";
        $this->db->query($sql, array($conversationId));

        $sql = "update `".MAILBOX_BOL_MessageDao::getInstance()->getTableName()."` set `originalConversationId` = ? where conversationId = ?";
        $this->db->query($sql, array($conversationId, $conversationId));
    }

    private function getConversationFirstAndLastMessages($conversationId)
    {
        $sql = "select * from `".MAILBOX_BOL_MessageDao::getInstance()->getTableName()."` where conversationId = ? order by timeStamp ASC";
        $firstMessage = $this->db->queryForRow($sql, array($conversationId));

        $sql = "select * from `".MAILBOX_BOL_MessageDao::getInstance()->getTableName()."` where conversationId = ? order by timeStamp DESC";
        $lastMessage = $this->db->queryForRow($sql, array($conversationId));

        return array($firstMessage, $lastMessage);
    }

    private function findChatPrevAndNextMessages($messageTimestamp, $chatId)
    {
        $sql = "select * from `".MAILBOX_BOL_MessageDao::getInstance()->getTableName()."` where conversationId = ? and timeStamp < ? order by timeStamp DESC";
        $prevMessage = $this->db->queryForRow($sql, array($chatId, $messageTimestamp));

        $sql = "select * from `".MAILBOX_BOL_MessageDao::getInstance()->getTableName()."` where conversationId = ? and timeStamp > ? order by timeStamp ASC";
        $nextMessage = $this->db->queryForRow($sql, array($chatId, $messageTimestamp));

        return array($prevMessage, $nextMessage);
    }

    private function getConvertedConversationLastMessage($originalConversationId)
    {
        $sql = "select * from `".MAILBOX_BOL_MessageDao::getInstance()->getTableName()."` where originalConversationId = ? order by timeStamp DESC limit 1";
        return $this->db->queryForRow($sql, array($originalConversationId));
    }

    private function getConversationNextMessage($message)
    {
        $sql = "select * from `".MAILBOX_BOL_MessageDao::getInstance()->getTableName()."` where conversationId = ? and timeStamp > ? order by timeStamp ASC limit 1";
        return $this->db->queryForRow($sql, array($message['conversationId'], $message['timeStamp']));
    }

    private function getConversationMessagesCount($conversationId)
    {
        $sql = "select count(id) from `".MAILBOX_BOL_MessageDao::getInstance()->getTableName()."` where conversationId = ?";
        $result = $this->db->queryForRow($sql, array($conversationId));
        return array_pop($result);
    }

    private function getConversationMessages($conversationId)
    {
        $sql = "select * from `".MAILBOX_BOL_MessageDao::getInstance()->getTableName()."` where conversationId = ? order by timeStamp ASC";
        return $this->db->queryForList($sql, array($conversationId));
    }

    private function siftChatMessage($message, $messagesCount, $chatId)
    {
        $sql = "update `".MAILBOX_BOL_MessageDao::getInstance()->getTableName()."` set `timeStamp` = `timeStamp` + ? where `timeStamp` >= ? and conversationId = ?";
        $this->db->query($sql, array($messagesCount, $message['timeStamp'], $chatId));
    }

    private function updateConversation($conversationId, $previousMessage, $chatId)
    {
        $messages = $this->getConversationMessages($conversationId);
        $timeStamp = $previousMessage['timeStamp'];
        $sql = "update `".MAILBOX_BOL_MessageDao::getInstance()->getTableName()."` set `timeStamp` = ?, `conversationId` = ?, `originalConversationId` = ? where id = ?";
        foreach ($messages as $m)
        {
            $timeStamp++;
            $this->db->query($sql, array($timeStamp, $chatId, $conversationId, $m['id']));
        }
    }

    private function moveMessages($data, $chatId)
    {
        list($firstMessage, $lastMessage) = $this->getConversationFirstAndLastMessages($data['id']);
        $messagesCount = $this->getConversationMessagesCount($data['id']);

        if ($messagesCount  == 0)
        {
            return;
        }

        list($prevChatMessage, $nextChatMessage) = $this->findChatPrevAndNextMessages($firstMessage['timeStamp'], $chatId);

        if (empty($prevChatMessage) &&  empty($nextChatMessage))
        {
            $sql = "update `".MAILBOX_BOL_MessageDao::getInstance()->getTableName()."` set `conversationId` = ?, `originalConversationId` = ? where conversationId = ?";
            $this->db->query($sql, array($chatId, $data['id'], $data['id']));
            return;
        }
        else if ( empty($prevChatMessage) && !empty($nextChatMessage))
        {
            $this->updateConversation($data['id'], $firstMessage, $chatId);
            return;
        }
        else if (!empty($prevChatMessage) && empty($nextChatMessage))
        {
            $this->updateConversation($data['id'], $prevChatMessage, $chatId);
            return;
        }

        if (!empty($nextChatMessage['originalConversationId']))
        {
            $prevChatMessage = $this->getConvertedConversationLastMessage($nextChatMessage['originalConversationId']);
            $nextChatMessage = $this->getConversationNextMessage($prevChatMessage);
        }

        if (empty($nextChatMessage))
        {
            $availableSize = $messagesCount + 1;
        }
        else
        {
            $availableSize = $nextChatMessage['timeStamp'] - $prevChatMessage['timeStamp'];
        }

        if ($availableSize > $messagesCount)
        {
            $this->updateConversation($data['id'], $prevChatMessage, $chatId);
        }
        else
        {
            $this->siftChatMessage($nextChatMessage, $messagesCount, $chatId);
            $this->updateConversation($data['id'], $prevChatMessage, $chatId);
        }
    }

}
