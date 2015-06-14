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

use \SoapClient;

class Gateway {
    /**
     * Customer's Terminal ID issued by bank authorities
     *
     * @var string
     */
    private $terminalId;

    /**
     * Customer's username of terminal
     *
     * @var string
     */
    private $username;

    /**
     * Customer's password of terminal
     *
     * @var string
     */
    private $password;

    /**
     * @var SoapClient
     */
    private $client;

    /**
     * Constructor
     *
     * @param string $terminalId
     * @param string $username
     * @param string $password
     */
    function __construct($terminalId = null, $username = null, $password = null)
    {
        $this->terminalId = $terminalId;
        $this->username = $username;
        $this->password = $password;

        $this->client = $this->createClient();
        $this->throwIfClientError();
    }

    /**
     * @return string
     */
    public function getTerminalId()
    {
        return $this->terminalId;
    }

    /**
     * @param string $terminalId
     *
     * @return Gateway $this
     */
    public function setTerminalId($terminalId)
    {
        $this->terminalId = $terminalId;
        return $this;
    }

    /**
     * @return string
     *
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return Gateway $this
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     *
     * @return Gateway $this
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Requests a payment and gets transaction refernece ID
     *
     * @param Order $order
     *
     * @return Order
     */
    function requestPayment(Order $order)
    {
        $parameters = array(
            'terminalId'     => $this->terminalId,
            'userName'       => $this->username,
            'userPassword'   => $this->password,
            'orderId'        => $order->getOrderId(),
            'amount'         => $order->getAmount(),
            'callBackUrl'    => $order->getReturnUrl(),
            'localDate'      => date('Ymd'),
            'localTime'      => date("His"),
            'additionalData' => $order->getAdditionalData(),
            'payerId'        => 0
        );

        $result = (array) $this->call('bpPayRequest', $parameters);

        $referenceId = null;
        list($responseCode, $referenceId) = explode(',', is_array($result) ? $result['return'] : $result);

        if ($responseCode != "0") {
            throw new \RuntimeException(
                sprintf('Received error from BankMellat service with code (%s)', $responseCode)
            );
        }

        // Update order with new Reference ID then set a redirect URL
        $order->setReferenceId($referenceId);
        $order->setRedirectUrl(BankMellat::PAYMENT_URL);

        return $order;
    }

    /**
     * Verifies if a payment is legitimate and successfully payed
     *
     * @param Order $order
     *
     * @return Order
     */
    function verifyPayment(Order $order)
    {
        $parameters = array(
            'terminalId'     => $this->terminalId,
            'userName'       => $this->username,
            'userPassword'   => $this->password,
            'orderId'        => $order->getOrderId(),
            'saleOrderId'    => $order->getOrderId(),
            'saleReferenceId'=> $order->getReferenceCode()
        );

        $result = (array) $this->call('bpVerifyRequest', $parameters);

        $responseCode = is_array($result) ? $result['return'] : $result;

        if ($responseCode != "0") {
            throw new \RuntimeException(
                sprintf('Received error from BankMellat service with code (%s)', $responseCode)
            );
        }

        // Update order with new verified status
        $order->setStatus(Order::STATUS_VERIFIED);

        return true;
    }

    /**
     * Settles a successfull payment so the money goes from customer's account to merchant's account
     *
     * @param Order $order
     *
     * @return Order
     */
    function settlePayment(Order $order)
    {
        $parameters = array(
            'terminalId'     => $this->terminalId,
            'userName'       => $this->username,
            'userPassword'   => $this->password,
            'orderId'        => $order->getOrderId(),
            'saleOrderId'    => $order->getOrderId(),
            'saleReferenceId'=> $order->getReferenceCode()
        );

        $result = (array) $this->call('bpSettleRequest', $parameters);

        $responseCode = is_array($result) ? $result['return'] : $result;

        if ($responseCode != "0") {
            throw new \RuntimeException(
                sprintf('Received error from BankMellat service with code (%s)', $responseCode)
            );
        }

        // Update order with new settled status (money deposited successfully)
        $order->setStatus(Order::STATUS_SETTLED);

        return true;
    }

    /**
     * Reverses a payment so money will be refunded to the customer
     *
     * @param Order $order
     *
     * @return Order
     */
    function refundPayment(Order $order)
    {
        $parameters = array(
            'terminalId'     => $this->terminalId,
            'userName'       => $this->username,
            'userPassword'   => $this->password,
            'orderId'        => $order->getOrderId(),
            'saleOrderId'    => $order->getOrderId(),
            'saleReferenceId'=> $order->getReferenceCode()
        );

        $result = (array) $this->call('bpReversalRequest', $parameters);

        $responseCode = is_array($result) ? $result['return'] : $result;

        if ($responseCode != "0") {
            throw new \RuntimeException(
                sprintf('Received error from BankMellat service with code (%s)', $responseCode)
            );
        }

        // Update order with new refunded status (money goes back to customer)
        $order->setStatus(Order::STATUS_REFUNDED);

        return true;
    }


    /**
     * @return SoapClient
     */
    private function createClient()
    {
        try {
            return new SoapClient(BankMellat::WSDL_URL, 'wsdl');
        } catch(\Exception $e) {
            try {
                return new SoapClient(BankMellat::WSDL_URL, array(
                    'uri' => BankMellat::WEBSERVICE_NAMESPACE
                ));
            } catch(\Exception $e) {
                throw new \RuntimeException(
                    'Cannot create a soap client, make sure you have installed php-soap extension. ' . $e->getMessage()
                );
            }
        }
    }

    /**
     * Calls a method on soap client
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    private function call($method, $parameters)
    {
        $this->throwIfClientError();

        if (method_exists($this->client, $method)) {
            return $this->client->call($method, $parameters, BankMellat::WEBSERVICE_NAMESPACE);
        } else {
            return $this->client->$method($parameters);
        }
    }

    /**
     * @throws \RuntimeException If there any problem with client
     */
    private function throwIfClientError()
    {
        if (!$this->client) {
            throw new \RuntimeException('Cannot create BankMellat soap client');
        }

        if ($error = @$this->client->fault) {
            throw new \RuntimeException(
                sprintf('BankMellat soap client has error, %s.', $error)
            );
        }

        if (method_exists($this->client, 'getError') &&
            $error = @$this->client->getError()) {
            throw new \RuntimeException(
                sprintf('BankMellat soap client has error, %s.', $error)
            );
        }
    }
}