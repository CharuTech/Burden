<?php

//Burden, Copyright Josh Fradley (http://github.com/joshf/Burden)

$version = "1.6dev";

if (!file_exists("config.php")) {
    die("Error: Config file not found! Please reinstall Burden.");
}

require_once("config.php");

$uniquekey = UNIQUE_KEY;

session_start();
if (!isset($_SESSION["is_logged_in_" . $uniquekey . ""])) {
    header("Location: login.php");
    exit; 
}

//Set cookie so we dont constantly check for updates
setcookie("burdenhascheckedforupdates", "checkedsuccessfully", time()+604800);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Burden</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
if (THEME == "default") {
    echo "<link href=\"resources/bootstrap/css/bootstrap.min.css\" type=\"text/css\" rel=\"stylesheet\">\n";  
} else {
    echo "<link href=\"//netdna.bootstrapcdn.com/bootswatch/2.3.2/" . THEME . "/bootstrap.min.css\" type=\"text/css\" rel=\"stylesheet\">\n";
}
?>
<link href="resources/bootstrap/css/bootstrap-responsive.min.css" type="text/css" rel="stylesheet">
<link href="resources/datatables/jquery.dataTables-bootstrap.min.css" type="text/css" rel="stylesheet">
<link href="resources/bootstrap-notify/css/bootstrap-notify.min.css" type="text/css" rel="stylesheet">
<style type="text/css">
body {
    padding-top: 60px;
}
@media (max-width: 980px) {
    body {
        padding-top: 0;
    }
}
<?php
//Fix broken superhero theme
if (THEME == "superhero") {
    echo "td {\n    color: #5A6A7D;\n}\n";
}
?>
</style>
<!-- Javascript start -->
<!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
<!--[if lt IE 9]>
<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
<script src="resources/jquery.min.js"></script>
<script src="resources/bootstrap/js/bootstrap.min.js"></script>
<script src="resources/datatables/jquery.dataTables.min.js"></script>
<script src="resources/datatables/jquery.dataTables-bootstrap.min.js"></script>
<script src="resources/bootstrap-notify/js/bootstrap-notify.min.js"></script>
<script src="resources/bootbox/bootbox.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
    /* Table selection */
    id_selected = false;
    $("#tasks input[name=id]").click(function() {
        id = $("#tasks input[name=id]:checked").val();
        id_selected = true;
    });
    /* End */
    /* Datatables */
    $("#tasks").dataTable({
        "sDom": "<'row'<'span6'l><'span6'f>r>t<'row'<'span6'i><'span6'p>>",
        "sPaginationType": "bootstrap",
        "aoColumns": [
            {"bSortable": false},
            null,
            null,
            {"sType": "date-uk"}
        ]
    });
    $.extend($.fn.dataTableExt.oStdClasses, {
        "sSortable": "header",
        "sWrapper": "dataTables_wrapper form-inline"
    });
    $.extend($.fn.dataTableExt.oSort, {
        "date-uk-pre": function (a) {
            var ukDatea = a.split("/");
            return (ukDatea[2] + ukDatea[1] + ukDatea[0]) * 1;
        },
        "date-uk-asc": function (a, b) {
            return ((a < b) ? -1 : ((a > b) ? 1 : 0));
        },
        "date-uk-desc": function (a, b) {
            return ((a < b) ? 1 : ((a > b) ? -1 : 0));
        }
    });
    /* End */
    /* Edit */
    $("#edit").click(function() {
        if (id_selected == true) {
            window.location = "edit.php?id="+ id +"";
        } else {
            $(".top-right").notify({
                type: "info",
                transition: "fade",
                icon: "info-sign",
                message: {
                    text: "No ID selected!"
                }
            }).show();
        }
    });
    /* End */
    /* Delete */
    $("#delete").click(function() {
        if (id_selected == true) {
            $("body").on("show", ".bootbox", function () {
                if (!$(".modal-header")[0]) {
                    $(".bootbox").prepend("<div class=\"modal-header\"><a href=\"javascript:;\" class=\"close\">&times;</a><h3>Confirm Delete</h3></div>");
                }
            });
            bootbox.confirm("Are you sure you want to delete the selected task?", "No", "Yes", function(result) {
                if (result == true) {
                    $.ajax({
                        type: "POST",
                        url: "actions/worker.php",
                        data: "action=delete&id="+ id +"",
                        error: function() {
                            $(".top-right").notify({
                                type: "error",
                                transition: "fade",
                                icon: "warning-sign",
                                message: {
                                    text: "Ajax query failed!"
                                }
                            }).show();
                        },
                        success: function() {
                            $(".top-right").notify({
                                type: "success",
                                transition: "fade",
                                icon: "ok",
                                message: {
                                    text: "Task deleted!"
                                },
                                onClosed: function() {
                                    window.location.reload();
                                }
                            }).show();
                        }
                    });
                }
            });
        } else {
            $(".top-right").notify({
                type: "info",
                transition: "fade",
                icon: "info-sign",
                message: {
                    text: "No ID selected!"
                }
            }).show();
        }
    });
    /* End */
    /* Complete */
    $("#complete").click(function() {
        if (id_selected == true) {
            $.ajax({
                type: "POST",
                url: "actions/worker.php",
                data: "action=complete&id="+ id +"",
                error: function() {
                    $(".top-right").notify({
                        type: "error",
                        transition: "fade",
                        icon: "warning-sign",
                        message: {
                            text: "Ajax query failed!"
                        }
                    }).show();
                },
                success: function() {
                    $(".top-right").notify({
                        type: "success",
                        transition: "fade",
                        icon: "ok",
                        message: {
                            text: "Task marked as completed!"
                        },
                        onClosed: function() {
                            window.location.reload();
                        }
                    }).show();
                }
            });
        } else {
            $(".top-right").notify({
                type: "info",
                transition: "fade",
                icon: "info-sign",
                message: {
                    text: "No ID selected!"
                }
            }).show();
        }
    });
    /* End */
    /* Restore */
    $("#restore").click(function() {
        if (id_selected == true) {
            $.ajax({
                type: "POST",
                url: "actions/worker.php",
                data: "action=restore&id="+ id +"",
                error: function() {
                    $(".top-right").notify({
                        type: "error",
                        transition: "fade",
                        icon: "warning-sign",
                        message: {
                            text: "Ajax query failed!"
                        }
                    }).show();
                },
                success: function() {
                    $(".top-right").notify({
                        type: "success",
                        transition: "fade",
                        icon: "ok",
                        message: {
                            text: "Task restored!"
                        },
                        onClosed: function() {
                            window.location.reload();
                        }
                    }).show();
                }
            });
        } else {
            $(".top-right").notify({
                type: "info",
                transition: "fade",
                icon: "info-sign",
                message: {
                    text: "No ID selected!"
                }
            }).show();
        }
    });
    /* End */
});
</script>
<!-- Javascript end -->
</head>
<body>
<!-- Nav start -->
<div class="navbar navbar-fixed-top">
<div class="navbar-inner">
<div class="container">
<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
<span class="icon-bar"></span>
<span class="icon-bar"></span>
<span class="icon-bar"></span>
</a>
<a class="brand" href="index.php">Burden</a>
<div class="nav-collapse collapse">
<ul class="nav">
<li class="divider-vertical"></li>
<li><a href="add.php"><i class="icon-plus-sign"></i> Add</a></li>
<li><a href="edit.php"><i class="icon-edit"></i> Edit</a></li>
</ul>
<ul class="nav pull-right">
<li class="dropdown">
<a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="icon-filter"></i> Filters <b class="caret"></b></a>
<ul class="dropdown-menu">
<li><a href="index.php?view=highpriority"><i class="icon-exclamation-sign"></i> High Priority Tasks</a></li>
<li><a href="index.php?view=completed"><i class="icon-ok"></i> Completed Tasks</a></li>
<li class="divider"></li>
<li><a href="index.php"><i class="icon-remove"></i> Clear Filters</a></li>
</ul>
</li>
<li class="divider-vertical"></li>
<li class="dropdown">
<a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="icon-user"></i> <? echo ADMIN_USER; ?> <b class="caret"></b></a>
<ul class="dropdown-menu">
<li><a href="settings.php"><i class="icon-cog"></i> Settings</a></li>
<li><a href="logout.php"><i class="icon-off"></i> Logout</a></li>
</ul>
</li>
</ul>
</div>
</div>
</div>
</div>
<!-- Nav end -->
<!-- Content start -->
<div class="container">
<div class="page-header">
<?php

if (isset($_GET["view"])) {
    $view = $_GET["view"];
} else {
    $view = "normal";
}

if ($view == "completed") {
    echo "<h1>Completed Tasks</h1>";
} elseif ($view == "highpriority") {
    echo "<h1>High Priority Tasks</h1>";
} else {
    echo "<h1>Current Tasks</h1>";
}
echo "</div><div class=\"notifications top-right\"></div>";		

echo "<noscript><div class=\"alert alert-info\"><h4 class=\"alert-heading\">Information</h4><p>Please enable JavaScript to use Burden. For instructions on how to do this, see <a href=\"http://www.activatejavascript.org\" target=\"_blank\">here</a>.</p></div></noscript>";

@$con = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
if (!$con) {
    die("<div class=\"alert alert-error\"><h4 class=\"alert-heading\">Error</h4><p>Could not connect to database (" . mysql_error() . "). Check your database settings are correct.</p><p><a class=\"btn btn-danger\" href=\"javascript:history.go(-1)\">Go Back</a></p></div></div></body></html>");
} else {
    $does_db_exist = mysql_select_db(DB_NAME, $con);
    if (!$does_db_exist) {
        die("<div class=\"alert alert-error\"><h4 class=\"alert-heading\">Error</h4><p>Database does not exist (" . mysql_error() . "). Check your database settings are correct.</p><p><a class=\"btn btn-danger\" href=\"javascript:history.go(-1)\">Go Back</a></p></div></div></body></html>");
    }
}

if ($view == "completed") {
    $gettasks = mysql_query("SELECT * FROM Data WHERE completed = \"1\"");
} elseif ($view == "highpriority") {
    $gettasks = mysql_query("SELECT * FROM Data WHERE highpriority = \"1\" AND completed = \"0\"");
} else {
    $gettasks = mysql_query("SELECT * FROM Data WHERE completed = \"0\"");
}

//Update checking
if (!isset($_COOKIE["burdenhascheckedforupdates"])) {
    $remoteversion = file_get_contents("https://raw.github.com/joshf/Burden/master/version.txt");
    if (preg_match("/^[0-9.-]{1,}$/", $remoteversion)) {
        if ($version < $remoteversion) {
            echo "<div class=\"alert\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\">&times;</button><h4 class=\"alert-heading\">Update</h4><p>Burden <a href=\"https://github.com/joshf/Burden/releases/$remoteversion\" target=\"_blank\">$remoteversion</a> is available. <a href=\"https://github.com/joshf/Burden#updating\" target=\"_blank\">Click here to update</a>.</p></div>";
        }
    }
} 

echo "<table id=\"tasks\" class=\"table table-striped table-bordered table-condensed\">
<thead>
<tr>
<th></th>
<th class=\"hidden-phone\">Category</th>
<th>Task</th>";
if ($view == "completed") {
    echo "<th>Date Completed</th>"; 
} else {
    echo "<th>Due</th>"; 
}
echo "</tr></thead><tbody>";

//Set counters to zero
$numberoftasksoverdue = "0"; 
$numberoftasksduetoday = "0";

while($row = mysql_fetch_assoc($gettasks)) {
    //Logic for due date
    list($day, $month, $year) = explode("/", $row["due"]);
    $dueflipped = "$year-$month-$day";
    $today = strtotime(date("Y-m-d")); 
    $due = strtotime($dueflipped);
    //Counters
    if ($today > $due) { 
        $numberoftasksoverdue++; 
    }
    if ($today == $due) { 
        $numberoftasksduetoday++; 
    }
    //Set cases
    if ($row["highpriority"] == "0" && $row["completed"] != "1" && $today < $due) {
        $case = "normal";
    }
    if ($row["highpriority"] == "1") {
        if ($today > $due) {
            $case = "overdue";
        } else {
            $case = "highpriority";
        }
    } 
    if ($today > $due) {
        if ($row["due"] == "") {
            if ($row["highpriority"] == "1") {
                $case = "highpriority";
            } else {
                $case = "normal";
            }
        } else {
            $case = "overdue";
        }
    }
    if ($row["completed"] == "1") {
        $case = "completed";
    }
    switch ($case) {
        case "highpriority":
            echo "<tr class=\"warning\">";
            break;
        case "overdue":
            echo "<tr class=\"error\">";
            break;
        case "completed":
            echo "<tr class=\"success\">";
            break;
        case "normal":
            echo "<tr>";
            break;
    } 
    echo "<td><input name=\"id\" type=\"radio\" value=\"" . $row["id"] . "\"></td>";
    echo "<td class=\"hidden-phone\">" . ucfirst($row["category"]) . "</td>";
    echo "<td>" . $row["task"] . "</td>";
    if ($view == "completed") {
        echo "<td>" . $row["datecompleted"] . "</td>";
    } else {
        echo "<td>" . $row["due"] . "</td>";
    }
    echo "</tr>";
}
echo "</tbody></table>";

?>
<div class="btn-group">
<button id="edit" class="btn">Edit</button>
<button id="delete" class="btn">Delete</button>
<?php
if ($view == "completed") {
    echo "<button id=\"restore\" class=\"btn\">Restore</button>";
}
if ($view == "normal" || $view == "highpriority") {
    echo "<button id=\"complete\" class=\"btn\">Complete</button>";
}
?>
</div>
<br>
<br>
<div class="alert alert-info">   
<strong>Info:</strong> High priority tasks are highlighted yellow, completed tasks green and overdue tasks red.  
</div>
<div class="well">
<?php

$getnumberoftasks = mysql_query("SELECT COUNT(id) FROM Data WHERE completed != \"1\"");
$resultnumberoftasks = mysql_fetch_assoc($getnumberoftasks);
echo "<i class=\"icon-tasks\"></i> <b>" . $resultnumberoftasks["COUNT(id)"] . "</b> tasks<br>";

echo "<i class=\"icon-warning-sign\"></i> <b>$numberoftasksduetoday</b> due today<br>";

echo "<i class=\"icon-exclamation-sign\"></i> <b>$numberoftasksoverdue</b> overdue";

mysql_close($con);

?>
</div>
<hr>
<p class="muted pull-right">Burden <?php echo $version; ?> &copy; <a href="http://github.com/joshf" target="_blank">Josh Fradley</a> <?php echo date("Y"); ?>. Themed by <a href="http://twitter.github.com/bootstrap/" target="_blank">Bootstrap</a>.</p>
</div>
<!-- Content end -->
</html>