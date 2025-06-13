<?php

namespace DominateAheadworksCompatibility\RewardPoints\Model\Calculator\Earning\EarnItemResolver\RawItemProcessor;

use Aheadworks\RewardPoints\Model\Calculator\Earning\Calculator\Invoice as InvoiceCalculator;
use Aheadworks\RewardPoints\Model\Calculator\Earning\EarnItemResolver\ItemFilter;
use Aheadworks\RewardPoints\Model\Calculator\Earning\EarnItemResolver\RawItemProcessor\OrderItemsResolver;
use Magento\Framework\Data\Collection;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\InvoiceItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\Invoice\Item as InvoiceItem;

/**
 * Class InvoiceItemsResolver
 */
class InvoiceItemsResolver extends \Aheadworks\RewardPoints\Model\Calculator\Earning\EarnItemResolver\RawItemProcessor\InvoiceItemsResolver
{
    /**
     * @var OrderItemsResolver
     */
    private $orderItemsResolver;

    /**
     * @var InvoiceCalculator
     */
    private $invoiceCalculator;

    /**
     * @var ItemFilter
     */
    private $itemFilter;

    /**
     * @param OrderItemsResolver $orderItemsResolver
     * @param InvoiceCalculator $invoiceCalculator
     * @param ItemFilter $itemFilter
     */
    public function __construct(
        OrderItemsResolver $orderItemsResolver,
        InvoiceCalculator $invoiceCalculator,
        ItemFilter $itemFilter
    ) {
        parent::__construct($orderItemsResolver, $invoiceCalculator, $itemFilter);
        
        $this->orderItemsResolver = $orderItemsResolver;
        $this->invoiceCalculator = $invoiceCalculator;
        $this->itemFilter = $itemFilter;
    }

    /**
     * Get invoice items
     *
     * @param InvoiceInterface $invoice
     * @return array
     */
    public function getItems(InvoiceInterface $invoice): array
    {
        $invoiceItems = [];
        /** @var OrderItemInterface $orderItems */
        $orderItems = $this->orderItemsResolver->getOrderItems($invoice->getOrderId());
        if (!empty($orderItems)) {
            /** @var InvoiceItem[] $items */
            $items = $invoice->getItems();

            // Convert the collection to an array if it's not already
            if ($items instanceof Collection) {
                $items = $items->getItems();
            }

            $qty = $this->invoiceCalculator->getQtyItems($orderItems, (int) $invoice->getTotalQty());
            foreach ($items as $item) {
                if (isset($orderItems[$item->getOrderItemId()])) {
                    /** @var OrderItemInterface $orderItem */
                    $orderItem = $orderItems[$item->getOrderItemId()];
                    $orderParentItemId = $orderItem->getParentItemId();
                    $parentItemId = null;
                    if ($orderParentItemId) {
                        $parentItem = $this->getInvoiceItemByOrderItemId((int)$orderParentItemId, $items);
                        $parentItemId = $parentItem->getEntityId();
                    }
                    $item
                        ->setItemId($item->getEntityId())
                        ->setParentItemId($parentItemId)
                        ->setProductType($orderItem->getProductType())
                        ->setIsChildrenCalculated($orderItem->isChildrenCalculated())
                        ->setAwRpAmountForOtherDeduction(
                            $this->invoiceCalculator->calculateAmount($orderItem, $invoice, $qty)
                        );

                    $invoiceItems[$item->getEntityId()] = $item;
                }
            }
        }
        return $this->itemFilter->filterItemsWithoutDiscount($invoiceItems);
    }

    /**
     * Get invoice item by order item
     *
     * @param int $orderItemId
     * @param InvoiceItemInterface[] $invoiceItems
     * @return InvoiceItemInterface|null
     */
    private function getInvoiceItemByOrderItemId(int $orderItemId, array $invoiceItems): ?InvoiceItemInterface
    {
        foreach ($invoiceItems as $invoiceItem) {
            if ($invoiceItem->getOrderItemId() == $orderItemId) {
                return $invoiceItem;
            }
        }
        return null;
    }
} 