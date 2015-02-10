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
 * Mailbox controller
 *
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow_plugin.mailbox.controllers
 * @since 1.0
 */
class MAILBOX_CTRL_Mailbox extends OW_ActionController
{
    /**
     * @var string
     */
    public $responderUrl;

    /**
     * @see OW_ActionController::init()
     *
     */
    public function init()
    {
        parent::init();

        $language = OW::getLanguage();

        $this->setPageHeading($language->text('mailbox', 'mailbox'));
        $this->setPageHeadingIconClass('ow_ic_mail');
    }

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->responderUrl = OW::getRouter()->urlFor("MAILBOX_CTRL_Mailbox", "responder");
    }

    /**
     * Action for mailbox ajax responder
     */
    public function responder()
    {
        if ( empty($_POST["function_"]) || !OW::getRequest()->isAjax() )
        {
            throw new Redirect404Exception();
        }

        $function = (string) $_POST["function_"];

        $responder = new MAILBOX_CLASS_Responder();
        $result = call_user_func(array($responder, $function), $_POST);

        echo json_encode(array('result' => $result, 'error' => $responder->error, 'notice' => $responder->notice));
        exit();
    }

    public function users( $params )
    {
        header('Content-Type: text/plain');

        if (!OW::getUser()->isAuthenticated())
        {
            exit( json_encode(array()) );
        }

        $conversationService = MAILBOX_BOL_ConversationService::getInstance();
        $data = $conversationService->getUserList(OW::getUser()->getId());

        exit( base64_encode(json_encode($data['list'])) );
    }

    public function convs( $params )
    {
        header('Content-Type: text/plain');

        if (!OW::getUser()->isAuthenticated())
        {
            exit( json_encode(array()) );
        }

        $conversationService = MAILBOX_BOL_ConversationService::getInstance();
        $data = $conversationService->getConversationListByUserId(OW::getUser()->getId());

        exit( base64_encode(json_encode($data)) );
    }

    public function testapi($params)
    {
        $commands = array(
            array(
                'name'=>'mailbox_api_ping',
                'params'=>array(
                    'lastRequestTimestamp'=>0
                )
            )
        );

        $commandsResult = array();
        foreach ($commands as $command)
        {
//            pv($command);
            $event = new OW_Event('base.ping' . '.' . trim($command["name"]), $command["params"]);
            OW::getEventManager()->trigger($event);

            $event = new OW_Event('base.ping', array(
                "command" => $command["name"],
                "params" => $command["params"]
            ), $event->getData());
            OW::getEventManager()->trigger($event);

            $commandsResult[] = array(
                'name' => $command["name"],
                'data' => $event->getData()
            );
        }

//        pv($commandsResult);

        exit('end');
    }
}