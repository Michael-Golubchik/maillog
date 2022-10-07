<?php

#Параметры подключения к базе данных
#*************
$DBParams['dbname'] = 'mail';# Наименование базы данных
$DBParams['user'] = 'umail'; #Пользователь для подключения к базе данных
$DBParams['password'] = 'Pias7Usasosa'; #Пароль для пользователя базы дынных
$DBParams['host'] = 'localhost'; #Хост для подключения к БД
$DBParams['port'] = 5432; #Порт для подключения к БД
#*************

$conn; #ресурс соединения с базой данных
$PageStr = ''; #Строка с содержимым страницы

DBConnect(); #Подключаемся к базе данных.
PrinSearchForm();
PrintLog(); #Печатаем лог

$PageStr .= <<<END_OF_RECORD
  </body>
</html>
END_OF_RECORD;

print $PageStr;

#PrinSearchForm - Выводит страницу с поисковой формой
function PrinSearchForm() {
global $PageStr;

$PageStr .= <<<END_OF_RECORD
<html>
  <body>
     <form action="print-log.php" method="post">
       <label for="email">Адреса получателя:</label>
       <input name="email" type="text" />
       <input name="email-submit" type="submit" value="Найти записи в логе" />
     </form>
END_OF_RECORD;

}


#PrintLog - Выводит из таблиц с логами почтового сервера строки лога относящиеся к письмам с указанным адресом получателя
function PrintLog() { 
  global $conn, $PageStr;
  $RowLimit = 100; #Ограничение на число записей
  $RowLimit1 = $RowLimit + 1; 
  
  if (isset($_POST["email"])) { 
    #Успешно передан адрес элкктронной почты получателя для поиска
    
    $EmailStr = $_POST["email"]; 
    if (!preg_match("/([a-z0-9\_\-\.]+\@[a-z0-9\_\-\.]+)/i", $EmailStr, $matches)) {
    
      $PageStr .= '\n<br />Неправильный адрес электронной почты: $EmailStr';
      return;
    }
    #Запрос к двум таблицам и поиску всех записей сообщений, в которых получетелем был указанный адрес электронной почты $EmailStr
    $QueryStr = <<<END_OF_RECORD
WITH reciver_int_id AS
(SELECT int_id AS r_int_id FROM log WHERE (log.address='$EmailStr'))
(SELECT created, int_id, str FROM log, reciver_int_id where int_id = r_int_id
UNION ALL
SELECT created, int_id, str FROM message, reciver_int_id where int_id = r_int_id)
ORDER BY int_id, created
LIMIT {$RowLimit1};

END_OF_RECORD;
  
    $result = pg_query($conn, $QueryStr);
    if (!$result) { die("An error in PrintLog in select occured.\n"); }
    
    $PageStr .= "Записи лога о сообщениях, в которых получателем был: $EmailStr<br /><br />\n<table>";

    #Перебираем полученный записи
    $RowInd = 0; #Номер записи 
    while ($row = pg_fetch_row($result)) { 
      $RowInd++;
      if ($RowInd <= $RowLimit) {
      
        $PageStr .= <<<END_OF_RECORD
<tr><td>$row[0]</td><td>$row[2]</td></tr>
END_OF_RECORD;
      } 
    }
    $PageStr .= "\n</table>";  
    if ($RowInd == $RowLimit1) {
      
        $PageStr .= "\n<br />В Логе больше {$RowLimit} записей. Показаны первые {$RowLimit} записей.";
    }
    if ($RowInd == 0) {
       $PageStr .= "\n<br />Не нашли записей соответствующих адресу получателя: {$EmailStr}";
    }
  }
}


# DBConnect - Подключает к базе данных. 
function DBConnect() { 
  
  global $conn, $DBParams;
  #Устанавливаем соединение с базой данных.
  if (is_null($conn)) { 
  
    $conn = pg_connect("host={$DBParams['host']} port={$DBParams['port']} dbname={$DBParams['dbname']} user={$DBParams['user']} password={$DBParams['password']}");
    if (!$conn) {
      
      header('http/1.1 503 Service Unavailable');
      die("DB Connect error occured.\n");
    }
  }
} 
?>