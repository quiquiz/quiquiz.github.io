<?php

    if(isset($_GET['err']))
    {
        $theErr = $_GET['err'];
        die($theErr);
    }

    function verifyNoErrors(&$errLst)
    {
        if(count($errLst) > 0)
        {
            //create our error string
            $errorStr = "--- ERROR LIST ---<br/>";
            for($i = 0; $i < count($errLst); $i++)
            {
                $errorStr .= "ERR[" . $i . "] >>> " . $errLst[$i] . "<br/>";
            }

            //reload this page and kill it, displaying the error
            header("Location: error.php?err={$errorStr}");
            exit;
        }
    }

    function reportError($err, &$errLst)
    {
        array_push($errLst, $err);
    }
?>