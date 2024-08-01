<?php

namespace LabelIT;

use Com\Tecnick\Barcode\Barcode;
use Com\Tecnick\Barcode\Exception;
use GdImage;

class LabelGenerator
{
    public const DEFAULT_WIDTH = 382;
    public const DEFAULT_HEIGHT = 182;
    private const FONT_SIZE = 18;
    private const FONT_FILE = "LiberationSans-Regular";
    private const FONT_FILE_BOLD = "LiberationSans-Bold";

    private readonly Barcode $barcode;
    private readonly GdImage $image;
    private readonly LabelBounds $bounds;

    private readonly int $background;
    private readonly int $color;

    public function __construct(float $width = self::DEFAULT_WIDTH, float $height = self::DEFAULT_HEIGHT)
    {
        $this->barcode = new Barcode();
        $this->image = imagecreate($width, $height);
        $this->bounds = new LabelBounds(0, 0, $width, $height);

        // allocate black & white colors
        $this->background = imagecolorallocate($this->image, 0xFF, 0xFF, 0xFF);
        $this->color = imagecolorallocate($this->image, 0x00, 0x00, 0x00);
    }

    public function __destruct()
    {
        imagedestroy($this->image);
    }

    public function addText(string $text, LabelBounds $bounds, bool $bold = false, bool $centerHorizontal = false, bool $centerVertical = false): bool
    {
        $font = $bold ? self::FONT_FILE_BOLD : self::FONT_FILE;

        // origin is lower-left
        $textBounds = imageftbbox(1 * self::FONT_SIZE, 0, $font, $text);
        $textWidth = $textBounds[4];
        $textHeight = -$textBounds[5];
        if ($textWidth > $bounds->width || $textHeight > $bounds->height) {
            $textGenerator = new LabelGenerator($textWidth, $textHeight);
            $textResult = $textGenerator->addText($text, $textGenerator->bounds, $bold);
            if ($textResult === false) {
                return false;
            }

            $textX = $bounds->x;
            if ($centerHorizontal) {
                $textX += ($bounds->width - $textWidth) / 2.0;
            }

            $textY = $bounds->y;
            if ($centerVertical) {
                $textY += ($bounds->height - $textHeight) / 2.0;
            }
            
            return imagecopyresized(
                $this->image,
                $textGenerator->image,
                $textX,
                $textY,
                0, 0,
                min($textWidth, $bounds->width),
                min($textHeight, $bounds->height),
                $textWidth, $textHeight
            );
        }

//        $size = self::FONT_SIZE * min(
//                1.0,
//                $bounds->width / $textBounds[4],
//                $bounds->height / -$textBounds[5]
//            );

        $textX = $bounds->x;
        if ($centerHorizontal) {
            $textX += ($bounds->width - $textWidth) / 2.0;
        }

        $textY = $bounds->y + $textHeight;
        if ($centerVertical) {
            $textY += ($bounds->height - $textHeight) / 2.0;
        }

        $textResult = imagefttext(
            $this->image,
            self::FONT_SIZE, 0.0,
            $textX, $textY,
            $this->color, $font, $text
        );
        return $textResult !== false;
    }

    public function addBarcode(string $type, string $content, LabelBounds $bounds): bool
    {
        try {
            $barcodeImage = $this->barcode->getBarcodeObj($type, $content, $bounds->width, $bounds->height)->getGd();
            if (!$barcodeImage instanceof GdImage) {
                $this->addText($content, $bounds, false);
                return false;
            }

            imagecopy($this->image, $barcodeImage, $bounds->x, $bounds->y, 0, 0, $bounds->width, $bounds->height);
            imagedestroy($barcodeImage);
            return true;
        } catch (Exception) {
            $this->addText($content, $bounds, false);
            return false;
        }
    }

    public function borderColor(LabelBounds $bounds, float $border = 2): void
    {
        imagefilledrectangle(
            $this->image,
            $bounds->x,
            $bounds->y,
            $bounds->x + $bounds->width,
            $bounds->y + $border,
            $this->color
        );
        imagefilledrectangle(
            $this->image,
            $bounds->x,
            $bounds->y + $bounds->height - $border,
            $bounds->x + $bounds->width,
            $bounds->y + $bounds->height,
            $this->color
        );

        imagefilledrectangle(
            $this->image,
            $bounds->x,
            $bounds->y,
            $bounds->x + $border,
            $bounds->y + $bounds->height,
            $this->color
        );
        imagefilledrectangle(
            $this->image,
            $bounds->x + $bounds->width,
            $bounds->y,
            $bounds->x + $bounds->width + $border,
            $bounds->y + $bounds->height,
            $this->color
        );
    }

    public function getImage(): GdImage
    {
        return $this->image;
    }

    public function getBounds(): LabelBounds
    {
        return $this->bounds;
    }

}