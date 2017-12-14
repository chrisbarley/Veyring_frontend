<?php
// get json data
$json_data = null;

$content_type = $_SERVER["CONTENT_TYPE"] ?: $_SERVER['HTTP_CONTENT_TYPE'];

if ($content_type && (strpos(strtolower($content_type), "json") !== false))
{
	$inp = file_get_contents('php://input');
	$json_data = json_decode($inp, true);
	file_put_contents("requests/".uniqid().".txt", $inp);
}

// require conn
require_once("../_conn.php");

// if (!$json_data)
// {
// 	$json_data = json_encode(['to' => '013812312341231', 'from' => '8236582736', 'body' => 'hello', 'hunt_group' => ['id' => '01934440509', 'name' => 'Hunt Group 1'], 'callback_url' => 'https://veyring.com/twillio/test_callback.php']);
// }
// die ('working...');
//
/*
{
	'to' => params['To'],
	'from' => params['From'],
	'body' => params['Body'],
	'customer' => {
		'id' => customer.id
	},
	'hunt_group' => {
		'name' => hunt_group.name,
		'id' => hunt_group.id
	},
	'callback_url' => ENV['CALLBACK_MESSAGE_URL']
}
*/

// get data from request
$to				= $json_data['to'];
$from			= $json_data['from'];
$twilioId		= $json_data['hunt_group']['id'];
$body			= $json_data['body'];
$callbackUrl	= $json_data['callback_url'];

// get veyring group by twilio id
$group = QQuery("Groups.{*,TwilioUsers.* WHERE TwilioNumber='{$to}'}")->Groups[0];

// throw exception if group not fpund
if (!$group)
	throw new Exception('Group could not be found!');

// get from
if (!$from)
	throw new Exception('From could not be identified!');

// set callback url on group
$group->TwilioCallbackURL = $callbackUrl;
$group->TwilioNumber = $to;
$group->TwilioId = $twilioId;

// set twilio users on group
if ($group->TwilioUsers === null)
	$group->TwilioUsers = new QModelArray();

// check existing user
$twilioUser = new VyTwilioUser();
$twilioUser->Name = $from;
$twilioUser->Group = $group;

// add user to collection
// $group->TwilioUsers[] = $twilioUser;

// update group
$group->merge("TwilioCallbackURL, TwilioNumber, TwilioId"); // TwilioUsers.*

// create message log for twillio
$messageLog = new VyMessageLog();
$messageLog->Message = $body;
$messageLog->KandyGroupId = $group->KandyId;
$messageLog->SenderId = str_replace('+', '', $from);
$messageLog->ReceiverId = str_replace('+', '', $to);
$messageLog->OfflineMessage = true;
$messageLog->Type = "pstn-message";
$messageLog->Unread = true;
$messageLog->DateTime = date("Y-m-d H:i:s");

// save into db
$messageLog->merge("Message, KandyGroupId, DateTime, ReceiverId, SenderId, OfflineMessage, Unread");

// create activity
$activity = new VyActivity();
// $activity->Owner 
$activity->ActivityType = 'pstn-message';
$activity->KandyGroupId = $group->KandyId;
$activity->Title = $body;
$activity->CallerNumber = str_replace('+', '', $from);
$activity->CalleeNumber = str_replace('+', '', $to);
$activity->DateTime = date("Y-m-d H:i:s");
$activity->Message = $messageLog;
$activity->Active = 1;

// save activity
$activity->merge("ActivityType, Title, KandyGroupId, Message.*, Active, DateTime, CallerNumber, CalleeNumber");

if ($group->KandyId)
{
	// get conversation
	$conversation = QQuery("Conversations.{*, Activities.* WHERE KandyGroupId='{$group->KandyId}' AND Status='opened'}")->Conversations[0];

	// create and populate conversation if it does not exist
	if (!$conversation)
	{
		// new conversation
		$conversation = new VyConversation();

		// populate conversation
		$conversation->KandyGroupId = $group->KandyId;
		// $conversation->Owner		= $loggedInUser;
		$conversation->AddedDate	= date("Y-m-d H:i:s");
		$conversation->Status		= 'opened';
		$conversation->Activities	= new QModelArray();
	}

	// add activity to conversations
	$conversation->Activities[] = $activity;

	// add last activity as current activity
	$conversation->LastActivity = $activity;

	// save conversation with activities
	$conversation->merge("KandyGroupId, Owner, AddedDate, Status, Activities.*, LastActivity");
}

if ($from)
{
	$curl1 = curl_init("http://customerdb.veyring.com/endUser/token/103");

	curl_setopt($curl1, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl1, CURLINFO_HEADER_OUT, true);
	$resp1 = curl_exec($curl1);

	$token = json_decode($resp1)->token;

	// $from_number = str_replace('+', '%2B', $from);

	$curl = curl_init("http://customerdb.veyring.com/customer");
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(['telephone_number' => $from]));
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER, array(
		'Authorization: Bearer ' . $token,
		'Content-Type: application/x-www-form-urlencoded'
	));
	$resp = curl_exec($curl);
}

// qvar_dump($activity); die;
/*
- from
- to | ???
- hunt_group => { :id, :name }
- body
- callback_url

this is a message for the hunt group => { :id, :name }
*/