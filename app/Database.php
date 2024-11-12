<?php

$user = $_CONFIG['db']['user'];
$password = $_CONFIG['db']['password'];
$database = $_CONFIG['db']['database'];
$host = $_CONFIG['db']['host'];
$port = $_CONFIG['db']['port'];
static $dbh = null;
if ($dbh != null) return $dbh;
$dbh = new PDO("mysql:dbname=$database;host=$host;charset=utf8;port=$port", $user, $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);


function query($query)
{
    global $dbh;
    return $dbh->query($query)->fetchAll();
}

function db_getAll($table)
{
    global $dbh;
    return $dbh->query("SELECT * FROM {$table}")->fetchAll();
}

//$users = db_getAll('users');


function db_getById($table, $id, $mark = null, $select = null)
{
    global $dbh;
    $select = $select == null ? '*' : $select;
    $mark = $mark == null ? 'id' : $mark;
    return $dbh->query("SELECT {$select} FROM {$table} WHERE {$mark}={$id}")->fetch();
}

//echo "<pre>";
//$user = db_getById('users',1);
//print_r($user);


function db_insert($table, $arr)
{
    global $dbh;
    $q = "INSERT INTO {$table} ";
    foreach ($arr as $key => $ar) {
        if ($ar != '' && $ar !== null && $ar !== '0000/00/00 00:00:00') {
            $fields[] = $key;
            $true_arr[$key] = $ar;
        }
    }
    $q .= "(" . implode(",", $fields) . ") VALUES (:" . implode(",:", $fields) . ")";
    $stmt = $dbh->prepare($q);


    $stmt->execute($true_arr);

}

function db_lastId()
{
    global $dbh;
    return $dbh->lastInsertId();
}

//inser 1 user
//db_insert('users', [
//    'name' => 'testuser',
//    'email' => 'test@test.com',
//    'password' => '15454841321321654684616'
//]);


function db_update($table, $id, $arr, $mark = null)
{
    global $dbh;

    $mark = $mark == null ? 'id' : $mark;

    $id = (int)$id;
    $q = "UPDATE {$table} SET ";
    $fields = array_keys($arr);
    $q .= implode("=?, ", $fields) . "=? WHERE {$mark}={$id}";
    $stmt = $dbh->prepare($q);
    $stmt->execute(array_values($arr));
}

//db_update('users',231,[
//    'name'=>'updatename'
//]);
function db_delete($table, $id)
{
    global $dbh;
    $id = (int)$id;
    $stmt = $dbh->query("DELETE FROM {$table} WHERE id={$id}");
}

//db_delete('users',11);
