<?php
//TODO:Known Problems Status:✖
//The admin panel fails when the database have no items (not a empty data file)
//Refresh Button may send duplicate requests to the server.Replay succeeded.
//db_old failed to work properly.

//TODO:Add Functions: ✖
//Generate a random key for public use, which prevents other links, which have simliar tokens, from being spotted!
$GLOBALS["log_text"]="";
$db=unserialize(file_get_contents("db.pdata"));
if(isset($_GET["s"])){
    $tk=$_GET["s"];//URL Token

    if(isset($db["link"][$tk])){
        //Ready for jumping & write data to log
        $db["stat"][$tk]["lastAccess"]=date("Ymd-His");
        $db["stat"][$tk]["triggerCount"]++;
        header("HTTP/1.1 302 Moved Temporarily");
        header("Location:".$db["link"][$tk]);
        logger("DEBUG","incoming new request, token is {".$tk."}");
        gracefullyExit($db);
    }else{
        //Bad Request
        header("HTTP/1.1 400 Bad Request");
        echo "<span style=\"font-size:larger\">Sorry, but it seems that your token in URL ($tk) doesn't refer to a real item in our database.Maybe you could check the spell?</span>";
        logger("INFO","incoming new request but not found, token is {".$tk."}");
        gracefullyExit($db);
    }
}
if(isset($_REQUEST["adm"])){
    //No authenticating methods,TODO.
    switch ($_REQUEST["act"]){
        case "add":{
            $tk=$_REQUEST["token"];
            $link=base64_decode($_REQUEST["link"]);
            $db["stat"][$tk]=["lastAccess"=>"<i>Not yet</i>","triggerCount"=>0];
            $db["link"][$tk]=$link;
            echo "<h2>Successfully added an entry:token is ($tk), link is ($link)</h2>";
            show_admin_panel($db);
            logger("INFO","added new token, tk is {".$tk."},link is {".$link."}");
            gracefullyExit($db,true);
        }break;
        case "del":{
            $tk=$_REQUEST["token"];
            $link=$db["link"][$tk];
            unset($db["stat"][$tk]);
            unset($db["link"][$tk]);
//            header("HTTP/1.1 204 No Content");
            show_admin_panel($db);
            logger("INFO","admin deleted a token, tk is {".$tk."},original-link is {".$link."}");
            gracefullyExit($db,true);
        }break;
//        case "":{
//        }break;
        default:
            show_admin_panel($db);
        break;
    }
}

function show_admin_panel($db){
    //Present a brief introduction of all the links.
//            TODO0:Provide a control panel of them.
    ?>

    <table border="1" style="border-collapse: collapse">
        <tbody>
        <tr><td colspan="5">Overview of all the shortened URLs</td></tr>
        <tr>
            <td>Short Token</td>
            <td>Link</td>
            <td>triggerCount</td>
            <td>lastAccess</td>
            <td>Operations</td>
        </tr>
        <?php foreach($db["link"] as $k=>$v){     ?>
            <tr>
                <!--            <td>--><?//=$k?><!--</td>-->
                <td><a target="_blank" href="https://c.gacenwinl.cn/link/?s=<?=$k?>"><?=$k?></a></td>
                <td class="link_column"><a target="_blank" href="<?=$v?>"><?=$v?></a></td>
                <td><?=$db["stat"][$k]["triggerCount"]?></td>
                <td><?=$db["stat"][$k]["lastAccess"]?></td>
                <td><a href="#" onclick="doDelToken(this)">✖</a></td>
            </tr>
        <?php } ?>
        </tbody>
    </table><hr/>
    <form action="" method="post" style="border:solid 1px gray;padding:10px 20px;">
        <!--    <input type="hidden" name="act" value="add">-->
        Action:<select name="act" id="act_Select">
            <option value="add" selected>➕</option>
            <option value="del" id="act_opt_Del">✖</option>
        </select><br/>
        Token: <input type="text" name="token" id="token"><br/>
        Link Target: <input type="text" name="link"><br/>
        <input type="submit" class="big_btn" onclick="doNewToken(this)" id="form_Key" value="{ Do it !! }">
    </form>
    <button onclick="location.reload()" style="" class="big_btn">-----Refresh-----</button>
    <script>
        function doNewToken(ele){
            let linkEle=ele.previousElementSibling.previousElementSibling;
            linkEle.value=btoa(linkEle.value);
        }
        function doDelToken(ele){
            let ele2=ele.parentElement.previousElementSibling.previousElementSibling.previousElementSibling.previousElementSibling;
            window.token.value=ele2.innerText;
            window.act_opt_Del.selected=true;
            window.form_Key.focus();
            return false;
        }
    </script>
    <style>
        td{
            text-align: center;
            padding:5px;
        }
        .big_btn{
            height:50px;width:200px;
            margin:5px auto;
            font-size:larger;
        }
        .link_column{
            width:250px;
            height:40px;
            font-size:smaller;
            overflow: hidden;
            text-overflow:clip;
            /*word-wrap: break-word;*/
            /*word-break: break-all;*/
            /*white-space: nowrap;*/
            display:-webkit-box;
            -webkit-box-orient:vertical;
            -webkit-line-clamp:2;
        }
    </style>

    <?php
    //End of control panel.
    logger("INFO","admin refreshed the graph.");
    gracefullyExit($db);
}

function logger($type,$text){
    $GLOBALS["log_text"].=sprintf("%s [%-5s] %s\n",date("Ymd His"),$type,$text);
}
function gracefullyExit($db,$isAdmin=false){
    if($isAdmin)file_put_contents("db_old",file_get_contents("db.pdata")."\n".file_get_contents("db_old"));
    file_put_contents("db.pdata",serialize($db));
    file_put_contents("log.txt",$GLOBALS["log_text"].file_get_contents("log.txt"));
    exit;
}