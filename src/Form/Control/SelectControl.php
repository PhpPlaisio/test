<?php
declare(strict_types=1);

namespace Plaisio\Test\Form\Control;

use Plaisio\Test\Random;

/**
 * Select box.
 */
class SelectControl extends Control
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The options of the select box.
   *
   * @var array
   */
  private array $options;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Control constructor.
   *
   * @param array $attributes The attributes of the form control.
   * @param array $options    The options of the select box.
   */
  public function __construct(array $attributes, array $options)
  {
    parent::__construct($attributes);

    $this->options = $options;

    $handle =fopen('php://output', 'wb');
    foreach ($this->options as $option)
    {
      fwrite($handle, print_r($option, true));
    }
    fclose($handle);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritDoc
   */
  public function getOriginalValue(): string
  {
    foreach ($this->options as $option)
    {
      if (isset($option['selected']))
      {
        return $option['value'];
      }
    }

    if (!empty($this->options))
    {
      $option = $this->options[array_key_first($this->options)];

      return $option['value'];
    }

    return '';
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * @inheritDoc
   */
  public function getRandomValue(): string
  {
    if (!empty($this->options))
    {
      $key = Random::randomKey($this->options);

      return $this->options[$key]['value'];
    }

    return '';
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
