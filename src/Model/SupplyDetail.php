<?php

namespace ONIXParser\Model;

/**
 * ONIX SupplyDetail (one supplier / distributor of a product, with its own availability and prices).
 *
 * A Product has one SupplyDetail per <SupplyDetail> element. Nothing is merged and nothing is
 * overwritten: with two distributors you get two objects, each with its own Price list.
 *
 * Dates are arrays ['role' => '18', 'format' => '00', 'date' => '20190510'].
 */
class SupplyDetail
{
    /** @var string|null Supplier GLN (SupplierIDType 06) */
    private $supplierGln;

    /** @var string|null */
    private $supplierName;

    /** @var string|null SupplierRole (list 93) */
    private $supplierRole;

    /** @var array<int,array{type:string,value:string}> every SupplierIdentifier */
    private $supplierIdentifiers = [];

    /** @var string|null ProductAvailability (list 65), exactly as sent */
    private $availability;

    /** @var array<int,array{type:?string,code:?string}> every ReturnsConditions */
    private $returnsConditions = [];

    /** @var array<int,array{role:?string,format:?string,date:?string}> */
    private $supplyDates = [];

    /** @var string|null OrderTime (days) */
    private $orderTime;

    /** @var string|null UnpricedItemType (list 57) */
    private $unpricedItemType;

    /** @var Price[] */
    private $prices = [];

    public function getSupplierGln(): ?string
    {
        return $this->supplierGln;
    }

    public function setSupplierGln(?string $gln): self
    {
        $this->supplierGln = $gln;
        return $this;
    }

    public function getSupplierName(): ?string
    {
        return $this->supplierName;
    }

    public function setSupplierName(?string $name): self
    {
        $this->supplierName = $name;
        return $this;
    }

    public function getSupplierRole(): ?string
    {
        return $this->supplierRole;
    }

    public function setSupplierRole(?string $role): self
    {
        $this->supplierRole = $role;
        return $this;
    }

    public function getSupplierIdentifiers(): array
    {
        return $this->supplierIdentifiers;
    }

    public function addSupplierIdentifier(string $type, string $value): self
    {
        $this->supplierIdentifiers[] = ['type' => $type, 'value' => $value];
        return $this;
    }

    /**
     * ProductAvailability code exactly as the sender wrote it (no interpretation).
     */
    public function getAvailability(): ?string
    {
        return $this->availability;
    }

    public function setAvailability(?string $availability): self
    {
        $this->availability = $availability;
        return $this;
    }

    public function getAvailabilityName(): ?string
    {
        if ($this->availability === null) {
            return null;
        }
        return \ONIXParser\CodeMaps::getAvailabilityCodeMap()[$this->availability] ?? null;
    }

    public function getReturnsConditions(): array
    {
        return $this->returnsConditions;
    }

    public function addReturnsConditions(?string $type, ?string $code): self
    {
        $this->returnsConditions[] = ['type' => $type, 'code' => $code];
        return $this;
    }

    /**
     * Type of the first ReturnsConditions of ReturnsCodeType 01 (proprietary / DILICOM), else the first one.
     */
    public function getReturnsCodeType(): ?string
    {
        $r = $this->primaryReturns();
        return $r['type'] ?? null;
    }

    public function getReturnsCode(): ?string
    {
        $r = $this->primaryReturns();
        return $r['code'] ?? null;
    }

    private function primaryReturns(): ?array
    {
        foreach ($this->returnsConditions as $r) {
            if ($r['type'] === '01') {
                return $r;
            }
        }
        return $this->returnsConditions[0] ?? null;
    }

    /**
     * @return array<int,array{role:?string,format:?string,date:?string}>
     */
    public function getSupplyDates(): array
    {
        return $this->supplyDates;
    }

    public function addSupplyDate(?string $role, ?string $format, ?string $date): self
    {
        $this->supplyDates[] = ['role' => $role, 'format' => $format, 'date' => $date];
        return $this;
    }

    /**
     * The date string of the first SupplyDate with this role (e.g. '18' last return date), or null.
     */
    public function getSupplyDate(string $role): ?string
    {
        foreach ($this->supplyDates as $d) {
            if ($d['role'] === $role) {
                return $d['date'];
            }
        }
        return null;
    }

    public function getOrderTime(): ?string
    {
        return $this->orderTime;
    }

    public function setOrderTime(?string $orderTime): self
    {
        $this->orderTime = $orderTime;
        return $this;
    }

    public function getUnpricedItemType(): ?string
    {
        return $this->unpricedItemType;
    }

    public function setUnpricedItemType(?string $type): self
    {
        $this->unpricedItemType = $type;
        return $this;
    }

    /**
     * Every <Price> of this SupplyDetail, in file order (also prices without an amount).
     *
     * @return Price[]
     */
    public function getPrices(): array
    {
        return $this->prices;
    }

    public function addPrice(Price $price): self
    {
        $this->prices[] = $price;
        return $this;
    }
}
