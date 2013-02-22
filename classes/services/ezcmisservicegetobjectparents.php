<?php
/**
 * Definition of eZCMISServiceGetObjectParents class
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
 * @service getObjectParents: Gets the parent folder(s) for the specified non-folder, fileable object.
 * @file ezcmisserviceegetobjectparents.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicebase.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetrepositoryinfo.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetproperties.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetchildren.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisatomtools.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmis.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisobjecthandler.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisexceptions.php' );

class eZCMISServiceGetObjectParents extends eZCMISServiceBase
{
    /**
     * @reimp
     */
    protected function createFields()
    {
        $this->addField( 'repositoryId', null, false );
        $this->addField( 'objectId', null, true );

        /**
         * Repositories SHOULD return only the properties specified in the property filter.
         * Valid values for this parameter are:
         *     Not set: The set of properties to be returned SHALL be determined by the repository.
         *     "[queryName1], [queryName2], ...": The properties explicitly listed SHALL be returned.
         *     Note: The [queryName] tokens in the above expression SHALL be valid \u201cqueryName\u201d values
         *     as defined in the Object-types for the Objects whose properties are being returned.
         *     *: All properties SHALL be returned for all objects.
         *
         * @TODO: Implement
         */
        $this->addField( 'filter', false, false );
    }

    /**
     * @reimp
     */
    protected function checkFields()
    {
        parent::checkFields();

        $repositoryIdField = $this->getField( 'repositoryId' );
        $repository = new eZCMISServiceGetRepositoryInfo( array( 'repositoryId' => $repositoryIdField->getValue() ) );
        $repositoryId = $repository->getRepositoryId();
        $repositoryIdField->setValue( $repositoryId );

        $rootNode = eZCMISObjectHandler::fetchNode( $repositoryId );

        $objectId = $this->getField( 'objectId' )->getValue();

        $node = eZCMISObjectHandler::fetchNode( $objectId );
        $this->CMISObject = eZCMISObjectHandler::getObject( $node );

        if ( !$this->CMISObject or !eZCMIS::isChild( $rootNode, $node ) or !$this->CMISObject->isDocument() )
        {
            eZCMISExceptions::resourceIsNotAvailable();
        }

        if ( !$node->canRead() )
        {
            eZCMISExceptions::accessDenied();
        }

        /**
         * @TODO:
         * constraint: The Repository SHALL throw this exception if this method is invoked on an object
         *             who Object-Type Definition specifies that it is not fileable.
         */
    }

    /**
     * @reimp
     */
    public function processRESTful()
    {
        $repositoryId = $this->getField( 'repositoryId' )->getValue();
        $objectId = $this->getField( 'objectId' )->getValue();

        $node = $this->CMISObject->getNode();

        $doc = eZCMISAtomTools::createDocument();

        $root = eZCMISAtomTools::createRootNode( $doc, 'feed' );
        $doc->appendChild( $root );

        eZCMISServiceGetChildren::createHeader( $doc, $root, $this->CMISObject, array( 'repositoryId' => $repositoryId,
                                                                                       'suffix' => 'Parent' ) );

        if ( $objectId != $repositoryId )
        {
            // Return set of CMIS folders containing the object
            $object = $node->object();

            $parentNodes = $object->parentNodes();
            foreach ( $parentNodes as $parentNode )
            {
                $cmisObjectParent = eZCMISObjectHandler::getObject( $parentNode );
                if ( !$cmisObjectParent )
                {
                    continue;
                }

                $entry = $doc->createElement( 'entry' );
                $root->appendChild( $entry );

                eZCMISServiceGetProperties::createPropertyList( $doc, $entry, $repositoryId, $cmisObjectParent );
            }
        }

        return $doc->saveXML();
    }
}
?>
