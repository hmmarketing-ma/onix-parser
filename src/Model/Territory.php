<?php

namespace ONIXParser\Model;

/**
 * ONIX Territory composite (where a price or a market applies).
 *
 * Example from the DILICOM files, the price for Morocco:
 *   RegionsIncluded WORLD, CountriesExcluded FR BE CA CH.
 */
class Territory
{
    /** @var string[] */
    private $countriesIncluded = [];

    /** @var string[] */
    private $regionsIncluded = [];

    /** @var string[] */
    private $countriesExcluded = [];

    /** @var string[] */
    private $regionsExcluded = [];

    /**
     * @param string|null $countriesIncluded space separated ISO 3166-1 codes
     */
    public function __construct(?string $countriesIncluded = null, ?string $regionsIncluded = null, ?string $countriesExcluded = null, ?string $regionsExcluded = null)
    {
        $this->countriesIncluded = self::split($countriesIncluded);
        $this->regionsIncluded = self::split($regionsIncluded);
        $this->countriesExcluded = self::split($countriesExcluded);
        $this->regionsExcluded = self::split($regionsExcluded);
    }

    /** @return string[] */
    private static function split(?string $list): array
    {
        if ($list === null || trim($list) === '') {
            return [];
        }
        return array_values(array_filter(preg_split('/\s+/', trim($list))));
    }

    /** @return string[] */
    public function getCountriesIncluded(): array
    {
        return $this->countriesIncluded;
    }

    /** @return string[] */
    public function getRegionsIncluded(): array
    {
        return $this->regionsIncluded;
    }

    /** @return string[] */
    public function getCountriesExcluded(): array
    {
        return $this->countriesExcluded;
    }

    /** @return string[] */
    public function getRegionsExcluded(): array
    {
        return $this->regionsExcluded;
    }

    /**
     * Does this territory include a country?
     *
     * The library does not know region membership, except WORLD (every country). For any other
     * region (EEA, ROW ...) pass the regions the country belongs to in $countryRegions.
     * Exclusions win over inclusions.
     *
     * @param string $country ISO 3166-1 alpha-2, e.g. 'MA'
     * @param string[] $countryRegions extra regions the country is part of, e.g. ['ROW']
     */
    public function coversCountry(string $country, array $countryRegions = []): bool
    {
        $country = strtoupper($country);
        $regions = array_merge(['WORLD'], array_map('strtoupper', $countryRegions));

        if (in_array($country, $this->countriesExcluded, true)) {
            return false;
        }
        if (array_intersect($regions, $this->regionsExcluded)) {
            return false;
        }
        if (in_array($country, $this->countriesIncluded, true)) {
            return true;
        }
        return (bool)array_intersect($regions, $this->regionsIncluded);
    }

    public function toArray(): array
    {
        return [
            'countries_included' => $this->countriesIncluded,
            'regions_included' => $this->regionsIncluded,
            'countries_excluded' => $this->countriesExcluded,
            'regions_excluded' => $this->regionsExcluded,
        ];
    }
}
