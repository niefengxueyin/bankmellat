<?php

/**
 * Copyright (c) 2014 Ravaj
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Aram Alipoor <aram.alipoor@gmail.com>
 * @license MIT
 */

namespace Ravaj\Component\Payment\BankMellat;


class Order {

    /**
     * Payment request statuses
     */
    const STATUS_INITIALIZED = 'initialized';
    const STATUS_VERIFIED = 'verified';
    const STATUS_SETTLED = 'settled';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    /**
     * Our own order Id
     *
     * @var string
     */
    private $orderId;

    /**
     * Bank-generated reference Id of payment request
     *
     * @var string
     */
    private $referenceId;

    /**
     * Bank-generated reference code of transaction
     *
     * @var string
     */
    private $referenceCode;

    /**
     * Half-visible card holder's pan-number
     *
     * @var string
     */
    private $cardHolderPan;

    /**
     * Card holder information hash (IMO this is a unqiue value for every card)
     *
     * @var string
     */
    private $cardHolderInfo;

    /**
     * Total amount of order request
     *
     * @var float
     */
    private $amount;

    /**
     * Redirect url if use needs to be redirected to bank
     *
     * @var string
     */
    private $redirectUrl;

    /**
     * Return url for when user completes his payment on bank's website
     *
     * @var string
     */
    private $returnUrl;

    /**
     * Useful additional data of order
     *
     * @var string
     */
    private $additionalData;

    /**
     * Current status of the request
     *
     * @var string
     */
    private $status;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->status = self::STATUS_INITIALIZED;
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param string $orderId
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * @return string
     */
    public function getReferenceId()
    {
        return $this->referenceId;
    }

    /**
     * @param string $referenceId
     */
    public function setReferenceId($referenceId)
    {
        $this->referenceId = $referenceId;
    }

    /**
     * @return string
     */
    public function getReferenceCode()
    {
        return $this->referenceCode;
    }

    /**
     * @param string $referenceCode
     */
    public function setReferenceCode($referenceCode)
    {
        $this->referenceCode = $referenceCode;
    }

    /**
     * @return string
     */
    public function getCardHolderPan()
    {
        return $this->cardHolderPan;
    }

    /**
     * @param string $cardHolderPan
     */
    public function setCardHolderPan($cardHolderPan)
    {
        $this->cardHolderPan = $cardHolderPan;
    }

    /**
     * @return string
     */
    public function getCardHolderInfo()
    {
        return $this->cardHolderInfo;
    }

    /**
     * @param string $cardHolderInfo
     */
    public function setCardHolderInfo($cardHolderInfo)
    {
        $this->cardHolderInfo = $cardHolderInfo;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param float $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        $this->updateReturnUrl();
    }

    /**
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * @param string $redirectUrl
     */
    public function setRedirectUrl($redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;
    }

    /**
     * @return string
     */
    public function getReturnUrl()
    {
        return $this->returnUrl;
    }

    /**
     * @param string $returnUrl
     */
    public function setReturnUrl($returnUrl)
    {
        $this->returnUrl = $returnUrl;
        $this->updateReturnUrl();
    }

    /**
     * @return string
     */
    public function getAdditionalData()
    {
        return $this->additionalData;
    }

    /**
     * @param string $additionalData
     */
    public function setAdditionalData($additionalData)
    {
        $this->additionalData = $additionalData;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return boolean
     */
    public function isSuccessful()
    {
        return $this->status === self::STATUS_SETTLED;
    }

    /**
     * @return boolean
     */
    public function isRedirect()
    {
        return $this->status === self::STATUS_INITIALIZED && $this->redirectUrl;
    }

    /**
     * Redirects user
     */
    public function redirect()
    {
        $redirectUrl = $this->getRedirectUrl();
        $referenceId = $this->getReferenceId();

        echo ("
            <script type='text/javascript'>
                window.onload = function () {
                    var form = document.getElementById('mellat-payment-form');
                        form.submit();
                };
            </script>
            <form id=\"mellat-payment-form\" action='$redirectUrl' method='POST'>
                <input type='hidden' id='RefId' name='RefId' value='$referenceId' />
            </form>
        ");

        exit;
    }

    private function updateReturnUrl()
    {
        if (!$this->returnUrl)
            return;

        $returnUrl = $this->returnUrl;

        $returnUrl .= (parse_url($returnUrl, PHP_URL_QUERY) ? '&' : '?')
            . 'amount='   . $this->amount
            . '&orderId=' . $this->orderId;

        $this->returnUrl = $returnUrl;
    }
}