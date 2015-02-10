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

try
{
    $sql = "REPLACE INTO `" . OW_DB_PREFIX . "base_preference` (`key`, `defaultValue`, `sectionName`, `sortOrder`) VALUES
                    ('mailbox_create_conversation_stamp', '0', 'general', 1),
                    ('mailbox_create_conversation_display_capcha', '0', 'general', 1)";

    Updater::getDbo()->query($sql);
}
catch ( Exception $ex )
{
    $errors[] = $ex;
}

OW::getPluginManager()->addPluginSettingsRouteName('mailbox', 'mailbox_admin_config');

if ( !OW::getConfig()->configExists('mailbox', 'enable_attachments') )
{
    OW::getConfig()->addConfig('mailbox', 'enable_attachments', true, 'Enable file attachments');
}

if ( !OW::getConfig()->configExists('mailbox', 'upload_max_file_size') )
{
    OW::getConfig()->addConfig('mailbox', 'upload_max_file_size', 2, 'Max upload file size(Mb)');
}

Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__) . DS . 'langs.zip', 'mailbox');

if ( !OW::getConfig()->configExists('mailbox', 'update_to_revision_3081') )
{
    OW::getConfig()->addConfig('mailbox', 'update_to_revision_3081', 1, '');
}

if ( !OW::getConfig()->configExists('mailbox', 'last_updated_id') )
{
    OW::getConfig()->addConfig('mailbox', 'last_updated_id', 0, '');
}

if ( !empty($errors) )
{
    printVar($errors);
}
