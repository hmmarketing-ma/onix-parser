<?php

namespace ONIXParser\Model;

/**
 * One <ResourceVersion> of a <SupportingResource> (a file at a URL: one size of a cover, a sample ...).
 *
 * DILICOM sends each size of a cover as its own SupportingResource with one ResourceVersion
 * ("-cover-full" 800 px, "-large" 400 px, "-medium" 200 px, 80 px thumbnail). The size is in the
 * features: type 02 = height (px), 03 = width (px), 07 = exact size in bytes, 04 = file name,
 * 01 = image format (D502 = JPEG).
 */
class ResourceVersion
{
    /** @var string|null ResourceContentType (list 158): 01 front cover, 15 ... */
    private $contentType;

    /** @var string|null ResourceMode (list 159) */
    private $mode;

    /** @var string|null ResourceForm (list 161) */
    private $form;

    /** @var string|null */
    private $url;

    /** @var array<int,array{type:?string,value:?string}> every ResourceVersionFeature */
    private $features = [];

    /** @var array<int,array{role:?string,format:?string,date:?string}> every ContentDate */
    private $contentDates = [];

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function setContentType(?string $v): self
    {
        $this->contentType = $v;
        return $this;
    }

    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function setMode(?string $v): self
    {
        $this->mode = $v;
        return $this;
    }

    public function getForm(): ?string
    {
        return $this->form;
    }

    public function setForm(?string $v): self
    {
        $this->form = $v;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $v): self
    {
        $this->url = $v;
        return $this;
    }

    public function addFeature(?string $type, ?string $value): self
    {
        $this->features[] = ['type' => $type, 'value' => $value];
        return $this;
    }

    /** @return array<int,array{type:?string,value:?string}> */
    public function getFeatures(): array
    {
        return $this->features;
    }

    public function getFeature(string $type): ?string
    {
        foreach ($this->features as $f) {
            if ($f['type'] === $type && $f['value'] !== null && $f['value'] !== '') {
                return $f['value'];
            }
        }
        return null;
    }

    /** Height in pixels (feature 02), or null. */
    public function getHeight(): ?int
    {
        $v = $this->getFeature('02');
        return ($v !== null && ctype_digit($v)) ? (int)$v : null;
    }

    /** Width in pixels (feature 03), or null. */
    public function getWidth(): ?int
    {
        $v = $this->getFeature('03');
        return ($v !== null && ctype_digit($v)) ? (int)$v : null;
    }

    /** Exact file size in bytes (feature 07), or null. */
    public function getBytes(): ?int
    {
        $v = $this->getFeature('07');
        return ($v !== null && ctype_digit($v)) ? (int)$v : null;
    }

    /** File name (feature 04), or null. */
    public function getFilename(): ?string
    {
        return $this->getFeature('04');
    }

    /** Image format code (feature 01), e.g. D502 = JPEG. */
    public function getImageFormat(): ?string
    {
        return $this->getFeature('01');
    }

    /** The bigger of height and width, or null when the size is unknown. */
    public function getLongestSide(): ?int
    {
        $h = $this->getHeight();
        $w = $this->getWidth();
        if ($h === null && $w === null) {
            return null;
        }
        return max((int)$h, (int)$w);
    }

    public function addContentDate(?string $role, ?string $format, ?string $date): self
    {
        $this->contentDates[] = ['role' => $role, 'format' => $format, 'date' => $date];
        return $this;
    }

    /** @return array<int,array{role:?string,format:?string,date:?string}> */
    public function getContentDates(): array
    {
        return $this->contentDates;
    }

    /**
     * Date string of the first ContentDate with this role. Role 17 = last updated.
     */
    public function getContentDate(string $role): ?string
    {
        foreach ($this->contentDates as $d) {
            if ($d['role'] === $role && $d['date'] !== null && $d['date'] !== '') {
                return $d['date'];
            }
        }
        return null;
    }

    /** Date of last update (ContentDateRole 17), or null. */
    public function getLastUpdated(): ?string
    {
        return $this->getContentDate('17');
    }
}
