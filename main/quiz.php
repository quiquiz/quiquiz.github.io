<?php
if(file_exists("no_github/__connect_to_db.php"))
{
    require("no_github/__connect_to_db.php");
}
else
{
    require("../no_github/__connect_to_db.php");        
}

    error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
    session_start();

    $URLcode = isset($_GET['qid']) ? $_GET['qid'] : null;
    if ($URLcode == null)
    {
        //just reroute back to the main page.
        header("Location: index.php");
        exit;        
    }

    //connect + verify
    $conn = db_connect();
    if(mysqli_connect_errno()){die("db conn failed: " . mysqli_connect_error() . "(" . mysqli_connect_errno() . ")");}

    $query  = "SELECT * FROM quizzes WHERE url_code = '{$URLcode}';";
    $result = $conn->query($query);
    $quiz = null;
    if($result->num_rows == 1)
    {
        $quiz = $result->fetch_assoc();
    }    
    if($quiz == null){die("Error occurred when accessing the database.");}

    $restrict = $quiz['ip_restrict'];
    $multiple = $quiz['multiple'];
    $question = $quiz['question'];
    $currentIps = $quiz['ip_list'];

    $answers = array();
    $answerVisible = array(0,0,0,0,0,0,0,0); //0 if it should be hidden, 1 if it should be shown
    
    //fill the answers array and, if the current value is not null, set the visible tag to 1
    for($i = 0; $i < 8; $i++)
    {
        $ansIdx = $i + 1;
        $ansAssoc = "answer{$ansIdx}";
        array_push($answers, $quiz[$ansAssoc]);
        if($answers[$i] == " " || $answers[$i] == "" || $answers[$i] == null)
        {
            $answers[$i] = null;
            $answerVisible[$i] = 0;
        }
        else
        {
            $answerVisible[$i] = 1;
        }
    }

    $answersVisibleStr = "";
    for($i = 0; $i < 8; $i++)
    {
        if($answerVisible[$i] == 0)
        {
            $answersVisibleStr .= "0";
        }
        else{
            $answersVisibleStr .= "1";
        }

        if($i < 7)
        {
            $answersVisibleStr .= ",";
        }
    }
    
    //get user's ip address
    $userIpAddress = $_SERVER['REMOTE_ADDR'] ? $_SERVER['REMOTE_ADDR'] : '-1.-1.-1.-1';

    $uniqueIp = null;
    if(strpos($currentIps, $userIpAddress) === false)
    {
        $uniqueIp = true;
        $newIpList = $currentIps . "," . $userIpAddress;
        $query = "UPDATE quizzes SET ip_list = ? WHERE url_code = ?;";
        $statement = $conn->prepare($query);
        $statement->bind_param("ss", $newIpList, $URLcode);
        $statement->execute();
        $statement->close();
    }
    else
    {
        $uniqueIp = false;
    }

    $currentResults = $quiz['results'];
    $conn->close();


?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $question; ?></title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <link rel="stylesheet" type="text/css" href="css/pages/quiz.css">
</head>
<body>
<div class="mainWrapper">
<div class="quizWrapper">
    

    <div class="contentWrapper">

        <div class="quiz">
        <h2><?php echo $question; ?></h2><br/>
            <div <?php if($answerVisible[0] == 0){echo "class='deleteme'";} ?> ><input id="sel1" type="<?php if($multiple == 'y'){echo 'checkbox';}else{echo 'radio';} ?>" name="tick" value="1"/> <span><?php if($answerVisible[0] == 1){echo $answers[0];} ?></span><br /></div><br />
            <div <?php if($answerVisible[1] == 0){echo "class='deleteme'";} ?> ><input id="sel2" type="<?php if($multiple == 'y'){echo 'checkbox';}else{echo 'radio';} ?>" name="tick" value="2"/> <span><?php if($answerVisible[1] == 1){echo $answers[1];} ?></span><br /></div><br />
            <div <?php if($answerVisible[2] == 0){echo "class='deleteme'";} ?> ><input id="sel3" type="<?php if($multiple == 'y'){echo 'checkbox';}else{echo 'radio';} ?>" name="tick" value="3"/> <span><?php if($answerVisible[2] == 1){echo $answers[2];} ?></span><br /></div><br />
            <div <?php if($answerVisible[3] == 0){echo "class='deleteme'";} ?> ><input id="sel4" type="<?php if($multiple == 'y'){echo 'checkbox';}else{echo 'radio';} ?>" name="tick" value="4"/> <span><?php if($answerVisible[3] == 1){echo $answers[3];} ?></span><br /></div><br />
            <div <?php if($answerVisible[4] == 0){echo "class='deleteme'";} ?> ><input id="sel5" type="<?php if($multiple == 'y'){echo 'checkbox';}else{echo 'radio';} ?>" name="tick" value="5"/> <span><?php if($answerVisible[4] == 1){echo $answers[4];} ?></span><br /></div><br />
            <div <?php if($answerVisible[5] == 0){echo "class='deleteme'";} ?> ><input id="sel6" type="<?php if($multiple == 'y'){echo 'checkbox';}else{echo 'radio';} ?>" name="tick" value="6"/> <span><?php if($answerVisible[5] == 1){echo $answers[5];} ?></span><br /></div><br />
            <div <?php if($answerVisible[6] == 0){echo "class='deleteme'";} ?> ><input id="sel7" type="<?php if($multiple == 'y'){echo 'checkbox';}else{echo 'radio';} ?>" name="tick" value="7"/> <span><?php if($answerVisible[6] == 1){echo $answers[6];} ?></span><br /></div><br />
            <div <?php if($answerVisible[7] == 0){echo "class='deleteme'";} ?> ><input id="sel8" type="<?php if($multiple == 'y'){echo 'checkbox';}else{echo 'radio';} ?>" name="tick" value="8"/> <span><?php if($answerVisible[7] == 1){echo $answers[7];} ?></span><br /></div><br />
            <button class="submitQuizResults" onclick="processQuizResults()">Submit</button><br />

        </div>
    </div>   

</div>
</div>


</body>
</html>



<!-- JAVASCRIPT/JQUERY BEGIN -->
<script>

    ///////////////////////////////////////////
    //  switchToResults()
    function switchToResults(res, vis, ans, per)
    {
        //remove the quiz
        $(".quiz").remove();
        $(".quizWrapper").toggleClass("quizWrapper");
        $(".contentWrapper").append("<div class='results'></div>");
        $(".results").append("<h2><?php echo $question; ?></h2>");
        
        html = "";
        for (var i = 0; i < 8; i++)
        {
            var thisRes = "";
            var thisAns = "";
            var visible = true;

            if (vis[i] == 1)
            {
                thisRes = res[i].toString();
                thisAns = ans[i];
            }
            else
            {
                visible = false;
            }

            if (i == 0) { html += "<ul>"; }

            if (visible) {
                //start this list item
                html += "<li class='resultsListItem'>";

                //print the answer
                html += "   <div class='answerDiv'>";
                html += ans[i];
                html += "   </div>";

                //this bar gets modified by other code and changes width depending on the results
                html += "   <div class='histogramBar'>";
                html += "   </div>";

                //print the results for this answer
                html += "   <div class='resultDiv'>";
                html += per[i].toFixed(1) + "%";
                html += "&nbsp";
                html += "(" + thisRes + ")";
                html += "   </div>";

                html += "</li>";
            }

            if (i == 7) { html += "</ul>"; }
            
        }
        $(".results").append(html);

        var numberOfValidAnswers = 0;
        for (var i = 0; i < 8; i++)
        {
            numberOfValidAnswers += vis[i];
        }
        
        for(var i = 0; i < numberOfValidAnswers; i ++)
        {            
            var thisEle = ".histogramBar:eq(" + i + ")";
            var thisPercentage = parseInt(per[i]);
            var thisWidth = 10 + (thisPercentage * 2.4);
            var thisWidthStr = thisWidth.toString() + "px";
            $(thisEle).width(thisWidthStr);
        }        
    }
    
    ///////////////////////////////////////////
    //  setCookie()
    function setCookie(cname, cvalue, exdays) {
        var d = new Date();
        d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
        var expires = "expires=" + d.toUTCString();
        document.cookie = cname + "=" + cvalue + "; " + expires;
    }

    ///////////////////////////////////////////
    //  getCookie()
    function getCookie(urlcode)
    {
        var cookielist = document.cookie;
        if(cookielist.indexOf(urlcode) == -1)
        {
            return false; //cookie not found
        }
        else
        {
            return true; //cookie found
        }
    }

    ///////////////////////////////////////////
    //  processQuizResults()
    function processQuizResults()
    {
        console.log("Processing quiz results...");

        var selections = [];
        for (var i = 1; i <= 8; i++)
        {
            var thestr = "#sel" + i;
            $(thestr).is(':checked') ? selections.push(1) : selections.push(0);
        }

        var URLcode = '<?php echo $URLcode; ?>';
        var ajaxURL = "processNewData.php?qid=" + URLcode + "&res=";
        for (var i = 0; i < 8; i++)
        {
            ajaxURL += selections[i];
        }

        //create a cookie for this user
        setCookie("quiquiz", URLcode, 1);

        //go to processNewData.php
        window.location.replace(ajaxURL);
    }

    ///////////////////////////////////////////
    //  DOCUMENT READY
    ///////////////////////////////////////////
    $(document).ready(function () {
        $('div.deleteme').remove();

        //okay, now the page is loaded.  to the flow chart!
        var unique = "<?php echo $uniqueIp; ?>";
        var results = "<?php echo $currentResults; ?>";
        var ipRestrict = "<?php echo $restrict; ?>";
        var URLcode = "<?php echo $URLcode; ?>";
        var answersVisible = "<?php echo $answersVisibleStr ?>";
        var answers = "<?php for($i=0;$i<8;$i++){if($answers[$i] != null){echo $answers[$i];}else{echo " ";} if($i<7){echo '|';} } ?>";

        var resultsArray = [];
        for (var i = 0; i < 8; i++) {
            var idx = results.indexOf(",");
            var tempstr = "";
            idx == -1 ? tempstr = results : tempstr = results.substr(0, idx);
            results = results.substr(idx + 1);
            resultsArray.push(parseInt(tempstr));
        }

        //calculate and construct a percentage array
        var percentagesArray = [];
        var totalVotes = 0;
        for (var i = 0; i < 8; i++)
        {
            totalVotes += resultsArray[i];
        }
        for (var i = 0; i < 8; i++)
        {
            thisPercentage = (resultsArray[i] / totalVotes) * 100;
            percentagesArray.push(thisPercentage);
        }

        var visibleArray = [];
        for (var i = 0; i < 8; i++) {
            var idx = answersVisible.indexOf(",");
            var tempstr = "";
            idx == -1 ? tempstr = answersVisible : tempstr = answersVisible.substr(0, idx);
            answersVisible = answersVisible.substr(idx + 1);
            visibleArray.push(parseInt(tempstr));
        }

        var answersArray = [];
        for (var i = 0; i < 8; i++) {
            var idx = answers.indexOf("|");
            var tempstr = "";
            idx == -1 ? tempstr = answers : tempstr = answers.substr(0, idx);
            answers = answers.substr(idx + 1);
            answersArray.push(tempstr);
        }

        //if we have a repeat ip and we're supposed to prevent IP's from voting more than once, then display the results and delete the quiz
        if(!unique)
        {
            if(ipRestrict == 'y')
            {
                switchToResults(resultsArray, visibleArray, answersArray, percentagesArray);
            }
            else //repeat ip, but no ip restrict
            {
                if(getCookie(URLcode))
                {
                    switchToResults(resultsArray, visibleArray, answersArray, percentagesArray);
                }
            }
        }

        

        


        

    });
</script>
<!-- JAVASCRIPT/JQUERY END -->
