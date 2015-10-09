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

class Info extends \Magento\Payment\Block\Info
{
    /**
     * @var string
     */
    protected $_template = 'Vuleticd_Assist::info.phtml';


    /**
     * Enter description here...
     *
     * @return $this
     */
    protected function _convertAdditionalData()
    {
        /*$details = @unserialize($this->getInfo()->getAdditionalData());
        if (is_array($details)) {
            $this->_payableTo = isset($details['payable_to']) ? (string)$details['payable_to'] : '';
            $this->_mailingAddress = isset($details['mailing_address']) ? (string)$details['mailing_address'] : '';
        } else {
            $this->_payableTo = '';
            $this->_mailingAddress = '';
        }*/
        return $this;
    }

    /**
     * @return string
     */
    public function toPdf()
    {
        /*$this->setTemplate('Magento_OfflinePayments::info/pdf/checkmo.phtml');*/
        return $this->toHtml();
    }
}