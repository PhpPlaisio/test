<?php
declare(strict_types=1);

namespace Plaisio\Test\Form\Control;

use Plaisio\Test\Random;

/**
 * Text form control.
 */
class TextControl extends Control
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
    if (str_contains($control['class'] ?? '', 'date') || str_contains($control['name'] ?? '', 'date'))
    {
      return Random::randomDate();
    }

    return Random::randomString($this->extractMaxLength());
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
