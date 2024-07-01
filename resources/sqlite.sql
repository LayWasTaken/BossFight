-- #!sqlite
-- #{ rewardsbuffer

-- #  { init
CREATE TABLE IF NOT EXISTS rewards(
  player VARCHAR(32) NOT NULL,
  data BLOB NOT NULL,
  PRIMARY KEY(player)
);
-- #  }

-- #  { get
-- #    :player string
SELECT HEX(data) AS data FROM rewards WHERE player=:player;
-- #  }

-- #  { save
-- #    :player string
-- #    :data string
INSERT OR REPLACE INTO rewards(player, data) VALUES(:player, :data);
-- #  }

-- #}