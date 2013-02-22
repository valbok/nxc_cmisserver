<?php
/**
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
 * List of CMIS exceptions
 *
 * @file ezcmisexceptions.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisgeneralexceptions.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisspecificexceptions.php' );

class eZCMISExceptions
{
    /**
     * Throws access denied exception
     */
    public static function accessDenied()
    {
        throw new eZCMISPermissionDeniedException( ezpI18n::tr( 'cmis', 'Access denied' ) );
    }

    /**
     * Throws resource is not available exception
     */
    public static function resourceIsNotAvailable()
    {
        throw new eZCMISObjectNotFoundException( ezpI18n::tr( 'cmis', 'Requested resource is not available' ) );
    }

    /**
     * Throws argument \a $var is not provided
     */
    public static function isNotProvided( $var )
    {
        throw new eZCMISInvalidArgumentException( "'" . $var . "' " . ezpI18n::tr( 'cmis', 'is not provided' ) );
    }

    /**
     * Throws content is not supported exception
     */
    public static function contentStreamIsNotSupported()
    {
        throw new eZCMISStreamNotSupportedException( ezpI18n::tr( 'cmis', 'Content stream is not supported' ) );
    }

    /**
     * Throws content is required exception
     */
    public static function contentStreamIsRequired()
    {
        throw new eZCMISConstraintViolationException( ezpI18n::tr( 'cmis', 'Content stream is required' ) );
    }

}
?>