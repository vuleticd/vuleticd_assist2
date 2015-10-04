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

namespace Vuleticd\Assist\Model;

class Payment extends \Magento\Payment\Model\Method\AbstractMethod
{
    const CODE = 'vuleticd_assist';
    const TEST_URL = 'https://test.paysec.by/pay/order.cfm';
    const LIVE_URL = 'https://paysec.by/pay/order.cfm';
    const CHARGE_URL = 'https://test.paysec.by/charge/charge.cfm';
    const ORDER_STATE_URL = 'https://test.paysec.by/orderstate/orderstate.cfm';
    const ORDER_RESULT_URL = 'https://test.paysec.by/orderresult/orderresult.cfm';
    const CANCEL_URL = 'https://test.paysec.by/cancel/cancel.cfm';

    const SEND_SEPARATOR = ';';
    const PEM_DIR = 'assist';

    protected $_code = self::CODE;

    protected $_isGateway  = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_isInitializeNeeded      = true;
    protected $_canUseForMultishipping = false;
    protected $_canVoid = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial     = false;

    protected $_formBlockType = 'Vuleticd\Assist\Block\Form';
    protected $_infoBlockType = 'Vuleticd\Assist\Block\Info';

    protected $_order;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_debugger;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Psr\Log\LoggerInterface $debugger,
        \Magento\Framework\Model\Resource\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->_urlBuilder = $urlBuilder;
        $this->_checkoutSession = $checkoutSession;
        $this->_debugger = $debugger;
        //$this->_exception = $exception;
        //$this->transactionRepository = $transactionRepository;
        //$this->transactionBuilder = $transactionBuilder;
    }

    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return parent::isAvailable($quote);
    }

    public function getOrder()
    {
        if (!$this->_order) {
            $this->_order = $this->getInfoInstance()->getOrder();
        }
        return $this->_order;
    }

    public function getCheckoutRedirectUrl()
    {
        return false;
    }

    public function getOrderPlaceRedirectUrl()
    {
        return $this->_urlBuilder->getUrl('assist/standard/redirect');
    }

    public function getUrl()
    {
        $url = self::TEST_URL;
        if ($this->getConfigData('mode') == 0) {
            $url = self::LIVE_URL;
        }
        return $url;
    }

    public function validate()
    {
        parent::validate();
        $this->_debugger->debug('validate');
        return;
    }
    
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        parent::authorize($payment, $amount);
        $payment->setIsTransactionClosed(false);
        $this->_debugger->debug('authorize');
        return $this;
    }

    public function initialize($paymentAction, $stateObject)
    {
        $this->_debugger->debug('initialize');
        $stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
        return $this;
    }

}