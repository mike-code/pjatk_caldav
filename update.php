<?php
require_once 'vendor/autoload.php';

use Carbon\Carbon;

const CAL_ID_FILE = 'cal.id';

$client = new Google_Client();
$client->useApplicationDefaultCredentials();
$client->setScopes(['https://www.googleapis.com/auth/calendar']);

$calservice = new Google_Service_Calendar ($client);

if(!file_exists(CAL_ID_FILE))
{
    $calendar = new Google_Service_Calendar_Calendar();
    $calendar->setSummary("Plan Zajęć PJWSTK");
    $calendar->setTimeZone('Europe/Warsaw');

    $cal = $calservice->calendars->insert($calendar);

    $rule = new Google_Service_Calendar_AclRule();
    $scope = new Google_Service_Calendar_AclRuleScope();
    $scope->setType("user");
    $scope->setValue("s18069@pjwstk.edu.pl");
    $rule->setScope($scope);
    $rule->setRole("writer");

    $calservice->acl->insert($cal->id, $rule);

    file_put_contents(CAL_ID_FILE, $cal->id);
}

$calId = file_get_contents(CAL_ID_FILE);

$o = json_decode(file_get_contents('events.json'));

foreach($o as $e)
{
    $typ = $e->TypZajec == 'Ćwiczenia' ? 'ćw' : 'wyk';

    $event = new Google_Service_Calendar_Event([
        'summary' => "($typ) {$e->Nazwa}",
        'location' => "Budynek {$e->Budynek}, Sala {$e->Nazwa_sali}",
        'description' => "#{$e->idRealizacja_zajec}#",
        'start' =>
        [
            'dateTime' => Carbon::createFromFormat('Y-m-d H:i', $e->Data_roz, 'Europe/Warsaw')->toAtomString(),
            'timeZone' => 'Europe/Warsaw',
        ],
        'end' =>
        [
            'dateTime' => Carbon::createFromFormat('Y-m-d H:i', $e->Data_zak, 'Europe/Warsaw')->toAtomString(),
            'timeZone' => 'Europe/Warsaw',
        ],
        'reminders' =>
        [
            'useDefault' => FALSE,
            'overrides' =>
            [
                array('method' => 'popup', 'minutes' => 15),
            ]
        ]
    ]);

    $event = $calservice->events->insert($calId, $event);
}

// $event = $calservice->events->listEvents($calId);

// dump($event);


// foreach($calservice->calendarList->listCalendarList() as $cal)
// {
//  dump($cal);
//  //$calservice->calendars->delete($cal->id);
// }
exit;

echo "done", PHP_EOL;
