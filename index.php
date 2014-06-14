<?php

require_once './bootstrap.php';

$db = new PDO("mysql:host=localhost;dbname=docman", "root", "root");

function fetchAll($sql, $db) {
    $r = $db->query($sql);
    if (!$r) {
        var_dump($db->errorInfo());
        throw new \Exception("Could not execute sql $sql ");
    }
    return $r->fetchAll(PDO::FETCH_ASSOC);
}

$list = fetchAll("select * from roles", $db);

foreach ($list as $row) {
    printf("Role %d - %s", $row['id'], $row['title']);
}

function loadView($file, $DATA) {
    echo "<h1>$file</h1>";
    var_dump($DATA);
    die();
    include $file;
    die();
}

function viewListOfRoles($db) {
    $list = fetchAll("SELECT * FROM roles ORDER BY title DESC", $db);
    loadView("list-roles.php", $list);
}

function viewRole($db, $roleId) {
    $roleId = (int) $roleId;
    
    // get groups for role
    $groupsSql = "
        SELECT g.*
        FROM roles AS r
        LEFT JOIN role_group_map AS rgm ON r.id = rgm.role_id
        LEFT JOIN groups AS g ON rgm.group_id = g.id
        WHERE r.id = $roleId";
    $groups = fetchAll($groupsSql, $db);
    
    // get options for role
    $optionsSql = "
        select g.*, o.*
        from roles as r
        left join role_group_map as rgm on r.id = rgm.role_id
        left join groups as g on g.id = rgm.group_id
        left join group_option_map as gom on g.id = gom.group_id
        left join options as o on gom.option_id = o.id
        where r.id = $roleId";
    $options = fetchAll($optionsSql, $db);
    loadView('form.php', array(
        'groups' => $groups,
        'options' => $options,
    ));
}

// =================

if (isset($_GET['role'])) {
    $roleId = (int) $_GET['role'];
    viewRole($db, $roleId);
} else {
    viewListOfRoles($db);
}