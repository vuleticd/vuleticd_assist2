ASSIST payment gateway for Magento2
==========================

[ASSIST Belarus](http://www.belassist.by/) is a subsidiary of ASSIST - the largest provider of electronic payment in Russia. This extension provides Magento2 payment method for ASSIST Belarus payment gateway.

ASSIST is Hosted Payment Pages gateway with IPN notifications and web services available for capture, refund and void.

Implementation:
------------------------

- Redirects customer to ASSIST Hosted Payment page after order is placed in Magento checkout.
- Listening for successful payment notifications(IPN) from ASSIST. Only success notifications are implemented.
- Processing returning customers after successful ASSIST payment. During this process ASSIST **orderstatus** web service is called to double check order state (in case IPN was not yet received)
- Processing returning customers after failed ASSIST payment.

Extension Name:
------------------------

Vuleticd_Assist

Version:
------------------------

Alpha: version 1.0.0

Change log:
------------------------

1.0.0: initial alpha release

Features:
------------------------

- Supports allowed payment systems configuration. Credit Card, YandexMoney, WebMoney, QIWI payment, Mobile phone money(MTS), Megafon, Beeline
- Supports Payment Action configuration. Authorize, Authorize & Capture
- Supports full amounth Void, Capture, Refund.
- Supports mobile payment pages configuration. Standard, Mobile
- Test mode switch.
- Debugging switch.
- Full open source code.


Installation
------------------------

1. Go to Magento2 root folder
2. Enter following commands to install module:

    ```bash
    composer config repositories.vuleticdassist git https://github.com/vuleticd/vuleticd_assist2.git
    composer require vuleticd/assist:dev-master
    ```
   Wait while dependencies are updated.

3. Enter following commands to enable module:

    ```bash
    php bin/magento module:enable Vuleticd_Assist --clear-static-content
    php bin/magento setup:upgrade
    ```
4. Enable and configure ASSIST in Magento Admin under Stores/Configuration/Sales/Payment Methods/ASSIST