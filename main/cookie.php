<?php

//example url:    .../cookie.php?action=set&name=f6s87gs8gg  --> creates cookie with URLcode as name
//                .../cookie.php?action=get&name=f6s87gs8gg  --> if the cookie exists, return locked. otherwise, return unlocked

if( isset($_GET['action']) && isset($_GET['name']) )
{
    if($_GET['action'] == 'set')
    {
        //set cookie
        $URLcode = $_GET['name'];
        setcookie($URLcode, 'q', time() + 604800); //expires in 7 days
        echo "set";
    }
    else
    {
        //get cookie
        if( isset($_COOKIE[$URLcode]) )
        {
            echo "locked";
        }
        else
        {
            echo "unlocked";
        }
    }

}
else{
    echo "ERROR: incorrect params";
}

?>