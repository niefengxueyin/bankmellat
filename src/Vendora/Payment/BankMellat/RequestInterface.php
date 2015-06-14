<?php

/**
 * Copyright (c) 2014 Vendora
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Aram Alipoor <aram.alipoor@gmail.com>
 * @license MIT
 */

namespace Vendora\Payment\BankMellat;


interface RequestInterface {

    /**
     * Get a parameter from _GET or _POST or _REQUEST
     *
     * @param string $name    Parameter name
     * @param mixed  $default If parameter does not exist
     * @param string $source  If you want to explicitly read from a source (GET, POST, REQUEST)
     *
     * @return mixed
     */
    function get($name, $default = null, $source = null);
}