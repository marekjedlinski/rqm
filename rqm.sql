-- Random Quote Machine database v 1.0 (a FreeCodeCamp project)


-- header table
CREATE TABLE [head] (
    -- version info etc
    [key] TEXT PRIMARY KEY NOT NULL
  , [value] TEXT
);

-- main: quotes
CREATE TABLE [quotes] (
    [id] INTEGER PRIMARY KEY
  , [text] TEXT COLLATE NOCASE -- quote text
  , [author] TEXT COLLATE NOCASE -- attribution
  , [source] TEXT COLLATE NOCASE -- may be empty
  , [extra] TEXT COLLATE NOCASE -- may be empty
  , [link] TEXT COLLATE NOCASE -- may be empty
  , [len] INTEGER -- length of quote text only
  , [fullen] INTEGER -- length of quote + author + 3 (for twitter)
);

CREATE INDEX [ix_quotes_qlen] ON [quotes] ( [len] );
CREATE INDEX [ix_quotes_fullen] ON [quotes] ( [fullen] );
-- CREATE INDEX [ix_quotes_quote] ON [quotes] ( [quote] );


