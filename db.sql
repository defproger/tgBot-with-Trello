create table chats
(
    id      int auto_increment
        primary key,
    chat_id varchar(228) null,
    constraint chats_pk2
        unique (chat_id)
);

create table usersInChats
(
    id        int auto_increment
        primary key,
    uid       bigint       null,
    chat      int          null,
    trello_id varchar(100) null,
    name      varchar(228) null,
    constraint usersInChats_pk2
        unique (uid, chat)
);

