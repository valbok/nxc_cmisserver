<?php
/**
 * Definition of eZCMISObjectDocument class
 *
 * Created on: <02-Jul-2009 20:59:01 vd>
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
 * Class of CMIS Document objects
 *
 * @file ezcmisobjectdocumennt.php
 */

include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/objects/ezcmisobjectbase.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/exceptions/ezcmisexceptions.php' );
include_once( eZExtension::baseDirectory() . '/nxc_cmisserver/classes/ezcmistypehandler.php' );

class eZCMISObjectDocument extends eZCMISObjectBase
{
    /**
     * @return string true if the repository SHALL throw an error at any attempt to update or delete the object.
     */
    public function isImmutable()
    {
        return $this->Node ? ( ( !$this->Node->canEdit() or !$this->Node->canRemove() ) ? 'true' : 'false' ) : 'false';
    }

    /**
     * @return string true if the Document object is the Latest Version in its Version Series.
     *                false otherwise.
     *
     * @TODO
     */
    public function isLatestVersion()
    {
        return 'true';
    }

    /**
     * @return string true if
     *
     * @TODO
     */
    public function isLatestMajorVersion()
    {
        return 'false';
    }

    /**
     * @return string true if the Document object is the Latest Major Version in its Version Series.
     *                false otherwise.
     *
     * @TODO
     */
    public function isMajorVersion()
    {
        return 'false';
    }

    /**
     * @return string Textual description the position of an individual object with respect to the version series. (For example, version 1.0).
     *
     * @TODO
     */
    public function getVersionLabel()
    {
        return '';
    }

    /**
     * @return string ID of the Version Series for this Object.
     *
     * @TODO
     */
    public function getVersionSeriesId()
    {
        return $this->Node ? $this->Node->attribute( 'remote_id' ) : '';
    }

    /**
     * @return string true if there currently exists a Private Working Copy for this Version Series.
     *                false otherwise
     *
     * @TODO
     */
    public function isVersionSeriesCheckedOut()
    {
        return 'false';
    }

    /**
     * If IsVersionSeriesCheckedOut is TRUE:  then an identifier for the user who created the Private Working Copy.
     * "Not set" otherwise.
     *
     * @TODO
     */
    public function getVersionSeriesCheckedOutBy()
    {
        return '';
    }

    /**
     * If IsVersionSeriesCheckedOut is TRUE:  The Identifier for the Private Working Copy.
     * "Not set" otherwise.
     *
     * @TODO
     */
    public function getVersionSeriesCheckedOutId()
    {
        return '';
    }

    /**
     * @return string Check in comment
     * @TODO
     */
    public function getCheckinComment()
    {
        return '';
    }

    /**
     * @return integer Length of the content stream (in bytes).
     */
    public function getContentStreamLength()
    {
        $length = 0;
        $attribute = $this->getContentAttribute();
        if ( !$attribute )
        {
            return $length;
        }

        if ( self::isFile( $attribute ) )
        {
            $info = $attribute->storedFileInformation( false, false, false );
            $file = eZClusterFileHandler::instance( $info['filepath'] );
            if ( $file->exists() )
            {
                $stat = $file->stat();
                $length = $stat['size'];
            }
        }
        else
        {
            $length = strlen( $attribute->toString() );
        }

        return $length;
    }

    /**
     * @return eZContentObjectAttribute Content attribute for the object
     */
    protected function getContentAttribute()
    {
        if ( !$this->Node )
        {
            return false;
        }

        $object = $this->Node->object();
        $dataMap = $object->dataMap();
        $attributeId = eZCMISTypeHandler::getContentAttributeId( $this->getObjectTypeId() );
        if ( !isset( $dataMap[$attributeId] ) )
        {
            throw new eZCMISRuntimeException( ezpI18n::tr( 'cmis', "Object does not have attribute: '%attribute%'", null, array( '%attribute%' => $attributeId ) ) );
        }

        return $dataMap[$attributeId];
    }

    /**
     * @return string MIME type of the Content Stream
     */
    public function getContentStreamMimeType()
    {
        $mimeType = 'text/plain';
        $attribute = $this->getContentAttribute();
        if ( !$attribute )
        {
            return $mimeType;
        }

        if ( self::isFile( $attribute ) )
        {
            $info = $attribute->storedFileInformation( false, false, false );
            if ( isset( $info['mime_type'] ) )
            {
                $mimeType = $info['mime_type'];
            }
        }
        else
        {
            // @TODO: Improve detecting of mimeType
            try
            {
                $element = new SimpleXMLElement( $attribute->toString() );
                $mimeType = 'text/xml';
            }
            catch ( Exception $e )
            {
                $mimeType = 'text/plain';
            }
        }

        return $mimeType;
    }

    /**
     * Checks if attribute contains a file
     *
     * @return bool
     */
    protected static function isFile( $attribute )
    {
        if ( !$attribute )
        {
            return null;
        }

        $dataType = $attribute->dataType();

        return $dataType->isRegularFileInsertionSupported();
    }

    /**
     * @return string File name of the content stream
     */
    public function getContentStreamFileName()
    {
        return $this->Node ? $this->Node->getName() : '';
    }

    /**
     * Fetches content stream
     *
     * @return byte[] Content stream
     */
    public function getContentStream()
    {
        $content = '';
        $attribute = $this->getContentAttribute();
        if ( !$attribute )
        {
            return $content;
        }

        if ( self::isFile( $attribute ) )
        {

            $info = $attribute->storedFileInformation($attribute->object(), $attribute->objectVersion(), $attribute->language() );

            $filePath = isset( $info['filepath'] ) ? $info['filepath'] : false;
            if ( !$filePath or !file_exists( $filePath ) )
            {
                // @TODO: Is it better to return empty string?
                eZCMISExceptions::resourceIsNotAvailable();
            }

            $content = file_get_contents( $filePath );
        }
        else
        {
            // @TODO: Review how to return XML
            $content = $attribute->toString();
        }

        return $content;
    }

    /**
     * Sets content stream to the object for current version
     *
     * @return bool
     */
    public function setContentStream( $content, $contentType )
    {
        $attribute = $this->getContentAttribute();
        if ( !$attribute )
        {
            return false;
        }

        $string = $content;
        if ( self::isFile( $attribute ) )
        {
            $mimeInfo = eZMimeType::findByName( $contentType );
            $suffix = isset( $mimeInfo['suffix'] ) ? '.' . $mimeInfo['suffix'] : '';
            $fileName = eZDir::path( array( sys_get_temp_dir(), uniqid( rand(), true ) . $suffix ) );
            $file = fopen( $fileName, 'w' );
            if ( !$file or ( !empty( $content ) and !fwrite( $file, $content ) ) )
            {
                throw new eZCMISStorageException( ezpI18n::tr( 'cmis', 'Could not store temp file' ) );
            }

            fclose( $file );

            $string = $fileName;
        }

        if ( !$attribute->fromString( $string ) )
        {
            throw new eZCMISStorageException( ezpI18n::tr( 'cmis', 'Could not store content to attribute' ) );
        }

        $attribute->sync();

        return true;
    }

    /**
     * Deletes content stream for current version
     *
     * @return bool
     */
    public function deleteContentStream()
    {
        $attribute = $this->getContentAttribute();
        if ( !$attribute )
        {
            return false;
        }

        $dataType = $attribute->dataType();
        if ( !$dataType )
        {
           return false;
        }

        $dataType->deleteStoredObjectAttribute( $attribute, $this->Node->attribute( 'contentobject_version' ) );

        return true;
    }

    /**
     * Checks if the content stream exists
     *
     * @return bool
     */
    public function hasContent()
    {
        $attribute = $this->getContentAttribute();

        return $attribute ? $attribute->hasContent() : false;
    }

    /**
     * Provides is content stream allowed
     *
     * @return string
     */
    public function getContentStreamAllowed()
    {
        $def = eZCMISTypeHandler::getTypeDefinition( $this->getObjectTypeId() );

        return isset( $def[0]['contentStreamAllowed'] ) ? $def[0]['contentStreamAllowed'] : '';
    }

    /**
     * Checks if content stream is allowed
     *
     * @return bool
     */
    public function isContentStreamAllowed()
    {
        return eZCMISTypeHandler::isContentStreamAllowedByTypeId( $this->getObjectTypeId() );
    }

    /**
     * Checks if content stream is NOT allowed
     *
     * @return bool
     */
    public function isContentStreamNotAllowed()
    {
        $type_handler = new eZCMISTypeHandler();
        return $type_handler->isContentStreamNotAllowedByTypeId( $this->getObjectTypeId() );
    }

    /**
     * Checks if content stream is required
     *
     * @return bool
     */
    public function isContentStreamRequired()
    {
        return eZCMISTypeHandler::isContentStreamRequiredByTypeId( $this->getObjectTypeId() );
    }
}
?>