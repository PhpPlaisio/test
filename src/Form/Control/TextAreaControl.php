<?php
declare(strict_types=1);

namespace Plaisio\Test\Form\Control;

use Plaisio\Test\Random;

/**
 * Textarea form control.
 */
class TextAreaControl extends Control
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The content of the textarea.
   *
   * @var string
   */
  private $content;

  //--------------------------------------------------------------------------------------------------------------------

  /**
   * Control constructor.
   *
   * @param array  $attributes The attributes of the form control.
   * @param string $content    The content of the textarea.
   */
  public function __construct(array $attributes, string $content)
  {
    parent::__construct($attributes);

    $this->content = $content;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritDoc
   */
  public function getOriginalValue(): string
  {
    return $this->inner ?? '';
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritDoc
   */
  public function getRandomValue(): string
  {
    return Random::randomString($this->extractMaxLength(2400));
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
