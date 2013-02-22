<?php
/**
 * Definition of eZCMISModuleChildren class
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
 * Handles operations on children collection
 *
 * @services:
 *    GET: getChildren
 *    POST:
 *         createDocument
 *         or createFolder
 *         or createPolicy
 *         or moveObject
 *         or addObjectToFolder
 *
 * @file ezcmismodulechildren.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/modules/ezcmismodulebase.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmisatomtools.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmistypehandler.php' );

class eZCMISModuleChildren extends eZCMISModuleBase
{
    /**
     * Processes GET methods
     */
    protected function processGET()
    {
        $this->Code = 200;

        return $this->processService( 'getChildren' );
    }

    /**
     * Processes POST methods
     */
    protected function processPOST()
    {
        $result = false;
        $postData = isset( $this->Params['post_data'] ) ? $this->Params['post_data'] : false;

        $info = eZCMISAtomTools::processXML( $postData, '/atom:entry' );

        // If the atom entry has a cmis property cmis:objectId that is valid for the repository, the object will be added to the folder.
        $objectId = eZCMISAtomTools::getPropertyValue( $info[0], 'cmis:objectId' );
        if ( $objectId )
        {
            // @TODO: Handle moveObject and addObjectToFolder services
        }
        else // If the cmis:objectId property is missing, object will be created and then added to the folder.
        {
            $typeId = eZCMISAtomTools::getPropertyObjectTypeId( $info[0] );
            $baseType = eZCMISTypeHandler::getBaseTypeByTypeId( $typeId );

            if ( !$typeId )
            {
                eZCMISExceptions::isNotProvided( 'objectTypeId' );
            }

            $baseType = eZCMISTypeHandler::getBaseTypeByTypeId( $typeId );

            if ( eZCMISTypeHandler::isDocument( $baseType ) )
            {
                $result = $this->processService( 'createDocument' );
            }
            elseif ( eZCMISTypeHandler::isFolder( $baseType ) )
            {
                $result = $this->processService( 'createFolder' );
            }
            else
            {
                throw new eZCMISRuntimeException( ezpI18n::tr( 'cmis', 'Unknown type: %type%', null, array( '%type%' => $typeId ) ) );
            }
        }

        $this->Code = 201;

        return $result;
    }

    /**
     * @reimp
     */
    public function process()
    {
        return $this->processByHTTPMethod( array( 'GET' => 'processGET',
                                                  'POST' => 'processPOST' ) );
    }
}
?>