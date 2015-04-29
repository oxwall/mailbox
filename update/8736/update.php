<?php

/**
 * Copyright (c) 2014, Skalfa LLC
 * All rights reserved.
 *
 * ATTENTION: This commercial software is intended for exclusive use with SkaDate Dating Software (http://www.skadate.com) and is licensed under SkaDate Exclusive License by Skalfa LLC.
 *
 * Full text of this license can be found at http://www.skadate.com/sel.pdf
 */

UPDATE_LanguageService::getInstance()->deleteLangKey('mailbox', 'disable_sounds');
UPDATE_LanguageService::getInstance()->deleteLangKey('mailbox', 'enable_sounds');

Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__) . DS . 'langs.zip', 'mailbox');