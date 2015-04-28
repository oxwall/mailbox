<?php

$sql = array(
    // fix for recipients
    'UPDATE 
            `' . OW_DB_PREFIX . 'mailbox_conversation` 
     SET 
        `interlocutorDeletedTimestamp` = UNIX_TIMESTAMP()
     WHERE 
        `deleted` = 2 
            AND 
         `interlocutorDeletedTimestamp` = 0',

    // fix for senders
    'UPDATE 
        `' . OW_DB_PREFIX . 'mailbox_conversation` 
     SET 
        `initiatorDeletedTimestamp` = UNIX_TIMESTAMP()
     WHERE 
        `deleted` = 1 
            AND 
        `initiatorDeletedTimestamp` = 0'
);

foreach ( $sql as $query )
{
    try
    {
        Updater::getDbo()->query($query);
    }
    catch ( Exception $e )
    {
        Updater::getLogger()->addEntry(json_encode($e));
    }
}