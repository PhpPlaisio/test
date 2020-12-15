<?php
declare(strict_types=1);

namespace Plaisio\Test;

use Plaisio\Cookie\CookieJar;
use Plaisio\PlaisioKernel;

/**
 * Abstract helper class fro crawling a website.
 */
abstract class MonkeyTestHelper
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Pattern of paths that must not be followed.
   *
   * @var array
   */
  protected $excludePatterns = [];

  /**
   * Pattern of paths for which forms must not be submitted.
   *
   * @var array
   */
  private $excludeFormSubmitPatterns = [];

  /**
   * Pattern of paths for which forms must submit changes.
   *
   * @var array
   */
  private $excludeSubmitChangesPatterns = [];

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Whether a subject match a list of preg patterns.
   *
   * @param array  $patterns The list of patterns.
   * @param string $subject  The subject.
   *
   * @return bool
   */
  private static function matchPattern(array $patterns, string $subject): bool
  {
    foreach ($patterns as $pattern)
    {
      if (preg_match($pattern, $subject)===1)
      {
        return true;
      }
    }

    return false;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the initial URL to crawl.
   *
   * @return string
   */
  abstract public function getHomePage(): string;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns a new instance of the kernel.
   *
   * @return PlaisioKernel
   */
  abstract public function getKernel(): PlaisioKernel;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Whether to exclude an URL from submitting forms based on the path of the URL.
   *
   * @param string $path The path of the URL.
   *
   * @return bool
   */
  public function isFormSubmitExcludedByPath(string $path): bool
  {
    return self::matchPattern($this->excludeFormSubmitPatterns, $path);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Whether to exclude an URL from submitting forms which changes based on the path of the URL.
   *
   * @param string $path The path of the URL.
   *
   * @return bool
   */
  public function isSubmitChangesExcludedByPath(string $path): bool
  {
    return self::matchPattern($this->excludeSubmitChangesPatterns, $path);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Whether to follow an URL.
   *
   * @param string $url The fully qualified URL.
   *
   * @return bool
   */
  abstract public function mustFollowUrl(string $url): bool;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Prepares for crawling the website. E.g. logon to the website.
   *
   * @return CookieJar
   */
  abstract public function prepare(): CookieJar;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Whether to exclude an URL from crawling based on the host of the URL.
   *
   * @param string $host The host of the URL.
   *
   * @return bool
   */
  protected function isExcludedByHost(string $host): bool
  {
    $parts = parse_url($this->getHomePage());

    return ($parts['host']!==$host);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Whether to exclude an URL from crawling based on the path of the URL.
   *
   * @param string $path The path of the URL.
   *
   * @return bool
   */
  protected function isExcludedByPath(string $path): bool
  {
    return self::matchPattern($this->excludePatterns, $path);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Registers a path to be exclude from crawling.
   *
   * @param string $path    The absolute path.
   * @param bool   $leading Whether to exclude the path based on the leading part of full path.
   */
  protected function registerPathToExclude(string $path, bool $leading = false): void
  {
    if ($leading)
    {
      $pattern = sprintf('|^%s|', preg_quote($path, '|'));
    }
    else
    {
      $pattern = sprintf('|^%s$|', preg_quote($path, '|'));
    }

    $this->excludePatterns[] = $pattern;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Registers a path to be exclude from submitting forms.
   *
   * @param string $path    The absolute path.
   * @param bool   $leading Whether to exclude the path based on the leading part of full path.
   */
  protected function registerPathToExcludeFormSubmit(string $path, bool $leading = false): void
  {
    if ($leading)
    {
      $pattern = sprintf('|^%s|', preg_quote($path, '|'));
    }
    else
    {
      $pattern = sprintf('|^%s$|', preg_quote($path, '|'));
    }

    $this->excludeFormSubmitPatterns[] = $pattern;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Registers a path to be exclude from submitting forms
   *
   * @param string $path    The absolute path.
   * @param bool   $leading Whether to exclude the path based on the leading part of full path.
   */
  protected function registerPathToExcludeSubmitChanges(string $path, bool $leading = false): void
  {
    if ($leading)
    {
      $pattern = sprintf('|^%s|', preg_quote($path, '|'));
    }
    else
    {
      $pattern = sprintf('|^%s$|', preg_quote($path, '|'));
    }

    $this->excludeSubmitChangesPatterns[] = $pattern;
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
