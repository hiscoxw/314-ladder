               /* player Entity Set */

create table player (
    name     varchar(80) not null,
    email    text not null,
    rank     smallint unique,
    phone    char(10),    /* optional */
    username varchar(80) not null,
    password text not null,
    primary key (username)
);


              /* challenge Entity Set */

create table challenge (
   challenger     varchar(80) not null,
   challengee     varchar(80) not null,
   issued         date not null,
   accepted       date,
   scheduled      timestamp not null,
   primary key (challenger, challengee, scheduled),
   foreign key (challenger) references player(username),
   foreign key (challengee) references player(username)
);


                /* game Entity Set */

create table game (
   winner         varchar(80) not null,
   loser          varchar(80) not null,
   played         timestamp not null,
   number         smallint not null,
   winner_score   smallint not null,
   loser_score    smallint not null,
   primary key (winner, loser, played, number),
   foreign key (winner) references player(username),
   foreign key (loser) references player(username)
);

         /* match_vew
            Tells us who won/lost each match and the game score.
            The strategy is to create two columns as subqueries 
            where each column totals up the number of games won by the
            "winner" and the "loser". This gives us a row for each game
            played and, if each player won at least one game, then each
            player appears as the "winner" at least once. Since each row
            includes the total games "won" by that "winner", we finish
            the query with a restriction that the number of games won by
            the "winner" is > number of games lost. This still gives us
            one row for each game the winner won, so we use distinct in
            this case to return only one row with the summary
            information about total games won. This query is clever and
            expensive.
          */

create view match_view as 
select distinct g.winner, g.loser, g.played, 
   (select count(*) from game as w where g.winner = w.winner and
                                         g.loser = w.loser and
                                         g.played = w.played) as won, 
   (select count(*) from game as l where g.loser = l.winner and
                                         g.winner = l.loser and
                                         g.played = l.played) as lost
   from game as g where
      (select count(*) from game as w where g.winner = w.winner and
                                         g.loser = w.loser and
                                         g.played = w.played) >
      (select count(*) from game as l where g.loser = l.winner and
                                         g.winner = l.loser and
                                         g.played = l.played);

