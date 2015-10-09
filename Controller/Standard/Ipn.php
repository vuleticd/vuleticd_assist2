<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category    Vuleticd
 * @package     Vuleticd_Assist
 * @copyright   Copyright (c) 2015 Vuletic Dragan (http://www.vuleticd.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Vuleticd\Assist\Controller\Standard;

use Magento\Framework\Controller\ResultFactory;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;

class Ipn extends \Magento\Framework\App\Action\Action
{
	/**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $_quoteFactory;

    /**
     * @var \Magento\Framework\DB\TransactionFactory
     */
    protected $_transactionFactory;

    /**
     * @var InvoiceSender
     */
    protected $_invoiceSender;

    /**
     * Order object
     *
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    protected $_paymentInst = null;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

	public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        InvoiceSender $invoiceSender
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_logger = $logger;
        $this->_quoteFactory = $quoteFactory;
        $this->_transactionFactory = $transactionFactory;
        parent::__construct($context);
        $this->_invoiceSender = $invoiceSender;
    }

    /**
     * Get frontend checkout session object
     *
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckout()
    {
        return $this->_checkoutSession;
    }

    protected function _getOrder($incrementId= null)
    {
        if (!$this->_order) {
            $incrementId = $incrementId ? $incrementId : $this->_getCheckout()->getLastRealOrderId();
            $this->_order = $this->_orderFactory->create()->loadByIncrementId($incrementId);
        }
        return $this->_order;
    }


    public function execute()
    {
        $error = false;
        $request = $this->getRequest()->getPost();
        $this->_logger->debug(var_export($request, true));
        try {
            if (!$this->getRequest()->isPost() || empty($request)) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid or empty request type.'));
            }
            $request = $this->_checkIpnRequest();
            // process based on order state
            switch ($request['orderstate']) {
                case 'Approved':
                    $this->_processApproved($request);
                    break;
                case 'Delayed':
                    $this->_processDelayed($request);
                    break;
            }
        } catch(\Magento\Framework\Exception\LocalizedException  $e) {
            $this->messageManager->addSuccessMessage($e->getMessage());
            $error = true;
            $this->_logger->debug('IPN error: ' . $e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addSuccessMessage($e->getMessage());
            $error = true;
            $this->_logger->debug('IPN error: ' . $e->getMessage());
        }
        $xml = $this->_generateIpnResponse($error, $request);
        $this->getResponse()->setHeader('Content-Type', 'text/xml; charset=utf-8')->setBody($xml);
        $this->_logger->debug(var_export($xml, true));
    }

    protected function _processApproved($request)
    {
        try {
            if ($this->_paymentInst->getConfigPaymentAction() != \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Wrong payment action.'));
            }
            // save transaction information
            $this->_order->getPayment()
                ->setTransactionId($request['billnumber'])
                ->setLastTransId($request['billnumber']);
            if (isset($request['approvalcode'])) {
                $this->_order->getPayment()->setAdditionalInformation('approvalcode', $request['approvalcode']);
            }
            $this->_processInvoice(); 
            $this->_order->save();
        } catch(\Magento\Framework\Exception\LocalizedException $e) {
            throw $e;
        }
    }

    protected function _processDelayed($request)
    {
        try {
            if ($this->_paymentInst->getConfigPaymentAction() != \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Wrong payment action.'));
            }
            
            // save transaction information
            $this->_order->getPayment()
                ->setTransactionId($request['billnumber'])
                ->setLastTransId($request['billnumber'])
                ->authorize(true,$request['orderamount']);
            if (isset($request['approvalcode'])) {
                $this->_order->getPayment()->setAdditionalInformation('approvalcode', $request['approvalcode']);
            }
            $this->_processInvoice(); 
            $this->_order->save();
        } catch(\Magento\Framework\Exception\LocalizedException $e) {
            throw $e;
        }
    }

    protected function _processInvoice()
    {   
        if ($this->_order->canInvoice()) {
            $invoice = $this->_order->prepareInvoice();
            switch ($this->_paymentInst->getConfigPaymentAction()) {
                case \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE:
                    $invoice->register();
                    $this->_order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, 'authorized');
                    break;
                
                case \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE:
                    $this->_paymentInst->setAssistCaptureResponse(true);
                    $invoice->register()->capture();
                    break;
            }
            /** @var \Magento\Framework\DB\Transaction $transaction */
            $transaction = $this->_transactionFactory->create();
            $transaction->addObject($invoice)
                ->addObject($invoice->getOrder())
                ->save();
            $this->_invoiceSender->send($invoice);
        } elseif ($this->_order->isCanceled()) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Order canceled'));
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('Order paid'));
        }
    }

    protected function _checkIpnRequest()
    {
        $request = $this->getRequest()->getPost();
        $this->_order = $this->_getOrder($request['ordernumber']);
        // check order ID
        $orderId = $this->_order->getRealOrderId();
        if ($orderId !=  $request['ordernumber']) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Order not found'));
        }
        $this->_paymentInst = $this->_order->getPayment()->getMethodInstance();
        // check merchant ID
        if ($this->_paymentInst->getConfigData('merchant') != $request['merchant_id']) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid merchant ID: ' . $request['merchant_id']));
        }
        // failed operation
        if ('AS000' !=  $request['responsecode']) {
            throw new \Magento\Framework\Exception\LocalizedException($this->_paymentInst->getAssistErrors($request['responsecode']));
        }
        // wrong test mode
        if ($this->_paymentInst->getConfigData('mode') !=  $request['testmode']) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Wrong Test Mode.'));
        }
        // accept only Approve operations, cancel and capture are processed real time
        if ('100' !=  $request['operationtype']) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Wrong Operation Type. Only Approve is supported by IPN.'));
        }
        // check currency
        if ($this->_order->getBaseCurrencyCode() != $request['ordercurrency']) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid currency: ' . $request['ordercurrency']));
        }
        // check amount
        $orderAmount = number_format($this->_order->getGrandTotal(), 2, '.', '');
        if ($orderAmount != $request['orderamount']) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid amount: ' . $request['orderamount']));
        }
        /*
        if (Mage::helper('assist')->isResponseSecuredMd5()) {
            $x = array(
                $this->_paymentInst->getConfigData('merchant'),
                $request['ordernumber'],
                $request['amount'],
                $request['currency'],
                $request['orderstate']
            );
            if ($this->_paymentInst->secreyKey(implode("", $x)) != $request['checkvalue']) {
                Mage::throwException('Incorrect Checkvalue: ' . $request['checkvalue']);
            }
        }
        if (Mage::helper('assist')->isResponseSecuredPgp()) {
            $y = implode("", array(
                $this->_paymentInst->getConfigData('merchant'),
                $request['ordernumber'],
                $request['amount'], 
                $request['currency'],
                $request['orderstate']
            ));
            $keyFile = Mage::getBaseDir('var') . DS . 'assist' . DS . $this->_paymentInst->getConfigData('assist_key');
            if ($this->_paymentInst->sign($y, $keyFile) != $request['signature']) {
                Mage::throwException('Incorrect Signature.');
            }
        }
        */
        return $request;
    }

    protected function _generateIpnResponse($error, $data)
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $rootNode = $dom->createElement('pushpaymentresult');
        $firstcode = $dom->createAttribute('firstcode');
        $firstcode->value = $error ? '1' : '0';
        $rootNode->appendChild($firstcode);
        $secondcode = $dom->createAttribute('secondcode');
        $secondcode->value = $error ? '1' : '0';
        $rootNode->appendChild($secondcode);
        $dom->appendChild($rootNode);
        if (!$error) {
            $order = $dom->createElement('order');
            $rootNode->appendChild($order);
            $billnumber = $dom->createElement('billnumber', $data['billnumber']);
            $packetdate = $dom->createElement('packetdate', $data['packetdate']);
            $order->appendChild($billnumber);
            $order->appendChild($packetdate);
        }
        return $dom->saveXML();
    }
}