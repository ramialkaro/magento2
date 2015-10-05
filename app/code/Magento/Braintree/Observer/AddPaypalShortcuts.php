<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Braintree\Observer;

use Magento\Framework\Event\ObserverInterface;

class AddPaypalShortcuts implements ObserverInterface
{
    const PAYPAL_SHORTCUT_BLOCK = 'Magento\Braintree\Block\PayPal\Shortcut';

    /**
     * @var \Magento\Braintree\Model\Config\PayPal
     */
    protected $paypalConfig;

    /**
     * @var \Magento\Braintree\Model\PaymentMethod\PayPal
     */
    protected $methodPayPal;

    /**
     * @param \Magento\Braintree\Model\PaymentMethod\PayPal $methodPayPal
     * @param \Magento\Braintree\Model\Config\PayPal $paypalConfig
     */
    public function __construct(
        \Magento\Braintree\Model\PaymentMethod\PayPal $methodPayPal,
        \Magento\Braintree\Model\Config\PayPal $paypalConfig
    ) {
        $this->methodPayPal = $methodPayPal;
        $this->paypalConfig = $paypalConfig;
    }

    /**
     * Add Braintree PayPal shortcut buttons
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        //Don't display shortcut on product view page
        if (!$this->methodPayPal->isActive() ||
            !$this->paypalConfig->isShortcutCheckoutEnabled() ||
            $observer->getEvent()->getIsCatalogProduct()) {
            return;
        }

        /** @var \Magento\Catalog\Block\ShortcutButtons $shortcutButtons */
        $shortcutButtons = $observer->getEvent()->getContainer();

        /** @var \Magento\Braintree\Block\PayPal\Shortcut $shortcut */
        $shortcut = $shortcutButtons->getLayout()->createBlock(
            self::PAYPAL_SHORTCUT_BLOCK,
            '',
            [
                'data' => [
                    'container' => $shortcutButtons,
                ]
            ]
        );

        if ($shortcut->skipShortcutForGuest()) {
            return;
        }
        $shortcut->setShowOrPosition(
            $observer->getEvent()->getOrPosition()
        );
        $shortcutButtons->addShortcut($shortcut);
    }
}
