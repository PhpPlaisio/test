<?php
declare(strict_types=1);

namespace Plaisio\Test\Form\Control;

use SetBased\Helper\Cast;

/**
 *
 */
abstract class Control
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The attributes of the form control.
   *
   * @var array
   */
  protected array $attributes;

  //--------------------------------------------------------------------------------------------------------------------

  /**
   * Control constructor.
   *
   * @param array       $attributes The attributes of the form control.
   */
  public function __construct(array $attributes)
  {
    $this->attributes = $attributes;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the original value of this form control.
   *
   * @return string
   */
  abstract public function getOriginalValue(): string;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a random value suitable for this form control.
   *
   * @return string
   */
  abstract public function getRandomValue(): string;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Return the maximum length of the input of this form control.
   *
   * @param int $default The default max length.
   *
   * @return int
   */
  protected function extractMaxLength(int $default = 1050): int
  {
    if (!isset($this->attributes['maxlength'])) return $default;

    if (Cast::isOptInt($this->attributes['maxlength']))
    {
      $length = Cast::toManInt($this->attributes['maxlength'], $default);

      if ($length>=0) return $length;
    }

    return $default;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
