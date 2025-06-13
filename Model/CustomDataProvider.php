<?php

namespace DominateAheadworksCompatibility\RewardPoints\Model;

use IWD\CheckoutConnector\Model\CustomDataProvider as IWDCustomDataProvider;
use DominateAheadworksCompatibility\RewardPoints\Block\Checkout\RewardPoints;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Newsletter\Model\Subscriber;
use Psr\Log\LoggerInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\AddressFactory;
use Magento\Customer\Model\ResourceModel\Address;
use IWD\CheckoutConnector\Helper\Order as OrderHelper;

/**
 * Class CustomDataProvider
 */
class CustomDataProvider extends IWDCustomDataProvider
{
    /**#@+
     * Allowed Custom Data Render Areas
     */
    public const BEFORE_COUPON = 'before_coupon';
    public const AFTER_COUPON  = 'after_coupon';
    /**#@-*/

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param CartRepositoryInterface $cartRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param PageFactory $resultPageFactory
     * @param Subscriber $subscriber
     * @param LoggerInterface $logger
     * @param CustomerFactory $customerFactory
     * @param AddressFactory $addressFactory
     * @param Address $addressResource
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        OrderRepositoryInterface $orderRepository,
        PageFactory $resultPageFactory,
        Subscriber $subscriber,
        LoggerInterface $logger,
        CustomerFactory $customerFactory,
        AddressFactory $addressFactory,
        Address $addressResource,
        OrderHelper $orderHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct(
            $cartRepository,
            $orderRepository,
            $resultPageFactory,
            $subscriber,
            $logger,
            $customerFactory,
            $addressFactory,
            $addressResource,
            $orderHelper
        );
    }

    /**
     * Pass Custom Data fields to Checkout
     *
     * @param $quote
     * @return array
     */
    public function getData($quote)
    {
        // Get data from parent method
        $data = parent::getData($quote);
        
        /**
         * Add Reward Points field to checkout
         */
        $resultPage = $this->resultPageFactory->create();
        /** @var RewardPoints $block */
        $block = $resultPage->getLayout()->createBlock('DominateAheadworksCompatibility\RewardPoints\Block\Checkout\RewardPoints');
        
        if (!empty($block)) {
            $content = $block
                ->setQuote($quote)
                ->setTemplate('DominateAheadworksCompatibility_RewardPoints::checkout/reward_points_field.phtml');
            
            if ($block->shouldDisplayRewardPoints()) {
                // Add Reward Points field to checkout
                $data[self::AFTER_COUPON] = $content->toHtml();
            }
        }
        
        return $data;
    }
} 