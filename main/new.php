<?php
    require("error.php");
    if(file_exists("no_github/__connect_to_db.php"))
    {
        require("no_github/__connect_to_db.php");
    }
    else
    {
        require("../no_github/__connect_to_db.php");        
    }

    $errors = array();
    error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
    session_start(); //start session so I can use $_SESSION

    //function written by Stephen Watkins, obtained from:        www.stackoverflow.com/questions/4356289/php-random-string-generator
    function generateRandomString($length) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    $_SESSION['created'] = false;

    //generate a link code for this quiz
    $quizURLcode = generateRandomString(10); //don't bother with the .php
    $answersArray = array();
    for($i = 0; $i < 8; $i++)
    {
        $idx = $i + 1;
        $poststr = "ans{$idx}text";
        if(isset($_POST[$poststr]))
        {
            $val = $_POST[$poststr];
            if($val != null && $val != "")
            {
                array_push($answersArray, $val);
            }
            else
            {
                array_push($answersArray, " ");
            }
        }
        else
        {
            array_push($answersArray, " ");
        }
    }
    //default all other required values
    $question = isset($_POST['question']) ? $_POST['question'] : " ";
    $multiple = isset($_POST['checkVSradio']) ? $_POST['checkVSradio'] : " ";
    $ip_restrict = isset($_POST['restrict_ip']) ? $_POST['restrict_ip'] : " ";
    $results = "0,0,0,0,0,0,0,0";
    $iplist = "0.0.0.0";
    //little bit of data reformatting for the database
    $multiple = $multiple == "on" ? "y" : "n";
    $ip_restrict = $ip_restrict == "on" ? "n" : "y";
    if($question != null && $answersArray[0] != null && $answersArray[1] != null && $question != " ")
    {
        $_SESSION['created'] = true;
    }
    if(isset($_SESSION['created']) && $_SESSION['created'] == true)
    {
        //reset so that if they go to make ANOTHER new quiz, the session variable won't block them from doing so
        $_SESSION['created'] = false;

        //connect to database
        $conn = db_connect();

        //verify connect success
        if(!$conn){ reportError("Unable to establish a connection to the database.  Check with the administrator if this problem persists.", $errors); }
        else{
            //build query string
            $query  = "INSERT INTO quizzes (question, answer1, answer2, answer3, answer4, answer5, answer6, answer7, answer8, results, url_code, multiple, ip_restrict, ip_list) ";
            $query .= "VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?);";
            //prepare and execute query
            $statement = $conn->prepare($query);
            if($conn->error){ reportError($conn->error, $errors); }
            $statement->bind_param("ssssssssssssss", $question, $answersArray[0], $answersArray[1], $answersArray[2], $answersArray[3], $answersArray[4], $answersArray[5], $answersArray[6], $answersArray[7], $results, $quizURLcode, $multiple, $ip_restrict, $iplist);
            if($conn->error){ reportError($conn->error, $errors); }
            $statement->execute();
            if($conn->error){ reportError($conn->error, $errors); }
            $statement->close();
            $conn->close();
        }

        //stop execution if any page errors occurred
        verifyNoErrors($errors);
        
        //redirect to this page, but now with a urlcode url parameter
        header("Location: quiz.php?qid=" . $quizURLcode);
        exit;
    }
?>
<!DOCTYPE html>
<html>
<head>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <link rel="stylesheet" type="text/css" href="css/pages/new.css">
    <title>New Quiquiz</title>
</head>
<body>
    <div class="mainWrapper">
        <div class="newWrapper">

            <h1>Quiquiz</h1>

            <form id="newform" method="post" action="new.php">
                <div class="textboxContainer">
                    <input id="question" type="text" name="question" placeholder="Question" /><br /><br />
                    <input type="text" name="ans1text" placeholder="Answer" value=""/><br />
                    <input type="text" name="ans2text" placeholder="Answer" value=""/><br />
                    <input type="text" name="ans3text" placeholder="Answer" value=""/><br />
                    <input type="text" name="ans4text" placeholder="Answer" value=""/><br />
                </div><br /><br />

                <div class="doubleButtonContainer">
                <button type="button" class="moreAnswersButton" name="more_answers" onclick="showAllAnswers()">More...</button>
                <input type="submit" class="button" name="submit" value="Finish" /><br />
               </div>

                <input type="checkbox" name="checkVSradio" id="checkVSradio"/> <!-- using javascript, check this value and change the form input types to what is selected -->
                <label style="cursor: pointer;" for="checkVSradio">Allow users to make multiple selections?</label>

                <br />

                <input type="checkbox" name="restrict_ip" id="restrict_ip"/> <!-- using javascript, check this value and change the form input types to what is selected -->
                <label style="cursor: pointer;" for="restrict_ip">Allow multiple submissions from the same IP address?</label>
            </form>

        </div>
        <?php require('__footer.php'); ?>
    </div>
    <script>
        function showAllAnswers()
        {
            $(".newWrapper").css("height", "90%");
            $(".textboxContainer").append("<input type='text' name='ans5text' placeholder='Answer' value=''/><br />");
            $(".textboxContainer").append("<input type='text' name='ans6text' placeholder='Answer' value=''/><br />");
            $(".textboxContainer").append("<input type='text' name='ans7text' placeholder='Answer' value=''/><br />");
            $(".textboxContainer").append("<input type='text' name='ans8text' placeholder='Answer' value=''/><br />");
            $(".moreAnswersButton").remove();
        }
    </script>
</body>
</html>