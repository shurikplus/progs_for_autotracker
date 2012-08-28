<?php

// функция обработки топливных данных
function fuel_get($db_conn, $carmodule_id, $fueltare_type, $fuel_sensor_v)
{
	global $fuel;
 /*
	$fuel["$fuel_sensor"]["tare_id"] = 0;
	$fuel["$fuel_sensor"]["tare_type"] = $fueltare_type;
 //	$fuel["$fuel_sensor"]["type"] = 0;
	$fuel["$fuel_sensor"]["v_current"] = $fuel_sensor_v;
	$fuel["$fuel_sensor"]["v_min"] = 0;
	$fuel["$fuel_sensor"]["v_max"] = 0;
	$fuel["$fuel_sensor"]["vol_current"] = 0;
	$fuel["$fuel_sensor"]["vol_min"] = 0;
	$fuel["$fuel_sensor"]["vol_max"] = 0;
	$fuel["$fuel_sensor"]["color"] = "#FF0000";
 //	$fuel["$fuel_sensor"][""] = 0;
 */
	global $script_fuel_tank;
	$script_fuel_tank = 0;
	
 // запрос ИД, литража бака
 $query_for_fueltareid = "SELECT FUELTAREID, FUELTARESENSOR, FUELTAREVOLUMEMIN, FUELTAREVOLUMEMAX FROM [AT].[dbo].[FUELTARE] where CARMODULEID = '$carmodule_id' and FUELTARESTATUS = '$fueltare_type'";
  $query_fueltareid = mssql_query( $query_for_fueltareid, $db_conn )
   or die( "Ошибка: неверный идентификатор тарировки" );

 while($script_fueltareid = mssql_fetch_array($query_fueltareid))
 {
  $script_fuel_tank++;
  $fuel_sensor = $script_fueltareid[1];
  $fuel["$fuel_sensor"]["tare_id"] = $script_fueltareid[0];
  $fuel["$fuel_sensor"]["vol_min"] = $script_fueltareid[2];
  $fuel["$fuel_sensor"]["vol_max"] = $script_fueltareid[3];
  $fuel["$fuel_sensor"]["v_current"] = $fuel_sensor_v[$fuel_sensor];
    
  // определение миниума-максимума напряжения ДУТ (доработать на предмет штатного датчика!!!)
  $fueltare_id = $fuel["$fuel_sensor"]["tare_id"];

  $query_for_fueltare = "SELECT FUELTAREPOINTSENV, FUELTAREPOINTVOLUME FROM [AT].[dbo].[FUELTAREPOINT] where FUELTAREID = '$fueltare_id' order by FUELTAREPOINTINDEX";
   $query_fueltare = mssql_query( $query_for_fueltare, $db_conn )
    or die( "Ошибка: неверный идентификатор тарировки" );
   while($script_fueltare = mssql_fetch_array($query_fueltare)) {
    $tare_vsen[] = $script_fueltare[0];
    $tare_vol[] = $script_fueltare[1];
   }
  mssql_free_result( $query_fueltare );	
 
   $tare_index = (count($tare_vsen) - 1);
   
   $fuel["$fuel_sensor"]["v_min"] = $tare_vsen[0];
   $fuel["$fuel_sensor"]["v_max"] = $tare_vsen[$tare_index];
   $point_min = 0;
   $point_cycle = 0;
    
  // выделение цветом ---- доделать для обратного порядка
  if ( $tare_vsen[$point_min] < $tare_vsen[$tare_index] )
  {
   if ( $fuel["$fuel_sensor"]["v_min"] <= $fuel["$fuel_sensor"]["v_current"] && $fuel["$fuel_sensor"]["v_current"] <= $fuel["$fuel_sensor"]["v_max"] )
    { $fuel["$fuel_sensor"]["color"] = "#00FF00"; }
   else { $fuel["$fuel_sensor"]["color"] = "#FF0000"; }
   if ( $fuel["$fuel_sensor"]["v_max"] < $fuel["$fuel_sensor"]["v_current"] && $fuel["$fuel_sensor"]["v_current"] < ( $fuel["$fuel_sensor"]["v_max"] * 1.03 ) )
    { $fuel["$fuel_sensor"]["color"] = "#FFFF00"; }
  }
  else
  {
   if ( $fuel["$fuel_sensor"]["v_min"] >= $fuel["$fuel_sensor"]["v_current"] && $fuel["$fuel_sensor"]["v_current"] >= $fuel["$fuel_sensor"]["v_max"] )
    { $fuel["$fuel_sensor"]["color"] = "#00FF00"; }
   else { $fuel["$fuel_sensor"]["color"] = "#FF0000"; }
   if ( $fuel["$fuel_sensor"]["v_min"] < $fuel["$fuel_sensor"]["v_current"] && $fuel["$fuel_sensor"]["v_current"] < ( $fuel["$fuel_sensor"]["v_min"] * 1.03 ) )
    { $fuel["$fuel_sensor"]["color"] = "#FFFF00"; }
  }

  // вычисляем ближайшие точки по тарировке
  if ( $tare_vsen[$point_min] < $tare_vsen[$tare_index] )
   {
    while( $point_cycle < $tare_index ) {
// 	 echo $point_cycle." min:".$point_min." - ".$tare_vsen[$point_cycle]."\n";
      if ( $fuel["$fuel_sensor"]["v_current"] >= $tare_vsen[$point_cycle] ) { $point_min = $point_cycle; }
      // && $fuel["$fuel_sensor"]["v_current"] <= $tare_vsen[($point_cycle +1)]
      $point_cycle++;
    }
   }
   else
   {
    while( $point_cycle < $tare_index ) {
 //	 echo $point_cycle." min:".$point_min." - ".$tare_vsen[$point_cycle]."\n";
      if ( $fuel["$fuel_sensor"]["v_current"] <= $tare_vsen[$point_cycle] ) { $point_min = $point_cycle; }
      // && $fuel["$fuel_sensor"]["v_current"] >= $tare_vsen[($point_cycle +1)]
      $point_cycle++;
    }
   }   
       
   $point_max = $point_min + 1;
   if ( $point_max > $tare_index ) { $point_max--; $point_min--; }
   
/*
   echo "\n";
   echo $point_min." - ".$tare_vsen[$point_min]." = ".$tare_vol[$point_min]."\n";
   echo $fuel["$fuel_sensor"]["v_current"]."\n";
   echo $point_max." - ".$tare_vsen[$point_max]." = ".$tare_vol[$point_max]."\n";
   echo "\n";
*/

  $m = (($tare_vol[$point_max] - $tare_vol[$point_min]) / ($tare_vsen[$point_max] - $tare_vsen[$point_min]));
  $n = $tare_vol[$point_max] - ($tare_vsen[$point_max] * $m);
//  echo $m." ".$n."\n";
  
  $fuel["$fuel_sensor"]["vol_current"] = round((($fuel["$fuel_sensor"]["v_current"] * $m) + $n), 1);

  if ( $fuel["$fuel_sensor"]["vol_current"] < 0 OR $fuel["$fuel_sensor"]["vol_current"] >  $fuel["$fuel_sensor"]["vol_max"] * 1.05 ) { $fuel["$fuel_sensor"]["vol_current"] = 0; }

/*
  echo "\nномер датчика: ".$fuel_sensor."\n";
  echo "v_current: ".$fuel[$fuel_sensor]["v_current"]."\n";
  echo "v_min: ".$fuel["$fuel_sensor"]["v_min"]."\n";
  echo "v_max: ".$fuel["$fuel_sensor"]["v_max"]."\n";
  echo "vol_current: ".$fuel["$fuel_sensor"]["vol_current"]."\n";
  echo "vol_min: ".$fuel["$fuel_sensor"]["vol_min"]."\n";
  echo "vol_max: ".$fuel["$fuel_sensor"]["vol_max"]."\n";
  echo "color: ".$fuel["$fuel_sensor"]["color"]."\n";
*/

  unset($tare_index);
  unset($tare_vol);
  unset($tare_vsen);
 }
 $fuel["$fuel_sensor"]["v_min"] = round($fuel["$fuel_sensor"]["v_min"], 2);
 $fuel["$fuel_sensor"]["v_max"] = round($fuel["$fuel_sensor"]["v_max"], 2);
 mssql_free_result( $query_fueltareid );
 return $fuel;
 return $script_fuel_tank;
}

?>
