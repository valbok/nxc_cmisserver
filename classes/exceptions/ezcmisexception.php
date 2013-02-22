<?php
/**
 * Definition of eZCMISException class

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
 * Base exception class to use getError() function
 *
 * @file ezcmisexception.php
 */

class eZCMISException extends Exception
{
    /**
     * Gets the Exception error
     *
     * @return string Error
     */
    public function getError()
    {
        // Exclude 'eZCMIS' from class name
        $class = substr( get_class( $this ), 6, strlen( get_class( $this ) ) );

        return $class . ': ' . $this->getMessage();
    }
}


?>