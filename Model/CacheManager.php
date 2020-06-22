<?php
/**
 * Frenet Shipping Gateway
 *
 * @category Frenet
 *
 * @author Tiago Sampaio <tiago@tiagosampaio.com>
 * @link https://github.com/tiagosampaio
 * @link https://tiagosampaio.com
 *
 * Copyright (c) 2020.
 */

declare(strict_types = 1);

namespace Frenet\Shipping\Model;

use Frenet\Framework\Data\Serializer;
use Frenet\ObjectType\Entity\Shipping\Quote\Service;
use Frenet\Shipping\Model\Cache\CacheKeyGeneratorInterface;
use Frenet\Shipping\Model\Cache\Type\Frenet as FrenetCacheType;
use Frenet\Shipping\Model\Formatters\PostcodeNormalizer;
use Frenet\Shipping\Model\Quote\CouponProcessor;
use Frenet\Shipping\Model\Quote\ItemQuantityCalculatorInterface;
use Frenet\Shipping\Model\Quote\QuoteItemValidatorInterface;
use Frenet\Shipping\Service\RateRequestProvider;
use Magento\Framework\App\Cache\StateInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Quote\Model\Quote\Item\AbstractItem as QuoteItem;

/**
 * Class CacheManager
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CacheManager
{
    /**
     * @var StateInterface
     */
    private $cacheState;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var QuoteItemValidatorInterface
     */
    private $quoteItemValidator;

    /**
     * @var ItemQuantityCalculatorInterface
     */
    private $itemQuantityCalculator;

    /**
     * @var PostcodeNormalizer
     */
    private $postcodeNormalizer;

    /**
     * @var Quote\CouponProcessor
     */
    private $couponProcessor;

    /**
     * @var RateRequestProvider
     */
    private $rateRequestProvider;

    /**
     * @var CacheKeyGeneratorInterface
     */
    private $cacheKeyGenerator;

    public function __construct(
        SerializerInterface $serializer,
        StateInterface $cacheState,
        CacheInterface $cache,
        QuoteItemValidatorInterface $quoteItemValidator,
        ItemQuantityCalculatorInterface $itemQuantityCalculator,
        PostcodeNormalizer $postcodeNormalizer,
        CouponProcessor $couponProcessor,
        Config $config,
        RateRequestProvider $rateRequestProvider,
        CacheKeyGeneratorInterface $cacheKeyGenerator
    ) {
        $this->serializer = $serializer;
        $this->cacheState = $cacheState;
        $this->cache = $cache;
        $this->config = $config;
        $this->quoteItemValidator = $quoteItemValidator;
        $this->itemQuantityCalculator = $itemQuantityCalculator;
        $this->couponProcessor = $couponProcessor;
        $this->postcodeNormalizer = $postcodeNormalizer;
        $this->rateRequestProvider = $rateRequestProvider;
        $this->cacheKeyGenerator = $cacheKeyGenerator;
    }

    /**
     * @return array|bool|string
     */
    public function load()
    {
        if (!$this->isCacheEnabled()) {
            return false;
        }

        $data = $this->cache->load($this->cacheKeyGenerator->generate());

        if ($data) {
            $data = $this->prepareAfterLoading($data);
        }

        return $data;
    }

    /**
     * @param array $services
     *
     * @return bool
     */
    public function save(array $services)
    {
        if (!$this->isCacheEnabled()) {
            return false;
        }

//        $identifier = $this->generateCacheKey();
        $identifier = $this->cacheKeyGenerator->generate();
        $lifetime = null;
        $tags = [FrenetCacheType::CACHE_TAG];

        return $this->cache->save(
            $this->prepareBeforeSaving($services),
            $identifier,
            $tags,
            $lifetime
        );
    }

    /**
     * @param $data
     *
     * @return array
     */
    private function prepareAfterLoading($data) : array
    {
        $newData  = [];
        $services = $this->serializer->unserialize($data);

        /** @var array $service */
        foreach ($services as $service) {
            $newData[] = $this->createServiceInstance()->setData($service);
        }

        return $newData;
    }

    /**
     * @param array $services
     *
     * @return bool|string
     */
    private function prepareBeforeSaving(array $services)
    {
        $newData = [];

        /** @var \Frenet\ObjectType\Entity\Shipping\QuoteInterface $service */
        foreach ($services as $service) {
            $newData[] = $service->getData();
        }

        return $this->serializer->serialize($newData);
    }

    /**
     * @return string
     */
    private function generateCacheKey()
    {
        $request = $this->rateRequestProvider->getRateRequest();

        $destPostcode = $request->getDestPostcode();
        $origPostcode = $this->config->getOriginPostcode();
        $items = [];

        /** @var QuoteItem $item */
        foreach ($request->getAllItems() as $item) {
            if (!$this->quoteItemValidator->validate($item)) {
                continue;
            }

            $productId = (int) $item->getProductId();

            if ($item->getParentItem()) {
                $productId = $item->getParentItem()->getProductId() . '-' . $productId;
            }

            $qty = (float) $this->itemQuantityCalculator->calculate($item);

            $items[$productId] = $qty;
        }

        ksort($items);

        $cacheKey = $this->serializer->serialize([
            $this->postcodeNormalizer->format($origPostcode),
            $this->postcodeNormalizer->format($destPostcode),
            $items,
            $this->couponProcessor->getCouponCode(),
            $this->config->isMultiQuoteEnabled() ? 'multi' : null
        ]);

        return $cacheKey;
    }

    /**
     * @return bool
     */
    private function isCacheEnabled()
    {
        return (bool) $this->cacheState->isEnabled(FrenetCacheType::TYPE_IDENTIFIER);
    }

    /**
     * @return Service
     */
    private function createServiceInstance()
    {
        return new Service(
            new Serializer()
        );
    }
}
