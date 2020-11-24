<?php
declare(strict_types=1);

namespace Plaisio\Test;

use SetBased\Stratum\SqlitePdo\SqlitePdoDataLayer;

/**
 * The data layer.
 */
class CrawlStore extends SqlitePdoDataLayer
{
  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects the URIs to be crawled.
   *
   * @return array[]
   */
  public function getCrawl(): array
  {
    $query = <<< EOT
select t2.uri_id
,      t2.bui_id
,      t2.uri_uri
from   TST_BASE_URI t1
join   TST_URI      t2  on  t2.bui_id = t1.bui_id
where  t1.bui_crawled < t1.bui_pages
and    t1.bui_crawled < 10
and    t2.uri_title   is null
order by t1.bui_crawled
,        random()
limit 10
;
EOT;
    $query = str_repeat(PHP_EOL, 5).$query;

    return $this->executeRows($query);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Selects an URI.
   *
   * @param string|null $pUriUri The URI.
   *
   * @return int|null
   */
  public function getUri(?string $pUriUri): ?int
  {
    $replace = [':p_uri_uri' => $this->quoteVarchar($pUriUri)];
    $query   = <<< EOT
select uri_id
from   TST_URI
where  uri_uri = :p_uri_uri
;
EOT;
    $query = str_repeat(PHP_EOL, 8).$query;

    return $this->executeSingleton0($query, $replace);
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   * Insert an URI.
   *
   * @param string|null $pUriUri       The URI to be inserted.
   * @param int|null    $pBuiId        The ID of the base URI of the URI to be inserted.
   * @param int|null    $pUriIdReferer The ID of the URI the is referring to the URI..
   *
   * @return int
   */
  public function insertUri(?string $pUriUri, ?int $pBuiId, ?int $pUriIdReferer): int
  {
    $replace = [':p_uri_uri' => $this->quoteVarchar($pUriUri), ':p_bui_id' => $this->quoteInt($pBuiId), ':p_uri_id_referer' => $this->quoteInt($pUriIdReferer)];
    $query   = <<< EOT
insert into TST_URI( uri_uri
,                    bui_id
,                    uri_id_referer )
values( :p_uri_uri
,       :p_bui_id
,       :p_uri_id_referer)
;

update TST_BASE_URI
set    bui_pages = bui_pages + 1
where  bui_id = :p_bui_id
;
EOT;
    $query = str_repeat(PHP_EOL, 9).$query;

    $this->executeNone($query, $replace);
    return $this->lastInsertId();
  }

  //--------------------------------------------------------------------------------------------------------------------
  /**
   *
   * @param int|null    $pUriId       The ID of the URI.
   * @param int|null    $pBuiId       The ID of the base URI.
   * @param int|null    $pUriStatus   The status of the URI.
   * @param string|null $pUriLocation The location of redirect header.
   * @param string|null $pUriTitle    The title of the URI.
   * @param string|null $pUriSource   The source of the URI.
   */
  public function updateUri(?int $pUriId, ?int $pBuiId, ?int $pUriStatus, ?string $pUriLocation, ?string $pUriTitle, ?string $pUriSource): void
  {
    $replace = [':p_uri_id' => $this->quoteInt($pUriId), ':p_bui_id' => $this->quoteInt($pBuiId), ':p_uri_status' => $this->quoteInt($pUriStatus), ':p_uri_location' => $this->quoteVarchar($pUriLocation), ':p_uri_title' => $this->quoteVarchar($pUriTitle), ':p_uri_source' => $this->quoteVarchar($pUriSource)];
    $query   = <<< EOT
update TST_URI
set    uri_status   = :p_uri_status
,      uri_location = :p_uri_location
,      uri_title    = :p_uri_title
,      uri_source   = :p_uri_source
where uri_id = :p_uri_id
;

update TST_BASE_URI
set    bui_crawled = bui_crawled + 1
where  bui_id = :p_bui_id
;
EOT;
    $query = str_repeat(PHP_EOL, 10).$query;

    $this->executeNone($query, $replace);
  }

  //--------------------------------------------------------------------------------------------------------------------
}

//----------------------------------------------------------------------------------------------------------------------
