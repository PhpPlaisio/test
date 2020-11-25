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
    foreach ($this->excludePatterns as $pattern)
    {
      if (preg_match($pattern, $path)===1)
      {
        return true;
      }
    }

    return false;
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
}

//----------------------------------------------------------------------------------------------------------------------
