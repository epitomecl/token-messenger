<?php 
namespace modules;

/**
* Firebase push notification service. This module send a push notification 
* to a device specified by current device token.
* 
*/
class PushNotification {
	private static $URL  = "https://fcm.googleapis.com/fcm/send";  //API URL of FCM
	private static $SERVER_ACCESS_KEY = 'AAAA4LxS8Jg:APA91bF_cIHoXuaDy4UcwuAEiqQ0JW1dtfTPNRxRZ3u0BD6HnOygfp34UOr2LeqtBlhavsgk63ufozil_yobEmF9iBo6H7dYwMC22vCMwpQ0IwBHMpP9dNVMextZPkViQ4LRWtL_-kqx'; // YOUR_FIREBASE_API_KEY
 
	public function __construct() {	}

	/**
	* something describes this method
	*
	* @param string $pushToken The device token of registered device
	* @param string $title The title of the push message
	* @param string $body The body of the push message
	*/	
	public function doPost($pushToken, $title, $body) {
		$fields = ["title" => $title, "body" => $body];
		$arrToken = array($pushToken);
		$result = PushNotification::sendPushNotification($arrToken, $fields);
		
		echo $result;
	}
	
	/** 
	 * @param array $registrationIds RegistrationIds
	 * @param array $fields Fields as ["title" => "message title 1", "body" => "message text 2"];
	 * @return json {"multicast_id":497400137751558933,"success":1,"failure":0,"canonical_ids":0,"results":[{"message_id":"0:1581495851775474%e609af1cf9fd7ecd"}]}
	*/
	public static function sendPushNotification($registrationIds = array(), $fields = array()) {
		$msg     = array('body' => $fields['body'], 'title'  => $fields['title']);
		$fields  = array('registration_ids' => $registrationIds, 'notification' => $msg);
		$headers = array('Authorization: key=' . self::$SERVER_ACCESS_KEY, 'Content-Type: application/json');

		if (count($registrationIds) > 0) {
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
		
		return NULL;
	}
}