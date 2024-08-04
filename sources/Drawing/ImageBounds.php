<?php

namespace LabelIT\Drawing;

class ImageBounds
{
    public readonly float $x;
    public readonly float $y;
    public readonly float $width;
    public readonly float $height;

    public function __construct(float $x, float $y, float $width, float $height)
    {
        $this->x = $x;
        $this->y = $y;
        $this->width = $width;
        $this->height = $height;
    }

    public function shift(float $x, float $y): ImageBounds
    {
        return $this->topLeft(
            $this->width * $x,
            $this->height * $y,
            $this->width, $this->height
        );
    }

    public function pad(float $padding): ImageBounds
    {
        if ($padding === 0.0) {
            return $this;
        }

        return new ImageBounds(
            $this->x + $padding,
            $this->y + $padding,
            $this->width - $padding - $padding,
            $this->height - $padding - $padding
        );
    }

    public function splitEvenly(float $columns, float $rows): ImageBounds
    {
        return $this->topLeft(0, 0, $this->width / $columns, $this->height / $rows);
    }

    function topLeft(float $x, float $y, float $width, float $height): ImageBounds
    {
        return new ImageBounds(
            $this->x + $x,
            $this->y + $y,
            $width,
            $height
        );
    }

    function bottomLeft(float $x, float $y, float $width, float $height): ImageBounds
    {
        return $this->topLeft(
            $x,
            $this->height - $height - $y,
            $width,
            $height
        );
    }

    function bottomRight(float $x, float $y, float $width, float $height): ImageBounds
    {
        return $this->topLeft(
            $this->width - $width - $x,
            $this->height - $height - $y,
            $width,
            $height
        );
    }

    function topRight(float $x, float $y, float $width, float $height): ImageBounds
    {
        return $this->topLeft(
            $this->width - $width - $x,
            $y,
            $width,
            $height
        );
    }
}