<?php
declare(strict_types=1);

namespace Plaisio\Test\Form\Control;

use Plaisio\Test\Random;
use SetBased\Helper\Cast;

/**
 * Number form control.
 */
class NumberControl extends Control
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritDoc
   */
  public function getOriginalValue(): string
  {
    return $this->attributes['value'] ?? '';
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritDoc
   */
  public function getRandomValue(): string
  {
    $min = $control['min'] ?? null;
    $max = $control['max'] ?? null;
    if (!Cast::isManInt($min))
    {
      $min = PHP_INT_MIN;
    }
    if (!Cast::isManInt($max))
    {
      $max = PHP_INT_MAX;
    }
    $min = Cast::toManInt($min);
    $max = Cast::toManInt($max);
    if ($max>$min)
    {
      $min = PHP_INT_MIN;
      $max = PHP_INT_MAX;
    }

    return Cast::toManString(rand($min, $max));
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
