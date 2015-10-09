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

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @var \Vuleticd\Assist\Helper\Data
     */
    protected $_assistHelper;


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
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Vuleticd\Assist\Helper\Data $assistHelper,
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
        $this->_localeResolver = $localeResolver;
        $this->_assistHelper = $assistHelper;
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
            $this->_order = $this->_checkoutSession->getLastRealOrder();
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

    public function void(\Magento\Payment\Model\InfoInterface $payment)
    {
        parent::void($payment);
        try {
            $data = array(
                'Merchant_ID' => urlencode($this->getConfigData('merchant')),
                'Billnumber' => urlencode($payment->getParentTransactionId()),
                'Login' => urlencode($this->getConfigData('api_login')),
                'Password' => urlencode($this->getConfigData('api_password'))
            );
            $xml = $this->callAssist(self::CANCEL_URL, $data);
            if ((int)$xml['firstcode'] || (int)$xml['secondcode']) {
                throw new \Magento\Framework\Exception\LocalizedException(__('error in call'));
            }
            if ('AS000' != (string)$xml->orders->order->responsecode) {
                throw new \Magento\Framework\Exception\LocalizedException($this->getAssistErrors((string)$xml->orders->order->responsecode));
            }
            /*
            if (Mage::helper('assist')->isServiceSecured()) {
                $y = implode("", array(
                            $this->getConfigData('merchant'),
                            (string)$xml->orders->order->ordernumber,
                            (string)$xml->orders->order->orderamount,
                            (string)$xml->orders->order->ordercurrency,
                            (string)$xml->orders->order->orderstate,
                            (string)$xml->orders->order->packetdate
                        ));
                $keyFile = Mage::getBaseDir('var') . DS . self::PEM_DIR . DS . $this->getConfigData('assist_key');
                if ((string)$xml->orders->order->signature != $this->sign($y, $keyFile)) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Incorrect Signature.'));
                }
            }
            */
            // success
            $this->_debugger->debug(var_export($xml, true));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->_debugger->debug($e->getMessage());
            throw $e;
        } 
        $this->_debugger->debug('void');
        return $this;
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        parent::refund($payment, $amount);
        $txn = $payment->getAuthorizationTransaction();
        if ($txn) {
            try {
                $billnumber = $txn->getParentTxnId() ? $txn->getParentTxnId() : $txn->getTxnId();
                $data = array(
                    'Merchant_ID' => urlencode($this->getConfigData('merchant')),
                    'Billnumber' => urlencode($billnumber),
                    'Login' => urlencode($this->getConfigData('api_login')),
                    'Password' => urlencode($this->getConfigData('api_password'))
                );
                $xml = $this->callAssist(self::CANCEL_URL, $data);
                if ((int)$xml['firstcode'] || (int)$xml['secondcode']) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('error in call'));
                }
                if ('AS000' != (string)$xml->orders->order->responsecode) {
                    throw new \Magento\Framework\Exception\LocalizedException($this->getAssistErrors((string)$xml->orders->order->responsecode));
                }
                /*
                if (Mage::helper('assist')->isServiceSecured()) {
                    $y = implode("", array(
                                $this->getConfigData('merchant'),
                                (string)$xml->orders->order->ordernumber,
                                (string)$xml->orders->order->orderamount,
                                (string)$xml->orders->order->ordercurrency,
                                (string)$xml->orders->order->orderstate,
                                (string)$xml->orders->order->packetdate
                            ));
                    $keyFile = Mage::getBaseDir('var') . DS . self::PEM_DIR . DS . $this->getConfigData('assist_key');
                    if ((string)$xml->orders->order->signature != $this->sign($y, $keyFile)) {
                        Mage::throwException('Incorrect Signature.');
                    }
                }
                */
                // success
                $this->_debugger->debug(var_export($xml, true));
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_debugger->debug($e->getMessage());
                throw $e;
            } 
        }
        $this->_debugger->debug('refund');
        return $this;
    }

    public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        parent::capture($payment, $amount);
        $txn = $payment->getAuthorizationTransaction();    
        if ($txn) {
            try {
                $data = array(
                    'Merchant_ID' => urlencode($this->getConfigData('merchant')),
                    'Billnumber' => urlencode($txn->getTxnId()),
                    'Language' => urlencode('EN'),
                    'Login' => urlencode($this->getConfigData('api_login')),
                    'Password' => urlencode($this->getConfigData('api_password'))
                );
                $xml = $this->callAssist(self::CHARGE_URL, $data);
                if ((int)$xml['firstcode'] || (int)$xml['secondcode']) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('error in call'));
                }
                if ('AS000' != (string)$xml->orders->order->responsecode) {
                    throw new \Magento\Framework\Exception\LocalizedException($this->getAssistErrors((string)$xml->orders->order->responsecode));
                }
                /*
                if (Mage::helper('assist')->isServiceSecured()) {
                    $y = implode("", array(
                                $this->getConfigData('merchant'),
                                (string)$xml->orders->order->ordernumber,
                                (string)$xml->orders->order->orderamount,
                                (string)$xml->orders->order->ordercurrency,
                                (string)$xml->orders->order->orderstate,
                                (string)$xml->orders->order->packetdate
                            ));
                    $keyFile = Mage::getBaseDir('var') . DS . self::PEM_DIR . DS . $this->getConfigData('assist_key');
                    if ((string)$xml->orders->order->signature != $this->sign($y, $keyFile)) {
                        Mage::throwException('Incorrect Signature.');
                    }
                }
                */
                // success
                $payment->setTransactionId($txn->getTxnId())->setIsTransactionClosed(false);
                $payment->setIsTransactionApproved(true);
                $this->_debugger->debug(var_export($xml, true));
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->_debugger->debug($e->getMessage());
                throw $e;
            } 
        } else {
            if (!$this->getAssistCaptureResponse()) {
                $message = __('Captured amount of %s offline. ASSIST does not support invoicing via web service.', $amount);
                $this->getOrder()->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true, $message);
            }
        }
        $this->_debugger->debug('capture');
        return $this;
    }

    public function orderstate($order)
    {
        $orderId = $order->getIncrementId();
        $orderAmount = number_format($order->getGrandTotal(), 2, '.', '');
        $orderCurr = $order->getBaseCurrencyCode();
        $data = array(
                'Merchant_ID' => urlencode($this->getConfigData('merchant')),
                'Ordernumber' => urlencode($orderId),
                'Login' => urlencode($this->getConfigData('api_login')),
                'Password' => urlencode($this->getConfigData('api_password'))
        );
        $xml = $this->callAssist(self::ORDER_STATE_URL, $data);
        if ((int)$xml['firstcode'] || (int)$xml['secondcode']) {
            throw new \Magento\Framework\Exception\LocalizedException(__('error in call'));
        }
        
        if ($this->getConfigPaymentAction() == self::ACTION_AUTHORIZE && 'Delayed' != (string)$xml->order->orderstate
            || $this->getConfigPaymentAction() == self::ACTION_AUTHORIZE_CAPTURE && 'Approved' != (string)$xml->order->orderstate) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Incorrect order state'));
        }
        /*
        if (Mage::helper('assist')->isResponseSecuredMd5()) {
            $x = array(
                $this->getConfigData('merchant'),
                $orderId,
                $orderAmount,
                $orderCurr,
                (string)$xml->order->orderstate
            );
            if ($this->secreyKey(implode("", $x)) != (string)$xml->order->checkvalue) {
                Mage::throwException('Incorrect Checkvalue.');
            }
        }
        if (Mage::helper('assist')->isResponseSecuredPgp()) {
            $y = implode("", array(
                $this->getConfigData('merchant'),
                $orderId,
                $orderAmount,
                $orderCurr,
                (string)$xml->order->orderstate,
                (string)$xml->order->packetdate
            ));
            $keyFile = Mage::getBaseDir('var') . DS . self::PEM_DIR . DS . $this->getConfigData('assist_key');
            if ($this->sign($y, $keyFile) != (string)$xml->order->signature) {
                Mage::throwException('Incorrect Signature.');
            }
        }
        */
        // success
        $this->_debugger->debug(var_export($xml, true));
        $this->_debugger->debug('orderstate');
        return (string)$xml->order->orderstate;
    }

    public function getFormFields()
    {        
        $locale = $this->_localeResolver->getLocale();
        $language = strtoupper(substr( $locale, 0, strpos( $locale, '_' ) ));
        $orderAmount = number_format($this->getOrder()->getGrandTotal(), 2, '.', '');
        $billingAddress = $this->getOrder()->getBillingAddress();
        $urlReturnOk = $this->_urlBuilder->getUrl("assist/standard/success", array('_secure' => true));
        $urlReturnNo = $this->_urlBuilder->getUrl("assist/standard/cancel", array('_secure' => true));
        $fields = array(
            'Merchant_ID' => $this->getConfigData('merchant'),
            'Delay' => $this->getConfigPaymentAction() == self::ACTION_AUTHORIZE ? 1 : 0,
            'OrderNumber' => $this->getOrder()->getRealOrderId(),
            'Language' => $language,
            'OrderAmount' => $orderAmount,
            'OrderCurrency' => $this->getOrder()->getBaseCurrencyCode(),
            'Lastname' => $billingAddress->getLastname(),
            'Firstname' => $billingAddress->getFirstname(),
            'Email' => $this->getOrder()->getCustomerEmail(),
            'MobilePhone' => $billingAddress->getMobile(),
            'URL_RETURN_OK' => $urlReturnOk,
            'URL_RETURN_NO' => $urlReturnNo,
            'OrderComment' => '',
            'Middlename' => $billingAddress->getMiddlename(),
            'Address'   => implode(", ", $billingAddress->getStreet()),
            'HomePhone' => $billingAddress->getTelephone(),
            'WorkPhone' => $billingAddress->getWorkphone(),
            'Fax' => $billingAddress->getFax(),
            'Country' => $billingAddress->getCountryId(), //getCountryModel()->getIso3Code(),
            'State' => $billingAddress->getRegionCode(),
            'City' => $billingAddress->getCity(),
            'Zip' => $billingAddress->getPostcode(),
            'MobileDevice' => $this->getConfigData('mobile')
        );
        $fields = $this->_mergePaymentSystems($fields);
        /*
        if (Mage::helper('assist')->isOrderSecuredMd5()) {
            $x = array(
                $this->getConfigData('merchant'),
                $this->getOrder()->getRealOrderId(),
                $orderAmount,
                $this->getOrder()->getBaseCurrencyCode()
            );
            $fields['Checkvalue'] = $this->secreyKey(implode(self::SEND_SEPARATOR, $x));
        }
        if (Mage::helper('assist')->isOrderSecuredPgp()) {
            $y = md5(implode(self::SEND_SEPARATOR, array(
                $this->getConfigData('merchant'),
                $this->getOrder()->getRealOrderId(),
                $orderAmount,
                $this->getOrder()->getBaseCurrencyCode()
            )));
            $keyFile = Mage::getBaseDir('var') . DS . self::PEM_DIR . DS . $this->getConfigData('merchant_key');
            $fields['Signature'] = $this->sign($y, $keyFile);
        }
        */
        //Mage::helper('assist')->debug($fields);
        $this->_debugger->debug(var_export($fields, true));
        return $fields;
    }

    protected function _mergePaymentSystems($data)
    {
        $systems = explode(",", $this->getConfigData('payment_system'));
        if (in_array('Controlled', $systems)) {
            return $data;
        } 
        foreach ((array) $systems as $system) {
            $data[$system] = '1';
        }
        return $data;
    }

    public function getAssistErrors($response)
    {
        switch ($response) {
            case 'AS100':
                $error = __('AUTHORIZATION DECLINED');
                break;
            case 'AS101':
                $error = __('AUTHORIZATION DECLINED Incorrect card parameters');
                break;
            case 'AS102':
                $error = __('AUTHORIZATION DECLINED Insufficient cash');
                break;
            case 'AS104':
                $error = __('AUTHORIZATION DECLINED Incorrect card validity period');
                break;
            case 'AS105':
                $error = __('AUTHORIZATION DECLINED Card operations limit exceeded');
                break;
            case 'AS107':
                $error = __('AUTHORIZATION DECLINED Data reception error');
                break;
            case 'AS108':
                $error = __('AUTHORIZATION DECLINED Suspicion of fraud');
                break;
            case 'AS109':
                $error = __('AUTHORIZATION DECLINED Operations limit exceeded');
                break;
            case 'AS110':
                $error = __('AUTHORIZATION DECLINED Authorization via 3D-Secure required');
                break;
            case 'AS200':
                $error = __('REPEAT AUTHORIZATION');
                break;
            case 'AS300':
                $error = __('OPERATION IN PROCESS. WAIT');
                break;
            case 'AS400':
                $error = __('NO PAYMENTS WITH SUCH PARAMETERS EXIST');
                break;
            case 'AS998':
                $error = __('SYSTEM ERROR. Connect to ASSIST');
                break;
            default:
                $error = __('Unrecognized error.');
                break;
        }
        return $error;
    }

    public function callAssist($url, $data)
    {
        try {
            $this->_debugger->debug(var_export($data, true));
            $data['Format'] = 3;
            foreach ( $data as $key => $value) {
                $chfields[] = $key . '=' . $value;
            }
            $post_string = implode ('&', $chfields);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_USERAGENT,
            "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
            $result = curl_exec($ch);
            $xml = simplexml_load_string($result);
            curl_close($ch);
            return $xml;
        } catch (Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException($e->getMessage());
        }
    }
}