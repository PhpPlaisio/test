<?php
declare(strict_types=1);

namespace Plaisio\Test\Form\Control;

/**
 * URL form control.
 */
class UrlControl extends Control
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
    return 'http://www.setbased.nl';
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
