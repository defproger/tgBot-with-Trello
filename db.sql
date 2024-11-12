create table chats
(
    id      int auto_increment
        primary key,
    chat_id varchar(228) null
);

create table users
(
    telegram_id bigint       not null
        primary key,
    username    varchar(50)  null,
    name        varchar(100) null,
    trello_id   int          null
);

create table usersInChats
(
    id   int auto_increment
        primary key,
    uid  bigint null,
    chat int    null
);

