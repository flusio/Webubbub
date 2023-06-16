CREATE TABLE subscriptions (
  id integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  created_at datetime NOT NULL,
  expired_at datetime,
  status text NOT NULL,

  callback text NOT NULL,
  topic text NOT NULL,
  lease_seconds integer NOT NULL,
  secret text,

  pending_request text,
  pending_lease_seconds integer,
  pending_secret text
);

CREATE TABLE contents (
  id integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  created_at datetime NOT NULL,
  fetched_at datetime,
  status text NOT NULL,

  url text NOT NULL,
  links text,
  type text,
  content text
);

CREATE TABLE content_deliveries (
  id integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  subscription_id integer NOT NULL,
  content_id integer NOT NULL,
  created_at datetime NOT NULL,
  try_at datetime NOT NULL,
  tries_count integer NOT NULL DEFAULT 0,
  FOREIGN KEY (subscription_id) REFERENCES subscriptions(id),
  FOREIGN KEY (content_id) REFERENCES contents(id)
);

CREATE TABLE jobs (
    id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    perform_at TEXT NOT NULL,
    name TEXT NOT NULL DEFAULT '',
    args TEXT NOT NULL DEFAULT '{}',
    frequency TEXT NOT NULL DEFAULT '',
    queue TEXT NOT NULL DEFAULT 'default',
    locked_at TEXT,
    number_attempts BIGINT NOT NULL DEFAULT 0,
    last_error TEXT NOT NULL DEFAULT '',
    failed_at TEXT
);
