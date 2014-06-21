<?php
require_once './bootstrap.php';

error_reporting(E_ALL);

use Pop\Color\Space\Rgb;
use Pop\Pdf\Pdf;

/**
 * Given a SQL statement, execute it and return all the results as an array of arrays
 */
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

/**
 * @route
 */
function viewListOfRoles($db) {
    $list = fetchAll("SELECT * FROM roles ORDER BY title DESC", $db);
    loadView("view-list-roles.php", $list);
}

/**
 * @route
 */
function viewRole($db, $roleId) {
    $data = loadRoleData($db, $roleId);
    $data['roleId'] = $roleId;
    loadView('view-form.php', $data);
}

/**
 * Fetch all the data needed to display a form or a PDF for one role
 */
function loadRoleData($db, $roleId) {
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

    return array(
        'groups' => $groups,
        'options' => $options,
        'groupOptionMap' => $groupOptionMap,
        'role' => $role,
    );
}

/**
 * Turn an sequentail array of data into a map based on the ID field of the rows
 */
function dataToArray($data) {
    $arr = [];
    foreach ($data as $o) {
        $arr[$o['id']] = $o;
    }
    return $arr;
}

/**
 * Simple function to manage writing to a PDF
 * Keep the result and pass it in as the last parameter every time you call
 * this so it can keep track of the next wiriting position.
 * 
 * You must intialze everything for the PDF, this function is only for writing text
 * 
 * lol
 */
function pdfWriteln($pdf, $string, $type, $lastPosition = 760) {
    $topOfPage = 760;
    $padding = 10;
    $typeOptions = array(
        'txt' => array(
            'size' => 30, 'color' => new Rgb(0,0,0),
        ),
        'header' => array(
            'size' => 40, 'color' => new Rgb(215,101,12),
        ),
    );
    $style = $typeOptions[$type];
    // add page?
    $pos = $lastPosition - $padding - $style['size'];
    if ($pos < $padding + $style['size']) {
        $pos = $topOfPage;
        $pdf->addPage("Letter");
    }
    $pdf->addText(30, $pos, $style['size'], $string, "Arial");
    return $pos;
}

/**
 * @route POST
 */
function buildPdf($postData, $db) {
    echo "<pre>";
    $roleData = loadRoleData($db, $postData['roleid']);

    $listString = $postData['allOptionIds'];
    $sql = "SELECT * FROM options WHERE id IN($listString);";
    $options = fetchAll($sql, $db);
    $options = dataToArray($options);
    $groups = dataToArray($roleData['groups']);

    $topOfPage = 760;
    $top = $topOfPage;
    $size = 16;
    $padding = 10;
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
        
        foreach ($roleData['groupOptionMap'] as $groupRow) {
            $groupId = $groupRow['group_id'];
            $groupObj = $groups[$groupId];
            $pdf->setTextParams()
                ->setFillColor(new Rgb(0, 0, 0))
                ->setStrokeColor(new Rgb(0, 0, 0));
            $pdf->addText(30, $top, $size+10, $groupObj['title'], 'Arial');
            $top = $top - $size - $padding;
            if ( $top < $size + $padding + $padding) {
                $top = $topOfPage;
                $pdf->addPage("Letter");
            }
            foreach ($groupRow['options'] as $optionId) {
                $optionObj = $options[$optionId];
                if (isset($postData[$optionId])) {
                    $title = '[X] ' . $optionObj['title'];
                } else {
                    $title = '[_] ' . $optionObj['title'];
                }
                $pdf->setTextParams()
                    ->setFillColor(new Rgb(12, 101, 215))
                    ->setStrokeColor(new Rgb(215, 101, 12));
                $pdf->addText(50, $top, $size, $title, 'Arial');
                $top = $top - $size - $padding;
                if ( $top < $size + $padding + $padding) {
                    $top = $topOfPage;
                    $pdf->addPage("Letter");
                }
            }
            
        }

        /*
        foreach ($options as $option) {
            $id = $option['id'];
            $title = $option['title'];
            if (isset($data['option'][$id])) {
                $title = "[X] " . $title;
            } else {
                $title = "[ ] " . $title;
            }
            $pdf->addText(30, $top, $size, $title, 'Arial');
            $top = $top - $size - $padding;
            if ($top < ($size + $padding + $padding)) {
                $top = $iStart;
                $pdf->addPage("Letter");
            }
        }
         */

        $pdf->save("./output.pdf");
        echo "<a href='./output.pdf'>Here it is</a>";
        //$pdf->output();
    } catch (\Exception $e) {
        var_dump($e->getTrace());
    }
}

// =================
// =================
// =================
// Begin Application

$CONFIG = parse_ini_file("database-config.ini", false);
$dsn = sprintf("mysql:host=%s;dbname=%s", $CONFIG['DB_HOST'], $CONFIG['DB_NAME']);
$db = new PDO($dsn, $CONFIG['DB_USER'], $CONFIG['DB_PASS']);

try {
    $routes = array();
    $routes['list-roles'] = function($GET, $POST) use ($db) {
        viewListOfRoles($db);
    };
    $routes['view-role'] = function($GET, $POST) use ($db) {
        $roleId = (int) $_GET['role'];
        viewRole($db, $roleId);
    };
    $routes['submit-form'] = function($GET, $POST) use ($db) {
        buildPdf($_POST, $db);
    };
    $routes['test'] = function($GET,$POST) {
        $pdf = new Pdf("./test.pdf");
        $p = pdfWriteln($pdf, "Hello!", "txt");
        $p = pdfWriteln($pdf, "HOw are you?", "txt", $p);
        $p->output();
    };

    if (isset($_GET['route'])) {
        $r = $_GET['route'];
    } else if (isset($_POST['route'])) {
        $r = $_POST['route'];
    } else {
        $r = 'list-roles';
    }
    if (!isset($routes[$r])) {
        throw new \Exception("I dont know what to do for this page. No route defined.");
    }
    $fn = $routes[$r];
    $fn($_GET, $_POST);
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
