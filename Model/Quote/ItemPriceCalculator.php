<?php

declare(strict_types = 1);

namespace Frenet\Shipping\Model\Quote;

use Frenet\Shipping\Model\Quote\Calculators\PriceCalculatorFactory;
use Magento\Quote\Model\Quote\Item as QuoteItem;

/**
 * Class ItemPriceCalculator
 *
 * @package Frenet\Shipping\Model\Quote
 */
class ItemPriceCalculator
{
    /**
     * @var ItemQuantityCalculatorInterface
     */
    private $itemQuantityCalculator;

    /**
     * @var PriceCalculatorFactory
     */
    private $priceCalculatorFactory;

    public function __construct(
        ItemQuantityCalculatorInterface $itemQuantityCalculator,
        PriceCalculatorFactory $priceCalculatorFactory
    ) {
        $this->itemQuantityCalculator = $itemQuantityCalculator;
        $this->priceCalculatorFactory = $priceCalculatorFactory;
    }

    /**
     * @param QuoteItem $item
     *
     * @return float
     */
    public function getPrice(QuoteItem $item)
    {
        return $this->priceCalculatorFactory->create($item)->getPrice($item);
//        return $this->getRealItem($item)->getPrice();
    }

    /**
     * @param QuoteItem $item
     *
     * @return float
     */
    public function getFinalPrice(QuoteItem $item)
    {
        return $this->priceCalculatorFactory->create($item)->getFinalPrice($item);
        /** @var QuoteItem $realItem */
//        $realItem = $this->getRealItem($item);
//        return $realItem->getRowTotal() / $this->itemQuantityCalculator->calculate($realItem);
    }

    /**
     * @param QuoteItem $item
     *
     * @return QuoteItem
     */
    private function getRealItem(QuoteItem $item)
    {
        $type = $item->getProductType();

        if ($item->getParentItemId()) {
            $type = $item->getParentItem()->getProductType();
        }

        switch ($type) {
            case \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE:
                return $item->getParentItem();

            case \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE:
                /**
                 * Product is Grouped.
                 * @todo Validate if this approach is the correct one.
                 */
            case \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE:
                /**
                 * Product is Bundle.
                 * @todo Validate if this approach is the correct one.
                 */
            case \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL:
            case \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE:
            case \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE:
            default:
                return $item;
        }
    }
}
