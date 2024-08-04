<?php

namespace LabelIT\Labels;

use LabelIT\Drawing\ImageDrawer;
use LabelIT\Items\Item;

class StandardLabelGenerator implements LabelGenerator
{
    function generate(ImageDrawer $drawer, Item $item): void
    {
        $bounds = $drawer->getBounds();

        $labelSpacing = 8;

        // Barcode C39
        $barcodeBounds = $bounds->bottomLeft(0, 0, $bounds->width, 20);
        $drawer->addBarcode("C39", $item->assetTag, $barcodeBounds);

        // QR Code
        $qrCodeSize = $bounds->height - $labelSpacing - $barcodeBounds->height;
        $qrCodeBounds = $bounds->topLeft(0, 0, $qrCodeSize, $qrCodeSize);
        $drawer->addBarcode("QRCODE,AN", $_ENV["APP_LOOKUP_ENDPOINT"] . $item->assetTag, $qrCodeBounds);

        // Text
        $textBounds = $bounds->topRight(
            0, 0,
            $bounds->width - $qrCodeBounds->width - $labelSpacing,
            $bounds->height - $barcodeBounds->height - $labelSpacing
        )->splitEvenly(1.0, 5.0);

        $titleBounds = $textBounds->splitEvenly(2, 1);
        $drawer->addText("# " . $item->assetTag, $titleBounds, bold: true, centerVertical: true);
        $drawer->addText($item->purchaseDate->format("d/m/Y"), $titleBounds->shift(1, 0), centerVertical: true);

        $textBounds = $textBounds->shift(0, 1);
        $drawer->addText($item->manufacturer, $textBounds, centerVertical: true);

        $textBounds = $textBounds->shift(0, 1);
        $drawer->addText($item->modelName, $textBounds, centerVertical: true);

        $textBounds = $textBounds->shift(0, 1);
        $drawer->addText("P/N " . $item->modelNumber, $textBounds, centerVertical: true);

        $textBounds = $textBounds->shift(0, 1);
        $drawer->addText("S/N " . $item->serialNumber, $textBounds, centerVertical: true);
    }
}