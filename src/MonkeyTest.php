<?php
declare(strict_types=1);

namespace Plaisio\Test;

use Plaisio\Cookie\Cookie;
use Plaisio\Cookie\CookieJar;
use Plaisio\Helper\Url;
use Plaisio\Test\Form\Form;
use SetBased\Helper\Cast;

/**
 * Test cases for CoreRequestParameterResolverTest.
 *
 * Note: A base URL is the part of an URL without (clean) parameters.
 */
class MonkeyTest
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The map from base URLs to bul_id.
   *
   * @var array<string,int>
   */
  private $baseUrls = [];

  /**
   * The cookie jar.
   *
   * @var CookieJar
   */
  private $cookies;

  /**
   * The helper object for monkey testing.
   *
   * @var MonkeyTestHelper
   */
  private $helper;

  /**
   * The data store.
   *
   * @var TestStore
   */
  private $store;

  //--------------------------------------------------------------------------------------------------------------------

  /**
   * MonkeyTest constructor.
   *
   * @param MonkeyTestHelper $helper The helper object for monkey testing.
   */
  public function __construct(MonkeyTestHelper $helper)
  {
    $this->helper = $helper;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the status count of the crawled pages.
   *
   * @return array[]
   */
  public function getStatusCount(): array
  {
    return $this->store->tstMonkeyUrlStatusCount();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Does monkey testing.
   */
  public function monkeyTest(): void
  {
    $this->cookies = new CookieJar();

    if (is_file('crawler.db'))
    {
      unlink('crawler.db');
    }
    $this->store = new TestStore('crawler.db', __DIR__.'/../lib/ddl/create_tables.sql');

    $this->cookies = $this->helper->prepare();
    $this->crawl();

    fwrite(STDERR, PHP_EOL);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Crawls the website for monkey testing.
   */
  private function crawl()
  {
    $url   = $this->helper->getHomePage();
    $bulId = $this->getBulId($url);
    $this->store->tstMonkeyUrlInsert($url, $bulId, null);

    $urls = $this->store->tstMonkeyUrlCrawl();
    while (!empty($urls))
    {
      foreach ($urls as $url)
      {
        $this->getPageUrl($url);
      }

      $urls = $this->store->tstMonkeyUrlCrawl();
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Whether a document has forms.
   *
   * @param \DOMDocument $doc The HTML document.
   *
   * @return \DOMNodeList
   */
  private function extractForms(\DOMDocument $doc): \DOMNodeList
  {
    $xpath = new \DOMXpath($doc);

    return $xpath->query('//form');
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Extracts the links from a HTML document.
   *
   * @param \DOMDocument $doc The HTMl document.
   * @param array        $url The details (see TST_MONKEY_URL) of the URL of the HTML document.
   */
  private function extractLinks(\DOMDocument $doc, array $url): void
  {
    $xpath = new \DOMXpath($doc);
    $links = $xpath->query('//a');

    foreach ($links as $link)
    {
      $href = $link->getAttribute('href');
      if ($href!==null && trim($href)!=='' && !str_starts_with($href, 'mailto:'))
      {
        $link = Url::combine($url['url_url'], $href);
        if ($this->helper->mustFollowUrl($link))
        {
          $bulId = $this->getBulId($link);
          if (!$this->store->tstMonkeyUrlExists($link))
          {
            $this->store->tstMonkeyUrlInsert($link, $bulId, $url['url_id']);
          }
        }
      }
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Extracts the title from a HTML document.
   *
   * @param \DOMDocument $doc The HTML document.
   *
   * @return string|null
   */
  private function extractTitle(\DOMDocument $doc): ?string
  {
    $xpath  = new \DOMXpath($doc);
    $titles = $xpath->query('/html/head/title');
    if ($titles->length>=1)
    {
      $title = $titles[0]->textContent;
    }
    else
    {
      $title = null;
    }

    return $title;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the ID of the base URL of an URL.
   *
   * @param string $url The URL.
   *
   * @return int
   */
  private function getBulId(string $url): int
  {
    $parts = parse_url($url);
    $path  = $parts['path'] ?? '/';
    $base  = '';

    if (preg_match('|^(?<base_url>/pag/[^/?]+)|', $path, $matches)===1)
    {
      $base = $matches['base_url'];
    }
    elseif (preg_match('|^(?<base_url>[^/?]+)|', $path, $matches)===1)
    {
      $base = $matches['base_url'];
    }
    elseif ($path==='/')
    {
      $base = $path;
    }

    $parts   = ['scheme' => $parts['scheme'],
                'host'   => $parts['host'],
                'path'   => $base];
    $baseUrl = Url::unParseUrl($parts);
    if (!isset($this->baseUrls[$baseUrl]))
    {
      $this->baseUrls[$baseUrl] = $this->store->tstMonkeyBaseUrlInsert($baseUrl);
    }

    return $this->baseUrls[$baseUrl];
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Request an URL with GET method.
   *
   * @param array $url The details of the URL.
   */
  private function getPageUrl(array $url)
  {
    $wrapper = $this->request($url['url_url'], 'GET');

    $location = ($wrapper->getHeaders())['location'] ?? '';
    $content  = $wrapper->getContent();

    if (substr($content ?? '', 0, 1)==='<')
    {
      $doc = new \DOMDocument();
      @$doc->loadHTML($content, LIBXML_NOWARNING | LIBXML_NOERROR);
      $this->extractLinks($doc, $url);
      $title    = $this->extractTitle($doc);
      $forms    = $this->extractForms($doc);
      $hasForms = (count($forms)>=1);
    }
    else
    {
      $doc      = null;
      $forms    = null;
      $title    = null;
      $hasForms = false;
    }

    $this->requestLog3($title ?? $location ?? '');

    $this->store->tstMonkeyUrlUpdate($url['url_id'],
                                     $url['bul_id'],
                                     'GET',
                                     $wrapper->getStatus(),
                                     $location,
                                     $title,
                                     Cast::toOptInt($hasForms),
                                     null,
                                     $wrapper->getContent());

    if ($hasForms)
    {
      $this->testForms($url, $doc, $forms);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns an URL with fragment (if any) removed.
   *
   * @param string $url The absolute URL of the page.
   *
   * @return string
   */
  private function removeFragment(string $url): string
  {
    $parts = parse_url($url);
    unset($parts['fragment']);

    return Url::unParseUrl($parts);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Requests an URL.
   *
   * @param string $url    The URL to request.
   * @param string $method The method to use.
   * @param array  $post   The $_POST global variable.
   *
   * @return KernelWrapper
   */
  private function request(string $url, string $method, array $post = []): KernelWrapper
  {
    $url = $this->removeFragment($url);

    $this->requestLog1($url, $method);

    $wrapper = new KernelWrapper($this->helper->getKernel());

    $cookies = [];
    foreach ($this->cookies as $cookie)
    {
      /** @var Cookie $cookie */
      $cookies[$cookie->name] = $cookie->value;
    }

    $wrapper->request($url, $method, [], $cookies, $post);

    $this->requestLog2($wrapper->getStatus());

    return $wrapper;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Logs a request to STDERR - part 1.
   *
   * @param string $url    The URL requested.
   * @param string $method The method of the request.
   */
  private function requestLog1(string $url, string $method): void
  {
    fwrite(STDERR, sprintf("\n %-60s %-4s", mb_substr($url, 0, 60), substr($method, 0, 4)));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Logs a request to STDERR - part 2.
   *
   * @param int $status The HTTP status code of the response to the request.
   */
  private function requestLog2(int $status): void
  {
    fwrite(STDERR, sprintf(' %3d', $status));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Logs a request to STDERR - part 3.
   *
   * @param string $title The title of the HTML page.
   */
  private function requestLog3(string $title): void
  {
    fwrite(STDERR, sprintf(' %-40s', mb_substr($title, 0, 40)));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test a form on a page.
   *
   * @param array        $url  The URL of the page.
   * @param \DOMDocument $doc  The DOM document.
   * @param \DOMNode     $node The form.
   */
  private function testForm(array $url, \DOMDocument $doc, \DOMNode $node): void
  {
    $form  = new Form($doc, $node);
    $parts = parse_url($url['url_url']);
    if ($form->isSubmittable() && !$this->helper->isFormSubmitExcludedByPath($parts['path'] ?? '/'))
    {
      if ($this->helper->isSubmitChangesExcludedByPath($parts['path'] ?? '/'))
      {
        $values = $form->getOriginalValues();
      }
      else
      {
        $values = $form->getRandomValues();
      }

      if (isset($this->cookies['ses_csrf_token']))
      {
        $values['ses_csrf_token'] = $this->cookies['ses_csrf_token']->value;
      }

      $link     = Url::combine($url['url_url'], $form->getUrl());
      $bulId    = $this->getBulId($link);
      $targetId = $this->store->tstMonkeyUrlInsert($link, $bulId, $url['url_id']);

      $wrapper = $this->request($link, $form->getMethod(), $values);

      $location = ($wrapper->getHeaders())['location'] ?? '';
      $content  = $wrapper->getContent();

      if (substr($content ?? '', 0, 1)==='<')
      {
        $doc = new \DOMDocument();
        @$doc->loadHTML($content, LIBXML_NOWARNING | LIBXML_NOERROR);
        $this->extractLinks($doc, $url);
        $title = $this->extractTitle($doc);
      }
      else
      {
        $doc   = null;
        $forms = null;
        $title = null;
      }

      $this->requestLog3($title ?? $location ?? '');

      $this->store->tstMonkeyUrlUpdate($targetId,
                                       null,
                                       $form->getMethod(),
                                       $wrapper->getStatus(),
                                       $location,
                                       $title,
                                       null,
                                       serialize($values),
                                       $content);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Test the forms on a page.
   *
   * @param array        $url   The URL of the page.
   * @param \DOMDocument $doc   The DOM document.
   * @param \DOMNodeList $forms The list with forms.
   */
  private function testForms(array $url, \DOMDocument $doc, \DOMNodeList $forms): void
  {
    foreach ($forms as $form)
    {
      $this->testForm($url, $doc, $form);
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
