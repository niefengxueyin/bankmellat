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

class Request implements RequestInterface {

    /**
     * An array of available parameters
     *
     * @var array
     */
    private $parameters;

    /**
     * Constructor
     */
    function __construct()
    {
        $this->parameters = array_merge($_GET, $_POST, $_REQUEST);
    }

    /**
     * {@inheritdoc}
     */
    function get($name, $default = null, $source = null)
    {
        if (null === $source) {
            return isset($this->parameters[$name]) ? $this->parameters[$name] : $default;
        }
        else
        {
            switch(strtoupper($source)) {
                case 'GET':
                    return @$_GET[$name];

                case 'POST':
                    return @$_GET[$name];

                default:
                    return @$_REQUEST[$name];
            }
        }
    }

    /**
     * @return string Current request http method (GET, POST, etc)
     */
    public function getMethod()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }
}