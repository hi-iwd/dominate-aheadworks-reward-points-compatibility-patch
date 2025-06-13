<?php

namespace DominateAheadworksCompatibility\RewardPoints\Model\Cart;

use IWD\CheckoutConnector\Model\Cart\CartTotals as IWDCartTotals;
use DominateAheadworksCompatibility\RewardPoints\Model\CartCustomDataProvider;
use Magento\Directory\Model\Currency;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Tax\Model\Config as TaxConfig;

/**
 * Class CartTotals
 */
class CartTotals extends IWDCartTotals
{
    /**
     * @var CartCustomDataProvider
     */
    private $cartCustomDataProvider;

    /**
     * @param Currency $currency
     * @param ModuleListInterface $moduleList
     * @param TaxConfig $taxConfig
     * @param CartCustomDataProvider $cartCustomDataProvider
     */
    public function __construct(
        Currency $currency,
        ModuleListInterface $moduleList,
        TaxConfig $taxConfig,
        CartCustomDataProvider $cartCustomDataProvider
    ) {
        $this->cartCustomDataProvider = $cartCustomDataProvider;
        parent::__construct($currency, $moduleList, $taxConfig);
    }

    /**
     * @param $quote Quote
     * @param bool $additional
     * @return array
     */
    public function getTotals($quote, $additional = true)
    {
        // Get totals from parent
        $totals = parent::getTotals($quote, $additional);
        
        // Add our custom data
        $totals['custom_data'] = $this->cartCustomDataProvider->getCustomData($quote);
        
        return $totals;
    }
} 