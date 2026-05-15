<?php
$encryptionKey = "ADAS*@$!2011";

function Convert($str, $ky = '')
{
    if ($ky == '')
        return $str;

    $ky = str_replace(chr(32), '', $ky);

    if (strlen($ky) < 8)
        exit('key error');

    $kl = strlen($ky) < 32 ? strlen($ky) : 32;

    $k = array();
    for ($i = 0; $i < $kl; $i++) {
        $k[$i] = ord($ky[$i]) & 0x1F;
    }

    $j = 0;
    for ($i = 0; $i < strlen($str); $i++) {
        $e = ord($str[$i]);
        $str[$i] = $e & 0xE0 ? chr($e ^ $k[$j]) : chr($e);
        $j++;
        $j = $j == $kl ? 0 : $j;
    }

    return $str;
}
function EncryptURL($plainText)
{        
	  // return base64_encode(Convert($plainText, "ADAS*@$!2011"));
	   return strtr(base64_encode(addslashes(gzcompress(serialize($plainText),9))), '+/=', '-_,');
	   
}
function DecryptURL($encryptedString)
{
	   // return Convert(base64_decode($encryptedString), "ADAS*@$!2011");
	   
	   return unserialize(gzuncompress(stripslashes(base64_decode(strtr($encryptedString, '-_,', '+/=')))));
}

function random_strings($length_of_string) 
{ 
  
    $str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'; 
    return substr(str_shuffle($str_result),0, $length_of_string); 
} 

function CalculateTimeDiff($t1,$t2)
{
	$a1 = explode(":",$t1);
	$a2 = explode(":",$t2);
	$time1 = (($a1[0]*60*60)+($a1[1]*60)+($a1[2]));
	$time2 = (($a2[0]*60*60)+($a2[1]*60)+($a2[2]));
	$diff = abs($time1-$time2);
	$hours = floor($diff/(60*60));
	$mins = floor(($diff-($hours*60*60))/(60));
	$secs = floor(($diff-(($hours*60*60)+($mins*60))));
	$result = $hours.":".$mins.":".$secs;
	//$result = $hours." Hours, ".$mins." minutes";
	return $result;
}

function CalculateTimeAdd($t1,$t2)
{
	$a1 = explode(":",$t1);
	$a2 = explode(":",$t2);
	$time1 = (($a1[0]*60*60)+($a1[1]*60)+($a1[2]));
	$time2 = (($a2[0]*60*60)+($a2[1]*60)+($a2[2]));
	$add = abs($time1+$time2);
	$hours = floor($add/(60*60));
	$mins = floor(($add-($hours*60*60))/(60));
	$secs = floor(($add-(($hours*60*60)+($mins*60))));
	$result = $hours.":".$mins;
	return $result;
}

function GetNoOfDaysbetweenDates($toDate, $fromDate)
{
	return (((strtotime($toDate) - strtotime($fromDate) ) / (60 * 60 * 24)) + 1);
}

function calculateAverageTime($t,$p)
{
	$add = abs($t / $p);
	$hours = floor($add/(60));
	$mins = round(($add-($hours*60))/(60));
	//$secs = floor(($add-(($hours*60*60)+($mins*60))));
	$result = $hours.":".$mins;

	return $result;
}

function calculateAverageTimeClock($t,$p)
{
	$a1 = explode(":",$t);
	$time1 = (($a1[0]*60*60)+($a1[1]*60)+($a1[2]));
	$add = abs($time1 / $p);
	$hours = floor($add/(60*60));
	$mins = floor(($add-($hours*60*60))/(60));
	$secs = floor(($add-(($hours*60*60)+($mins*60))));
	$result = $hours.":".$mins;

	return $result;
}

function encode($str)
{
  for($i=0; $i<11;$i++)
  {
    $str=strrev(base64_encode($str)); //apply base64 first and then reverse the string
  }
  return $str;
}

//function to decrypt the string
function decode($str)
{
  for($i=0; $i<11;$i++)
  {
    $str=base64_decode(strrev($str)); //apply base64 first and then reverse the string}
  }
  return $str;
}

function GetNoOfDaysInMonth($month, $year)
{
   return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year %400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
}

function GetPermissions($moduleId)
{
	$URL = $_SERVER['PHP_SELF'];
	$folders = explode('/', $URL);
	$CURRENT_URL = $folders[count($folders) - 1] ;

	$arrPerms = ($_SESSION["_PERMISSIONS_"][$moduleId]);
	$PagePerm = array();
	if(is_array($arrPerms))
	{
		foreach($arrPerms as $objPerm)
		{
			$menuLink = $objPerm["ADAS_ERP_MODULE_MENU_LINK_URL"];
			list($folder, $url) = explode('/', $menuLink);
			if($url == $CURRENT_URL)
			{
				$PagePerm[$objPerm["BUTTON_NAME"]] =  $objPerm["ADAS_ERP_BUTTONS_ID"];
			}
		}
	}
	else
	{
		echo 'Permission Denied.';
		exit();
	}
	return $PagePerm;
}

function calculateAge($birthday)// this function will return age in year
{
    list($year,$month,$day) = explode("-",$birthday);
    $year_diff  = date("Y") - $year;
    $month_diff = date("m") - $month;
    $day_diff   = date("d") - $day;
    if ($day_diff < 0 || $month_diff < 0)
      $year_diff--;
    return $year_diff;     
}	
//function to convert number to words. eg 1560 to One Thousane Five Hundred Sixty .........DEBA 27May2010
function convert_number($number) 
{ 
    if (($number < 0) || ($number > 999999999)) 
    { 
    	throw new Exception("Number is out of range");
    } 

    $Gn = floor($number / 1000000);  /* Millions (giga) */ 
    $number -= $Gn * 1000000; 
    $kn = floor($number / 1000);     /* Thousands (kilo) */ 
    $number -= $kn * 1000; 
    $Hn = floor($number / 100);      /* Hundreds (hecto) */ 
    $number -= $Hn * 100; 
    $Dn = floor($number / 10);       /* Tens (deca) */ 
    $n = $number % 10;               /* Ones */ 
    $res = ""; 
    if ($Gn) 
    { 
        $res .= convert_number($Gn) . " Million"; 
    } 

    if ($kn) 
    { 
        $res .= (empty($res) ? "" : " ") . 
            convert_number($kn) . " Thousand"; 
    } 

    if ($Hn) 
    { 
        $res .= (empty($res) ? "" : " ") . 
            convert_number($Hn) . " Hundred"; 
    } 
    $ones = array("", "One", "Two", "Three", "Four", "Five", "Six", 
        "Seven", "Eight", "Nine", "Ten", "Eleven", "Twelve", "Thirteen", 
        "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eightteen", 
        "Nineteen"); 
    $tens = array("", "", "Twenty", "Thirty", "Fourty", "Fifty", "Sixty", 
        "Seventy", "Eigthy", "Ninety"); 

    if ($Dn || $n) 
    { 
        if (!empty($res)) 
        { 
            $res .= " and "; 
        } 

        if ($Dn < 2) 
        { 
            $res .= $ones[$Dn * 10 + $n]; 
        } 
        else 
        { 
            $res .= $tens[$Dn]; 

            if ($n) 
            { 
                $res .= "-" . $ones[$n]; 
            } 
        } 
    } 

    if (empty($res)) 
    { 
        $res = "zero"; 
    } 

    return $res; 
} 

function checkWeeklyOff($dateOfMonth,$weekDayArray,$dayFlag)
{  
	$dayNumber  = date('N',strtotime($dateOfMonth));

	if(count($weekDayArray) > 0)
	{
		if(array_key_exists($dayNumber,$weekDayArray))
		{
			list($nonWorkingDayStr,$nonWorkingHalfDayStr) = explode("_",$weekDayArray[$dayNumber]);
	
			$weekNumber = getWeekOfTheMonth1($dateOfMonth);
			if($dayFlag == 'F')
			{
				if(in_array($weekNumber,explode(",",$nonWorkingDayStr)))
					return true;
			}
			else// for half day
			{
				if(in_array($weekNumber,explode(",",$nonWorkingHalfDayStr)))
					return true;
			}
		}
	}
	return false;
}


function getWeekOfTheMonth($dateOfMonth)
{
	$d = date('j',strtotime($dateOfMonth));
	$w = date('w',strtotime($dateOfMonth))+1; //add 1 because date returns value between 0 to 6

	$dt = (floor($d % 7)!=0)? floor($d % 7) : 7;
	$k = (($w-$dt) > 0) ?  $w-$dt : 7+ ($w-$dt);
	
	$W = ceil(($d + $k)/7);
	
	return $W ;
}

function getWeekOfTheMonth1($dateOfMonth)
{
	list($year,$month,$day) = explode("-",$dateOfMonth);
	$startDateOfMonth = $year.'-'.$month.'-01';
	
	$dayNumberWeekArray = array();
	$firstDayOfFirstDate = date('w',strtotime($startDateOfMonth));//1 (for Monday) through 7 (for Sunday)
	
	$weekNumber = 1;
	for($d=1;$d<=$day;$d++)
	{
		if($d<10)
			$d = '0'.$d;
	
		$dateIndex = $year.'-'.$month.'-'.$d;
		if($firstDayOfFirstDate < 7)
		{
			$dayNumberWeekArray[$dateIndex] = $weekNumber;
			$firstDayOfFirstDate++;
		}
		else
		{
			$weekNumber++;
			$firstDayOfFirstDate = 1;
			$dayNumberWeekArray[$dateIndex] = $weekNumber;
		}
	}
	
	return $dayNumberWeekArray[$dateOfMonth];
}

function GetHeadValueByFormula($formula,$salHeadAmountDetail)// this function will return a function expression as string in which head will be replace by head value
{
	foreach($salHeadAmountDetail as $head => $headAmount)
	{
		$formula = preg_replace('/\b'.$head.'\b/', $headAmount, $formula);
	}
	
	return $formula;
}

function numberToRoman($num) 
{
	 // Make sure that we only use the integer portion of the value
	 $n = intval($num);
	 $result = '';
	 // Declare a lookup array that we will use to traverse the number:
	 $lookup = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
	 'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,
	 'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);
	 foreach ($lookup as $roman => $value) 
	 {
		 // Determine the number of matches
		 $matches = intval($n / $value);
		 // Store that many characters
		 $result .= str_repeat($roman, $matches);
		 // Substract that from the number
		 $n = $n % $value;
	 }
	 // The Roman numeral should be built, return it
	 return $result;
}

function http_post_lite($url, $data, $headers=null) {

$data = http_build_query($data);
$opts = array('http' => array('method' => 'POST', 'content' => $data));

if($headers) {
$opts['http']['header'] = $headers;
}
$st = stream_context_create($opts);
//$fp = fopen($url, 'rb', false, $st);

if(!$fp) {
return false;
}
return stream_get_contents($fp);
}

function drawGraph($val) 
{
	require_once ('../User/piegraph/jpgraph.php');
	require_once ('../User/piegraph/jpgraph_pie.php');
	
	$data = array(60,40);
	//$data = explode("_",$val);
	
	$graph = new PieGraph(500,200);
	$graph->SetShadow();
	$graph->title->Set("A simple Pie plot");
	
	$p1 = new PiePlot($data);
	$graph->Add($p1);
	$graph->Stroke();
}

function getLeaveModeMonthArray($leaveMode,$leaveStartMonth)//  this function will return an array acording to leave mode
{
	$monthsInKey = 12/$leaveMode;
	$allMonthArray = array();
	
	for($x=0; $x<$leaveMode; $x++)
	{
		$mArray = array();
		$c = 0;
		if(!in_array(12,$allMonthArray))
		{
			for($m = trim($leaveStartMonth,'0'); $m <= 12; $m++)
			{
				if(!in_array($m,$allMonthArray))
				{
					if($c < $monthsInKey)
					{
						$mArray[] = $m;
						$allMonthArray[] = $m;
						$c++;
					}
					else
					{
						$c=0;
						break;
					}
				}
			}
		}
		if(in_array(12,$allMonthArray))
		{
			for($m = 1; $m < trim($leaveStartMonth,'0'); $m++)
			{
				if(!in_array($m,$allMonthArray))
				{
					if($c < $monthsInKey)
					{
						$mArray[] = $m;
						$allMonthArray[] = $m;
						$c++;
					}
					else
					{
						$c=0;
						break;
					}
				}
			}
		}
		$LeaveModeMonthArray[] = $mArray;
	}
	return $LeaveModeMonthArray;
}

function SMSMerge($template_data, $data)
{
	foreach($data as $templateVariable => $variableValue)
	{
		$template_data = str_replace($templateVariable, $variableValue, $template_data);
	}	
	return $template_data;
}	
function excelReport($body, $filename)
{	
	header('Content-Type: application/force-download');
	header("Content-type: application/vnd.ms-excel"); 
	header("Content-Disposition: attachment; filename=".$filename);
	header('Content-Transfer-Encoding: binary');
	header("Pragma: no-cache");
	header("Expires: 0");
	print $body;
}

function calculateDaysBetweenDates($startDate,$endDate)// this function will return age in year
{
    $start_ts = strtotime($startDate);
	$end_ts = strtotime($endDate);
	$diff = $end_ts - $start_ts;
	
	$days = round($diff / 86400);
    return $days;     
}
function DateInWords($date)// this function will return date in words
{
   $d=date('j',strtotime($date));
   $m=date('F',strtotime($date));
   $y=date('Y',strtotime($date));
   
   $words=convert_number($d).' '.$m.' '.str_replace(' and','',convert_number($y));
    return $words;     
}
function exportIntoExcel($data, $headerNames, $filename, $type="", $grandTotal="")
{
	$head = "<table border=1><tr><td colspan='". count($headerNames) ."'  align='center'  bgcolor='#ABA7BA'> <strong>". 
				substr($filename, 0, (strlen($filename)-4)). " &nbsp;Report" ."</strong></td></tr><tr>";
	foreach ($headerNames as $headerName)
	{
		$head = $head."<td align=\"left\" bgcolor='#D18274'> <strong>".$headerName."</strong></td>";
	}
	
	$head = $head."</tr>";

	$dataList="<tr>";

	if(is_array($data))
	{
		$bgcolor = 0;
		foreach($data as $value)
		{
			$bgcolor = '#CCCCCC';
			if($bgcolor % 2 ==0)
				$bgcolor = '';
				
			if(is_array($value) && count($value) > 0)
			{
				foreach($value as $dataValue)
				{
					$dataList = $dataList."<td align=\"left\" bgcolor='".$bgcolor."'>". $dataValue."</td>"; 
				}
				$dataList = $dataList. "</tr>"; 
			}	
			$value=array();
			$bgcolor++;
		}
	}
	if($type == 1)
	{
		$dataList=$dataList."<tr><td style='text-align:right' colspan='".(count($headerNames)-1)."'><strong>Grand Total : Rs.</strong></td><td style='text-align:left'><strong>".$grandTotal."</strong></td></tr>";
	}
	$dataList=$dataList."</table>";
	header("Content-type: application/vnd.ms-excel"); 
	header("Content-Disposition: attachment; filename=".$filename);
	header("Pragma: no-cache");
	header("Expires: 0");
	print $head.$dataList;
}

function exportIntoExcelSinglePage($dataList, $filename)// in this function we send complete page data in htme formate
{
	$dataList = str_replace('border="0"', 'border="1"', $dataList);
	header("Content-type: application/vnd.ms-excel"); 
	header("Content-Disposition: attachment; filename=".$filename);
	header("Pragma: no-cache");
	header("Expires: 0");
	print $dataList;
}

function GetQueryStringParameters()
{
	$paramArray = array();
	if(isset($_GET['urlstring']))
	{
		$urlParams = DecryptURL($_GET['urlstring']);
		$params = explode('&', $urlParams);
		$paramArray = array();
		foreach($params as $param)
		{
			if ($param === '' || strpos($param, '=') === false) {
				continue;
			}
			list($key, $value) = explode('=',$param, 2);
			$paramArray[$key] = $value;
		}
	}

	return $paramArray;
}

function sinelec_set_flash(?string $type, ?string $message): void
{
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}

	$_SESSION['type'] = $type;
	$_SESSION['message'] = $message;
}

function sinelec_consume_flash(): array
{
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}

	$type = $_SESSION['type'] ?? null;
	$message = $_SESSION['message'] ?? null;

	$_SESSION['type'] = null;
	$_SESSION['message'] = null;
	unset($_SESSION['type'], $_SESSION['message']);

	return [
		'type' => $type,
		'message' => $message,
	];
}

function sinelec_env(string $key, ?string $default = null): ?string
{
	static $envCache = null;

	if ($envCache === null) {
		$envCache = [];
		$envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
		if (is_file($envPath)) {
			$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			if (is_array($lines)) {
				foreach ($lines as $line) {
					$line = trim($line);
					if ($line === '' || str_starts_with($line, '#') || strpos($line, '=') === false) {
						continue;
					}

					list($envKey, $envValue) = explode('=', $line, 2);
					$envKey = trim($envKey);
					$envValue = trim($envValue);
					$envValue = trim($envValue, "\"'");
					$envCache[$envKey] = $envValue;
				}
			}
		}
	}

	return $envCache[$key] ?? $default;
}

function sinelec_otp_email_html(string $email, string $otp, string $year, string $heading, string $intro): string
{
    $emailEsc   = htmlspecialchars($email);
    $headingEsc = htmlspecialchars($heading);
    $introEsc   = htmlspecialchars($intro);
    $yearEsc    = htmlspecialchars($year);

    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#EEF2F7;font-family:'Segoe UI',Inter,Arial,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#EEF2F7;padding:32px 0">
  <tr><td align="center">
    <table width="520" cellpadding="0" cellspacing="0" style="background:#FFFFFF;border-radius:14px;overflow:hidden;max-width:520px;box-shadow:0 4px 24px rgba(0,0,0,0.10)">
      <tr>
        <td style="background:#0a1a30;padding:26px 36px;text-align:center">
          <p style="margin:0;color:#FFFFFF;font-size:20px;font-weight:700;letter-spacing:0.5px">Sinelec Technologies</p>
        </td>
      </tr>
      <tr>
        <td style="padding:36px 36px 24px">
          <h2 style="margin:0 0 12px;color:#0a1a30;font-size:22px;font-weight:700">{$headingEsc}</h2>
          <p style="margin:0 0 24px;color:#4A5568;font-size:15px;line-height:1.7">{$introEsc} for <strong>{$emailEsc}</strong>.<br>Use the OTP below to continue. This code expires in <strong>10 minutes</strong>.</p>
          <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td style="background:#EBF3FF;border:2px dashed #0f5ebf;border-radius:12px;padding:24px;text-align:center">
                <p style="margin:0 0 8px;color:#0f5ebf;font-size:11px;font-weight:600;letter-spacing:2.5px;text-transform:uppercase">Your One-Time Password</p>
                <p style="margin:0;font-size:44px;font-weight:800;letter-spacing:18px;color:#0f5ebf;font-family:'Courier New',Courier,monospace">{$otp}</p>
              </td>
            </tr>
          </table>
          <p style="margin:24px 0 8px;color:#718096;font-size:13px;line-height:1.7">&#9200; Valid for <strong>10 minutes</strong> only. Do not share this code with anyone.</p>
          <p style="margin:0;color:#718096;font-size:13px;line-height:1.7">&#128274; If you did not request this, you can safely ignore this email. Your account is secure.</p>
        </td>
      </tr>
      <tr>
        <td style="background:#F7F9FC;padding:16px 36px;text-align:center;border-top:1px solid #E2E8F0">
          <p style="margin:0;color:#A0AEC0;font-size:12px">&copy; {$yearEsc} Sinelec Technologies. All rights reserved.</p>
        </td>
      </tr>
    </table>
  </td></tr>
</table>
</body>
</html>
HTML;
}

/**
 * Send one or more emails via SMTP.
 *
 * @param array $recipients  Each item: ['to_mail_id'=>'', 'subject'=>'', 'body'=>'']
 *                           Body can be HTML. Works for single or multiple recipients.
 */
function sinelec_send_mail(array $recipients): bool
{
    if (empty($recipients)) {
        return true;
    }

    $host       = (string)sinelec_env('MAIL_HOST', '');
    $port       = (int)sinelec_env('MAIL_PORT', '465');
    $username   = (string)sinelec_env('MAIL_USERNAME', '');
    $password   = (string)sinelec_env('MAIL_PASSWORD', '');
    $encryption = strtolower((string)sinelec_env('MAIL_ENCRYPTION', 'ssl'));
    $fromAddr   = (string)sinelec_env('MAIL_FROM_ADDRESS', $username);
    $fromName   = (string)sinelec_env('MAIL_FROM_NAME', 'Sinelec Technologies');

    if ($host === '' || $username === '' || $password === '') {
        error_log('sinelec_send_mail: SMTP configuration is incomplete.');
        return false;
    }

    $allSuccess = true;

    foreach ($recipients as $item) {
        $toEmail = trim((string)($item['to_mail_id'] ?? ''));
        $subject = trim((string)($item['subject'] ?? '(no subject)'));
        $body    = (string)($item['body'] ?? '');

        if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            error_log('sinelec_send_mail: skipping invalid or empty address — ' . $toEmail);
            continue;
        }

        $sent = sinelec_smtp_deliver(
            $host, $port, $encryption,
            $username, $password,
            $fromAddr, $fromName,
            $toEmail, $subject, $body
        );

        if (!$sent) {
            $allSuccess = false;
        }
    }

    return $allSuccess;
}

/**
 * Low-level SMTP delivery over a single connection.
 * Supports implicit SSL (port 465) and STARTTLS (port 587).
 */
function sinelec_smtp_deliver(
    string $host,
    int    $port,
    string $encryption,
    string $username,
    string $password,
    string $fromAddr,
    string $fromName,
    string $toAddr,
    string $subject,
    string $bodyHtml
): bool {
    $timeout = 20;
    $errno   = 0;
    $errstr  = '';

    $sslContext = stream_context_create([
        'ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ],
    ]);

    if ($encryption === 'ssl') {
        $conn = @stream_socket_client(
            "ssl://{$host}:{$port}", $errno, $errstr, $timeout,
            STREAM_CLIENT_CONNECT, $sslContext
        );
    } else {
        $conn = @stream_socket_client(
            "{$host}:{$port}", $errno, $errstr, $timeout,
            STREAM_CLIENT_CONNECT
        );
    }

    if (!is_resource($conn)) {
        error_log("sinelec_smtp_deliver: connect failed [{$errno}] {$errstr}");
        return false;
    }

    stream_set_timeout($conn, $timeout);

    $read = static function () use ($conn): string {
        $buf = '';
        while (!feof($conn)) {
            $line = fgets($conn, 1024);
            if ($line === false) {
                break;
            }
            $buf .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        return $buf;
    };

    $cmd = static function (string $c) use ($conn, $read): string {
        fputs($conn, $c . "\r\n");
        return $read();
    };

    $code = static function (string $r): int {
        return (int)substr(trim($r), 0, 3);
    };

    // Banner
    $banner = $read();
    if ($code($banner) !== 220) {
        error_log("sinelec_smtp_deliver: unexpected banner: {$banner}");
        fclose($conn);
        return false;
    }

    // EHLO
    $r = $cmd('EHLO ' . (gethostname() ?: 'localhost'));
    if ($code($r) !== 250) {
        error_log("sinelec_smtp_deliver: EHLO failed: {$r}");
        fclose($conn);
        return false;
    }

    // STARTTLS upgrade for port 587 / tls
    if ($encryption === 'tls') {
        $r = $cmd('STARTTLS');
        if ($code($r) !== 220) {
            error_log("sinelec_smtp_deliver: STARTTLS failed: {$r}");
            fclose($conn);
            return false;
        }
        if (!stream_socket_enable_crypto($conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            error_log('sinelec_smtp_deliver: TLS crypto handshake failed');
            fclose($conn);
            return false;
        }
        $cmd('EHLO ' . (gethostname() ?: 'localhost'));
    }

    // AUTH LOGIN
    $r = $cmd('AUTH LOGIN');
    if ($code($r) !== 334) {
        error_log("sinelec_smtp_deliver: AUTH LOGIN init failed: {$r}");
        fclose($conn);
        return false;
    }
    $cmd(base64_encode($username));
    $r = $cmd(base64_encode($password));
    if ($code($r) !== 235) {
        error_log("sinelec_smtp_deliver: AUTH credentials rejected: {$r}");
        fclose($conn);
        return false;
    }

    // MAIL FROM
    $r = $cmd("MAIL FROM:<{$fromAddr}>");
    if ($code($r) !== 250) {
        error_log("sinelec_smtp_deliver: MAIL FROM rejected: {$r}");
        fclose($conn);
        return false;
    }

    // RCPT TO
    $r = $cmd("RCPT TO:<{$toAddr}>");
    if ($code($r) !== 250) {
        error_log("sinelec_smtp_deliver: RCPT TO rejected for {$toAddr}: {$r}");
        fclose($conn);
        return false;
    }

    // DATA
    $r = $cmd('DATA');
    if ($code($r) !== 354) {
        error_log("sinelec_smtp_deliver: DATA init rejected: {$r}");
        fclose($conn);
        return false;
    }

    // Build multipart/alternative MIME message
    $boundary   = '----=_Part_' . md5(uniqid('sinelec', true));
    $encSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $encFrom    = '=?UTF-8?B?' . base64_encode($fromName) . '?= <' . $fromAddr . '>';
    $plainText  = strip_tags(str_replace(
        ['<br>', '<br/>', '<br />', '</p>', '</li>', '</h1>', '</h2>', '</h3>'],
        ["\n",   "\n",    "\n",     "\n\n", "\n",    "\n",    "\n",    "\n"],
        $bodyHtml
    ));

    $headers  = "Date: " . date('r') . "\r\n";
    $headers .= "From: {$encFrom}\r\n";
    $headers .= "To: {$toAddr}\r\n";
    $headers .= "Subject: {$encSubject}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
    $headers .= "X-Mailer: Sinelec-PHP-Mailer/1.0\r\n";

    $mime  = "--{$boundary}\r\n";
    $mime .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $mime .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $mime .= chunk_split(base64_encode($plainText)) . "\r\n";
    $mime .= "--{$boundary}\r\n";
    $mime .= "Content-Type: text/html; charset=UTF-8\r\n";
    $mime .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $mime .= chunk_split(base64_encode($bodyHtml)) . "\r\n";
    $mime .= "--{$boundary}--";

    // Send message body + end-of-data marker
    fputs($conn, $headers . "\r\n" . $mime . "\r\n.\r\n");
    $r = $read();
    if ($code($r) !== 250) {
        error_log("sinelec_smtp_deliver: message body rejected: {$r}");
        fclose($conn);
        return false;
    }

    $cmd('QUIT');
    fclose($conn);
    return true;
}

function sinelec_validate_turnstile(string $token, ?string $remoteIp = null): array
{
	$secretKey = sinelec_env('SECRET_KEY');
	if (!$secretKey) {
		return [
			'success' => false,
			'error-codes' => ['missing-secret-key'],
		];
	}

	if ($token === '') {
		return [
			'success' => false,
			'error-codes' => ['missing-input-response'],
		];
	}

	$payload = [
		'secret' => $secretKey,
		'response' => $token,
	];

	if ($remoteIp) {
		$payload['remoteip'] = $remoteIp;
	}

	$options = [
		'http' => [
			'header' => "Content-type: application/x-www-form-urlencoded\r\n",
			'method' => 'POST',
			'content' => http_build_query($payload),
			'timeout' => 10,
		],
	];

	$context = stream_context_create($options);
	$response = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);

	if ($response === false) {
		return [
			'success' => false,
			'error-codes' => ['internal-error'],
		];
	}

	$decoded = json_decode($response, true);
	return is_array($decoded) ? $decoded : [
		'success' => false,
		'error-codes' => ['invalid-json'],
	];
}
?>
