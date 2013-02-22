<?php
/**
 * Definition of eZCMISModuleHandler class
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
 * Module handler.
 * Goal is to check and call needed module
 *
 * @file ezcmismodulehandler.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisexceptions.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisservicehandler.php' );

class eZCMISModuleHandler
{
    /**
     * Processes requested module
     *
     * @param string Module name
     * @param array View parameters
     * @param string CMIS binding
     *
     * @return string XML or bool
     */
    public static function process( $moduleName, $params = array(), $binding = eZCMISServiceHandler::BINDING_REST )
    {
        $moduleMap = self::moduleMap();

        if ( !isset( $moduleMap[$moduleName] ) )
        {
            throw new eZCMISObjectNotFoundException( ezpI18n::tr( 'cmis', "Requested module is not available: '%module%'", null, array( '%module%' => $moduleName ) ) );
        }

        $moduleFile = $moduleMap[$moduleName]['script'];

        if ( !file_exists( $moduleFile ) )
        {
            throw new eZCMISRuntimeException( ezpI18n::tr( 'cmis', "Module include file does not exist: '%file%'", null, array( '%file%' => $moduleFile ) ) );
        }

        include_once( $moduleFile );

        $moduleClass = $moduleMap[$moduleName]['class'];
        if ( !class_exists( $moduleClass ) )
        {
            throw new eZCMISRuntimeException( ezpI18n::tr( 'cmis', "Class '%class%' does not exist", null, array( '%class%' => $moduleClass ) ) );
        }

        $module = new $moduleClass( $params, $binding );
        $result = $module->process();

        // If http code is defined need to return it
        if ( $module->getCode() )
        {
            header( 'HTTP/1.0 ' . $module->getCode() . ' Service Code', false, $module->getCode() );
        }

        return $result;
    }

    /**
     * Provides module map
     */
    public static function moduleMap()
    {
        return array( 'repository'   => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/modules/ezcmismodulerepository.php',
                                               'class' => 'eZCMISModuleRepository' ),
                      // Perhaps it should not be there. Move it to 'repository'?
                      'repositories' => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/modules/ezcmismodulerepositories.php',
                                               'class' => 'eZCMISModuleRepositories' ),
                      'node'         => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/modules/ezcmismodulenode.php',
                                               'class' => 'eZCMISModuleNode' ),
                      'children'     => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/modules/ezcmismodulechildren.php',
                                               'class' => 'eZCMISModuleChildren' ),
                      'descendants'  => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/modules/ezcmismoduledescendants.php',
                                               'class' => 'eZCMISModuleDescendants' ),
                      'content'      => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/modules/ezcmismodulecontent.php',
                                               'class' => 'eZCMISModuleContent' ),
                      'type'         => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/modules/ezcmismoduletype.php',
                                               'class' => 'eZCMISModuleType' ),
                      'types'        => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/modules/ezcmismoduletypes.php',
                                               'class' => 'eZCMISModuleTypes' ),
                      'login'        => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/modules/ezcmismodulelogin.php',
                                               'class' => 'eZCMISModuleLogin' ),
                      'parent'       => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/modules/ezcmismoduleparent.php',
                                               'class' => 'eZCMISModuleParent' ),
                      'parents'      => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/modules/ezcmismoduleparents.php',
                                               'class' => 'eZCMISModuleParents' ),
                      'test'         => array( 'script' => eZExtension::baseDirectory() . '/nxc_cmisserver/classes/modules/ezcmismoduletest.php',
                                               'class' => 'eZCMISModuleTest' ),

                      );
    }

    /**
     * Checks if module exists
     *
     * @return bool
     */
    public static function moduleExists( $name )
    {
        $moduleMap = self::moduleMap();

        return isset( $moduleMap[$name] );
    }
}
?>