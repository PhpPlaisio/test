<?php
declare(strict_types=1);

namespace Plaisio\Test\Form\Control;

use Plaisio\Test\Random;

/**
 * Checkbox control.
 */
class CheckboxControl extends Control
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritDoc
   */
  public function getOriginalValue(): string
  {
    return isset($this->attributes['checked']) ? ($this->attributes['value'] ?? 'on') : '';
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritDoc
   */
  public function getRandomValue(): string
  {
    return (Random::randomBool()) ? ($this->attributes['value'] ?? 'on') : '';
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
