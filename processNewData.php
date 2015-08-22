<?php
session_start();
if( isset($_GET['qid']) && isset($_GET['res']) )
{
    $URLcode = $_GET['qid'];
    $quizResults = $_GET['res'];

    $_SESSION['quizComplete'] = true;

    //create our new table entry 
    $dbhost = "localhost";
    $dbuser = "quiquiz_manager";
    $dbpass = "quiquizletmein";
    $dbname = "quiquiz";
    $conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

    if(mysqli_connect_errno())
    {
        die("db conn failed: " . mysqli_connect_error() . "(" . mysqli_connect_errno() . ")");
    }

    $query  = "SELECT * FROM quizzes WHERE url_code = '{$URLcode}';";
    $result = $conn->query($query);
    $quiz = null;
    if($result->num_rows == 1)
    {
        //good to go
        $quiz = $result->fetch_assoc();
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
    $statement->bind_param("ss", $finalResultsStr, $URLcode);
    $statement->execute();
    $statement->close();
    $conn->close();

    echo "success";
}
else
{
    echo "ERROR: did not receive necessary params";
}




?>