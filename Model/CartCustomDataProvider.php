<?php

namespace DominateAheadworksCompatibility\RewardPoints\Model;

use DominateAheadworksCompatibility\RewardPoints\Block\Checkout\RewardPoints;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class CartCustomDataProvider
 */
class CartCustomDataProvider
{
    /**#@+
     * Allowed Custom Cart Totals Render Areas
     */
    public const BEFORE_SUBTOTAL   = 'before_subtotal';
    public const AFTER_SUBTOTAL    = 'after_subtotal';
    public const AFTER_SHIPPING    = 'after_shipping';
    public const AFTER_TAX         = 'after_tax';
    public const AFTER_DISCOUNT    = 'after_discount';
    public const AFTER_GRAND_TOTAL = 'after_grand_total';
    /**#@-*/

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Get custom data for cart totals
     *
     * @param $quote
     * @return array
     */
    public function getCustomData($quote)
    {
        $data = [];
        
        /**
         * Add Reward Points to totals section
         */
        $resultPage = $this->resultPageFactory->create();
        /** @var RewardPoints $block */
        $block = $resultPage->getLayout()->createBlock('DominateAheadworksCompatibility\RewardPoints\Block\Checkout\RewardPoints');
        
        if (!empty($block)) {
            $block->setQuote($quote);
            
            // Add variables for reward points data
            $data['variables'] = [
                'appliedRewardPointsAmount' => $block->getAppliedRewardPointsAmount(),
                'maxAllowedRewardPoints' => $block->getMaxAllowedRewardPointsToApply(),
                'appliedRewardPointsQty' => $block->getAppliedRewardPointsQty(),
                'canApplyRewardPoints' => $block->canApplyRewardPoints(),
                'areRewardPointsApplied' => $block->areRewardPointsApplied()
            ];
            
            $content = $block->setTemplate('DominateAheadworksCompatibility_RewardPoints::checkout/reward_points_totals.phtml');
            
            if ($block->areRewardPointsApplied()) { 
                // Add Reward Points to totals section
                $data['html_blocks'][self::AFTER_DISCOUNT] = $content->toHtml();
            }
        }
        
        return $data;
    }
} 