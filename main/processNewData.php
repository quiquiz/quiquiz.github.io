<?php
if(file_exists("no_github/__connect_to_db.php"))
{
    require("no_github/__connect_to_db.php");
}
else
{
    require("../no_github/__connect_to_db.php");        
}
session_start();
$errorstr = "";
if( isset($_GET['qid']) && isset($_GET['res']) )
{	
	
    $URLcode = $_GET['qid'];
    $quizResults = $_GET['res'];

    $_SESSION['quizComplete'] = true;

    //create our new table entry 

    $conn = db_connect();
    
    if(mysqli_connect_errno())
    {
        die("db conn failed: " . mysqli_connect_error() . "(" . mysqli_connect_errno() . ")");
        $errorstr .= "Could not connect to database. <br/>";
    }

    $query  = "SELECT * FROM quizzes WHERE url_code = '{$URLcode}';";
    $result = $conn->query($query);
    $quiz = null;
    if($result->num_rows == 1)
    {
        //good to go
        $quiz = $result->fetch_assoc();
    }
    else
    {
    	$errorstr .= "Incorrect number of rows affected. <br/>";
    }
    
    if(!$quiz)
    {
    	$errorstr .= "Associative array was not populated. <br/>";
    }

    $currentResults = $quiz['results'];
    $currentResultsArr = array();

    for($i = 0; $i < 8; $i++)
    {
        $commaIdx = strpos($currentResults, ',');
        array_push($currentResultsArr, intval(substr($currentResults, 0, $commaIdx)));
        $currentResults = substr($currentResults, $commaIdx+1);
    }

    $quizResultsArr = array();
    for($i = 0; $i < 8; $i++)
    {
        array_push($quizResultsArr, intval($quizResults[$i]));
    }

    $finalResultsArr = array();
    for($i = 0; $i < 8; $i++)
    {
        array_push($finalResultsArr, $quizResultsArr[$i] + $currentResultsArr[$i]);
    }

    $finalResultsStr = "";
    for($i = 0; $i < 8; $i++)
    {
        $finalResultsStr .= $finalResultsArr[$i];
        if($i == 7){break;}
        $finalResultsStr .= ",";
    }

    $query = "UPDATE quizzes SET results = ? WHERE url_code = ?";
    $statement = $conn->prepare($query);
    if($conn->error){ $errorstr .= $conn->error . "<br/>";}
    $statement->bind_param("ss", $finalResultsStr, $URLcode);
    if($conn->error){ $errorstr .= $conn->error . "<br/>";}
    $statement->execute();
    if($conn->error){ $errorstr .= $conn->error . "<br/>";}
    $statement->close();
    $conn->close();

    if($errorstr == "")
    {
        //if there's no error, go back to quiz.php
    	header("Location: quiz.php?qid=" . $URLcode);
        exit;
    }
    else
    {
    	echo $errorstr;
    }
}
else
{
    echo "ERROR: did not receive necessary params";
}




?>