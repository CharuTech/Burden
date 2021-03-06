<?php

//Burden, Copyright Josh Fradley (http://github.com/joshf/Burden)

require_once("assets/version.php");

if (!file_exists("config.php")) {
    header("Location: install");
    exit;
}

require_once("config.php");

session_start();
if (!isset($_SESSION["burden_user"])) {
    header("Location: login.php");
    exit;
}

//Set cookie so we dont constantly check for updates
setcookie("burdenupdatecheck", time(), time()+3600*24*7);

//Connect to database
@$con = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if (mysqli_connect_errno()) {
    die("Error: Could not connect to database (" . mysqli_connect_error() . "). Check your database settings are correct.");
}

$getusersettings = mysqli_query($con, "SELECT `user` FROM `Users` WHERE `id` = \"" . $_SESSION["burden_user"] . "\"");
if (mysqli_num_rows($getusersettings) == 0) {
    session_destroy();
    header("Location: login.php");
    exit;
}
$resultgetusersettings = mysqli_fetch_assoc($getusersettings);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimal-ui">
<title>Burden</title>
<link rel="apple-touch-icon" href="assets/icon.png">
<link href="assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/bootstrap-datepicker/css/bootstrap-datepicker3.min.css" rel="stylesheet">
<link href="assets/bootstrap-notify/css/bootstrap-notify.min.css" rel="stylesheet">
<style type="text/css">
.datepicker {
    z-index:1151 !important;
}
body {
    padding-top: 30px;
    padding-bottom: 30px;
}
a.close.pull-right {
    padding-left: 10px;
}
.complete, .edit, #doaddcategoryforaddform, #doaddcategoryforeditform, .restore, .delete, .details {
    cursor: pointer;
}
</style>
<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
<script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
<![endif]-->
</head>
<body>
<nav class="navbar navbar-default navbar-fixed-top" role="navigation">
<div class="container">
<div class="navbar-header">
<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse">
<span class="sr-only">Toggle navigation</span>
<span class="icon-bar"></span>
<span class="icon-bar"></span>
<span class="icon-bar"></span>
</button>
<a class="navbar-brand" href="index.php">Burden</a>
</div>
<div class="navbar-collapse collapse" id="navbar-collapse">
<div class="navbar-form navbar-left" role="search">
<div class="form-group">
<input type="text" class="form-control" id="search" placeholder="Search tasks">
</div>
</div>
<ul class="nav navbar-nav navbar-right">
<li class="dropdown">
<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">Filters <span class="caret"></span></a>
<ul class="dropdown-menu" role="menu">
<li><a href="index.php?filter=highpriority">High Priority Tasks</a></li>
<li><a href="index.php?filter=completed">Completed Tasks</a></li>
<li><a href="index.php?filter=duetoday">Due Today</a></li>
<li class="divider"></li>
<li class="dropdown-header">Categories</li>
<?php

//Get categories
$getcategories = mysqli_query($con, "SELECT DISTINCT(category) FROM `Data` WHERE `category` != \"\" AND `completed` != \"1\"");

while($row = mysqli_fetch_assoc($getcategories)) {
    echo "<li><a href=\"index.php?filter=categories&amp;cat=" . $row["category"] . "\">" . ucfirst($row["category"]) . "</a></li>";
}

?>
<li class="divider"></li>
<li class="dropdown-header">Sort</li>
<li><a href="index.php?filter=date">Due Date</a></li>
<li class="divider"></li>
<li><a href="index.php">Clear Filters</a></li>
</ul>
</li>
<li class="dropdown">
<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><?php echo $resultgetusersettings["user"]; ?> <span class="caret"></span></a>
<ul class="dropdown-menu" role="menu">
<li><a href="settings.php">Settings</a></li>
<li><a href="logout.php">Logout</a></li>
</ul>
</li>
</ul>
</div>
</div>
</nav>
<div class="container">
<div class="page-header">
<h1>Tasks
<?php

if (isset($_GET["filter"])) {
    $filter = mysqli_real_escape_string($con, $_GET["filter"]);
    //Prevent bad strings from messing with sorting
    $filters = array("categories", "normal", "highpriority", "completed", "date", "duetoday");
    if (!in_array($filter, $filters)) {
        $filter = "normal";
    }
    //Make sure cat exists
	if ($filter == "categories") {
		if (isset($_GET["cat"])) {
		    $cat = mysqli_real_escape_string($con, $_GET["cat"]);
		    $checkcatexists = mysqli_query($con, "SELECT `category` FROM `Data` WHERE `category` = \"$cat\"");
		    if (mysqli_num_rows($checkcatexists) == 0) {
		        $filter = "normal";
		    }
		} else {
			$filter = "normal";
		}
	}
} else {
    $filter = "normal";
}

if ($filter == "completed") {
    echo "<small>Completed</small>";
} elseif ($filter == "highpriority") {
    echo "<small>High Priority</small>";
} elseif ($filter == "categories") {
    if ($cat != "none") {
        echo "<small>$cat</small>";
    } else {
        echo "<small>No category</small>";
    }
} elseif ($filter == "date") {
    echo "<small>Sorted By Date</small>";
} elseif ($filter == "normal") {
    echo "<small>Current</small>";
} elseif ($filter == "duetoday") {
    echo "<small>Due Today</small>";
}
echo "</h1></div><div class=\"notifications top-right\"></div>";

echo "<noscript><div class=\"alert alert-info\"><h4 class=\"alert-heading\">Information</h4><p>Please enable JavaScript to use Burden. For instructions on how to do this, see <a href=\"http://www.activatejavascript.org\" class=\"alert-link\" target=\"_blank\">here</a>.</p></div></noscript>";

//Update checking
if (!isset($_COOKIE["burdenupdatecheck"])) {
    $remoteversion = file_get_contents("https://raw.github.com/joshf/Burden/master/version.txt");
    if (version_compare($version, $remoteversion) < 0) {
        echo "<div class=\"alert alert-warning\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button><h4 class=\"alert-heading\">Update</h4><p>Burden <a href=\"https://github.com/joshf/Burden/releases/$remoteversion\" class=\"alert-link\" target=\"_blank\">$remoteversion</a> is available. <a href=\"https://github.com/joshf/Burden#updating\" class=\"alert-link\" target=\"_blank\">Click here for instructions on how to update</a>.</p></div>";
    }
}

if ($filter == "completed") {
    $gettasks = mysqli_query($con, "SELECT * FROM `Data` WHERE `completed` = \"1\"");
} elseif ($filter == "highpriority") {
    $gettasks = mysqli_query($con, "SELECT * FROM `Data` WHERE `highpriority` = \"1\" AND `completed` = \"0\"");
} elseif ($filter == "categories") {
	$gettasks = mysqli_query($con, "SELECT * FROM `Data` WHERE `completed` = \"0\" AND `category` = \"$cat\"");
} elseif ($filter == "date") {
	$gettasks = mysqli_query($con, "SELECT * FROM `Data` WHERE `completed` = \"0\" ORDER BY `due` ASC");
} elseif ($filter == "duetoday") {
    $gettasks = mysqli_query($con, "SELECT * FROM `Data` WHERE `completed` = \"0\" AND `due` = CURDATE()");
} else {
    $gettasks = mysqli_query($con, "SELECT * FROM `Data` WHERE `completed` = \"0\"");
}

//Set counters to zero
$numberoftasks = "0";
$numberoftasksoverdue = "0";
$numberoftasksduetoday = "0";

echo "<ul class=\"list-group\">";
if (mysqli_num_rows($gettasks) != 0) {
    while($row = mysqli_fetch_assoc($gettasks)) {
        //Count tasks
        $numberoftasks++;
        //Logic
        $today = strtotime(date("Y-m-d"));
        $due = strtotime($row["due"]);
        //Counters
        if ($today > $due) {
            $numberoftasksoverdue++;
        }
        if ($today == $due) {
            $numberoftasksduetoday++;
        }
        //Set cases
        if ($row["highpriority"] == "0" && $row["completed"] != "1" && $today < $due || $today == $due) {
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
        if ($today == $due) {
            $case = "duetoday";
        }
        if ($row["completed"] == "1") {
            $case = "completed";
        }
        switch ($case) {
            case "highpriority":
                $label = "warning";
                break;
            case "overdue":
            $label = "danger";
                break;
            case "completed":
            $label = "success";
                break;
            case "normal":
            $label = "default";
                break;
            case "duetoday":
            $label = "info";
                break;
        }
        if ($filter == "completed") {
            $segments = explode("-", $row["datecompleted"]);
            if (count($segments) == 3) {
                list($year, $month, $day) = $segments;
            }
            $date = "$day-$month-$year";
        } else {
            $segments = explode("-", $row["due"]);
            if (count($segments) == 3) {
                list($year, $month, $day) = $segments;
            }
            $date = "$day-$month-$year";
        }
        echo "<li class=\"list-group-item\" id=\"" . $row["id"] . "\"><span class=\"details\" data-id=\"" . $row["id"] . "\">" . $row["task"] . "</span><div class=\"pull-right\">";
        if ($row["category"] != "none") {
            echo "<a href=\"?filter=categories&amp;cat=" . $row["category"] . "\"><span class=\"hidden-xs label label-primary\" data-id=\"" . $row["category"] . "\">" . $row["category"] . "</span></a> ";
        } 
        echo "<span class=\"label label-$label\" data-id=\"" . $row["id"] . "\">" . $date . "</span> ";
        
        if ($filter == "completed") {
            echo "<span class=\"delete glyphicon glyphicon-trash\" data-id=\"" . $row["id"] . "\"></span> ";
        } else {
            echo "<span class=\"edit glyphicon glyphicon-edit\" data-id=\"" . $row["id"] . "\"></span> ";
        }
        
        if ($filter == "completed") {
            echo "<span class=\"restore glyphicon glyphicon-repeat\" data-id=\"" . $row["id"] . "\"></span>";
        } else {
            echo "<span class=\"complete glyphicon glyphicon-ok\" data-id=\"" . $row["id"] . "\"></span>";
        }
        echo "</div></li>";
    }
} else {
    echo "<li class=\"list-group-item\">No tasks to show</li>";
}
echo "</ul>";

?>
<button type="button" id="launchaddmodal" class="btn btn-default">Add</button><br><br>
<?php

echo "<i class=\"glyphicon glyphicon-tasks\"></i> <b>$numberoftasks</b> tasks<br><i class=\"glyphicon glyphicon-warning-sign\"></i> <b>$numberoftasksduetoday</b> due today<br><i class=\"glyphicon glyphicon-exclamation-sign\"></i> <b>$numberoftasksoverdue</b> overdue";

?>

<!-- Add form -->
<div class="modal fade" id="addformmodal" tabindex="-1" role="dialog" aria-labelledby="addformmodal" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
<h4 class="modal-title" id="addformmodaltitle">Add Task</h4>
</div>
<div class="modal-body">
<form id="addform" role="form" autocomplete="off">
<div class="form-group">
<input type="text" class="form-control" id="task" name="task" placeholder="Type a task..." required autofocus>
</div>
<div class="form-group">
<textarea rows="2" class="form-control" id="details" name="details" placeholder="Type any extra details.."></textarea>
</div>
<div class="form-group">
<input type="date" class="due form-control" id="due" name="due" required>
</div>
<div id="categoryselectforaddform" class="form-group">
<select class="form-control" id="category" name="category">
<?php

//Don't duplicate none entry
$doesnoneexist = mysqli_query($con, "SELECT `category` FROM `Data` WHERE `category` = \"none\"");
if (mysqli_num_rows($doesnoneexist) == 0) {
    echo "<option value=\"none\">None</option>";
}

//Get categories
$getcategories = mysqli_query($con, "SELECT DISTINCT(category) FROM `Data` WHERE `category` != \"\"");

while($row = mysqli_fetch_assoc($getcategories)) {
        echo "<option value=\"" . $row["category"] . "\">" . ucfirst($row["category"]) . "</option>";
}

?>
</select>
<span class="help-block"><button type="button" id="addcategoryforaddform" class="btn btn-default btn-xs">Add Category</button></span>
</div>
<div id="showcategoryforaddform" style="display: none;" class="form-group ">
<div class="input-group">
<input type="text" class="form-control" id="newcategoryforaddform" name="newcategoryforaddform" placeholder="Type a new category...">
<span id="doaddcategoryforaddform" class="input-group-addon">
<i class="glyphicon glyphicon-plus"></i>
</span>
</div>
</div>
<div class="checkbox">
<label>
<input type="checkbox" id="highpriority" name="highpriority"> High priority</label>
</div>
<input type="hidden" id="action" name="action" value="add">
</form>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
<button type="button" id="add" class="btn btn-primary">Add</button>
</div>
</div>
</div>
</div>
<!-- Add form end -->
<!-- Edit form -->
<div class="modal fade" id="editformmodal" tabindex="-1" role="dialog" aria-labelledby="editformmodal" aria-hidden="true">
<div class="modal-dialog">
<div class="modal-content">
<div class="modal-header">
<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
<h4 class="modal-title" id="editformmodaltitle">Edit Task</h4>
</div>
<div class="modal-body">
<form id="editform" role="form" autocomplete="off">
<div class="form-group">
<input type="text" class="form-control" id="edittask" name="task" placeholder="Type a task..." required>
</div>
<div class="form-group">
<textarea rows="2" class="form-control" id="editdetails" name="details" placeholder="Type any extra details.."></textarea>
</div>
<div class="form-group">
<input type="date" class="due form-control" id="editdue" name="due" required>
</div>
<div id="categoryselectforeditform" class="form-group">
<select class="form-control" id="editcategory" name="category">
<?php

//Don't duplicate none entry
$doesnoneexist = mysqli_query($con, "SELECT `category` FROM `Data` WHERE `category` = \"none\"");
if (mysqli_num_rows($doesnoneexist) == 0) {
    echo "<option value=\"none\">None</option>";
}

//Get categories
$getcategories = mysqli_query($con, "SELECT DISTINCT(category) FROM `Data` WHERE `category` != \"\"");

while($row = mysqli_fetch_assoc($getcategories)) {
        echo "<option value=\"" . $row["category"] . "\">" . ucfirst($row["category"]) . "</option>";
}

mysqli_close($con);

?>
</select>
<span class="help-block"><button type="button" id="addcategoryforeditform" class="btn btn-default btn-xs">Add Category</button></span>
</div>
<div id="showcategoryforeditform" style="display: none;" class="form-group ">
<div class="input-group">
<input type="text" class="form-control" id="newcategoryforeditform" name="newcategoryforeditform" placeholder="Type a new category...">
<span id="doaddcategoryforeditform" class="input-group-addon">
<i class="glyphicon glyphicon-plus"></i>
</span>
</div>
</div>
<div class="checkbox">
<label>
<input type="checkbox" id="edithighpriority" name="highpriority"> High priority</label>
</div>
<input type="hidden" id="editaction" name="action" value="edit">
<input type="hidden" id="editid" name="id"></form>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
<button type="button" id="edit" class="btn btn-primary">Edit</button>
</div>
</div>
</div>
</div>
<!-- Edit form end -->
<hr>
<div class="footer">
Burden <?php echo $version; ?> &copy; <a href="http://joshf.co.uk" target="_blank">Josh Fradley</a> <?php echo date("Y"); ?>. Themed by <a href="http://getbootstrap.com" target="_blank">Bootstrap</a>.
</div>
</div>
<script src="assets/jquery.min.js"></script>
<script src="assets/bootstrap/js/bootstrap.min.js"></script>
<script src="assets/bootstrap-notify/js/bootstrap-notify.min.js"></script>
<script src="assets/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
<script src="assets/nod.min.js"></script>
<script src="assets/modernizr.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {
    /* Search */
    $("#search").keyup(function() {
        $("#search-error").remove();
        var filter = $(this).val();
        var count = 0;
        $(".list-group .list-group-item").each(function() {
            if ($(this).text().search(new RegExp(filter, "i")) < 0) {
                $(this).hide();
            } else {
                $(this).show();
                count++;
            }            
        });
        if (count === 0) {
            $(".list-group").prepend("<li class=\"list-group-item\" id=\"search-error\">No tasks found</li>");
        }
        document.title = "Burden (" + count + ")";
    });
    /* End */
    /* Set Up Notifications */
    var show_notification = function(type, icon, text, reload) {
        $(".top-right").notify({
            type: type,
            transition: "fade",
            icon: icon,
            message: {
                text: text
            },
            onClosed: function() {
                if (reload == true) {
                    window.location.reload();
                }
            }
        }).show();
    };
    /* End */
    /* Form Overrides */
    $("#addformmodal").on("keypress", function(e) {
        if (e.keyCode === 13) {
            e.preventDefault();
            $("#add").trigger("click");
        }
    });
    $("#editformmodal").on("keypress", function(e) {
        if (e.keyCode === 13) {
            e.preventDefault();
            $("#edit").trigger("click");
        }
    });
    /* End */
    /* Datepicker */
    if (!Modernizr.inputtypes.date) {
        $(".due").datepicker({
            format: "dd-mm-yyyy",
            autoclose: "true",
            todayHighlight: "true"
        });
    }
    /* End */
    /* Add Category */
    /* Add */
    $("#addcategoryforaddform").click(function() {
        $("#categoryselectforaddform").hide();
        $("#showcategoryforaddform").show();
    });
    $("#doaddcategoryforaddform").click(function() {
        newcategory=$("#newcategoryforaddform").val()
        if (newcategory != null && newcategory != "") {
            $("#addform select").append("<option value=\"" + newcategory + "\" selected=\"selected\">" + newcategory + "</option>");
        }
        $("#newcategoryforaddform").val("")
        $("#categoryselectforaddform").show();
        $("#showcategoryforaddform").hide();
    });
    /* Edit */
    $("#addcategoryforeditform").click(function() {
        $("#categoryselectforeditform").hide();
        $("#showcategoryforeditform").show();
    });
    $("#doaddcategoryforeditform").click(function() {
        newcategory=$("#newcategoryforeditform").val()
        if (newcategory != null && newcategory != "") {
            $("#editform select").append("<option value=\"" + newcategory + "\" selected=\"selected\">" + newcategory + "</option>");
        }
        $("#newcategoryforeditform").val("")
        $("#categoryselectforeditform").show();
        $("#showcategoryforeditform").hide();
    });
    /* End */
    /* Add */
    $("#launchaddmodal").click(function() {
        $("#addformmodal").modal();
        var addval = nod();  
        addval.configure({
            submit: "#add",
            disableSubmit: true,
            delay: 1000,
            parentClass: "form-group",
            successClass: "has-success",
            errorClass: "has-error",
            successMessageClass: "text-success",
            errorMessageClass: "text-danger"
        });
        addval.add([{
            selector: "#task",
            validate: "presence",
            errorMessage: "Task cannot be empty!"
        }, {
            selector: "#due",
            validate: "presence",
            errorMessage: "A due date is required (DD-MM-YYYY)!"
        
        }]);
    });
    $("#add").click(function() {
        $.ajax({
            type: "POST",
            url: "worker.php",
            data: $("#addform").serialize(),
            error: function() {
                show_notification("danger", "warning-sign", "Ajax query failed!");
            },
            success: function() {
                show_notification("success", "ok", "Task added!", true);
                $("#addformmodal").modal("hide");
            }
        });
    });
    /* End */
    /* Edit */
    $("li").on("click", ".edit", function() {
        var id = $(this).data("id");
        $.ajax({
            type: "POST",
            dataType: "json",
            url: "worker.php",
            data: "action=details&id="+ id +"",
            error: function() {
                show_notification("danger", "warning-sign", "Ajax query failed!");
            },
            success: function(data) {
                /* Stop auto checked */
                $("#edithighpriority").prop("checked", false);
                $("#edittask").val(data[0]);
                $("#editdetails").val(data[1]);
                if (!Modernizr.inputtypes.date) {
                    raw = data[2].split("-");
                    date = raw[2]+"-"+raw[1]+"-"+raw[0];
                    $("#editdue").val(date);
                } else {
                    $("#editdue").val(data[2]);
                }
                if (data[3] != "") {
                    $("#editcategory").val(data[3]);
                } else {
                    $("#editcategory").val("none"); 
                }
                if (data[4] == "1") {
                    $("#edithighpriority").prop("checked", true);
                }
                $("#editid").val(id);
            }
        });        
        $("#editformmodal").modal();
        var editval = nod();
        editval.configure({
            submit: "#edit",
            disableSubmit: true,
            delay: 10,
            parentClass: "form-group",
            successClass: "has-success",
            errorClass: "has-error",
            successMessageClass: "text-success",
            errorMessageClass: "text-danger"
        });
        editval.add([{
            selector: "#edittask",
            validate: "presence",
            errorMessage: "Task cannot be empty!",
            defaultStatus: "valid"
        }, {
            selector: "#editdue",
            validate: "presence",
            errorMessage: "A due date is required (DD-MM-YYYY)!",
            defaultStatus: "valid"
        }]);        
    });
    $("#edit").click(function() {
        $.ajax({
            type: "POST",
            url: "worker.php",
            data: $("#editform").serialize(),
            error: function() {
                show_notification("danger", "warning-sign", "Ajax query failed!");
            },
            success: function() {
                show_notification("success", "ok", "Task edited!", true);
                $("#editformmodal").modal("hide");
            }
        });
    });
    /* End */
    /* Complete */
    $("li").on("click", ".complete", function() {
        var id = $(this).data("id");
        $.ajax({
            type: "POST",
            url: "worker.php",
            data: "action=complete&id="+ id +"",
            error: function() {
                show_notification("danger", "warning-sign", "Ajax query failed!");
            },
            success: function() {
                show_notification("success", "ok", "Task marked as completed!", true);
            }
        });
    });
    /* End */
    /* Details */
    $("li").on("click", ".details", function() {
        var id = $(this).data("id");
        if ($("#detailsitem"+id).length) {
            $("#detailsitem"+id).hide("fast");
            setTimeout(function() {
                $("#detailsitem"+id).remove();
            }, 400);            
            return false;
        }
        $.ajax({
            type: "POST",
            dataType: "json",
            url: "worker.php",
            data: "action=details&id="+ id +"",
            error: function() {
                show_notification("danger", "warning-sign", "Ajax query failed!");
            },
            success: function(data) {
                if (data[1] == "") {
                    data[1] = "<i>No details available</i>";
                }
                raw = data[5].split("-");
                created = raw[2]+"-"+raw[1]+"-"+raw[0];                
                $("#"+id).append("<div id=\"detailsitem"+ id +"\" style=\"display: none;\"><p><dl><dt>Details</dt><dd>" + data[1] +  "</dd><dt>Due</dt><dd>" + data[6] +  "</dd><dt>Created</dt><dd>" + created +  "</dd></dl></p></div>");
                $("#detailsitem"+id).show("fast");
            }
        });
    });
    /* End */
    /* Restore */
    $("li").on("click", ".restore", function() {
        var id = $(this).data("id");
        $.ajax({
            type: "POST",
            url: "worker.php",
            data: "action=restore&id="+ id +"",
            error: function() {
                show_notification("danger", "warning-sign", "Ajax query failed!");
            },
            success: function() {
                show_notification("success", "ok", "Task restored!", true);
            }
        });
    });
    /* End */
    /* Delete */
    $("li").on("click", ".delete", function() {
        var id = $(this).data("id");
        $.ajax({
            type: "POST",
            url: "worker.php",
            data: "action=delete&id="+ id +"",
            error: function() {
                show_notification("danger", "warning-sign", "Ajax query failed!");
            },
            success: function() {
                show_notification("success", "ok", "Task deleted!", true);
            }
        });
    });
    /* End */
    /* Update Title */
    document.title = "Burden (<?php echo $numberoftasks; ?>)";
    /* End */
});
</script>
</body>
</html>
