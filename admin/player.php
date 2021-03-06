<?php
session_start ();
require_once "../config.php";
// DEBUG CHECK
if ($debug=="1"){
error_reporting(E_ALL); 
ini_set('display_errors', 1);
}
require_once PROJECT_PATH."/lang/de.php";
require_once PROJECT_PATH."/include/db.php";
require_once PROJECT_PATH."/admin/include/function_html_basic_admin.php";
loggedin();
//START RELOAD AREA
if (!empty($_POST)){
    $error_user = array();
    // REMOVE USER
    if ($_POST['type'] == "delete"){
        $edit_player_id = intval($_POST['id']);
        $remove_player = mysql_query("DELETE FROM players WHERE uid = '".$edit_player_id."'");
            if(!$remove_player) {
                echo "Error: ".mysql_error()."<br>"; 
                exit();        
            }
    }
}
//END RELOAD AREA

//PAGGINATION
$page = 1;

//GET NUMBERS OF RESULTS
$start = $page * $setting_rows - $setting_rows;

// NEW QUERRY

$sql_querry = "SELECT * FROM players ";
$get_rows = $sql_querry;
$get_url = array();
$get_url_string = "";
if(!empty($_GET))
{
    //GET NEW PAGE
    if (isset($_GET['page'])){
        $page = intval($_GET['page']);
    }
    $start = $page * $setting_rows - $setting_rows;
    //GET SEARCH - LIKE
    if (isset($_GET['search']) && (isset($_GET['searchtype'])))
    {
        //GET SECURE SEARCH DATA
        $search = mysql_real_escape_string($_GET['search']);
        $searchtype = mysql_real_escape_string($_GET['searchtype']);
        if ($searchtype == "name"){
            $sql_querry .= "WHERE name LIKE '%$search%'"; 
            
        }
        elseif ($searchtype == "id"){
            $sql_querry .= "WHERE uid LIKE '%$search%'"; 
            
        }
        elseif ($searchtype == "steamid"){
            $sql_querry .= "WHERE steamid LIKE '%$search%'"; 
            
        }
        else {
            echo "WRONG SEARCH TYPE, DONT PLAY WITH GET VARS";
            exit;
        }
        //RECREATE SEARCH URL
        $get_url["search"] = "search=".$search;
        $get_url["searchtype"] = "searchtype=".$searchtype;
                
    }
    //GET LETTER - WHERE name LIKE
    if (isset($_GET['letter'])){
        if ($_GET['letter'] == "special"){
            $sql_querry .= "WHERE name NOT RLIKE '^[A-Z]'";
        }
        else {
            $sql_querry .= "WHERE name LIKE '".mysql_real_escape_string($_GET['letter'])."%'";
        }
        
        //RECREATE SEARCH URL
        $get_url["letter"] = "letter=".$_GET['letter'];
    }
    //GET SORT - ORDER BY
    if (isset($_GET['sort']) && isset($_GET['type'])){
        $get_sort = mysql_real_escape_string($_GET['sort'])." ". mysql_real_escape_string($_GET['type']);
        $sql_querry .= "ORDER BY ". $get_sort; 
        $get_url["sort"] = "sort=".$_GET['sort'];
        $get_url["type"] = "type=".$_GET['type'];
    }
    else{
        $sql_querry .= "ORDER BY uid";
    }
    
    //RECREATE SEARCH URL
    
    

    //SET GET ROWS WITHOUT LIMIT
    $get_rows = $sql_querry;
    
    
    // GET PAGINATION - SET LIMIT
    $sql_querry .= " LIMIT ".$start.",".$setting_rows;
    
    foreach($get_url as $value){
        
        $get_url_string .= "&".$value;
        
    }
   
}
else{
    //IF !isset GET set LIMIT for Pagination
    $sql_querry .= " LIMIT ".$start.",".$setting_rows;
}

$player_SQL = mysql_query($sql_querry) OR die("Error: $sql_querry <br>".mysql_error());
//DISPLAY HTML CONTENT
startHTML();
?>
   <div class="container" style="padding-top: 60px;">
            <div class="row">
                <ol class="breadcrumb">
                    <li><a href="index.php">Главная</a></li>
                    <li class="active">Список Игроков</li>
                </ol>
            </div>
            <div class="panel panel-default">
                <div class="panel-heading"><span class='glyphicon glyphicon-user'></span> Список Игроков </div>
                <div class="panel-body">
                    <p>Сдесь вы можете просматривать, редактировать или удалять игроков из базы данных.</p>
                    <div class="row">
<!-- A-Z RANGE AREA -->
                        <div class="col-lg-9">
                            <ul class="pagination pagination-sm">
                                <li><a href="player.php">All</a></li>
                                <li><a href="player.php?letter=special">[]0-9</a></li>
                                <?php
                                $azRange = range('A', 'Z');
                                foreach ($azRange as $letter){
                                    if(isset($_GET['letter'])){
                                        if($_GET['letter'] == $letter){
                                            echo "<li class='active'><a href='player.php?letter=".$letter."' style='padding: 5px 9px;'>".$letter."</a></li>";    
                                        }
                                        else{
                                            echo "<li><a href='player.php?letter=".$letter."' style='padding: 5px 9px;'>".$letter."</a></li>";
                                        }
                                    }
                                    else{
                                        echo "<li><a href='player.php?letter=".$letter."' style='padding: 5px 9px;'>".$letter."</a></li>";
                                    }
                                }
                                ?>
                            </ul>
                        </div>
<!-- DISPLAY SEARCH BOX -->
                        <form action="player.php" method="get">
                        <div class="col-lg-3" style="margin:20px 0;">
                            <div class="input-group" >
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Поиск" <?php if (isset($_GET['search'])){echo "value='".$_GET['search']."'";}?>>
                                    <span class="input-group-btn">
                                        <button class="btn btn-default" type="submit">Go!</button>
                                    </span>
                                </div><!-- /input-group -->
                                <label class="radio-inline">
                                    <input type="radio" name="searchtype" value="name" checked> Имя Игрока
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="searchtype" value="id"> ID
                                </label>
                                <label class="radio-inline">
                                    <input type="radio" name="searchtype" value="steamid"> Steam ID
                                </label>
                            </div><!-- /.col-lg-6 -->
                        </div>
                        </form>
                    </div>
<!-- DISPLAY Pagination AREA -->
                    <div class="row">
                        <div class="col-lg-12">
                            <ul class="pagination pagination-sm">
                                <?php
                                $get_rows_querry = mysql_query($get_rows);
                                $number_rows = mysql_num_rows($get_rows_querry); 
                                $number_pages = $number_rows / $setting_rows; 
                                                               
                                if($page == 1)
                                {
                                    echo "<li class='disabled'><a>&laquo; Prev</a></li>";
                                    echo "<li class='active'><a href='?page=1".$get_url_string."'>1</a></li>";
                                }
                                else
                                {
                                    echo "<li><a href='?page=".($page-1).$get_url_string."'>&laquo; Prev</a></li>";
                                    echo "<li><a href='?page=1".$get_url_string."'>1</a></li>";
                                }


                                for($a=($page-5); $a < ($page+5); $a++)
                                { 
                                    $b = $a + 1; 
                                    //IF AT PAGE

                                    if(($page == $b) && ($b < $number_pages) && ($b >1)){
                                        echo "<li class='active'><a href='?page=".$b.$get_url_string."'>".$b."</a></li>"; 
                                    } 
                                    else { 
                                        if(($b > 1) && ($b < $number_pages) )

                                        echo "<li><a href='?page=".$b.$get_url_string."'>".$b."</a></li> "; 
                                    } 
                                }

                                if($page >= $number_pages)
                                {
                                    if($page == 1){
                                        echo "<li class='disabled'><a>Next &raquo;</a></li>";
                                    }
                                    else{
                                        echo "<li class='active'><a href='?page=".ceil($number_pages).$get_url_string."'>".ceil($number_pages)."</a></li>";
                                        echo "<li class='disabled'><a>Next &raquo;</a></li>";
                                    }
                                }
                                else
                                {
                                    echo "<li><a href='?page=".ceil($number_pages).$get_url_string."'>".ceil($number_pages)."</a></li>";
                                    echo "<li><a href='?page=".($page+1).$get_url_string."'>Next &raquo;</a></li>";
                                }

                                ?>

                            </ul>
                        </div>
                </div>
            <div class="table-responsive">
                <table class="table table-hover table-striped ">
                    <thead>
                        <tr>
                            <td class="text-center"><strong>#</strong> <a href="player.php?sort=uid&type=ASC<?php if(isset($_GET['letter'])) {echo "&letter=".$_GET['letter'];}?><?php if(isset($_GET['page'])) {echo "&page=".$_GET['page'];}?>" style="color:grey;"><span class="glyphicon glyphicon-sort-by-attributes"></span></a><a href="player.php?sort=uid&type=DESC<?php if(isset($_GET['letter'])) {echo "&letter=".$_GET['letter'];}?><?php if(isset($_GET['page'])) {echo "&page=".$_GET['page'];}?>" style="color:grey;"><span class="glyphicon glyphicon-sort-by-attributes-alt"></span></a></td>
                            <td class="text-center"><strong>Имя</strong> <a href="player.php?sort=PlayerName&type=ASC<?php if(isset($_GET['letter'])) {echo "&letter=".$_GET['letter'];}?><?php if(isset($_GET['page'])) {echo "&page=".$_GET['page'];}?>" style="color:grey;"><span class="glyphicon glyphicon-sort-by-alphabet"></span></a><a href="player.php?sort=PlayerName&type=DESC<?php if(isset($_GET['letter'])) {echo "&letter=".$_GET['letter'];}?><?php if(isset($_GET['page'])) {echo "&page=".$_GET['page'];}?>" style="color:grey;"><span class="glyphicon glyphicon-sort-by-alphabet-alt"></span></a></td>
                            <td class="text-center"><strong>Наличка</strong> <a href="player.php?sort=cash&type=ASC<?php if(isset($_GET['letter'])) {echo "&letter=".$_GET['letter'];}?><?php if(isset($_GET['page'])) {echo "&page=".$_GET['page'];}?>" style="color:grey;"><span class="glyphicon glyphicon-sort-by-attributes"></span></a><a href="player.php?sort=cash&type=DESC<?php if(isset($_GET['letter'])) {echo "&letter=".$_GET['letter'];}?><?php if(isset($_GET['page'])) {echo "&page=".$_GET['page'];}?>" style="color:grey;"><span class="glyphicon glyphicon-sort-by-attributes-alt"></span></a></td>
                            <td class="text-center"><strong>Банк</strong> <a href="player.php?sort=bankacc&type=ASC<?php if(isset($_GET['letter'])) {echo "&letter=".$_GET['letter'];}?><?php if(isset($_GET['page'])) {echo "&page=".$_GET['page'];}?>" style="color:grey;"><span class="glyphicon glyphicon-sort-by-attributes"></span></a><a href="player.php?sort=bankacc&type=DESC<?php if(isset($_GET['letter'])) {echo "&letter=".$_GET['letter'];}?><?php if(isset($_GET['page'])) {echo "&page=".$_GET['page'];}?>" style="color:grey;"><span class="glyphicon glyphicon-sort-by-attributes-alt"></span></a></td>
                            <td class="text-center"><strong>Коп</strong> <a href="player.php?sort=coplevel&type=ASC<?php if(isset($_GET['letter'])) {echo "&letter=".$_GET['letter'];}?><?php if(isset($_GET['page'])) {echo "&page=".$_GET['page'];}?>" style="color:grey;"><span class="glyphicon glyphicon-sort-by-attributes"></span></a><a href="player.php?sort=coplevel&type=DESC<?php if(isset($_GET['letter'])) {echo "&letter=".$_GET['letter'];}?><?php if(isset($_GET['page'])) {echo "&page=".$_GET['page'];}?>" style="color:grey;"><span class="glyphicon glyphicon-sort-by-attributes-alt"></span></a></td>
                            <td class="text-center"><strong>Медик</strong> <a href="player.php?sort=mediclevel&type=ASC<?php if(isset($_GET['letter'])) {echo "&letter=".$_GET['letter'];}?><?php if(isset($_GET['page'])) {echo "&page=".$_GET['page'];}?>" style="color:grey;"><span class="glyphicon glyphicon-sort-by-attributes"></span></a><a href="player.php?sort=mediclevel&type=DESC<?php if(isset($_GET['letter'])) {echo "&letter=".$_GET['letter'];}?><?php if(isset($_GET['page'])) {echo "&page=".$_GET['page'];}?>" style="color:grey;"><span class="glyphicon glyphicon-sort-by-attributes-alt"></span></a></td>
                            <td class="text-center"><strong>VIP</strong> <a href="player.php?sort=donatorlvl&type=ASC<?php if(isset($_GET['letter'])) {echo "&letter=".$_GET['letter'];}?><?php if(isset($_GET['page'])) {echo "&page=".$_GET['page'];}?>" style="color:grey;"><span class="glyphicon glyphicon-sort-by-attributes"></span></a><a href="player.php?sort=donatorlvl&type=DESC<?php if(isset($_GET['letter'])) {echo "&letter=".$_GET['letter'];}?><?php if(isset($_GET['page'])) {echo "&page=".$_GET['page'];}?>" style="color:grey;"><span class="glyphicon glyphicon-sort-by-attributes-alt"></span></a></td>
                            <td class="text-center"><strong>Админ</strong> <a href="player.php?sort=adminlevel&type=ASC<?php if(isset($_GET['letter'])) {echo "&letter=".$_GET['letter'];}?><?php if(isset($_GET['page'])) {echo "&page=".$_GET['page'];}?>" style="color:grey;"><span class="glyphicon glyphicon-sort-by-attributes"></span></a><a href="player.php?sort=adminlevel&type=DESC<?php if(isset($_GET['letter'])) {echo "&letter=".$_GET['letter'];}?><?php if(isset($_GET['page'])) {echo "&page=".$_GET['page'];}?>" style="color:grey;"><span class="glyphicon glyphicon-sort-by-attributes-alt"></span></a></td>
                            <td class="text-center"><strong>Настройки</strong></td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        //CHECH IF QUERRYRESULT EMPTY OR FALSE AND IF SEARCH
                        if(mysql_num_rows($player_SQL) < 1 && isset($_GET['search'])){
                            //DISPLAY INFORMATION THAT QUERRY FALSE OR EMPTY
                            echo "<tr><td colspan=9 class='text-center'><h2><span class='label label-info'>Пустой Поисковый Запрос</span></h2></td></tr> ";
                        }
                        elseif(mysql_num_rows($player_SQL) < 1){
                            echo "<tr><td colspan=9 class='text-center'><h2><span class='label label-info'>No Results</span></h2></td></tr> ";
                        }
                        //NORMAL QUERRY FETCHING TO ROWS FOR TABEL
                        while($row = mysql_fetch_object($player_SQL)){ ?>

                        <tr>
                            <td class="text-center"><?php echo $row->uid;?></td>
                            <td class="text-left"><?php echo "<a href='player_detail.php?uid=".$row->uid."'>".htmlspecialchars($row->name)."</a>";?></td>
                            <td class="text-right"><?php echo money($row->cash);?></td>
                            <td class="text-right"><?php echo money($row->bankacc);?></td>
                            <td class="text-center"><?php echo $row->coplevel;?></td>
                            <td class="text-center"><?php echo $row->mediclevel;?></td>
                            <td class="text-center"><?php echo $row->donatorlvl;?></td>
                            <td class="text-center"><?php echo $row->adminlevel;?></td>
                            <td class="text-center"><a href="player_detail.php?uid=<?php echo $row->uid;?>" class="btn btn-primary"><span class="glyphicon glyphicon-pencil"></span></a>
                            <a data-toggle="modal" href="player.php#player_delete_<?php echo $row->uid;?>" class="btn btn-primary"><span class="glyphicon glyphicon-trash"></span></a></td>

                        </tr>


                    <!-- Modal Delete Player -->
                    <div class="modal fade" id="player_delete_<?php echo $row->uid;?>" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                        <h4 class="modal-title"><span class="glyphicon glyphicon-pencil"></span> Удалить <?php echo $row->name;?></h4>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <form method="post" action="player.php#player_delete_<?php echo $row->uid;?>" role="form"> 
                                                <input type="hidden" name="type" value="delete" />
                                                <input type="hidden" name="id" value="<?php echo $row->uid;?>" />
                                                <p>Вы действительно хотите удалить игрока? "<?php echo $row->name;?>"?</p>                                    
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button class="btn btn-default" data-dismiss="modal" type="reset">Отмена</button>
                                        <button class="btn btn-primary" type="submit">Удалить Игрока</button>
                                    </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                </tbody>
            </table>
            </div>
   </div>
</div>
       </div>

<?php
closeHTML();
?>
       
