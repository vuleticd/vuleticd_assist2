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

class Success extends \Magento\Framework\App\Action\Action
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
     * Order object
     *
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

	public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Quote\Model\QuoteFactory $quoteFactory
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_logger = $logger;
        $this->_quoteFactory = $quoteFactory;
        parent::__construct($context);
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
        $params = $this->getRequest()->getParams();
        $orderId = $params['ordernumber'];
        $order = $this->_getOrder($orderId);
        $txnId = $params['billnumber'];
        $paymentInst = $order->getPayment()->getMethodInstance();
        try {
            $session = $this->_getCheckout();
            if ($session->getLastRealOrderId() != $orderId) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Order not in session'));
            }
            if (\Magento\Sales\Model\Order::STATE_CANCELED == $order->getState()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Order already canceled'));
            }
            
            $state = $paymentInst->orderstate($order);
            $session->setQuoteId($session->getAssistQuoteId(true));
            $session->getQuote()->setIsActive(false)->save();
            // success payments URL
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('checkout/onepage/success');
            
        } catch(\Magento\Framework\Exception\LocalizedException $e) {
            $this->_logger->debug('SUCCESS Error: ' . $e->getMessage());
        }
        $this->_logger->debug('success');
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('');
    }
}