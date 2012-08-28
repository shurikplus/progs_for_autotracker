<?php

require( 'fuel_get.php' ); 

$script_garagenum = $_GET['car'];

date_default_timezone_set('Asia/Yekaterinburg');

echo "<!DOCTYPE html><html lang=\"ru-RU\"><head>";

echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">";

echo "<style type=\"text/css\">";
echo "#table_style";
echo "{ font-family:\"Trebuchet MS\", Arial, Helvetica, sans-serif;";
echo "width:100%;";
echo "border-collapse:collapse; }";

echo "#table_style td, #table_style th";
echo "{ font-size:1em;";
echo "border:1px solid #98bf21;";
echo "padding:3px 7px 2px 7px; }";

echo "#table_style th";
echo "{ font-size:1.1em;";
echo "text-align:left;";
echo "padding-top:5px;";
echo "padding-bottom:4px;";
echo "background-color:#A7C942;";
echo "color:#ffffff; }";

echo "#table_style tr.alt td";
echo "{ color:#000000;";
echo "background-color:#EAF2D3; }";
echo "</style>";

echo "</head>";
echo "<body>";

echo "<title>Информация о состоянии автомобиля ".$script_garagenum."</title>";

echo "<form  id=\"table_style\" action=\"car_status.php\">";
echo "Гаражный номер: <input type=text name=\"car\">";
echo "<input type=submit value=\"Поиск\"><br>";
echo "</form>";
echo "<br>";

if ($script_garagenum == "") { die( "<strong>Ошибка: введите гаражный номер</strong>" ); }

  // connect to database server
  // имя-сервера, база, пароль
  $db_conn = mssql_connect("AtServer","AutotrackerDB","testpassword")
    or die( "<strong>ERROR: Connection to MYSERVER failed</strong>" );

  // select database - only if we want to query another database than the default one
  mssql_select_db( "at", $db_conn )
    or die( "<strong>Ошибка: база данных не доступна</strong>" );

// вытягиваем текущее время
$query_date = mssql_query( "SELECT GETDATE()", $db_conn )
    or die( "<strong>Ошибка: невозможно определить время</strong>" );
$sql_time = mssql_fetch_array($query_date);
$current_time = strtotime($sql_time[0]);

  // query the database
//  $query_result = mssql_query( "SELECT * FROM carmodule where carmoduleid = '252'", $db_conn )
$query_result = mssql_query( "SELECT TOP 1 * FROM [AT].[dbo].[CARMODULE] where CARMODULEDESCRIPTION LIKE ('$script_garagenum%')", $db_conn )
    or die( "<strong>Ошибка: машины с таким гаражным номером нет в базе</strong>" );

while($row = mssql_fetch_array($query_result))
{
	
// Запрос данных о машине
// запрос группы
$query_for_group = "SELECT CARGROUPNAME FROM [AT].[dbo].[CARGROUP] where CARGROUPID = '$row[2]'";
$query_group = mssql_query( $query_for_group, $db_conn )
 or die( "Ошибка: неверный идентификатор группы" );
$script_group = mssql_fetch_array($query_group);
mssql_free_result( $query_group );

// запрос ответственного
$query_for_userid = "SELECT USERID FROM [AT].[dbo].[CARMODULERESPONSIBLE] where CARMODULEID = '$row[0]' and CARMODULERESPONSIBLESTATUS = '0'";
$query_userid = mssql_query( $query_for_userid, $db_conn )
 or die( "Ошибка: неверный идентификатор ответственного" );
$script_userid = mssql_fetch_array($query_userid);
mssql_free_result( $query_userid );
$query_for_user = "SELECT USERLOGIN FROM [AT].[dbo].[USERS] where USERID = '$script_userid[0]'";
$query_user = mssql_query( $query_for_user, $db_conn )
 or die( "Ошибка: неверный ответственный" );
$script_user = mssql_fetch_array($query_user);
mssql_free_result( $query_user );
$script_car = explode(" ", iconv("CP1251","UTF-8//IGNORE",$row[12]));
$script_car_number = $script_car[0];
$script_car[0] = "";
$script_car_descr = implode(" ", $script_car);
$script_car_comment = iconv("CP1251","UTF-8//IGNORE",$row[14]);

// Запрос данных навигации
// запрос времени последней видимости

$script_time_lastcoordstime = strtotime($row[23]);
$delta_lastcoordstime = $current_time - $script_time_lastcoordstime;

if ($delta_lastcoordstime < 600) {$lastcoordstime_color = "#00FF00";}
elseif ($delta_lastcoordstime < 3600) {$lastcoordstime_color = "#FFFF00";}
else {$lastcoordstime_color = "#FF0000";}

if ($row[29] < 3) {$satelite_color = "#FF0000";}
elseif ($row[29] == 3) {$satelite_color = "#FFFF00";}
elseif ($row[29] > 3) {$satelite_color = "#00FF00";}

if ($row[27] < 1) {$speed_color = "#00FF00";}
elseif ($row[27] > 90) {$speed_color = "#FF0000";}
else {$speed_color = "#00FF99";}

// Запрос данных о топливе, спутниковой системе

switch ("$row[50]") { 
   case "FUEL_P": 
      $script_fuel_tank = 1;
      $script_fs_type = "AT FLM";
      $script_sat = "GPS";
      break; 
   case "FUEL2_P": 
      $script_fuel_tank = 2;
      $script_fs_type = "AT FLM";
      $script_sat = "GPS";
      break; 
   case "ENT_P": 
      $script_fuel_tank = 1;
      $script_fs_type = "Штатный";
      $script_sat = "GPS";
      break; 
   case "ENT2_P": 
      $script_fuel_tank = 2;
      $script_fs_type = "Штатный";
      $script_sat = "GPS";
      break; 
   case "GLONASS":
      $script_fuel_tank = 1;
      $script_fs_type = "AT FLM";
      $script_sat = "GLONASS";
      break; 
   case "GLOFUEL2"; 
      $script_fuel_tank = 2;
      $script_fs_type = "AT FLM";
      $script_sat = "GLONASS";
      break; 
}

// Запрос данных о соединении

if ($row[22] == 1 ) { $gprs_color = "#00FF00"; $script_gprs = "на связи";}
else { $gprs_color = "#FF0000"; $script_gprs = "нет связи";}

// запрос данных о датчиках

$vbort = round($row[53],2);
$vacc = round($row[54],2);

if ($row[53] > 10 && $row[53] < 35) {$vbort_color = "#00FF00";}
else {$vbort_color = "#FF0000";}
if ($row[54] > 10 && $row[54] <= 14) {$vacc_color = "#00FF00";}
else {$vacc_color = "#FF0000";}

switch ("$row[35]") { 
   case 0: 
       $sensor1 = 0; $sensor2 = 0;  $sensor3 = 0;  $sensor4 = 0; 
       break; 
   case 1: 
       $sensor1 = 1; $sensor2 = 0;  $sensor3 = 0;  $sensor4 = 0; 
       break; 
   case 2: 
       $sensor1 = 0; $sensor2 = 1;  $sensor3 = 0;  $sensor4 = 0; 
       break; 
   case 3: 
       $sensor1 = 1; $sensor2 = 1;  $sensor3 = 0;  $sensor4 = 0; 
       break;      
   case 4: 
       $sensor1 = 0; $sensor2 = 0;  $sensor3 = 1;  $sensor4 = 0; 
       break; 
   case 5: 
       $sensor1 = 1; $sensor2 = 0;  $sensor3 = 1;  $sensor4 = 0; 
       break;            
   case 6: 
       $sensor1 = 0; $sensor2 = 1;  $sensor3 = 1;  $sensor4 = 0; 
       break;      
   case 7: 
       $sensor1 = 1; $sensor2 = 1;  $sensor3 = 1;  $sensor4 = 0; 
       break;
   case 8: 
       $sensor1 = 0; $sensor2 = 0;  $sensor3 = 0;  $sensor4 = 1; 
       break;
   case 9: 
       $sensor1 = 1; $sensor2 = 0;  $sensor3 = 0;  $sensor4 = 1; 
       break;
   case 10: 
       $sensor1 = 0; $sensor2 = 1;  $sensor3 = 0;  $sensor4 = 1; 
       break;
   case 11: 
       $sensor1 = 1; $sensor2 = 1;  $sensor3 = 0;  $sensor4 = 1; 
       break;
   case 12: 
       $sensor1 = 0; $sensor2 = 0;  $sensor3 = 1;  $sensor4 = 1; 
       break;
   case 13: 
       $sensor1 = 1; $sensor2 = 0;  $sensor3 = 1;  $sensor4 = 1; 
       break;
   case 14: 
       $sensor1 = 0; $sensor2 = 1;  $sensor3 = 1;  $sensor4 = 1; 
       break;
   case 15: 
       $sensor1 = 1; $sensor2 = 1;  $sensor3 = 1;  $sensor4 = 1; 
       break;
}       

if ($sensor1 == 1) {$sensor1_color = "#FF0000";}
else {$sensor1_color = "#00FF00";}
if ($sensor2 == 1) {$sensor2_color = "#00FF99";}
else {$sensor2_color = "#00FF00";}
if ($sensor3 == 1) {$sensor3_color = "#00FF99";}
else {$sensor3_color = "#00FF00";}
if ($sensor4 == 1) {$sensor4_color = "#00FF99";}
else {$sensor4_color = "#00FF00";}

// Запрос топливных значений
// тип тарировки, 3 - загруженная в ББ, 1 - сохранённая, 4 - серверная
$fueltare_type = 3;
$fuel_sensor_v[0] = round($row[55], 2);
$fuel_sensor_v[1] = round($row[56], 2);
$carmodule_id = $row[0];

fuel_get($db_conn, $carmodule_id, $fueltare_type, $fuel_sensor_v);


// Вывод данных о машине

//echo "<table id=\"table_style\" border=\"1\" cellpadding=\"5\" width=\"100%\">";
echo "<table id=\"table_style\" style=text-align:center;>";
echo "<tr>";
 echo "<th colspan=6>Автомобиль</th>";
echo "</tr>";
echo "<tr class=\"alt\">";
 echo "<td>Гаражный номер</td>";
 echo "<td>Марка автомобля</td>";
 echo "<td colspan=2>Группа</td>";
 echo "<td colspan=2>Ответственный</td>"; 
echo "</tr>";
echo "<tr>";
 echo "<td>$script_car_number</td>";
 echo "<td>$script_car_descr</td>";
 echo "<td colspan=2>".iconv("CP1251","UTF-8//IGNORE",$script_group[0])."</td>";
 echo "<td colspan=2>".iconv("CP1251","UTF-8//IGNORE",$script_user[0])."</td>";
echo "</tr>";
echo "<tr class=\"alt\">"; // выделение полутоном
 echo "<td colspan=6 style=text-align:left;>Комментарий: ";
 echo $script_car_comment;
echo "</td></tr>";
echo "<td colspan=6 style=text-align:left;> серийный номер:".$row[47]." версия:".$row[49]." ID:".$row[0]." sim:".$row[3]."</td>";

// Вывод данных о датчиках
echo "<tr class=\"alt\">";
 echo "<td colspan=2>Напряжение</td>";
 echo "<td colspan=4>Состояние датчиков</td>";
echo "</tr>"; 
echo "<tr class=\"alt\">";
 echo "<td>бортсети</td>";
 echo "<td>резервного АКБ</td>";
 echo "<td>вскрытия</td>";
 echo "<td>спецоборудования</td>";
 echo "<td>допотопителя</td>";
 echo "<td>зажигания</td>";
echo "</tr>";
echo "<tr>";
 echo "<td bgcolor=\"$vbort_color\">$vbort</td>";
 echo "<td bgcolor=\"$vacc_color\">$vacc</td>";           
 echo "<td bgcolor=\"$sensor1_color\">$sensor1</td>";
 echo "<td bgcolor=\"$sensor2_color\">$sensor2</td>";
 echo "<td bgcolor=\"$sensor3_color\">$sensor3</td>";
 echo "<td bgcolor=\"$sensor4_color\">$sensor4</td>";
echo "</tr>";
echo "</table>";
echo "<br>";

// Вывод данных навигации
echo "<table id=\"table_style\" style=text-align:center;>";
 echo "<tr>";
  echo "<th colspan=6>Координаты</th>";
 echo "</tr>";
 echo "<tr class=\"alt\">";
  echo "<td>Время последней видимости</td>";
  echo "<td>Широта</td>";
  echo "<td>Долгота</td>";
  echo "<td>Тип навигации</td>";
  echo "<td>Количество<br>спутников</td>";
  echo "<td>Скорость<br>движения</td>";
 echo "</tr>";
 echo "<tr>";
  echo "<td bgcolor=\"$lastcoordstime_color\">".date("d.m.Y G:i:s" ,$script_time_lastcoordstime)."</td>";
  echo "<td>$row[25]</td>";
  echo "<td>$row[26]</td>";
  echo "<td>$script_sat</td>";
  echo "<td bgcolor=\"$satelite_color\">$row[29]</td>";
  echo "<td bgcolor=\"$speed_color\">$row[27]</td>";
 echo "</tr>";
echo "</table>";
echo "<br>";

// Вывод данных о соединении
echo "<table id=\"table_style\" style=text-align:center;>";
 echo "<tr>";
  echo "<th colspan=3>Соединение</th>";
 echo "</tr>";
 echo "<tr class=\"alt\">";
  echo "<td>Время последнего запроса</td>";
  echo "<td>Время последнего ответа</td>";
  echo "<td>GPRS</td>";
 echo "</tr>";
 echo "<tr>";
  echo "<td>".date("d.m.Y G:i:s" ,strtotime($row[44]))."</td>";
  echo "<td>".date("d.m.Y G:i:s" ,strtotime($row[20]))."</td>";
  echo "<td bgcolor=\"$gprs_color\">$script_gprs</td>";
 echo "</tr>";
echo "</table>";
echo "<br>";

// Вывод данных о топливе
$fuel_sensor_count = count($fuel);

echo "<table id=\"table_style\" style=text-align:center;>";
 echo "<tr>";
  echo "<th colspan=3>Топливо</th>";
 echo "</tr>";
 echo "<tr>";
  echo "<td width=50% colspan=1>Количество баков: ".$fuel_sensor_count."</td>";
  echo "<td width=50% colspan=2>Тип датчиков: ".$script_fs_type."</td>";
 echo "</tr>";
 echo "<tr class=\"alt\">";
  echo "<td width=50%>Пределы тарировки (напряжение датчика), вольты</td>";
  echo "<td width=25%>Объём бака, литры</td>";
  echo "<td width=25%>Количество топлива, литры</td>";
 echo "</tr>";
 foreach($fuel as $fuel_sensor)
  {
    echo "<tr>";
     $color = $fuel_sensor["color"];
     echo "<td bgcolor=\"$color\">".$fuel_sensor["v_min"]." ---> ( <big>".$fuel_sensor["v_current"]."</big> ) <-- ".$fuel_sensor["v_max"]."</td>";
     echo "<td>".$fuel_sensor["vol_max"]."</td>";
     echo "<td>".$fuel_sensor["vol_current"]."</td>";
    echo "</tr>";
  }
echo "</table>";
}

echo "</body></html>";
echo "\n";

  mssql_free_result( $query_result ); // unnecessary in PHP 4
  mssql_close( $db_conn );            // unnecessary in PHP 4
?>
