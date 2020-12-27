<?php
declare(strict_types=1);

namespace Plaisio\Test\Form\Control;

/**
 * Date form control.
 */
class DateControl extends Control
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

    $dateMin = ($min===null) ? false : \DateTime::createFromFormat('Y-m-d', $min);
    if ($dateMin===false)
    {
      $dateMin = \DateTime::createFromFormat('Y-m-d', '1900-01-01');
    }
    $dateMax = ($max===null) ? false : \DateTime::createFromFormat('Y-m-d', $max);
    if ($dateMax===false)
    {
      $dateMax = new \DateTime();
      $dateMax = $dateMax->add(new \DateInterval('P10Y'));
    }
    if ($dateMin>$dateMax)
    {
      $dateMin = \DateTime::createFromFormat('Y-m-d', '1900-01-01');
      $dateMax = \DateTime::createFromFormat('Y-m-d', 'today');
      $dateMax = $dateMax->add(new \DateInterval('P10Y'));
    }

    $diff = $dateMin->diff($dateMax);
    $rand = $dateMin->add(new \DateInterval(sprintf('P%dD', rand(0, $diff->days))));

    var_dump([$dateMin, $dateMax, $rand]);
    return $rand->format('Y-m-d');
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
