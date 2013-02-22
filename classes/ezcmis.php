<?php
/**
 * Definition of eZCMIS class
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
 * CMIS Domain Model
 *
 * @file ezcmis.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisexceptions.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmismodulehandler.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisserviceurl.php' );

class eZCMIS
{
    /**
     * Version of supported CMIS
     */
    const VERSION = '1.0';

    /**
     * Vendor of CMIS
     */
    const VENDOR = 'eZ Publish';

    /**
     * Processes requested service.
     *
     * @param array Parameters to handle the service
     * @return string Result XML
     */
    public static function process( $viewParameters )
    {
        $cmisURL = new eZCMISServiceURL( $viewParameters );
        $moduleName = $cmisURL->getModuleName();

        if ( !$moduleName )
        {
            eZCMISExceptions::isNotProvided( 'Module' );
        }
        $http = eZHTTPTool::instance();
$e = var_export($_SERVER,true);
eZLog::write( $e, "fuck.log" );

#exit;
        if ( isset( $_SERVER['PHP_AUTH_USER'] ) )
        {
            $userLogin = $_SERVER['PHP_AUTH_USER'];
            $userPassword = isset( $_SERVER['PHP_AUTH_PW'] ) ? $_SERVER['PHP_AUTH_PW'] : '';
            self::login( $userLogin, $userPassword );
        }
        elseif ( $http->hasGetVariable( eZCMISServiceURL::CMIS_TICKET ) )
        {
            self::loginByTicket( $http->getVariable( eZCMISServiceURL::CMIS_TICKET ) );
        }

        return eZCMISModuleHandler::process( $moduleName, $cmisURL->getServiceParams() );
    }

    /**
     * Logs in to eZ Publish by session key \a $ticket
     */
    public static function loginByTicket( $ticket )
    {
        $db = eZDB::instance();
        $ticket = $db->escapeString( $ticket );

        $user = $db->arrayQuery( 'SELECT user_id
                                  FROM   ezsession
                                  WHERE  session_key = \'' . $ticket . '\'' );

        $userObject = isset( $user[0]['user_id'] ) ? eZUser::fetch( $user[0]['user_id'] ) : false;
        if ( !$userObject )
        {
            eZCMISExceptions::accessDenied();
        }

        $userObject->loginCurrent();

        $http = eZHTTPTool::instance();
        $http->setSessionVariable( eZCMISServiceURL::CMIS_TICKET, $ticket );

        return true;
    }

    /**
     * Logs out from eZ Publish
     */
    public static function logout()
    {
        $user->logoutCurrent();

        $http = eZHTTPTool::instance();

        $http->removeSessionVariable( eZCMISServiceURL::CMIS_TICKET );
        $http->setSessionVariable( 'force_logout', 1 );
    }

    /**
     * Logs in to eZ Publish
     */
    public static function login( $userLogin, $userPassword = '' )
    {
        // @TODO: Use LoginHandler setting to handle login?
        $hasAccessToSite = true;

        $user = eZUser::loginUser( $userLogin, $userPassword );
        if ( $user instanceof eZUser )
        {
            $hasAccessToSite = $user->canLoginToSiteAccess( $GLOBALS['eZCurrentAccess'] );
            if ( !$hasAccessToSite )
            {
                $user->logoutCurrent();
                $user = null;
                $siteAccessName = $GLOBALS['eZCurrentAccess']['name'];
                $siteAccessAllowed = false;
            }
        }

        if ( !$user or !$hasAccessToSite )
        {
            eZCMISExceptions::accessDenied();
        }

        return true;
    }

    /**
     * Provides supported capabilities
     */
    public static function getCapabilities()
    {
        return array( 'capabilityACL' => 'none',
                      'capabilityAllVersionsSearchable' => 'false',
                      'capabilityChanges' => 'none',
                      'capabilityContentStreamUpdatability' => 'anytime',
                      'capabilityGetDescendants' => 'true',
                      'capabilityGetFolderTree' => 'false',
                      'capabilityMultifiling' => 'false',
                      'capabilityPWCSearchable' => 'false',
                      'capabilityPWCUpdatable' => 'false',
                      'capabilityQuery' => 'none',
                      'capabilityRenditions' => 'none',
                      'capabilityUnfiling' => 'false',
                      'capabilityVersionSpecificFiling' => 'false',
                      'capabilityJoin' => 'none',
                       );
    }

    /**
     * Checks if \a $node is child of \a $rootNode
     */
    public static function isChild( $rootNode, $node )
    {
        $db = eZDB::instance();
        $nodeId = $node->attribute( 'node_id' );
        $nodePath = $rootNode->attribute( 'path_string' );

        $pathString = "path_string like '$nodePath%' and ";

        $query = "SELECT ezcontentobject_tree.*
                  FROM   ezcontentobject_tree,
                         ezcontentobject,
                         ezcontentclass
                  WHERE  $pathString
                         node_id = $nodeId AND
                         ezcontentclass.version=0 AND
                         ezcontentobject_tree.contentobject_id = ezcontentobject.id  AND
                         ezcontentclass.id = ezcontentobject.contentclass_id";

        $nodeListArray = $db->arrayQuery( $query );

        return isset( $nodeListArray[0] ) ? true : false;
    }
}
?>