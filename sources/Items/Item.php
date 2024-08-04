<?php

namespace LabelIT\Items;

use DateTimeInterface;

class Item
{
    public readonly string $assetTag;
    public readonly string $modelName;
    public readonly string $modelNumber;
    public readonly string $manufacturer;
    public readonly string $serialNumber;
    public readonly DateTimeInterface $purchaseDate;

    public function __construct(string $assetTag, string $modelName, string $modelNumber, string $manufacturer, string $serialNumber, DateTimeInterface $purchaseDate)
    {
        $this->assetTag = $assetTag;
        $this->modelName = $modelName;
        $this->modelNumber = $modelNumber;
        $this->manufacturer = $manufacturer;
        $this->serialNumber = $serialNumber;
        $this->purchaseDate = $purchaseDate;
    }

}