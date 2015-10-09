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
namespace Vuleticd\Assist\Model\Source;

class System implements \Magento\Framework\Option\ArrayInterface
{
    const CONTROLLED = 'Controlled';
    const CARD_PAYMENT = 'CardPayment';
    const YM_PAYMENT = 'YMPayment';
    const WM_PAYMENT = 'WMPayment';
    const QIWI_PAYMENT = 'QIWIPayment';
    const MTS_PAYMENT = 'QIWIMtsPayment';
    const MEGAFON_PAYMENT = 'QIWIMegafonPayment';
    const BEELINE_PAYMENT = 'QIWIBeelinePayment';

    protected $_options;

    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options = [
                self::CONTROLLED => __('Controlled in ASSIST account'),
                self::CARD_PAYMENT => __('Credit Card'),
                self::YM_PAYMENT => __('YandexMoney'),
                self::WM_PAYMENT => __('WebMoney'),
                self::QIWI_PAYMENT => __('QIWI payment'),
                self::MTS_PAYMENT => __('Mobile phone money (MTS)'),
                self::MEGAFON_PAYMENT => __('Mobile phone money (Megafon)'),
                self::BEELINE_PAYMENT => __('Mobile phone money (Beeline)'),
            ];   
        }     
        return $this->_options;
    }
}
