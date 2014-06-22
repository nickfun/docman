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

function loadDataFromTicket($ticketId, $data) {
    $ticketId = (int) $ticketId;
    $sql = "select * from tickets where id = $data";
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
            'size' => 14, 'color' => new Rgb(12, 101, 215),
        ),
        'option' => array(
            'size' => 16, 'color' => new Rgb(0,0,0),
        ),
        'header' => array(
            'size' => 26, 'color' => new Rgb(215,50,50),
        ),
    );
    $style = $typeOptions[$type];
    // add page?
    $pos = $lastPosition - $padding - $style['size'];
    if ($pos < $padding + $style['size']) {
        $pos = $topOfPage;
        $pdf->addPage("Letter");
    }
    // do the thing
    $pdf->setTextParams()
                ->setFillColor($style['color'])
                ->setStrokeColor($style['color']);
    $pdf->addText(30, $pos, $style['size'], $string, "Arial");
    return $pos;
}

/**
 * @route POST
 */
function buildPdf($postData, $db) {
    $roleData = loadRoleData($db, $postData['roleid']);

    $listString = $postData['allOptionIds'];
    $sql = "SELECT * FROM options WHERE id IN($listString);";
    $options = fetchAll($sql, $db);
    $options = dataToArray($options);
    $groups = dataToArray($roleData['groups']);
    
    try {
        $pdf = new Pdf('./doc.pdf');
        $pdf->addPage('Letter');

        $pdf->setVersion('1.4')
                ->setTitle('TICKET NUMBER')
                ->setAuthor('Pac Bio')
                ->setSubject('Subject goes here')
                ->setCreateDate(date('D, M j, Y h:i A'));

        $pdf->setCompression(true);

        $pdf->setTextParams();
        $pdf->addFont('Arial');
        
        $last = pdfWriteln($pdf, "Role: " . $roleData['role']['title'], "header");
        $last = pdfWriteln($pdf, "By: " . $postData['author'], 'txt', $last);
        $last = pdfWriteln($pdf, "TICKET# " . $postData['ticket'], 'txt', $last);
        $last = pdfWriteln($pdf, $postData['date'], 'txt', $last);
        
        foreach (explode("\n", $postData['notes']) as $noteLine) {
            $last = pdfWriteln($pdf, $noteLine, 'txt', $last);
        }
        
        foreach ($roleData['groupOptionMap'] as $groupRow) {
            $groupId = $groupRow['group_id'];
            $groupObj = $groups[$groupId];
            $last = pdfWriteln($pdf, $groupObj['title'], "header", $last);
            foreach ($groupRow['options'] as $optionId) {
                $optionObj = $options[$optionId];
                if (isset($postData['option'][$optionId])) {
                    $title = '[X] ' . $optionObj['title'];
                } else {
                    $title = '[_] ' . $optionObj['title'];
                }
                $last = pdfWriteln($pdf, $title, 'option', $last);
            }
            
        }

//        $pdf->save("./output.pdf");
//        echo "<a href='./output.pdf'>Here it is</a>";
        $pdf->output();
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
    // Define Routes
    // -------------
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
        var_dump($GET,$POST);
    };
    $routes['view-data'] = function($GET,$POST) use ($db) {
        $ticketId = $POST['ticket'];
        $data = loadDataFromTicket($ticketId, $db);
        loadView('view-form.php', $data);
    };
    
    // Execute Route
    // -------------

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
