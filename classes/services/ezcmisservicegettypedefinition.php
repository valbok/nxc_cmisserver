<?php
/**
 * Definition of eZCMISServiceGetTypeDefinition class
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
 * @service getTypeDefinition: Gets the definition of the specified Object-Type.
 * @file ezcmisservicegettypedefinition.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicebase.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisatomtools.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmis.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisexceptions.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmistypehandler.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegetproperties.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/services/ezcmisservicegettypedescendants.php' );

class eZCMISServiceGetTypeDefinition extends eZCMISServiceBase
{
    /**
     * @reimp
     */
    protected function createFields()
    {
        $this->addField( 'repositoryId', null, false );
        $this->addField( 'typeId', null, true );
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
    }

    /**
     * @reimp
     */
    public function processRESTful()
    {
        $repositoryId = $this->getField( 'repositoryId' )->getValue();
        $typeId = $this->getField( 'typeId' )->getValue();

        $type = eZCMISTypeHandler::getTypeDefinition( $typeId, 1, true );
        if ( !isset( $type[0] ) )
        {
            eZCMISExceptions::resourceIsNotAvailable();
        }

        $doc = eZCMISAtomTools::createDocument();

        $root = eZCMISAtomTools::createRootNode( $doc, 'entry' );
        $doc->appendChild( $root );

        eZCMISServiceGetTypeDescendants::createElement( $doc, $root, $type[0], $repositoryId );

        return $doc->saveXML();
    }
}
?>
