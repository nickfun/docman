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

function loadView($file, $DATA) {
//    echo "<h1>$file</h1>";
//    var_dump($DATA);
//    die();
    include $file;
    die();
}

function viewListOfRoles($db) {
    $list = fetchAll("SELECT * FROM roles ORDER BY title DESC", $db);
    loadView("view-list-roles.php", $list);
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

    $groupIds = array();
    foreach ($groups as $row) {
        $groupIds[] = (int) $row['id'];
    }
    $groupIdList = implode(",", $groupIds);
    $groupOptionMapSql = "
        select g.id, group_concat(o.id) as option_id_list
        from groups as g
        left join group_option_map as gom on gom.group_id = g.id
        left join options as o on gom.option_id = o.id
        where g.id in ($groupIdList)
        group by g.id";
    $groupOptionMapResults = fetchAll($groupOptionMapSql, $db);
    $groupOptionMap = array();
    foreach ($groupOptionMapResults as $row) {
        $list = explode(",", $row['option_id_list']);
        $groupOptionMap[] = array(
            'groupid' => $row['id'],
            'options' => $list,
        );
    }

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

    loadView('view-form.php', array(
        'groups' => $groups,
        'options' => $options,
        'groupOptionMap' => $groupOptionMap,
    ));
}

// =================

if (isset($_GET['role'])) {
    $roleId = (int) $_GET['role'];
    viewRole($db, $roleId);
} else {
    viewListOfRoles($db);
}