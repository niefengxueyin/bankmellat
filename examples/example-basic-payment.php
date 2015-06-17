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

// Composer autoloader
include_once('../vendor/autoload.php');

use Ravaj\Component\Payment\BankMellat\BankMellat;
use Ravaj\Component\Payment\BankMellat\Order;
use Ravaj\Component\Payment\BankMellat\Request;

// Initial
session_start();
$errorMessage = null;
$successMessage = null;

// Initialize a simple request helper
$request = new Request();

// Whether to use Test API (for development) or production API (for staging)
define('USE_TEST_WEBSERVICE', true);

// If form is posted OR returned back from the bank
if ($request->getMethod() === 'POST' || $request->get('RefId')) {

    try {
        // Create a BankMellat instance with provided credentials
        $bankmellat = new BankMellat($request);

        // WARNING!
        //
        // For the sake of this example I need to store credentionals in session
        // so when we're returning back from the bank we can create a BankMellat instance.
        //
        // DO NOT USE IN PRODUCTION
        if ($request->get('terminalId')) {
            $terminalId = $_SESSION['vendora.bankmellat.terminalId'] = $request->get('terminalId');
        } else {
            $terminalId = $_SESSION['vendora.bankmellat.terminalId'];
        }
        if ($request->get('username')) {
            $username = $_SESSION['vendora.bankmellat.username'] = $request->get('username');
        } else {
            $username = $_SESSION['vendora.bankmellat.username'];
        }
        if ($request->get('password')) {
            $password = $_SESSION['vendora.bankmellat.password'] = $request->get('password');
        } else {
            $password = $_SESSION['vendora.bankmellat.password'];
        }

        // Set credentionals
        $bankmellat->setCredentials($terminalId, $username, $password);

        // We're setting return url to current file (example-basic-payment.php)
        $returnUrl =  'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

        // Get order ID and amount from request
        $orderId = $request->get('orderId');
        $amount  = $request->get('amount');

        // Handle the request (New payment or Verification)
        $response = $bankmellat->execute($orderId, $amount, $returnUrl);

        // Validate order with a custom logic
        $bankmellat->addOrderValidator(function (Order $order) {
            // Do some logic on $order
            return true;
        });

        // If order needs to redirect the user
        if ($response->isRedirect()) {
            $response->redirect();
        }
        // If order has been successfully payed
        else if ($response->isSuccessful()) {
            $referenceCode  = $response->getReferenceCode();
            $successMessage = "Your order has been successfully payed with reference code of <b>$referenceCode</b>";
        } else {
            $errorMessage = "Your order has not been placed. Unexpected error happend.";
        }
    }
    catch (Exception $exception)
    {
        $errorMessage = $exception->getMessage();
    }
}

?>
