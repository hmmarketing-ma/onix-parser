<?php

namespace ONIXParser\Model;

/**
 * ONIX Tax composite inside a Price. A price can carry several (multi-VAT).
 * Amounts are floats; the raw strings stay available because senders write '5.5', '5.50', '20.0'.
 */
class Tax
{
    /** @var string|null TaxType (list 174), e.g. 01 VAT */
    private $type;

    /** @var string|null TaxRateCode (list 62): S, R, Z ... */
    private $rateCode;

    /** @var string|null TaxRatePercent exactly as sent */
    private $ratePercentRaw;

    /** @var string|null */
    private $taxableAmountRaw;

    /** @var string|null */
    private $taxAmountRaw;

    public function __construct(?string $type = null, ?string $rateCode = null, ?string $ratePercent = null, ?string $taxableAmount = null, ?string $taxAmount = null)
    {
        $this->type = $type;
        $this->rateCode = $rateCode;
        $this->ratePercentRaw = $ratePercent;
        $this->taxableAmountRaw = $taxableAmount;
        $this->taxAmountRaw = $taxAmount;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getRateCode(): ?string
    {
        return $this->rateCode;
    }

    public function getRatePercent(): ?float
    {
        return self::num($this->ratePercentRaw);
    }

    public function getRatePercentRaw(): ?string
    {
        return $this->ratePercentRaw;
    }

    public function getTaxableAmount(): ?float
    {
        return self::num($this->taxableAmountRaw);
    }

    public function getTaxAmount(): ?float
    {
        return self::num($this->taxAmountRaw);
    }

    private static function num(?string $v): ?float
    {
        return ($v !== null && $v !== '' && is_numeric($v)) ? (float)$v : null;
    }
}
