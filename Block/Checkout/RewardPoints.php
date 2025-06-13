<?php

namespace DominateAheadworksCompatibility\RewardPoints\Block\Checkout;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Quote\Model\Quote;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Aheadworks\RewardPoints\Api\RewardPointsCartManagementInterface;
use Aheadworks\RewardPoints\Api\Data\CustomerCartMetadataInterface;

/**
 * Class RewardPoints
 * Comprehensive block for all reward points functionality in checkout
 */
class RewardPoints extends Template
{
    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var PricingHelper
     */
    protected $pricingHelper;

    /**
     * @var RewardPointsCartManagementInterface
     */
    private $rewardPointsCartService;

    /**
     * @var CustomerCartMetadataInterface|null
     */
    private $customerCartMetadata;

    /**
     * @param Context $context
     * @param PricingHelper $pricingHelper
     * @param RewardPointsCartManagementInterface $rewardPointsCartService
     * @param array $data
     */
    public function __construct(
        Context $context,
        PricingHelper $pricingHelper,
        RewardPointsCartManagementInterface $rewardPointsCartService,
        array $data = []
    ) {
        $this->pricingHelper = $pricingHelper;
        $this->rewardPointsCartService = $rewardPointsCartService;
        parent::__construct($context, $data);
    }

    /**
     * Set quote
     *
     * @param Quote $quote
     * @return $this
     */
    public function setQuote(Quote $quote)
    {
        $this->quote = $quote;
        $this->customerCartMetadata = null; // Reset metadata when quote changes
        return $this;
    }

    /**
     * Get quote
     *
     * @return Quote
     */
    public function getQuote()
    {
        return $this->quote;
    }

    /**
     * Get customer cart metadata
     *
     * @return CustomerCartMetadataInterface|null
     */
    private function getCustomerCartMetadata()
    {
        if ($this->customerCartMetadata === null && $this->quote && $this->isCustomerLoggedIn()) {
            try {
                $this->customerCartMetadata = $this->rewardPointsCartService->getCustomerCartMetadata(
                    $this->quote->getCustomerId(),
                    $this->quote->getId()
                );
            } catch (\Exception $e) {
                // If we can't get metadata, return null and methods will handle gracefully
                $this->customerCartMetadata = null;
            }
        }
        return $this->customerCartMetadata;
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     */
    public function isCustomerLoggedIn()
    {
        return (bool) $this->quote && $this->quote->getCustomer()->getId();
    }

    /**
     * Get reward points segment from totals
     *
     * @return array|null
     */
    public function getRewardPointsSegment()
    {
        if (!$this->quote) {
            return null;
        }

        $totals = $this->quote->getTotals();
        if (isset($totals['aw_reward_points'])) {
            return $totals['aw_reward_points'];
        }

        return null;
    }

    /**
     * Get reward points title from totals
     *
     * @return string
     */
    public function getRewardPointsTitle()
    {
        $segment = $this->getRewardPointsSegment();
        if ($segment && isset($segment['title'])) {
            return $segment['title'];
        }
        return '';
    }

    /**
     * Get reward points value from totals
     *
     * @return float
     */
    public function getRewardPointsValue()
    {
        $segment = $this->getRewardPointsSegment();
        if ($segment && isset($segment['value'])) {
            return (float)$segment['value'];
        }
        return 0;
    }

    /**
     * Get formatted reward points value
     *
     * @return string
     */
    public function getFormattedRewardPointsValue()
    {
        $value = $this->getRewardPointsValue();
        if ($value != 0) {
            return $this->formatPrice($value);
        }
        return '';
    }

    /**
     * Format price
     *
     * @param float $amount
     * @return string
     */
    public function formatPrice($amount)
    {
        return $this->pricingHelper->currency($amount, true, false);
    }

    /**
     * Check if reward points can be applied
     *
     * @return bool
     */
    public function canApplyRewardPoints()
    {
        $metadata = $this->getCustomerCartMetadata();
        return $metadata ? $metadata->getCanApplyRewardPoints() : false;
    }

    /**
     * Check if reward points are applied (from metadata)
     *
     * @return bool
     */
    public function areRewardPointsApplied()
    {
        $metadata = $this->getCustomerCartMetadata();
        return $metadata ? $metadata->getAreRewardPointsApplied() : false;
    }

    /**
     * Get applied reward points quantity
     *
     * @return int
     */
    public function getAppliedRewardPointsQty()
    {
        $metadata = $this->getCustomerCartMetadata();
        return $metadata ? (int)$metadata->getAppliedRewardPointsQty() : 0;
    }

    /**
     * Get applied reward points amount
     *
     * @return float
     */
    public function getAppliedRewardPointsAmount()
    {
        $metadata = $this->getCustomerCartMetadata();
        return $metadata ? (float)$metadata->getAppliedRewardPointsAmount() : 0.0;
    }

    /**
     * Get customer's available reward points balance
     *
     * @return int
     */
    public function getCustomerRewardPointsBalance()
    {
        $metadata = $this->getCustomerCartMetadata();
        return $metadata ? (int)$metadata->getRewardPointsBalanceQty() : 0;
    }

    /**
     * Get maximum allowed reward points to apply
     *
     * @return int
     */
    public function getMaxAllowedRewardPointsToApply()
    {
        $metadata = $this->getCustomerCartMetadata();
        return $metadata ? (int)$metadata->getRewardPointsMaxAllowedQtyToApply() : 0;
    }

    /**
     * Get reward points conversion rate (1 point = X currency)
     *
     * @return float
     */
    public function getRewardPointsConversionRate()
    {
        $metadata = $this->getCustomerCartMetadata();
        return $metadata ? (float)$metadata->getRewardPointsConversionRatePointToCurrencyValue() : 0.0;
    }

    /**
     * Get formatted conversion rate
     *
     * @return string
     */
    public function getFormattedConversionRate()
    {
        $rate = $this->getRewardPointsConversionRate();
        return $this->pricingHelper->currency($rate, true, false);
    }

    /**
     * Get reward points label name
     *
     * @return string
     */
    public function getRewardPointsLabelName()
    {
        $metadata = $this->getCustomerCartMetadata();
        return $metadata ? $metadata->getRewardPointsLabelName() : __('Reward Points');
    }

    /**
     * Get reward points tab label name
     *
     * @return string
     */
    public function getRewardPointsTabLabelName()
    {
        $metadata = $this->getCustomerCartMetadata();
        return $metadata ? $metadata->getRewardPointsTabLabelName() : __('Reward Points');
    }

    /**
     * Get formatted applied reward points amount
     *
     * @return string
     */
    public function getFormattedAppliedRewardPointsAmount()
    {
        $amount = $this->getAppliedRewardPointsAmount();
        return $this->pricingHelper->currency($amount, true, false);
    }

    /**
     * Get input field value (max allowed or applied amount)
     *
     * @return int
     */
    public function getInputFieldValue()
    {
        if ($this->areRewardPointsApplied()) {
            return $this->getAppliedRewardPointsQty();
        }
        
        return $this->getMaxAllowedRewardPointsToApply();
    }

    /**
     * Get button text based on state
     *
     * @return string
     */
    public function getButtonText()
    {
        return $this->areRewardPointsApplied() ? __('Remove') : __('Apply');
    }

    /**
     * Get button action
     *
     * @return string
     */
    public function getButtonAction()
    {
        return $this->areRewardPointsApplied() ? 'remove' : 'apply';
    }

    /**
     * Check if reward points functionality should be displayed
     *
     * @return bool
     */
    public function shouldDisplayRewardPoints()
    {
        return $this->isCustomerLoggedIn() && 
               ($this->canApplyRewardPoints() || $this->areRewardPointsApplied());
    }
} 