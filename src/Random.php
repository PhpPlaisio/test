<?php
declare(strict_types=1);

namespace Plaisio\Test;

/**
 * Utility class for random values.
 */
class Random
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a random boolean.
   *
   * @return bool
   */
  public static function randomBool(): bool
  {
    return (rand(0, 1)===1);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a random date.
   */
  public static function randomDate(): string
  {
    $days = sprintf('P%dD', rand(0, 10 * 365));
    $date = new \DateTime();
    if (rand(0,1)===1)
    {
      $date->add(new \DateInterval($days));
    }
    else
    {
      $date->sub(new \DateInterval($days));
    }

    return $date->format('Y-m-d');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a random element from an array.
   *
   * @param array $array The values.
   *
   * @return mixed
   */
  public static function randomElement(array $array): mixed
  {
    return $array[rand(0, count($array) - 1)];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a random key from an array.
   *
   * @param array $array The values.
   *
   * @return int|string
   */
  public static function randomKey(array $array): int|string
  {
    $keys = array_keys($array);

    return $keys[rand(0, count($keys) - 1)];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a random string.
   *
   * @param int $length The length of the string.
   *
   * @return string
   */
  public static function randomString(int $length): string
  {
    $characters = " 01234abcdefghijklmNOPQRSTUVWXYZ'\"\n\r\t";

    $length = rand(0, $length);
    $value  = '';
    for ($i = 0; $i<$length; $i++)
    {
      $value .= $characters[rand(0, mb_strlen($characters) - 1)];
    }

    return $value;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
