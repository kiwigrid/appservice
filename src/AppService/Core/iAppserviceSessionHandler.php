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

/**
 * An Interface class to provide a clean interface for storing and retrieving the session-id needed for each api call.
 * 
 * A Session class should implement this interface.
 * @author soeren.lubitz
 *
 */
interface iAppserviceSessionHandler
{
 	/**
     * Sets the Session
     *
     * @param string $sessionId The Session ID
     *
     * @return mixed Data associated with the key
     *
     */
    function storeSession($sessionId);

    /**
     * Gets the session
     *
     * The preferred format for a key is directory style so naming conflicts can be avoided.
     *
     */
    function retrieveSession();
}