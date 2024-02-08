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
namespace gipfl\ZfDb\Adapter\Exception;

use gipfl\ZfDb\Exception\DbException;
use Exception;

/**
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class AdapterException extends DbException
{
    protected $_chainedException = null;

    public function __construct($message = '', $code = 0, Exception $e = null)
    {
        if ($e && (0 === $code)) {
            $code = $e->getCode();
            $code = ctype_digit($code) ? (int) $code : 0;
        }
        parent::__construct($message, $code, $e);
    }

    public function hasChainedException()
    {
        return ($this->getPrevious() !== null);
    }

    public function getChainedException()
    {
        return $this->getPrevious();
    }
}
