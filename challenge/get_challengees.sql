SELECT name, email, phone, rank, username FROM player WHERE rank < ? AND rank >= ? - 3
    AND username NOT IN (SELECT challengee FROM challenge WHERE accepted IS NOT NULL)
    AND username NOT IN (SELECT challenger FROM challenge WHERE accepted IS NOT NULL);
