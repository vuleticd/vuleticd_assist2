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

namespace Vuleticd\Assist\Block;

class Redirect extends \Magento\Framework\View\Element\Template
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_paymentMethodCode = 'vuleticd_assist';

    /**
     * Payment data
     *
     * @var \Magento\Payment\Helper\Data
     */
    protected $_paymentData = null;

        /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param array $data
     */
    public function __construct(
    	\Magento\Framework\View\Element\Template\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Payment\Helper\Data $paymentData,
        \Psr\Log\LoggerInterface $logger,
        array $data = []
    ) {
    	$this->_logger = $logger;
    	$this->_orderFactory = $orderFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_paymentData = $paymentData;
        parent::__construct($context, $data);
    }

    /**
     * Get frontend checkout session object
     *
     * @return \Magento\Checkout\Model\Session
     */
    protected function _getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    public function getFormData()
    {
        return $this->_paymentData->getMethodInstance($this->_paymentMethodCode)->getFormFields();
    }
    public function getFormAction()
    {
        return $this->_paymentData->getMethodInstance($this->_paymentMethodCode)->getUrl();
    }
}