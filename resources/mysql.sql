-- !mysql
-- #{ rewardsbuffer
-- #    { init
CREATE TABLE IF NOT EXISTS rewards (
    player VAR_CHAR(32) NOT NULL,
    data BLOB NOT NULL,
    PRIMARY KEY(player)
);
-- #    }

-- #    { get
-- #        :player string
SELECT data FROM rewards WHERE player=:player;
-- #    }

-- #    { save
-- #        :player string
-- #        :data string
INSERT INTO rewards(player, data) VALUES(:player, :data)
ON DUPLICATE KEY UPDATE data=VALUES(data);
-- #    }
-- #}