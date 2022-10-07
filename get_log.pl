# Get_log Загружает лог почтового сервера
use utf8;
use DBI;
use Encode qw/encode decode/;

$LogFileName = 'out'; #Название файла почтовых логов.
#Параметры подключения к базе данных
#***********
$user = 'umail'; #Пользователь для подключения к базе данных
$password = 'Pias7Usasosa'; #Пароль для пользователя базы дынных
$database = 'mail'; #Название базы данных
#***********

$ISLinux = 0; #Устанавливаем в 1 Если выполняется на linux

InitDB(); #Подключаемся к базе данных

#Удаляем старые записи в таблице
DoSQuery(qq{DELETE FROM message;});
DoSQuery(qq{DELETE FROM log;});


open(FLogFile, "<:utf8", "$LogFileName");
@FileStr = <FLogFile>; #Массив строк из файла постовых логов
close(FDatFile);

#Перебираем строки лога
for ($FileStrI = 0; $FileStrI < @FileStr; $FileStrI++) {

  #Формируем массив элементов лога
  @FileStrEl = split(/ /,$FileStr[$FileStrI]);
  
  my $StrWithoutTime = ''; #строка лога (без временной метки)
  my $IsErrStr = 0; #Есть ли ошибка при извлечени данных из строки, если 1 то есть. 0 нет
  
  if ($FileStr[$FileStrI] =~ /\s.+?\s(.+)/) {
  
    $StrWithoutTime = $1;
    $StrWithoutTime =~ s!'!''!g;
  }
  else {
  
    wprint("В строке $FileStr[$FileStrI] нет строки без временной метки\n"); 
    $IsErrStr = 1;
  }
  
  if ($FileStrEl[3] eq '<=') {
    
    
    if ($StrWithoutTime =~ /\sid=(\S+)/) {
    
      $ID = $1; #Извлекаем значение поля id=xxxx из строки лога
    }
    else {
    
      wprint("В строке $FileStr[$FileStrI] нет id\n");
      $IsErrStr = 1;
    }
    
    if ($IsErrStr == 0) {
    
      DoSQuery(qq{INSERT INTO message (created, id, int_id, str) VALUES ('$FileStrEl[0] $FileStrEl[1]', '$ID', '$FileStrEl[2]', '$StrWithoutTime')});
    }
    
  } 
  elsif ($FileStrEl[3] =~ /^(=>|->|\*\*|==)$/) {
  
    if ($IsErrStr == 0) {
      
      DoSQuery(qq{INSERT INTO log (created, int_id, str, address) VALUES ('$FileStrEl[0] $FileStrEl[1]', '$FileStrEl[2]', '$StrWithoutTime', '$FileStrEl[4]')});
    }
  }
}

$rc = $dbh->disconnect;

exit(0);

#Инициализирует базу данных и некоторые переменные извлеченные из нее.
sub InitDB { 
  my $driver = "Pg";
  
  $dbh = DBI->connect("DBI:$driver:dbname=$database", $user, $password);
  if ($DBI::err != 0) { wprint($DBI::errstr . "\n"); exit($DBI::err);}
  $dbh->{pg_enable_utf8} = 1;
}

#Выполняет запрос с проверкой на ошибку 
sub DoSQuery { 
  my ($statement) = @_;
  
  #Выполняем переданный запрос
  my $sth = $dbh -> prepare($statement); 
  my $rv = $sth -> execute;
  if (!defined $rv) { wprint("При выполнении запроса \"$statement\" возникла ошибка: " . $dbh->errstr . "\n"); exit(0);}
}

#Печатает строку в windows кодировке 866 для командной строки если работаем под windows
sub wprint { 
  my ($str) = @_;
  
  if ($ISLinux == 1) { 
    print $str;
  }
  else { 
    print encode('cp866', $str);
  }
}
