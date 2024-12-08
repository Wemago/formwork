<?php

namespace Formwork\Images\Transform;

use Formwork\Images\ImageInfo;
use Formwork\Utils\Constraint;
use GdImage;
use InvalidArgumentException;

class Colorize extends AbstractTransform
{
    final public function __construct(
        protected int $red,
        protected int $green,
        protected int $blue,
        protected int $alpha,
    ) {
        if (!Constraint::isInIntegerRange($red, 0, 255)) {
            throw new InvalidArgumentException(sprintf('$red value must be in range 0-255, %d given', $red));
        }

        if (!Constraint::isInIntegerRange($green, 0, 255)) {
            throw new InvalidArgumentException(sprintf('$green value must be in range 0-255, %d given', $green));
        }

        if (!Constraint::isInIntegerRange($blue, 0, 255)) {
            throw new InvalidArgumentException(sprintf('$blue value must be in range 0-255, %d given', $blue));
        }

        if (!Constraint::isInIntegerRange($alpha, 0, 127)) {
            throw new InvalidArgumentException(sprintf('$alpha value must be in range 0-127, %d given', $alpha));
        }
    }

    public static function fromArray(array $data): static
    {
        return new static($data['red'], $data['green'], $data['blue'], $data['alpha']);
    }

    public function apply(GdImage $gdImage, ImageInfo $imageInfo): GdImage
    {
        imagefilter($gdImage, IMG_FILTER_COLORIZE, $this->red, $this->green, $this->blue, $this->alpha);
        return $gdImage;
    }
}
