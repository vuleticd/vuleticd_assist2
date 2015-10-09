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

class Mobile implements \Magento\Framework\Option\ArrayInterface
{
    const CONTROLLED = 0;
    const STANDARD = 1;
    const MOBILE = 2;

    public function toOptionArray()
    {
        $mobileOptions = [
            self::CONTROLLED => __('Controlled in ASSIST account'),
            self::STANDARD => __('Standard pages'),
            self::MOBILE => __('Mobile pages'),
        ];   
        return $mobileOptions;
    }
}
