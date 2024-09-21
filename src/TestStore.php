<?php
declare(strict_types=1);

namespace Plaisio\Test;

use SetBased\Stratum\SqlitePdo\SqlitePdoDataLayer;

/**
 * The data layer.
 */
class TestStore extends SqlitePdoDataLayer
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Insert a base URL.
   *
   * @param string|null $pBulUrl The base URL to be inserted.
   *                             text
   *
   * @return int
   */
  public function tstMonkeyBaseUrlInsert(?string $pBulUrl): int
  {
    $replace = [':p_bul_url' => $this->quoteText($pBulUrl)];
    $query   = <<< EOT
insert into TST_MONKEY_BASE_URL(bul_url, bul_pages, bul_crawled)
values (:p_bul_url, 0, 0);
EOT;
    $query = str_repeat(PHP_EOL, 7).$query;

    $this->executeNone($query, $replace);
    return $this->lastInsertId();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects URLs to be crawled.
   *
   * @return array[]
   */
  public function tstMonkeyUrlCrawl(): array
  {
    $query = <<< EOT
select t2.url_id
     , t2.bul_id
     , t2.url_url
     , t2.url_id_referrer
from TST_MONKEY_BASE_URL t1
join TST_MONKEY_URL      t2 on t2.bul_id = t1.bul_id
where t1.bul_crawled < t1.bul_pages
  and t1.bul_crawled < 10
  and t2.url_status is null
order by t1.bul_crawled
       , random()
limit 10;
EOT;
    $query = str_repeat(PHP_EOL, 5).$query;

    return $this->executeRows($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects whether a URL exists.
   *
   * @param string|null $pUrlUrl The URL.
   *                             text
   *
   * @return bool
   */
  public function tstMonkeyUrlExists(?string $pUrlUrl): bool
  {
    $replace = [':p_url_url' => $this->quoteText($pUrlUrl)];
    $query   = <<< EOT
select 1
from TST_MONKEY_URL
where url_url = :p_url_url
  and ifnull(url_method, 'GET') = 'GET'
limit 1;
EOT;
    $query = str_repeat(PHP_EOL, 7).$query;

    return !empty($this->executeSingleton0($query, $replace));
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects the details of a URL.
   *
   * @param int|null $pUrlId The ID of the URL.
   *                         int
   *
   * @return array
   */
  public function tstMonkeyUrlGetDetails(?int $pUrlId): array
  {
    $replace = [':p_url_id' => $this->quoteInt($pUrlId)];
    $query   = <<< EOT
select url_id
     , url_url
     , url_id_referrer
from TST_MONKEY_URL
where url_id = :p_url_id;
EOT;
    $query = str_repeat(PHP_EOL, 7).$query;

    return $this->executeRow1($query, $replace);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects URLs with forms.
   *
   * @return array[]
   */
  public function tstMonkeyUrlGetUrlsWithForms(): array
  {
    $query = <<< EOT
select url_id
     , bul_id
     , url_url
     , url_id_referrer
from TST_MONKEY_URL
where url_method = 'GET'
  and url_has_forms = 1
order by random()
EOT;
    $query = str_repeat(PHP_EOL, 5).$query;

    return $this->executeRows($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Insert a URL.
   *
   * @param string|null $pUrlUrl        The URL to be inserted.
   *                                    text
   * @param int|null    $pBulId         The ID of the base URL of the URI to be inserted.
   *                                    int
   * @param int|null    $pUrlIdReferrer The ID of the URI the is referring to the URI.
   *                                    int
   *
   * @return int
   */
  public function tstMonkeyUrlInsert(?string $pUrlUrl, ?int $pBulId, ?int $pUrlIdReferrer): int
  {
    $replace = [':p_url_url' => $this->quoteText($pUrlUrl),
                ':p_bul_id' => $this->quoteInt($pBulId),
                ':p_url_id_referrer' => $this->quoteInt($pUrlIdReferrer)];
    $query   = <<< EOT
insert into TST_MONKEY_URL(url_url, bul_id, url_id_referrer)
values (:p_url_url, :p_bul_id, :p_url_id_referrer);

update TST_MONKEY_BASE_URL
set bul_pages = bul_pages + 1
where bul_id = :p_bul_id;
EOT;
    $query = str_repeat(PHP_EOL, 9).$query;

    $this->executeNone($query, $replace);
    return $this->lastInsertId();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects the number of URL crawled group by status code classes, i.e. '1xx', '2xx', ..., '5xx'.
   *
   * @return array[]
   */
  public function tstMonkeyUrlStatusCount(): array
  {
    $query = <<< EOT
select url_status
     , sum(url_count) as url_count
from ( select '1xx' as url_status
            , 0     as url_count

       union all

       select '2xx'
            , 0

       union all

       select '3xx'
            , 0

       union all

       select '4xx'
            , 0

       union all

       select '5xx'
            , 0

       union all

       select '' || (url_status / 100) || 'xx'
            , count(*)
       from TST_MONKEY_URL
       where url_status is not null
       group by 1 ) t
group by 1;
EOT;
    $query = str_repeat(PHP_EOL, 5).$query;

    return $this->executeRows($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Updates a URL.
   *
   * @param int|null    $pUrlId        The ID of the URL.
   *                                   int
   * @param int|null    $pBulId        The ID of the base URL.
   *                                   int
   * @param string|null $pUrlMethod    The used method.
   *                                   text
   * @param int|null    $pUrlStatus    The status of the URL.
   *                                   int
   * @param string|null $pUrlLocation  The location of redirect header.
   *                                   text
   * @param string|null $pUrlTitle     The title of the URL.
   *                                   text
   * @param int|null    $pUrlHasForms  Whether the document has forms.
   *                                   int
   * @param string|null $pUrlSubmitted The submitted values.
   *                                   text
   * @param string|null $pUrlSource    The source of the URL.
   *                                   text
   */
  public function tstMonkeyUrlUpdate(?int $pUrlId, ?int $pBulId, ?string $pUrlMethod, ?int $pUrlStatus, ?string $pUrlLocation, ?string $pUrlTitle, ?int $pUrlHasForms, ?string $pUrlSubmitted, ?string $pUrlSource): void
  {
    $replace = [':p_url_id' => $this->quoteInt($pUrlId),
                ':p_bul_id' => $this->quoteInt($pBulId),
                ':p_url_method' => $this->quoteText($pUrlMethod),
                ':p_url_status' => $this->quoteInt($pUrlStatus),
                ':p_url_location' => $this->quoteText($pUrlLocation),
                ':p_url_title' => $this->quoteText($pUrlTitle),
                ':p_url_has_forms' => $this->quoteInt($pUrlHasForms),
                ':p_url_submitted' => $this->quoteText($pUrlSubmitted),
                ':p_url_source' => $this->quoteText($pUrlSource)];
    $query   = <<< EOT
update TST_MONKEY_URL
set url_method    = :p_url_method
  , url_status    = :p_url_status
  , url_location  = :p_url_location
  , url_title     = :p_url_title
  , url_has_forms = :p_url_has_forms
  , url_submitted = :p_url_submitted
  , url_source    = :p_url_source
where url_id = :p_url_id;

update TST_MONKEY_BASE_URL
set bul_crawled = bul_crawled + 1
where bul_id = :p_bul_id;
EOT;
    $query = str_repeat(PHP_EOL, 15).$query;

    $this->executeNone($query, $replace);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
