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
 * Data Access Object for `mailbox_attachment` table.
 *
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_plugin.mailbox.bol
 * @since 1.0
 *
 */

class MAILBOX_BOL_AttachmentDao extends OW_BaseDao
{

    /**
     * Class constructor
     *
     */
    protected function __construct()
    {
        parent::__construct();
    }
    /**
     * Class instance
     *
     * @var MAILBOX_BOL_AttachmentDao
     */
    private static $classInstance;

    /**
     * Returns class instance
     *
     * @return MAILBOX_BOL_AttachmentDao
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * @see OW_BaseDao::getDtoClassName()
     *
     */
    public function getDtoClassName()
    {
        return 'MAILBOX_BOL_Attachment';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'mailbox_attachment';
    }

    /**
     *
     * @param array $messageIdList
     * @return array<MAILBOX_BOL_Attachment>
     */
    public function findAttachmentsByMessageIdList( array $messageIdList )
    {
        if ( empty($messageIdList) )
        {
            return array();
        }

        $example = new OW_Example();
        $example->andFieldInArray('messageId', $messageIdList);
        $example->setOrder('id');

        return $this->findListByExample($example);
    }

    /**
     *
     * @param int $messageId
     * @return MAILBOX_BOL_Attachment
     */
    public function findAttachmentsByMessageId( $messageId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('messageId', $messageId);
        $example->setOrder('id');

        return $this->findListByExample($example);
    }

    /**
     *
     * @param array $conversationIdList
     * @return array
     */
    public function getAttachmentsCountByConversationList( array $conversationIdList )
    {
        if ( empty($conversationIdList) )
        {
            return array();
        }

        $messageDao = MAILBOX_BOL_MessageDao::getInstance();

        $query = "
            SELECT `m`.`conversationId`, COUNT(a.id) AS `attachments`
            FROM `" . $this->getTableName() . "` AS `a`
            RIGHT JOIN `" . $messageDao->getTableName() . "` AS `m` ON (`a`.`messageId`=`m`.`id`)
            WHERE `m`.`conversationId` IN (" . $this->dbo->mergeInClause($conversationIdList) . ")
            GROUP BY `m`.`conversationId`
            ORDER BY null
        ";

        $result = $this->dbo->queryForList($query);
        $list = array();
        
        foreach( $result as $value )
        {
            $list[$value['conversationId']] = $value;
        }
        
        return $list;
    }

    /**
     *
     * @param array $conversationIdList
     * @return array
     */
    public function findAttachmentstByConversationList( array $conversationIdList )
    {
        if ( empty($conversationIdList) )
        {
            return array();
        }

        $messageDao = MAILBOX_BOL_MessageDao::getInstance();

        $query = "
            SELECT a.*, `m`.`conversationId`
            FROM `" . $this->getTableName() . "` AS `a`
            INNER JOIN `" . $messageDao->getTableName() . "` AS `m` ON (`a`.`messageId`=`m`.`id`)
            WHERE `m`.`conversationId` IN (" . $this->dbo->mergeInClause($conversationIdList) . ")
            ORDER BY `m`.`conversationId`, a.id
        ";

        $result = $this->dbo->queryForList($query);

        return $result;
    }

    public function findConversationsWithAttachmentFromConversationList($conversationIdList)
    {
        $condition = $this->dbo->mergeInClause($conversationIdList);

        $sql = "SELECT `m`.`conversationId` FROM `".MAILBOX_BOL_AttachmentDao::getInstance()->getTableName()."` as a
INNER JOIN `". MAILBOX_BOL_MessageDao::getInstance()->getTableName(). "` as m ON `m`.`id` = `a`.`messageId`
WHERE conversationId IN ({$condition})
GROUP BY `m`.`conversationId`";

        return $this->dbo->queryForColumnList($sql);
    }
    
    public function getAttachmentForDelete()
    {
          $sql = "SELECT `attach`.* 
                  FROM {$this->getTableName()} AS attach
                  LEFT OUTER JOIN `". MAILBOX_BOL_MessageDao::getInstance()->getTableName(). "` AS msg ON `attach`.`messageId` = `msg`.`id`
                  WHERE `msg`.`id` IS NULL
                  LIMIT 100";
          
          
          return $this->dbo->queryForObjectList($sql, $this->getDtoClassName());             
    }

//    /**
//     *
//     * @param array $conversationIdList
//     */
//    public function deleteAttachmentstByConversationList( array $conversationIdList )
//    {
//        if ( empty($conversationIdList) )
//        {
//            return;
//        }
//
//        $messageDao = MAILBOX_BOL_MessageDao::getInstance();
//
//        $query = "
//            DELETE a FROM `" . $this->getTableName() . "` AS `a`
//            INNER JOIN `" . $messageDao->getTableName() . "` AS `m` ON (`a`.`messageId`=`m`.`id`)
//            WHERE `m`.`conversationId` IN (" . $this->dbo->mergeInClause($conversationIdList) . ")
//        ";
//
//        $this->dbo->delete($query);
//    }
}