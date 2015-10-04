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

class Action implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        $paymentActions = [
            \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE => __('Authorization'),
            \Magento\Payment\Model\Method\AbstractMethod::ACTION_AUTHORIZE_CAPTURE => __('Authorization & Capture'),
        ];
        
        return $paymentActions;
    }
}
