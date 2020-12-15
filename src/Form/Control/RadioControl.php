<?php
declare(strict_types=1);

namespace Plaisio\Test\Form\Control;

/**
 * Radio control.
 */
class RadioControl extends Control
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
    return $this->attributes['value'] ?? '';
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns whether is radio control is checked.
   *
   * @return bool
   */
  public function isChecked(): bool
  {
    return isset($control['checked']);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
