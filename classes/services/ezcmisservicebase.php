<?php
/**
 * Definition of eZCMISServiceBase class
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
 * Abstract class for CMIS services
 *
 * @file ezcmisservicebase.php
 */

abstract class eZCMISServiceBase
{
    /**
     * Field list
     *
     * @var array of eZCMISServiceField
     */
    protected $Fields = array();

    /**
     * CMIS Object
     *
     * @var eZCMISObjectBase descendants
     */
    protected $CMISObject = null;

    /**
     * Header list
     *
     * @var array
     */
    protected $Headers = array();

    /**
     * Constructor.
     *
     * @param array Parameters
     */
    public function __construct( $params = array() )
    {
        $this->createFields();
        $this->initFields( $params );
        $this->checkFields();
    }

    /**
     * Addes header
     *
     * @param string Name
     * @param string Value
     * @param bool Overwrite existing
     */
    protected function addHeader( $name, $value, $overwrite = true )
    {
        if ( !$overwrite and isset( $this->Headers[$name] ) )
        {
            return;
        }

        $this->Headers[$name] = $value;
    }

    /**
     * Provides list of headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->Headers;
    }

    /**
     * Creates fields for current service
     */
    abstract protected function createFields();

    /**
     * Initializes fields by values provided in \a $params
     *
     * @param array Key is name of field, Value is its value
     */
    protected function initFields( $params )
    {
        foreach ( $this->getFields() as $name => $field )
        {
           if ( isset( $params[$name] ) )
           {
               $field->setValue( $params[$name] );
           }
        }
    }

    /**
     * Checks fields
     */
    protected function checkFields()
    {
        foreach ( $this->getFields() as $name => $field )
        {
            if ( $field->isRequired() and !$field->hasValue() )
            {
                eZCMISExceptions::isNotProvided( $name );
            }
        }
    }

    /**
     * Provides list of all fields
     *
     * @return array of eZCMISServiceField
     */
    protected function getFields()
    {
        return $this->Fields;
    }

    /**
     * Creates new field
     *
     * @param string Name
     * @param string Value
     * @param bool Required
     */
    protected function addField( $name, $value = null, $required = true )
    {
        if ( isset( $this->Fields[$name] ) )
        {
            throw new eZCMISRuntimeException( ezpI18n::tr( 'cmis', "Field '%name%' already exists", null, array( '%name%' => $name ) ) );
        }


        $this->Fields[$name] = new eZCMISServiceField( $name, $value, ( is_bool( $required ) ?  $required : false ) );
    }

    /**
     * Provides field object by \a $name
     *
     * @return eZCMISServiceField
     */
    protected function getField( $name )
    {
        if ( !isset( $this->Fields[$name] ) )
        {
            throw new eZCMISRuntimeException( ezpI18n::tr( 'cmis', "Field '%name%' does not exist", null, array( '%name%' => $name ) ) );
        }

        return $this->Fields[$name];
    }

    /**
     * Handles RESTFul binding
     *
     * @return string XML or boolean
     */
    abstract protected function processRESTful();

    /**
     * Handles soap binding
     *
     * @return string XML
     */
    protected function processSOAP()
    {
        return '';
    }
}

/**
 * Class for service fields
 */
class eZCMISServiceField
{
    /**
     * Field name
     *
     * @var string
     */
    protected $Name = null;

    /**
     * Field value
     *
     * @var string
     */
    protected $Value = null;

    /**
     * Is current field required
     *
     * @var bool
     */
    protected $IsRequired = null;

    /**
     * Constructor.
     *
     * @param string Name
     * @param string Value
     * @param bool Required
     */
    public function __construct( $name, $value = null, $isRequired = false )
    {
        $this->setName( $name );
        $this->setValue( $value );
        $this->setIsRequired( $isRequired );
    }

    /**
     * Sets name
     */
    public function setName( $name )
    {
       $this->Name = $name;
    }

    /**
     * Sets value
     */
    public function setValue( $value )
    {
        $this->Value = $value;
    }

    /**
     * Sets required
     */
    public function setIsRequired( $isRequired )
    {
        $this->IsRequired = $isRequired;
    }

    /**
     * Provides name
     */
    public function getName()
    {
       return $this->Name;
    }

    /**
     * Provides value
     */
    public function getValue()
    {
        return $this->Value;
    }

    /**
     * Checks if value is set
     */
    public function hasValue()
    {
        return $this->Value !== null ? true : false;
    }

    /**
     * Checks if fiels is required
     */
    public function IsRequired()
    {
        return $this->IsRequired;
    }
}

?>