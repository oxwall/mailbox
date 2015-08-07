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

class MAILBOX_Cron extends OW_Cron
{
    const UPLOAD_FILES_REMOVE_TIMEOUT = 86400; // 1 day

    public function __construct()
    {
        parent::__construct();

        if ( OW::getConfig()->configExists('mailbox', 'update_to_revision_7200') )
        {
            $this->addJob('mailboxUpdate', 2);
        }

        $this->addJob('resetAllUsersLastData', 1);
        $this->addJob('deleteAttachmentFiles', 1440); //1 day
    }

    public function run()
    {
        //ignore
    }

    public function mailboxUpdate()
    {
        MAILBOX_BOL_ConversationService::getInstance()->convertHtmlTags();
    }

    public function resetAllUsersLastData()
    {
        $sql = "SELECT COUNT(*) FROM `".MAILBOX_BOL_UserLastDataDao::getInstance()->getTableName()."` AS `uld`
LEFT JOIN `".BOL_UserOnlineDao::getInstance()->getTableName()."` AS uo ON uo.userId = uld.userId
WHERE uo.id IS NULL";

        $usersOfflineButOnline = OW::getDbo()->queryForColumn($sql);
        if ($usersOfflineButOnline > 0)
        {
            MAILBOX_BOL_ConversationService::getInstance()->resetAllUsersLastData();
        }
    }
    
    public function deleteAttachmentFiles()
    {
        MAILBOX_BOL_ConversationService::getInstance()->deleteAttachmentFiles();
    }
}