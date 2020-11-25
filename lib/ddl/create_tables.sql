create table TST_MONKEY_BASE_URL
(
  bul_id      integer not null primary key asc,
  bul_url     varchar not null,
  bul_pages   int default 0,
  bul_crawled int default 0
);

create table TST_MONKEY_URL
(
  url_id          integer not null primary key asc,
  bul_id          integer not null,
  url_id_referrer integer,
  url_url         varchar not null,
  url_status      int,
  url_location    varchar,
  url_title       varchar,
  url_source      varchar,
  foreign key (bul_id) references TST_MONKEY_BASE_URL(bul_id)
);
