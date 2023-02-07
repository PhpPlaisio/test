<?php
declare(strict_types=1);

namespace Plaisio\Test;

use Plaisio\Cookie\CookieJar;
use Plaisio\ErrorLogger\DevelopmentErrorLogger;
use Plaisio\Helper\OB;
use Plaisio\PlaisioKernel;
use Plaisio\Response\BaseResponse;
use Plaisio\Response\InternalServerErrorResponse;
use Plaisio\Response\Response;
use SetBased\ErrorHandler\ErrorHandler;

/**
 * A wrapper around the kernel for requesting directly the response to a request to the kernel.
 */
class KernelWrapper
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * The content of the HTTP response.
   *
   * @var string|null
   */
  private ?string $content = null;

  /**
   * The error handler.
   *
   * @var ErrorHandler|null
   */
  private ?ErrorHandler $errorHandler = null;

  /**
   * The kernel.
   *
   * @var PlaisioKernel
   */
  private PlaisioKernel $kernel;

  /**
   * The output buffer for logging the output of the kernel.
   *
   * @var OB
   */
  private OB $ob;

  /**
   * The response object of the kernel.
   *
   * @var Response|null
   */
  private ?Response $response = null;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param PlaisioKernel $kernel The kernel.
   */
  public function __construct(PlaisioKernel $kernel)
  {
    $this->kernel = $kernel;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the content of the response the to request.
   *
   * @return string
   */
  public function getContent(): string
  {
    return $this->content ?? $this->response->getContent();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the cookies of the response the to request.
   *
   * @return CookieJar
   */
  public function getCookies(): CookieJar
  {
    return $this->kernel->cookie;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the headers of the response the to request as key value pairs.
   *
   * @return array
   */
  public function getHeaders(): array
  {
    return $this->response->getHeaders();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the output of the kernel.
   *
   * @return string
   */
  public function getOutput(): string
  {
    return $this->ob->getContents();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Returns the status of the response the to request.
   *
   * @return int
   */
  public function getStatus(): int
  {
    return $this->response->getStatus();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Requests a URL to the kernel.
   *
   * @param string $url     The URL to request.
   * @param string $method  The method.
   * @param array  $server  The server variable to use.
   * @param array  $cookies The cookie to send to the kernel.
   * @param array  $post    The POST variables.
   */
  public function request(string $url, string $method, array $server = [], array $cookies = [], array $post = []): void
  {
    $preserve = [$_SERVER, $_GET, $_COOKIE, $_POST];

    $_SERVER = $server;
    $_COOKIE = $cookies;
    $_POST   = $post;
    $_GET    = [];

    $this->initGlobals($url, $method, $preserve[0]['PLAISIO_ENV']);

    $this->setUp();
    try
    {
      BaseResponse::$skipClearOutput = true;

      $this->errorHandler = new ErrorHandler();
      $this->errorHandler->registerErrorHandler();

      $this->response = $this->kernel->requestHandler->handleRequest();
    }
    catch (\Throwable $throwable)
    {
      $this->internalError($throwable);
    }
    finally
    {
      $this->tearDown();
    }

    if ($this->response->getContent()==='')
    {
      $this->content = $this->ob->getClean();
    }

    [$_SERVER, $_GET, $_COOKIE, $_POST] = $preserve;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Initializes the global variables $_SERVER, $_GET, and $_POST.
   *
   * @param string $url        The requested URL.
   * @param string $method     The method for requesting the URL.
   * @param string $plaisioEnv The environment (dev, prod, test) in which the kernel is operating.
   */
  private function initGlobals(string $url, string $method, string $plaisioEnv): void
  {
    $parts = parse_url($url);

    $_SERVER['PLAISIO_ENV']        = $plaisioEnv;
    $_SERVER['REQUEST_METHOD']     = strtoupper($method);
    $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
    $_SERVER['REQUEST_URI']        = sprintf('%s%s%s',
                                             $parts['path'] ?? '/',
                                             (isset($parts['query']) ? '?' : '').($parts['query'] ?? ''),
                                             (isset($parts['fragment']) ? '#' : '').($parts['fragment'] ?? ''));
    $_SERVER['HTTP_USER_AGENT']    = 'Mozilla/5.0 Plaisio/Crawler';
    $_SERVER['HTTPS']              = (($parts['scheme'] ?? '')==='https') ? '1' : null;
    $_SERVER['HTTP_HOST']          = $parts['host'] ?? null;

    $vars = explode(';', $parts['query'] ?? '');
    foreach ($vars as $pair)
    {
      $sides           = explode('=', $pair);
      $_GET[$sides[0]] = urldecode($sides[1] ?? '');
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Handles an internal error of the kernel.
   *
   * @param \Throwable $throwable
   */
  private function internalError(\Throwable $throwable): void
  {
    $logger = new DevelopmentErrorLogger();
    $logger->dumpVars(['GLOBALS' => $GLOBALS, 'nub' => $this->kernel]);
    $logger->logError($throwable);

    $this->response = new InternalServerErrorResponse();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setups the kernel wrapper.
   */
  private function setUp(): void
  {
    $this->ob           = new OB();
    $this->errorHandler = null;
    $this->content      = null;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Tears down the kernel wrapper.
   */
  private function tearDown(): void
  {
    // Restore the initial error handler.
    $this->errorHandler?->unregisterErrorHandler();

    // Ensure the transaction is rolled back and the connection has been disconnected.
    try
    {
      $this->kernel->DL->rollback();
      $this->kernel->DL->disconnect();
    }
    catch (\Throwable $exception)
    {
      // Nothing to do.
    }
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
