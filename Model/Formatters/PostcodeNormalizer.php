<?php

declare(strict_types = 1);

namespace Frenet\Shipping\Model\Formatters;

/**
 * Class PostcodeNormalizer
 */
class PostcodeNormalizer
{
    /**
     * @param string $postcode
     *
     * @return string
     */
    public function format(string $postcode = null)
    {
        $postcode = preg_replace('/[^0-9]/', null, $postcode);
        $postcode = str_pad($postcode, 8, '0', STR_PAD_LEFT);

        return $postcode;
    }
}