<?php
/**
 * Definition of eZCMISServiceURL class
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
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisservicehandler.php' );

class eZCMISServiceURL
{
    /**
     * Module view of API
     */
    const API_VIEW = 'cmis/api';

    /**
     * Name of session key variable
     */
    const CMIS_TICKET = 'ez_ticket';

    /**
     * View parameters (requested url)
     *
     * @var array
     */
    protected $ViewParameters = array();

    /**
     * Constructor.
     *
     * @param array View parameters
     */
    public function __construct( $viewParameters )
    {
        $this->ViewParameters = $viewParameters;
    }

    /**
     * Provides URL to api view
     *
     * @return string URL
     */
    public static function getAPIView()
    {
        $view = self::API_VIEW;
        eZURI::transformURI( $view );

        return eZSys::serverURL() . $view;
    }

    /**
     * Organizes CMIS URL by \a $serviceName
     *
     * @param string Service name
     * @param array list of GET params where key is the var name and value - its value
     * @return string URL to service
     *
     * @sa self::getModuleName()
     * @TODO Handle params. Need to check params according to service
     */
    public static function createURL( $moduleName, $params = array() )
    {
        if ( !eZCMISModuleHandler::moduleExists( $moduleName ) )
        {
            return '';
        }

        $paramList = array();
        foreach ( $params as $name => $value )
        {
            $paramList[] = $name . '=' . $value;
        }

        $http = eZHTTPTool::instance();

        $paramStr = implode( '&', $paramList );
        $sessionKey = $http->hasSessionVariable( self::CMIS_TICKET ) ?  self::CMIS_TICKET . '=' . $http->sessionVariable( self::CMIS_TICKET ) . '&' : '';

        return self::getAPIView() . '/'. $moduleName . '?' . $sessionKey . $paramStr;
    }

    /**
     * Fetches module name from view parameters (means from requested URL)
     *
     * @return string Module name according to its URL
     *
     * @sa self::createURL()
     */
    public function getModuleName()
    {
        return isset( $this->ViewParameters[0] ) ? $this->ViewParameters[0] : false;
    }

    /**
     * Provides parameters that should be passed to requested service
     *
     * @return array Params
     */
    public function getServiceParams()
    {
        $http = eZHTTPTool::instance();
        $result = array();

        foreach ( $http->attribute( 'get' ) as $name => $param )
        {
            $result[$name] = $param;
        }

        // Provide post data
        $result['post_data'] = file_get_contents( 'php://input' );

        return $result;
    }

    /**
     * Provides current requested URI
     */
    public static function getRequestedURI()
    {
        return eZSys::serverURL() . $_SERVER['REQUEST_URI'];
    }
}
?>
