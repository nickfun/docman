<?php
require_once './bootstrap.php';

use Pop\Color\Space\Rgb;
use Pop\Pdf\Pdf;

$CONFIG = parse_ini_file("database-config.ini", false);
$dsn = sprintf("mysql:host=%s;dbname=%s", $CONFIG['DB_HOST'], $CONFIG['DB_NAME']);
$db = new PDO($dsn, $CONFIG['DB_USER'], $CONFIG['DB_PASS']);

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

    // get role info
    $roleSql = "SELECT * FROM roles WHERE id=$roleId";
    $row = fetchAll($roleSql, $db);
    if (empty($row)) {
        throw new \Exception("Can not find role $roleId");
    }
    $role = $row[0];

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
            'group_id' => $row['id'],
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
        'role' => $role,
    ));
}

function buildPdf($data) {
    try {
        $pdf = new Pdf('./doc.pdf');
        $pdf->addPage('Letter');

        $pdf->setVersion('1.4')
                ->setTitle('TICKET NUMBER')
                ->setAuthor('Pac Bio')
                ->setSubject('Subject goes here')
                ->setCreateDate(date('D, M j, Y h:i A'));

        $pdf->setCompression(true);

        $pdf->setTextParams()
                ->setFillColor(new Rgb(12, 101, 215))
                ->setStrokeColor(new Rgb(215, 101, 12));
        $pdf->addFont('Arial');
        $pdf->addText(50, 620, 48, $data['notes'], 'Arial');

        $pdf->output();
    } catch (\Exception $e) {
        var_dump($e->getTrace());
    }
}

// =================
// =================
// =================

try {
    if (isset($_GET['role'])) {
        $roleId = (int) $_GET['role'];
        viewRole($db, $roleId);
    } else if (!empty($_POST)) {
        buildPdf($_POST);
    } else {
        viewListOfRoles($db);
    }
} catch (Exception $ex) {
    ?>
    <body><h1>ERROR</h1><p>There was a problem:</p>
        <h2><?= $ex->getMessage() ?></h2>
        <pre><?= $ex->getTraceAsString() ?></pre>
        <p><a href="<?= basename(__FILE__) ?>">Home</a></p>
    </body>
    <?php
    die("EXCEPTION");
}
