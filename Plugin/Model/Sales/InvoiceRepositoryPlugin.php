<?php

namespace DominateAheadworksCompatibility\RewardPoints\Plugin\Model\Sales;

use Aheadworks\RewardPoints\Api\CustomerRewardPointsManagementInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;

/**
 * Class InvoiceRepositoryPlugin
 */
class InvoiceRepositoryPlugin extends \Aheadworks\RewardPoints\Plugin\Model\Sales\InvoiceRepositoryPlugin
{
    /**
     * @var CustomerRewardPointsManagementInterface
     */
    protected $customerRewardPointsService;

    /**
     * @param CustomerRewardPointsManagementInterface $customerRewardPointsService
     */
    public function __construct(
        CustomerRewardPointsManagementInterface $customerRewardPointsService
    ) {
        parent::__construct($customerRewardPointsService);
        
        $this->customerRewardPointsService = $customerRewardPointsService;
    }

    /**
     * After save plugin for invoice repository
     *
     * @param InvoiceRepositoryInterface $subject
     * @param InvoiceInterface $result
     * @return InvoiceInterface
     */
    public function afterSave(InvoiceRepositoryInterface $subject, InvoiceInterface $result): InvoiceInterface
    {
        if ($result->getEntityId() && $result->wasPayCalled() && $result->getTransactionId()) {
            $this->customerRewardPointsService->addPointsForPurchases($result->getEntityId());
        }

        return $result;
    }
} 