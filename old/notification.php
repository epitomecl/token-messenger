<?php 

class PushNotification {
	private static $URL  = "https://fcm.googleapis.com/fcm/send";  //API URL of FCM
	private static $SERVER_ACCESS_KEY = 'AAAA4LxS8Jg:APA91bF_cIHoXuaDy4UcwuAEiqQ0JW1dtfTPNRxRZ3u0BD6HnOygfp34UOr2LeqtBlhavsgk63ufozil_yobEmF9iBo6H7dYwMC22vCMwpQ0IwBHMpP9dNVMextZPkViQ4LRWtL_-kqx'; // YOUR_FIREBASE_API_KEY
 

	public function __construct() {	}
 
	public static function sendPushNotification($token = "", $fields = array())
	{
		$registrationIds = array();
		 
		array_push($registrationIds, $token);

		$msg     = array('body' => $fields['body'], 'title'  => $fields['title']);
		$fields  = array('registration_ids' => $registrationIds, 'notification' => $msg);
		$headers = array('Authorization: key=' . self::$SERVER_ACCESS_KEY, 'Content-Type: application/json');

		#Send Reponse To FireBase Server    
		$ch = curl_init(); 
		curl_setopt($ch,CURLOPT_URL, self::$URL);
		curl_setopt($ch,CURLOPT_POST, true);
		curl_setopt($ch,CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch,CURLOPT_POSTFIELDS, json_encode($fields));
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
}
 
$device_token    =  isset($_GET["token"]) ? trim($_GET["token"]) : "";
$fields          =  ["title" => "5 token from X", "body" => "Check this out."];
$response        =  PushNotification::sendPushNotification($device_token, $fields);
print_r($response);  