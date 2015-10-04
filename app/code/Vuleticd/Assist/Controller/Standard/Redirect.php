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

class Redirect extends \Magento\Framework\App\Action\Action
{
    public function execute()
    {
        echo 'TTTT';
        /*
        try {
            if (!$this->_getOrder()->getId()) {
                Mage::throwException(Mage::helper('assist')->__('No order for processing found'));
            }
            $this->_getCheckoutSession()->setAssistQuoteId($this->_getCheckoutSession()->getQuoteId());
            $this->loadLayout();
            $this->renderLayout();
            $this->_getCheckoutSession()->unsQuoteId();
            $this->_getCheckoutSession()->unsRedirectUrl();
            return;
        } catch (Mage_Core_Exception $e) {
            $this->_getCheckoutSession()->addError($e->getMessage());
        } catch(Exception $e) {
            Mage::helper('assist')->debug('error: ' . $e->getMessage());
            Mage::logException($e);
        }
        $this->_redirect('checkout/cart');
        ////////////////////////////////////////////
        $pageId = $this->getRequest()->getParam('page_id', $this->getRequest()->getParam('id', false));
        $resultPage = $this->_objectManager->get('Magento\Cms\Helper\Page')->prepareResultPage($this, $pageId);
        if (!$resultPage) {
            $resultForward = $this->resultForwardFactory->create();
            return $resultForward->forward('noroute');
        }
        return $resultPage;
        */
    }
}
