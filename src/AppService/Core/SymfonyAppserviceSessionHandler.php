<?php
/* ************************************************************************* *
 *                                                                           *
*   Copyright (c) 2012 KIWIGRID GmbH                                        *
*                                                                           *
*   All Rights Reserved                                                     *
*                                                                           *
*   This source code is property of KIWIGRID GmbH. Any redistribution of    *
*   the source code (modified or not modified) is protected by copyright.   *
*   You must not copy, distribute, compile or decompile this code           *
*   or parts of it.                                                         *
*                                                                           *
*   http://www.kiwigrid.com                                                 *
*                                                                           *
* ************************************************************************* */
namespace AppService\Core;

use Symfony\Component\HttpFoundation\Session\Session;

/**
 * A Symfony2 specific AppServiceSessionHandler to enrich the Symfony Session with this functionality
 * @author soeren.lubitz
 *
 */
class SymfonyAppserviceSessionHandler extends Session implements iAppserviceSessionHandler
{
	//the key under which the session-id should be stored in the user session
	const SESSION_KEY = 'appservice.session';
 	/**
     * Sets the Session
     *
     * @param string $sessionId The Session ID
     *
     * @return mixed Data associated with the key
     *
     */
    public function storeSession($sessionId)
    {
    	$this->set(self::SESSION_KEY, $sessionId);
    }

    /**
     * Gets the session
     *
     * The preferred format for a key is directory style so naming conflicts can be avoided.
     *
     */
    public function retrieveSession()
    {
    	return $this->get(self::SESSION_KEY);
    }
}