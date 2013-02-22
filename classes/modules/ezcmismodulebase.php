<?php
/**
 * Definition of eZCMISModuleBase class
 *
 * Created on: <25-Apr-2009 20:59:01 vd>
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
 * Base class for modules.
 * Module's goal is to handle HTTP method and call needed service
 *
 *
 * @file ezcmismodulebase.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisservicehandler.php' );

abstract class eZCMISModuleBase
{
    /**
     * List of service parameters
     *
     * @var array
     */
    protected $Params = array();

    /**
     * Current binding
     *
     * @var string
     * @sa eZCMISServiceHandler::BINDING_REST, eZCMISServiceHandler::BINDING_SOAP
     */
    protected $Binding = null;

    /**
     * HTTP Code
     *
     * var integer
     */
    protected $Code = null;

    /**
     * Constructor.
     *
     * @param array Parameters
     */
    public function __construct( $params = array(), $binding = eZCMISServiceHandler::BINDING_REST )
    {
        $this->Params = $params;
        $this->Binding = $binding;
    }

    /**
     * Processes by HTTP methods
     *
     * @param array where key is HTTP method and value is a function which should be executed on this method
     */
    protected function processByHTTPMethod( $methodBindingList )
    {
        $function = isset( $methodBindingList[$_SERVER['REQUEST_METHOD']] ) ? $methodBindingList[$_SERVER['REQUEST_METHOD']] : false;

        if ( !$function )
        {
            throw new eZCMISRuntimeException( ezpI18n::tr( 'cmis', 'Method not allowed' ) );
        }

        if ( !is_callable( array( $this, $function ) ) )
        {
            throw new eZCMISRuntimeException( ezpI18n::tr( 'cmis', "Class '%service%' does not have method '%method%'", null, array( '%service%' => get_class( $this ), '%method%' => $function ) ) );
        }

        return $this->$function();
    }

    /**
     * Handles requested module
     *
     * @return string XML or boolean
     */
    abstract protected function process();

    /**
     * Processes service by \a $serviceName
     */
    protected function processService( $serviceName )
    {
        return eZCMISServiceHandler::process( $serviceName, $this->Params, $this->Binding );
    }

    /**
     * Sets param value
     */
    protected function setParam( $name, $value )
    {
        $this->Params[$name] = $value;
    }

    /**
     * Provides param by \a $name
     *
     * @return string Param value
     */
    protected function getParamValue( $name )
    {
        return isset( $this->Params[$name] ) ? $this->Params[$name] : false;
    }

    /**
     * @return integer HTTP Code of handled operation
     */
    public function getCode()
    {
        return $this->Code;
    }
}

?>