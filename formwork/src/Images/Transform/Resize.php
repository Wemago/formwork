<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use GdImage;
use InvalidArgumentException;
use RuntimeException;
use UnexpectedValueException;

class Resize extends AbstractTransform
{
    final public function __construct(
        protected int $width,
        protected int $height,
        protected ResizeMode $resizeMode = ResizeMode::Cover,
    ) {
        if ($width <= 0) {
            throw new InvalidArgumentException('$width must be greater than 0');
        }

        if ($height <= 0) {
            throw new InvalidArgumentException('$height must be greater than 0');
        }
    }

    public static function fromArray(array $data): static
    {
        return new static($data['width'], $data['height'], $data['mode']);
    }

    public function apply(GdImage $gdImage, ImageInfo $imageInfo): GdImage
    {
        $sourceWidth = imagesx($gdImage);
        $sourceHeight = imagesy($gdImage);

        $cropAreaWidth = $sourceWidth;
        $cropAreaHeight = $sourceHeight;

        $cropOriginX = 0;
        $cropOriginY = 0;

        $destinationX = 0;
        $destinationY = 0;

        $sourceRatio = $sourceWidth / $sourceHeight;
        $destinationRatio = $this->width / $this->height;

        $destinationWidth = $this->width;
        $destinationHeight = $this->height;

        $width = $this->width;
        $height = $this->height;

        switch ($this->resizeMode) {
            case ResizeMode::Fill:
                $cropAreaWidth = $sourceWidth;
                $cropAreaHeight = $sourceHeight;
                break;

            case ResizeMode::Cover:
                if ($sourceRatio > $destinationRatio) {
                    $cropAreaWidth = $sourceHeight * $destinationRatio;
                    $cropOriginX = ($sourceWidth - $cropAreaWidth) / 2;
                } else {
                    $cropAreaHeight = $sourceWidth / $destinationRatio;
                    $cropOriginY = ($sourceHeight - $cropAreaHeight) / 2;
                }
                break;

            case ResizeMode::Contain:
                if ($sourceRatio < $destinationRatio) {
                    $destinationWidth = $this->height * $sourceRatio;
                    $width = (int) $destinationWidth;
                } else {
                    $destinationHeight = $this->width / $sourceRatio;
                    $height = (int) $destinationHeight;
                }
                break;

            case ResizeMode::Center:
                if ($sourceRatio < $destinationRatio) {
                    $destinationWidth = $this->height * $sourceRatio;
                    $destinationX = ($this->width - $destinationWidth) / 2;
                } else {
                    $destinationHeight = $this->width / $sourceRatio;
                    $destinationY = ($this->height - $destinationHeight) / 2;
                }
                break;
        }

        if ($width <= 0) {
            throw new UnexpectedValueException('Unexpected non-positive calculated width');
        }

        if ($height <= 0) {
            throw new UnexpectedValueException('Unexpected non-positive calculated height');
        }

        $destinationImage = imagecreatetruecolor($width, $height);

        if ($destinationImage === false) {
            throw new RuntimeException('Cannot create destination image');
        }

        if ($imageInfo->hasAlphaChannel()) {
            $this->enableTransparency($destinationImage);
        }

        imagecopyresampled(
            $destinationImage,
            $gdImage,
            (int) $destinationX,
            (int) $destinationY,
            (int) $cropOriginX,
            (int) $cropOriginY,
            (int) $destinationWidth,
            (int) $destinationHeight,
            (int) $cropAreaWidth,
            (int) $cropAreaHeight
        ) ?: throw new RuntimeException('Cannot resize image');

        return $destinationImage;
    }

    protected function enableTransparency(GdImage $gdImage): void
    {
        if (($transparent = imagecolorallocatealpha($gdImage, 0, 0, 0, 127)) === false) {
            throw new RuntimeException('Cannot allocate transparent color');
        }
        imagealphablending($gdImage, true);
        imagesavealpha($gdImage, true);
        imagecolortransparent($gdImage, $transparent);
        imagefill($gdImage, 0, 0, $transparent);
    }
}
