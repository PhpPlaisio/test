<?php
declare(strict_types=1);

namespace Plaisio\Test;

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
  private $content;

  /**
   * The error handler.
   *
   * @var ErrorHandler|null
   */
  private $errorHandler;

  /**
   * The kernel.
   *
   * @var PlaisioKernel|null
   */
  private $kernel;

  /**
   * The class of the kernel.
   *
   * @var string
   */
  private $kernelClass;

  /**
   * The output buffer for logging the output of the kernel.
   *
   * @var OB
   */
  private $ob;

  /**
   * The response object of the kernel.
   *
   * @var Response|null
   */
  private $response;

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Object constructor.
   *
   * @param string $kernelClass The class of the kernel.
   */
  public function __construct(string $kernelClass)
  {
    $this->kernelClass = $kernelClass;
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Requests an URL to the kernel.
   *
   * @param string $url     The URL to request.
   * @param string $method  The method.
   * @param array  $server  The server variable to use.
   * @param array  $cookies The cookie to send to the kernel.
   * @param array  $post    The POST variables.
   *
   * @return array
   */
  public function request(string $url,
                          string $method,
                          array $server = [],
                          array $cookies = [],
                          array $post = []): array
  {
    $preserve = [$_SERVER, $_GET, $_COOKIE, $_POST];

    $_SERVER = $server;
    $_COOKIE = $cookies;
    $_POST   = $post;
    $_GET    = [];

    $this->initGlobals($url, $preserve[0]['PLAISIO_ENV'], $method);

    $this->setUp();
    try
    {
      BaseResponse::$skipClearOutput = true;

      $this->errorHandler = new ErrorHandler();
      $this->errorHandler->registerErrorHandler();

      /** @var PlaisioKernel $kernel */
      $this->kernel   = new $this->kernelClass();
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

    foreach ($this->kernel->cookie as $cookie)
    {
      if ($cookie->expires===null)
      {
        $this->kernel->cookie->remove($cookie->name, false);
      }
    }

    [$_SERVER, $_GET, $_COOKIE, $_POST] = $preserve;

    return ['status'  => $this->response->getStatus(),
            'headers' => $this->response->getHeaders(),
            'content' => $this->content ?? $this->response->getContent(),
            'cookies' => $this->kernel->cookie,
            'output'  => $this->ob->getClean()];
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
   * @param \Exception $throwable
   */
  private function internalError(\Exception $throwable): void
  {
    $ob     = new OB();
    $logger = new DevelopmentErrorLogger();
    $logger->dumpVars(['GLOBALS' => $GLOBALS, 'nub' => $this->kernel]);
    $logger->logError($throwable);

    $this->content  = $ob->getClean();
    $this->response = new InternalServerErrorResponse();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Setups the kernel wrapper.
   */
  private function setUp(): void
  {
    $this->ob           = new OB();
    $this->kernel       = null;
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
    if ($this->errorHandler!==null)
    {
      $this->errorHandler->unregisterErrorHandler();
    }

    // Ensure the transaction is rolled back.
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
