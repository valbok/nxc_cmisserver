<?php
/**
 * Created on: <1-Jun-2009 10:00:00 vd>
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
 * View for CMIS services
 */

include_once( eZExtension::baseDirectory() .'/nxc_cmisserver/classes/ezcmis.php' );

$Module = $Params['Module'];
$userParameters = $Params['UserParameters'];

try
{

    $result = eZCMIS::process( $Module->ViewParameters );
    if ( $result )
    {
        echo $result;
    }
}
catch ( Exception $error )
{
    $code = $error->getCode();
    // If access is denied propose to provide user/pass by Basic HTTP Authentication
    if ( $code == 403 )
    {
        header( 'WWW-Authenticate: Basic realm="eZ Publish"' );
    }
    else
    {
        header( 'HTTP/1.0 ' . $code . ' ' . $error->getMessage(), true, $code );
    }

    echo $error instanceof Exception ? ( method_exists( $error, 'getError' ) ? $error->getError() : $error->getMessage() ) : '';
}

eZExecution::cleanExit();

?>