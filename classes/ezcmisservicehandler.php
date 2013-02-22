<?php
/**
 * Definition of eZCMISServiceHandler class
 *
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
 * Handler for CMIS services
 *
 * @file ezcmisservicehandler.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisexceptions.php' );

class eZCMISServiceHandler
{
    const BINDING_REST = 'RESTful';
    const BINDING_REST_METHOD = 'processRESTful';
    const BINDING_SOAP = 'SOAP';
    const BINDING_SOAP_METHOD = 'processSOAP';

    /**
     * Processes requested service
     *
     * @param array View parameters
     * @param string CMIS binding
     */
    public static function process( $serviceName, $params = array(), $binding = self::BINDING_REST )
    {
        $serviceMap = self::serviceMap();

        if ( !isset( $serviceMap[$serviceName] ) )
        {
            throw new eZCMISRuntimeException( ezpI18n::tr( 'cmis', "Requested service is not available: '%service%'", null, array( '%service%' => $serviceName ) ) );
        }

        $serviceFile = $serviceMap[$serviceName]['script'];

        if ( !file_exists( $serviceFile ) )
        {
            throw new eZCMISRuntimeException( ezpI18n::tr( 'cmis', "Service include file does not exist: '%service_file%'", null, array( '%service_file%' => $serviceFile ) ) );
        }

        include_once( $serviceFile );

        $serviceClass = $serviceMap[$serviceName]['class'];
        if ( !class_exists( $serviceClass ) )
        {
            throw new eZCMISRuntimeException( ezpI18n::tr( 'cmis', "Class '%class%' does not exist", null, array( '%class%' => $serviceClass ) ) );
        }

        $service = new $serviceClass( $params );

        $method = false;

        switch ( $binding )
        {
            case self::BINDING_REST:
            {
                $method = self::BINDING_REST_METHOD;
            } break;

            case self::BINDING_SOAP:
            {
                $method = self::BINDING_SOAP_METHOD;
            } break;

            default:
            {
                throw new eZCMISRuntimeException( ezpI18n::tr( 'cmis', 'Unknown binding' ) );
            } break;
        }

        if ( !method_exists( $service, $method ) )
        {
            throw new eZCMISRuntimeException( ezpI18n::tr( 'cmis', "Class '%service%' does not have method '%method%'", null, array( '%service%' => $serviceClass, '%method%' => $method ) ) );
        }

        $result = $service->$method();

        foreach ( $service->getHeaders() as $name => $value )
        {
            header( $name . ': ' . $value );
        }

        return $result;
    }

    /**
     * Provides service map
     */
    protected static function serviceMap()
    {
        return array( 'getRepositoryInfo'   => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetrepositoryinfo.php',
                                                      'class' => 'eZCMISServiceGetRepositoryInfo' ),
                      'getRepositories'     => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetrepositories.php',
                                                      'class' => 'eZCMISServiceGetRepositories' ),
                      'getProperties'       => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetproperties.php',
                                                      'class' => 'eZCMISServiceGetProperties' ),
                      'getChildren'         => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetchildren.php',
                                                      'class' => 'eZCMISServiceGetChildren' ),
                      'getDescendants'      => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetdescendants.php',
                                                      'class' => 'eZCMISServiceGetDescendants' ),
                      'getContentStream'    => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetcontentstream.php',
                                                      'class' => 'eZCMISServiceGetContentStream' ),
                      'setContentStream'    => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicesetcontentstream.php',
                                                      'class' => 'eZCMISServiceSetContentStream' ),
                      'deleteContentStream' => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicedeletecontentstream.php',
                                                      'class' => 'eZCMISServiceDeleteContentStream' ),
                      'deleteObject'        => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicedeleteobject.php',
                                                      'class' => 'eZCMISServiceDeleteObject' ),
                      'deleteTree'          => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicedeletetree.php',
                                                      'class' => 'eZCMISServiceDeleteTree' ),
                      'createFolder'        => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicecreatefolder.php',
                                                      'class' => 'eZCMISServiceCreateFolder' ),
                      'createDocument'      => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicecreatedocument.php',
                                                      'class' => 'eZCMISServiceCreateDocument' ),
                      'getFolderParent'     => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetfolderparent.php',
                                                      'class' => 'eZCMISServiceGetFolderParent' ),
                      'getObjectParents'    => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetobjectparents.php',
                                                      'class' => 'eZCMISServiceGetObjectParents' ),

                      'updateProperties'    => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisserviceupdateproperties.php',
                                                      'class' => 'eZCMISServiceUpdateProperties' ),
                      'getTypeDescendants'  => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegettypedescendants.php',
                                                      'class' => 'eZCMISServiceGetTypeDescendants' ),
                      'getTypeDefinition'   => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegettypedefinition.php',
                                                      'class' => 'eZCMISServiceGetTypeDefinition' ),

                      'login'               => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicelogin.php',
                                                      'class' => 'eZCMISServiceLogin' ),

                      );
    }

    /**
     * Checks if service is supported
     *
     * @return bool
     */
    public static function isServiceSupported( $serviceName )
    {
        $serviceMap = eZCMISServiceHandler::serviceMap();

        return isset( $serviceMap[$serviceName] );
    }

}
?>