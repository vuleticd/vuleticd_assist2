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

class Cancel extends \Magento\Framework\App\Action\Action
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
        $session = $this->_getCheckout();
        $session->setQuoteId($session->getAssistQuoteId(true));
        $orderId = $params['ordernumber'];
        $order = $this->_getOrder($orderId);
        $quote = $this->_quoteFactory->create()->load($order->getQuoteId());
        $txnId = $params['billnumber'];
        if ($order->getBaseTotalDue() && $quote->getId()) {
        	$order->registerCancellation(__('Customer payment on ASSIST failed.'))->save();
        	$this->messageManager->addSuccessMessage(
                    __('Sorry, your transaction is failed and cannot be'
                        . ' processed, please choose another payment method'
                        . ' or contact Customer Care to complete'
                        . ' your order.')
                );
        	if ($this->_getCheckout()->restoreQuote()) {
	            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
		        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
		        return $resultRedirect->setPath('checkout/cart');
	        }  
        }
        $this->_logger->debug('cancel');
    }
}