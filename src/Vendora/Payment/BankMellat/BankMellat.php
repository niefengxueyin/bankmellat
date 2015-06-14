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

class BankMellat {

    /**
     * Webservice endpoint
     */
    const WSDL_URL = 'https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl';

    /**
     * Payment URI of the bank
     */
    const PAYMENT_URL = 'https://bpm.shaparak.ir/pgwchannel/startpay.mellat';

    /**
     * Namespace used in soap
     */
    const WEBSERVICE_NAMESPACE = 'http://interfaces.core.sw.bps.com/';

    /**
     * Webservice gateway for interactions with bank API
     *
     * @var Gateway
     */
    private $gateway;

    /**
     * Request helper
     *
     * @var RequestInterface
     */
    private $request;

    /**
     * An array of user-defined order validators
     *
     * @var array
     */
    private $orderValidators;

    /**
     * Constructor
     *
     * @param RequestInterface $request
     */
    function __construct(RequestInterface $request)
    {
        $this->gateway         = new Gateway();
        $this->request         = $request;
        $this->orderValidators = array();
    }

    /**
     * Sets gateway credentials
     *
     * @param string $terminalId
     * @param string $username
     * @param string $password
     */
    function setCredentials($terminalId, $username, $password)
    {
        $this->gateway->setTerminalId($terminalId);
        $this->gateway->setUsername($username);
        $this->gateway->setPassword($password);
    }

    /**
     * Get gateway instance
     *
     * @return Gateway
     */
    function getGateway()
    {
        return $this->gateway;
    }

    /**
     * This method will handle request
     * Initialized a payment session and throws a redirect exception if needed
     *
     * @param string $orderId
     * @param string $amount
     * @param string $returnUrl
     *
     * @return Order
     */
    function execute($orderId, $amount, $returnUrl)
    {
        // Create an order instance
        $order = $this->initOrderInstance($orderId, $amount, $returnUrl);

        if ($this->request->get('SaleReferenceId')) {
            // If user returned back from the bank
            $this->completePayment($order);
        } else {
            // If this is a new payment order
            $this->beginPayment($order);
        }

        return $order;
    }

    /**
     * Creates a new order instance
     *
     * @param $orderId
     * @param $amount
     * @param $returnUrl
     * @param string $additionalData
     *
     * @return Order
     */
    function initOrderInstance($orderId = null, $amount = null, $returnUrl = '', $additionalData = '')
    {
        if (!is_numeric($amount)) {
            throw new \InvalidArgumentException('Amount should be numeric');
        }

        $order = new Order();

        $order->setOrderId($orderId);
        $order->setAmount($amount);
        $order->setReturnUrl($returnUrl);
        $order->setAdditionalData($additionalData);

        return $order;
    }

    /**
     * @param Order $order
     */
    public function beginPayment(Order $order)
    {
        $this->gateway->requestPayment($order);
    }

    /**
     * @param Order $order
     */
    public function completePayment(Order $order)
    {
        $resultCode     = $this->request->get('ResCode');
        $referenceId    = $this->request->get('RefId');
        $orderId        = $this->request->get('SaleOrderId');
        $referenceCode  = $this->request->get('SaleReferenceId');
        $cardHolderInfo = $this->request->get('CardHolderInfo');
        $cardHolderPan  = $this->request->get('CardHolderPan');

        if ($resultCode != '0') {
            throw new \RuntimeException(
                sprintf('Could not complete the payment. (Error: %s)', $resultCode)
            );
        }

        $order->setCardHolderInfo($cardHolderInfo);
        $order->setCardHolderPan($cardHolderPan);
        $order->setReferenceId($referenceId);
        $order->setReferenceCode($referenceCode);
        $order->setOrderId($orderId);

        if ($this->verifyOrder($order)) {
            $this->gateway->settlePayment($order);
        } else {
            $this->gateway->refundPayment($order);
        }
    }

    /**
     * Add a new order validator
     *
     * @param callable $validator
     *
     * @return BankMellat $this
     */
    public function addOrderValidator($validator)
    {
        $this->orderValidators[] = $validator;
        return $this;
    }

    /**
     * Verifies an order against bank and user-defined validators
     *
     * @param Order $order
     *
     * @return bool Whetehr if the order is verified or not
     */
    private function verifyOrder(Order $order)
    {
        if (false === $this->gateway->verifyPayment($order)) {
            return false;
        }

        foreach ($this->orderValidators as $validator) {
            if (false === call_user_func($validator, $order)) {
                return false;
            }
        }

        return true;
    }
}