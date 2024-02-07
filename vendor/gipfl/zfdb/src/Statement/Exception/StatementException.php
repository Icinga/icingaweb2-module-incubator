<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */
namespace gipfl\ZfDb\Statement\Exception;

use gipfl\ZfDb\Exception\DbException;

/**
 * Zend_Db_Statement_Exception
 *
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class StatementException extends DbException
{
    /**
     * Check if this general exception has a specific database driver specific exception nested inside.
     *
     * @return bool
     */
    public function hasChainedException()
    {
        return ($this->getPrevious() !== null);
    }

    /**
     * @return \Exception|null
     */
    public function getChainedException()
    {
        return $this->getPrevious();
    }
}
