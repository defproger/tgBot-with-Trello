create table users
(
    telegram_id bigint       not null,
    username    varchar(50)  null,
    name        varchar(100) null,
    trello_id   int          null,
    constraint users_pk
        primary key (telegram_id)
);


