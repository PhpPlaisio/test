<?php
declare(strict_types=1);

namespace Plaisio\Test\Form;

use Plaisio\Test\Form\Control\CheckboxControl;
use Plaisio\Test\Form\Control\Control;
use Plaisio\Test\Form\Control\NumberControl;
use Plaisio\Test\Form\Control\RadioControl;
use Plaisio\Test\Form\Control\SelectControl;
use Plaisio\Test\Form\Control\SubmitControl;
use Plaisio\Test\Form\Control\TextAreaControl;
use Plaisio\Test\Form\Control\TextControl;
use Plaisio\Test\Random;
use SetBased\Exception\FallenException;

/**
 * Class for submitting forms.
 */
class Form
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The form attributes.
   *
   * @var array
   */
  private $attributes = [];

  /**
   * The form elements attributes.
   *
   * @var Control[]
   */
  private $controls = [];

  //--------------------------------------------------------------------------------------------------------------------

  /**
   * Object constructor.
   *
   * @param \DOMDocument $doc  The DOM document.
   * @param \DOMNode     $form The form.
   */
  public function __construct(\DOMDocument $doc, \DOMNode $form)
  {
    $this->extractForm($form);
    $this->extractElements($doc, $form);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the method (in upper case) for submitting the form.
   *
   * @return string
   */
  public function getMethod(): string
  {
    return strtoupper($this->attributes['method']);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the original values for submitting the form.
   */
  public function getOriginalValues(): array
  {
    $values  = [];
    $submits = [];
    foreach ($this->controls as $name => $control)
    {
      switch (get_class($control))
      {
        case RadioControl::class;
          /** @var RadioControl $control */
          if ($control->isChecked())
          {
            $values[$name] = $control->getOriginalValue();
          }
          break;

        case SubmitControl::class:
          $submits[$name][] = $control->getOriginalValue();
          break;

        default:
          $values[$name] = $control->getOriginalValue();
      }
    }

    $name          = Random::randomKey($submits);
    $values[$name] = Random::randomElement($submits[$name]);

    return $values;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns random values for submitting the form.
   */
  public function getRandomValues(): array
  {
    $values  = [];
    $radios  = [];
    $submits = [];
    foreach ($this->controls as $name => $control)
    {
      switch (get_class($control))
      {
        case RadioControl::class;
          /** @var RadioControl $control */
          $radios[$name][] = $control->getOriginalValue();
          break;

        case SubmitControl::class:
          $submits[$name][] = $control->getOriginalValue();
          break;

        default:
          $values[$name] = $control->getRandomValue();
      }
    }

    foreach ($radios as $name => $options)
    {
      $values[$name] = Random::randomElement($options);
    }

    $name          = Random::randomKey($submits);
    $values[$name] = Random::randomElement($submits[$name]);

    return $values;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the names of the submit controls of this form.
   *
   * @return string[]
   */
  public function getSubmitControls(): array
  {
    $controls = [];
    foreach ($this->controls as $control)
    {
      if (isset($control['type']) && $control['type']==='submit')
      {
        $controls[] = $control['name'];
      }
    }

    return $controls;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the to which the for mus be submitted.
   *
   * @return string
   */
  public function getUrl(): string
  {
    return $this->attributes['action'];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns whether this form can be submitted.
   *
   * @return bool
   */
  public function isSubmittable(): bool
  {
    if (!isset($this->attributes['action'])) return false;

    foreach ($this->controls as $control)
    {
      if (is_a($control, SubmitControl::class) && $control->getOriginalValue()!=='')
      {
        return true;
      }
    }

    return false;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Extract the form elements.
   *
   * @param \DOMDocument $doc  The DOM document.
   * @param \DOMNode     $form The form.
   */
  private function extractElements(\DOMDocument $doc, \DOMNode $form)
  {
    $xpath = new \DOMXpath($doc);
    $list  = $xpath->query('//input|//select|//textarea|//button', $form);
    foreach ($list as $item)
    {
      /** @var \DOMNode $item */
      $tag = $item->nodeName;

      $attributes = [];
      if ($item->hasAttributes())
      {
        foreach ($item->attributes as $attribute)
        {
          $attributes[$attribute->nodeName] = $attribute->nodeValue;
        }
      }

      switch ($tag)
      {
        case 'input':
          $type = $attributes['type'] ?? 'null';
          switch ($type)
          {
            case 'checkbox':
              $control = new CheckboxControl($attributes);
              break;

            case 'number':
              $control = new NumberControl($attributes);
              break;

            case 'radio':
              $control = new RadioControl($attributes);
              break;

            case 'submit':
              $control = new SubmitControl($attributes);
              break;

            case 'hidden':
            case 'password':
            case 'text':
              $control = new TextControl($attributes);
              break;

            case 'button':
            case 'file':
            case 'null':
              $control = null;
              break;

            default:
              throw new FallenException('type', $type);
          }
          break;

        case 'select':
          $options = $this->extractSelectOptions($item->childNodes);
          $control = new SelectControl($attributes, $options);
          break;

        case 'textarea';
          $control = new TextAreaControl($attributes, $item->textContent);
          break;

        default:
          throw new FallenException('tag', $tag);
      }

      if (isset($attributes['name']) && $control!==null)
      {
        $this->controls[$attributes['name']] = $control;
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Extract the form attributes.
   *
   * @param \DOMNode $form The form.
   */
  private function extractForm(\DOMNode $form)
  {
    if ($form->hasAttributes())
    {
      foreach ($form->attributes as $attribute)
      {
        $this->attributes[$attribute->nodeName] = $attribute->nodeValue;
      }
    }

    if (!isset($this->attributes['method']))
    {
      $this->attributes['method'] = 'post';
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Extracts the options of a select box.
   *
   * @param \DOMNodeList $childNodes The select options
   *
   * @return array
   */
  private function extractSelectOptions(\DOMNodeList $childNodes): array
  {
    $options = [];
    foreach ($childNodes as $node)
    {
      /** @var \DOMNode $node */
      if ($node->hasAttributes())
      {
        $option = [];
        foreach ($node->attributes as $attribute)
        {
          /** @var \DOMNode $attribute */
          $option[$attribute->nodeName] = $attribute->nodeValue;
        }

        if (isset($option['value']))
        {
          $options[] = $option;
        }
      }
    }

    return $options;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
