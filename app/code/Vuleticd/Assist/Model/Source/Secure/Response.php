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
namespace Vuleticd\Assist\Model\Source\Secure;

class Response implements \Magento\Framework\Option\ArrayInterface
{
    const NONE = 0;
    const MD5 = 1;
    const PGP = 2;

    public function toOptionArray()
    {
        $options = [
            self::MD5 => __('MD5'),
            self::PGP => __('PGP'),
            self::NONE => __('None'),
        ];   
        return $options;
    }
}
