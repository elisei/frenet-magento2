<?php

declare(strict_types = 1);

namespace Frenet\Shipping\Api;

/**
 * Class WeightConverterInterface
 * @package Frenet\Shipping\Api
 */
interface WeightConverterInterface
{
    /**
     * @var float
     */
    const LBS_TO_KG_FACTOR = 0.453592;

    /**
     * @var float
     */
    const KG_TO_LBS_FACTOR = 2.20462;

    /**
     * @param float $weight
     * @return float
     */
    public function convertToKg($weight);

    /**
     * @param float $weight
     * @return float
     */
    public function convertToLbs($weight);
}
