<?php
/**
 * Definition of eZCMISAtomTools class
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
 * List of tools for atom xml
 *
 * @file ezcmisatomtools.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisexceptions.php' );

class eZCMISAtomTools
{
    /**
     * Provides namespaces
     */
    public static function getNamespaceList()
    {
        return array( 'atom'   => 'http://www.w3.org/2005/Atom',
                      'app'    => 'http://www.w3.org/2007/app',
                      'cmis'   => 'http://docs.oasis-open.org/ns/cmis/core/200908/',
                      'cmisra' => 'http://docs.oasis-open.org/ns/cmis/restatom/200908/' );
    }

    /**
     * Creates DOM document
     */
    public static function createDocument()
    {
        $doc = new DOMDocument( '1.0', 'UTF-8' );
        $doc->formatOutput = true;

        return $doc;
    }

    /**
     * Creates root node by \a $documentType
     */
    public static function createRootNode( DOMDocument $doc, $documentType, $mainNs = 'atom' )
    {
        $namespaces = self::getNamespaceList();
        $root = $doc->createElementNS( $namespaces[$mainNs], $documentType );
        // @TODO: Review it, quite strange behaviour
        $addNs = $mainNs == 'atom' ? 'app' : 'atom';

        foreach( array( $addNs, 'cmis', 'cmisra' ) as $prefix )
        {
            $root->setAttributeNS( 'http://www.w3.org/2000/xmlns/', 'xmlns:' . $prefix, $namespaces[$prefix] );
        }

        return $root;
    }

    /**
     * Creates xml elements by \a $array with name \a $elementName
     */
    public static function createElementByArray( DOMDocument $doc, $elementName, $array, $prefix = 'cmis:' )
    {
        if ( !count( $array ) )
        {
            return false;
        }

        $element = $doc->createElement( $prefix . $elementName );
        foreach ( $array as $key => $value )
        {
            $subElement = !is_array( $value ) ? $doc->createElement( 'cmis:' . $key, htmlentities( $value ) ) : self::createElementByArray( $doc, $key, $value, 'cmis:' );
            $element->appendChild( $subElement );
        }

        return $element;
    }

    /**
     * Provides date
     *
     * @return string Date
     */
    public static function getDate( $timestamp )
    {
        return date( 'Y-m-d\TH:i:sP', $timestamp );
    }

    /**
     * Processes CMIS XML
     *
     * @param string $xml CMIS response XML.
     * @param string $xpath xpath expression.
     */
    public static function processXML( $xml, $xpath )
    {
        try
        {
            $cmisService = new SimpleXMLElement( $xml );
        }
        catch ( Exception $e )
        {
            throw new eZCMISRuntimeException( $e->getMessage() . ":\n " . $xml );
        }

        foreach ( self::getNamespaceList() as $key => $ns )
        {
            $cmisService->registerXPathNamespace( $key, $ns );
        }

        return $cmisService->xpath( $xpath );
    }

    /**
     * Provides XML node value
     *
     * @param $entry CMIS XML Node.
     * @param string $xpath expression.
     */
    public static function getXMLValue( SimpleXMLElement $entry, $xpath )
    {
        if ( !is_object( $entry ) )
        {
            return null;
        }

        $value = $entry->xpath( $xpath );

        return isset( $value[0] ) ? $value[0] : null;
    }

    /**
     * Fetches value from simple xml element
     *
     * @return SimpleXMLElement|bool
     */
    public static function getValue( SimpleXMLElement $entry, $name, $ns = 'atom' )
    {
        if ( !is_object( $entry ) )
        {
            return null;
        }

        // First try to fetch from entry
        if ( $entry->$name )
        {
            return $entry->$name;
        }

        $value = $entry->xpath( "//$ns:$name" );

        return isset( $value[0] ) ? $value[0] : false;
    }

    /**
     * Fetches an attribute from simple xml element
     *
     * @return string value
     */
    public static function getAttribute( $entry, $name, $attribut )
    {
        if( empty( $entry ) or empty( $name ) or empty( $attribut ) )
        {
            return null;
        }

        $xml = simplexml_load_string( $entry );
        if( $xml instanceof SimpleXMLElement )
        {
            $attributes = (array) $xml->$name->attributes();
            $value = $attributes["@attributes"][$attribut];

            return $value;
        }
        else
        {
            return null;
        }
    }

    /**
     * Fetches Object Type Id from XML Element \a $element
     *
     */
    public static function getPropertyValue( SimpleXMLElement $element, $name, $type = '*' )
    {
        return (string) self::getXMLValue( $element, 'cmisra:object/cmis:properties/cmis:' . $type . '[@propertyDefinitionId="' . $name . '"]/cmis:value' );
    }

    /**
     * Fetches Object Type Id property
     *
     * @note: Due to possible mess in XML request from clients about property types (like propertyString instead of propertyId)
     *        we temporary don't use types to find property value (default '*' type is used)
     */
    public static function getPropertyObjectTypeId( SimpleXMLElement $element )
    {
        return self::getPropertyValue( $element, 'cmis:objectTypeId' );
    }

    /**
     * Removes namespaces
     *
     * @return string
     */
    public static function removeNamespaces( $value )
    {
        $list = explode( ':', $value );

        return isset( $list[1] ) ? $list[1] : $list[0];
    }
}
?>