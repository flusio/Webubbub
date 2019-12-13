CREATE TABLE subscriptions (
  id integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  created_at datetime NOT NULL,
  expired_at datetime,
  status text NOT NULL,
  pending_request text,

  callback text NOT NULL,
  topic text NOT NULL,
  lease_seconds integer NOT NULL,
  secret text
);

CREATE TABLE contents (
  id integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  created_at datetime NOT NULL,
  url text NOT NULL
);
