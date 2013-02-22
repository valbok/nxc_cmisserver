<?php
/**
 * Definition of General Exceptions
 *
 * Created on: <1-Jun-2009 20:59:01 vd>
 *
 * COPYRIGHT NOTICE: Copyright (C) 2001-2009 NXC AS
 * SOFTWARE LICENSE: GNU General Public License v2.0
 * NOTICE: >
 *   This program is free software; you can redistribute it and/or
 *   modify it under the terms of version 2.0  of the GNU General
 *   Public License as published by the Free Software Foundation.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of version 2.0 of the GNU General
 *   Public License along with this program; if not, write to the Free
 *   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 *   MA 02110-1301, USA.
 */

/**
 * The following exceptions MAY be returned by a repository in response to ANY CMIS service method call.
 *
 * @file ezcmisgeneralexceptions.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisexception.php' );

/**
 * One or more of the input parameters to the service method is missing or invalid.
 */
class eZCMISInvalidArgumentException extends eZCMISException
{
    /**
     * Constructor.
     */
    public function __construct( $message = '', $code = 400 )
    {
        parent::__construct( $message, $code );
    }
}

/**
 * The service call has specified an object that does not exist in the Repository.
 */
class eZCMISObjectNotFoundException extends eZCMISException
{
    /**
     * Constructor.
     */
    public function __construct( $message = '', $code = 404 )
    {
        parent::__construct( $message, $code );
    }
}

/**
 * The service method invoked requires an optional capability not supported by the Repository.
 */
class eZCMISOperationNotSupportedException extends eZCMISException
{
    /**
     * Constructor.
     */
    public function __construct( $message = '', $code = 405 )
    {
        parent::__construct( $message, $code );
    }
}

/**
 * The caller of the service method does not have sufficient permissions to perform the operation.
 */
class eZCMISPermissionDeniedException extends eZCMISException
{
    /**
     * Constructor.
     */
    public function __construct( $message = '', $code = 403 )
    {
        parent::__construct( $message, $code );
    }
}

/**
 * Any other cause not expressible by another CMIS exception.
 */
class eZCMISRuntimeException extends eZCMISException
{
    /**
     * Constructor.
     */
    public function __construct( $message = '', $code = 500 )
    {
        parent::__construct( $message, $code );
    }
}

?>
