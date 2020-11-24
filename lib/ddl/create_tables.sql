create table TST_BASE_URI
(
  bui_id      integer not null primary key asc,
  bui_uri     varchar not null,
  bui_pages   int default 0,
  bui_crawled int default 0
);

create table TST_URI
(
  uri_id         integer not null primary key asc,
  bui_id         integer not null,
  uri_id_referer integer,
  uri_uri        varchar not null,
  uri_status     int,
  uri_location   varchar,
  uri_title      varchar,
  uri_source     varchar,

  foreign key (bui_id) references TST_BASE_URI(bui_id)
);
