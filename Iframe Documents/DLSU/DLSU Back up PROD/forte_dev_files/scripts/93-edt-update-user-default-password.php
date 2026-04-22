<?php 

require 'vendor/autoload.php';
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

$pmHost = getenv('API_HOST');
$pmToken = getenv('API_TOKEN');
$pmHeaders = array(
   "Accept: application/json",
   "Authorization: Bearer ". $pmToken,
);

$pm4_users = $pmHost.'/users/?per_page=99999';
$pm4_user_curl = curl_init($pm4_users);
curl_setopt($pm4_user_curl, CURLOPT_URL, $pm4_users);
curl_setopt($pm4_user_curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($pm4_user_curl, CURLOPT_HTTPHEADER, $pmHeaders);
curl_setopt($pm4_user_curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($pm4_user_curl, CURLOPT_SSL_VERIFYPEER, false);
$user_result = curl_exec($pm4_user_curl);
curl_close($pm4_user_curl);
$user_result_json = json_decode($user_result);

// $apiInstance = $api->users();
// $user = $apiInstance->getUserById(549);
// $group_name = $user -> getEmail();
$user_result_json_count = count($user_result_json->data);
$user_result = $user_result_json->data;
$all_user_ids = [];

for($x=0; $x < $user_result_json_count; $x++){
   $user_id = $user_result[$x]->id;
   $email = $user_result[$x]->email;
   $firstname = $user_result[$x]->firstname;
   $lastname = $user_result[$x]->lastname;
   $title = $user_result[$x]->title;
   $user = ["user_id"=>$user_id, "email"=>$email, "firstname"=>$firstname, "lastname"=>$lastname, "title"=>$title];
   array_push($all_user_ids, $user);
   // $default_password = "Dlsu!-20231013_".$user_id;
   // if($user_id == 706){
   // if($user_id==50 || $user_id== 523  || $user_id==51  || $user_id==52  
   // || $user_id==53  || $user_id==54  || $user_id==55 || $user_id== 784 
   // || $user_id== 56  || $user_id== 57 || $user_id== 58 || $user_id== 59 
   // || $user_id== 60  || $user_id== 583 || $user_id== 61 || $user_id== 62 
   // || $user_id== 63 || $user_id== 64 || $user_id== 508 || $user_id== 65
   // || $user_id== 566){
/*
   if($user_id == 50 || $user_id ==523 || $user_id ==51 || $user_id ==52 
   || $user_id ==53 || $user_id ==54 || $user_id ==55 || $user_id ==784 
   || $user_id ==56 || $user_id ==57 || $user_id ==58 || $user_id ==59 
   || $user_id ==60 || $user_id ==583 || $user_id ==62 || $user_id ==63 
   || $user_id ==64 || $user_id ==508 || $user_id ==65 || $user_id ==66 
   || $user_id ==68 || $user_id ==69 || $user_id ==499 || $user_id ==70 
   || $user_id ==71 || $user_id ==550 || $user_id ==614 || $user_id ==781 
   || $user_id ==586 || $user_id ==490 || $user_id ==73 || $user_id ==74 
   || $user_id ==75 || $user_id ==76 || $user_id ==540 || $user_id ==77 
   || $user_id ==646 || $user_id ==662 || $user_id ==80 || $user_id ==687 
   || $user_id ==82 || $user_id ==83 || $user_id ==698 || $user_id ==553 
   || $user_id ==778 || $user_id ==668 || $user_id ==84 || $user_id ==595 
   || $user_id ==85 || $user_id ==86 || $user_id ==456 || $user_id ==88 
   || $user_id ==89 || $user_id ==90 || $user_id ==91 || $user_id ==92 
   || $user_id ==93 || $user_id ==791 || $user_id ==94 || $user_id ==95 
   || $user_id ==96 || $user_id ==512 || $user_id ==476 || $user_id ==97 
   || $user_id ==493 || $user_id ==690 || $user_id ==802 || $user_id ==645 
   || $user_id ==98 || $user_id ==599 || $user_id ==99 || $user_id ==100 
   || $user_id ==660 || $user_id ==101 || $user_id ==102 || $user_id ==559 
   || $user_id ==497 || $user_id ==520 || $user_id ==103 || $user_id ==769 
   || $user_id ==104 || $user_id ==715 || $user_id ==810 || $user_id ==106 
   || $user_id ==455 || $user_id ==794 || $user_id ==107 || $user_id ==648 
   || $user_id ==108 || $user_id ==109 || $user_id ==110 || $user_id ==749 
   || $user_id ==111 || $user_id ==665 || $user_id ==112 || $user_id ==113 
   || $user_id ==114 || $user_id ==115 || $user_id ==688 || $user_id ==116 
   || $user_id ==700 || $user_id ==811 || $user_id ==707 || $user_id ==117 
   || $user_id ==461 || $user_id ==118 || $user_id ==119 || $user_id ==120 
   || $user_id ==121 || $user_id ==685 || $user_id ==122 || $user_id ==446 
   || $user_id ==123 || $user_id ==780 || $user_id ==591 || $user_id ==796 
   || $user_id ==124 || $user_id ==125 || $user_id ==126 || $user_id ==127 
   || $user_id ==760 || $user_id ==584 || $user_id ==128 || $user_id ==129 
   || $user_id ==130 || $user_id ==131 || $user_id ==489 || $user_id ==132 
   || $user_id ==773 || $user_id ==457 || $user_id ==500 || $user_id ==604 
   || $user_id ==133 || $user_id ==134 || $user_id ==741 || $user_id ==135 
   || $user_id ==799 || $user_id ==486 || $user_id ==136 || $user_id ==659 
   || $user_id ==137 || $user_id ==804 || $user_id ==138 || $user_id ==139 
   || $user_id ==140 || $user_id ==777 || $user_id ==141 || $user_id ==142 
   || $user_id ==143 || $user_id ==144 || $user_id ==485 || $user_id ==145 
   || $user_id ==146 || $user_id ==577 || $user_id ==147 || $user_id ==633 
   || $user_id ==148 || $user_id ==473 || $user_id ==149 || $user_id ==150 
   || $user_id ==598 || $user_id ==151 || $user_id ==788 || $user_id ==152 
   || $user_id ==153 || $user_id ==154 || $user_id ==155 || $user_id ==758 
   || $user_id ==571 || $user_id ==538 || $user_id ==156 || $user_id ==157 
   || $user_id ==158 || $user_id ==720 || $user_id ==562 || $user_id ==160 
   || $user_id ==161 || $user_id ==162 || $user_id ==163 || $user_id ==164 
   || $user_id ==165 || $user_id ==166 || $user_id ==717 || $user_id ==167 
   || $user_id ==168 || $user_id ==468 || $user_id ==169 || $user_id ==770 
   || $user_id ==170 || $user_id ==171 || $user_id ==172 || $user_id ==173 
   || $user_id ==670 || $user_id ==174 || $user_id ==175 || $user_id ==176 || $user_id ==177 || $user_id ==178 || $user_id ==179 || $user_id ==180 || $user_id ==181 || $user_id ==475 || $user_id ==756 || $user_id ==477 || $user_id ==182 || $user_id ==183 || $user_id ==184 || $user_id ==783 || $user_id ==795 || $user_id ==441 || $user_id ==185 || $user_id ==634 || $user_id ==465 || $user_id ==609 || $user_id ==716 || $user_id ==186 || $user_id ==187 || $user_id ==188 || $user_id ==594 || $user_id ==189 || $user_id ==190 || $user_id ==191 || $user_id ==518 || $user_id ==693 || $user_id ==192 || $user_id ==193 || $user_id ==492 || $user_id ==194 || $user_id ==750 || $user_id ==459 || $user_id ==195 || $user_id ==196 || $user_id ==197 || $user_id ==580 || $user_id ==755 || $user_id ==198 || $user_id ==513 || $user_id ==644 || $user_id ==199 || $user_id ==200 || $user_id ==806 || $user_id ==201 || $user_id ==202 || $user_id ==636 || $user_id ==203 || $user_id ==736 || $user_id ==775 || $user_id ==204 || $user_id ==205 || $user_id ==206 || $user_id ==207 || $user_id ==488 || $user_id ==695 || $user_id ==445 || $user_id ==208 || $user_id ==601 || $user_id ==534 || $user_id ==574 || $user_id ==209 || $user_id ==718 || $user_id ==506 || $user_id ==464 || $user_id ==714 || $user_id ==785 || $user_id ==761 || $user_id ==533 || $user_id ==539 || $user_id ==514 || $user_id ==724 || $user_id ==210 || $user_id ==702 || $user_id ==480 || $user_id ==517 || $user_id ==680 || $user_id ==211 || $user_id ==212 || $user_id ==807 || $user_id ==762 || $user_id ==213 || $user_id ==214 || $user_id ==215 || $user_id ==216 || $user_id ==460 || $user_id ==467 || $user_id ==438 || $user_id ==217 || $user_id ==641 || $user_id ==218 || $user_id ==710 || $user_id ==219 || $user_id ==725 || $user_id ==220 || $user_id ==221 || $user_id ==551 || $user_id ==639 || $user_id ==735 || $user_id ==222 || $user_id ==223 || $user_id ==740 || $user_id ==444 || $user_id ==572 || $user_id ==224 || $user_id ==643 || $user_id ==751 || $user_id ==225 || $user_id ==603 || $user_id ==226 || $user_id ==227 || $user_id ==228 || $user_id ==752 || $user_id ==656 || $user_id ==229 || $user_id ==712 || $user_id ==230 || $user_id ==709 || $user_id ==581 || $user_id ==231 || $user_id ==232 || $user_id ==772 || $user_id ==505 || $user_id ==543 || $user_id ==233 || $user_id ==657 || $user_id ==726 || $user_id ==234 || $user_id ==674 || $user_id ==732 || $user_id ==236 || $user_id ==237 || $user_id ==642 || $user_id ==545 || $user_id ==238 || $user_id ==613 || $user_id ==239 || $user_id ==560 || $user_id ==240 || $user_id ==782 || $user_id ==241 || $user_id ==532 || $user_id ==494 || $user_id ==242 || $user_id ==675 || $user_id ==805 || $user_id ==243 || $user_id ==439 || $user_id ==711 || $user_id ==244 || $user_id ==774 || $user_id ==798 || $user_id ==245 || $user_id ==684 || $user_id ==246 || $user_id ==247 || $user_id ==730 || $user_id ==496 || $user_id ==683 || $user_id ==744 || $user_id ==793 || $user_id ==248 || $user_id ==694 || $user_id ==471 || $user_id ==249 || $user_id ==527 || $user_id ==652 || $user_id ==686 || $user_id ==587 || $user_id ==250 || $user_id ==522 || $user_id ==251 || $user_id ==676 || $user_id ==697 || $user_id ==252 || $user_id ==729 || $user_id ==612 || $user_id ==611 || $user_id ==253 || $user_id ==661 || $user_id ==254 || $user_id ==255 || $user_id ==256 || $user_id ==257 || $user_id ==258 || $user_id ==259 || $user_id ==260 || $user_id ==454 || $user_id ==261 || $user_id ==701 || $user_id ==262 || $user_id ==529 || $user_id ==651 || $user_id ==263 || $user_id ==264 || $user_id ==637 || $user_id ==803 || $user_id ==265 || $user_id ==266 || $user_id ==654 || $user_id ==267 || $user_id ==268 || $user_id ==570 || $user_id ==269 || $user_id ==270 || $user_id ==271 || $user_id ==272 || $user_id ==536 || $user_id ==273 || $user_id ==274 || $user_id ==275 || $user_id ==666 || $user_id ==276 || $user_id ==537 || $user_id ==277 || $user_id ==278 || $user_id ==790 || $user_id ==279 || $user_id ==766 || $user_id ==280 || $user_id ==576 || $user_id ==281 || $user_id ==282 || $user_id ==283 || $user_id ==284 || $user_id ==285 || $user_id ==286 || $user_id ==287 || $user_id ==568 || $user_id ==288 || $user_id ==682 || $user_id ==289 || $user_id ==728 || $user_id ==290 || $user_id ==291 || $user_id ==573 || $user_id ==292 || $user_id ==484 || $user_id ==293 || $user_id ==478 || $user_id ==294 || $user_id ==295 || $user_id ==296 || $user_id ==297 || $user_id ==298 || $user_id ==299 || $user_id ==469 || $user_id ==300 || $user_id ==301 || $user_id ==302 || $user_id ==737 || $user_id ==303 || $user_id ==753 || $user_id ==304 || $user_id ==305 || $user_id ==307 || $user_id ==544 || $user_id ==653 || $user_id ==308 || $user_id ==309 || $user_id ==746 || $user_id ==310 || $user_id ==311 || $user_id ==312 || $user_id ==589 || $user_id ==708 || $user_id ==313 || $user_id ==314 || $user_id ==771 || $user_id ==306 || $user_id ==463 || $user_id ==315 || $user_id ==669 || $user_id ==316 || $user_id ==579 || $user_id ==602 || $user_id ==535 || $user_id ==448 || $user_id ==526 || $user_id ==317 || $user_id ==318 || $user_id ==319 || $user_id ==554 || $user_id ==320 || $user_id ==321 || $user_id ==673 || $user_id ==322 || $user_id ==323 || $user_id ==443 || $user_id ==548 || $user_id ==452 || $user_id ==324 || $user_id ==530 || $user_id ==638 || $user_id ==325 || $user_id ==649 || $user_id ==705 || $user_id ==326 || $user_id ==327 || $user_id ==491 || $user_id ==328 || $user_id ==575 || $user_id ==329 || $user_id ==330 || $user_id ==567 || $user_id ==451 || $user_id ==331 || $user_id ==332 || $user_id ==578 || $user_id ==450 || $user_id ==481 || $user_id ==333 || $user_id ==334 || $user_id ==335 || $user_id ==757 || $user_id ==808 || $user_id ==336 || $user_id ==787 || $user_id ==337 || $user_id ==338 || $user_id ==738 || $user_id ==474 || $user_id ==541 || $user_id ==339 || $user_id ==558 || $user_id ==340 || $user_id ==786 || $user_id ==557 || $user_id ==719 || $user_id ==764 || $user_id ==809 || $user_id ==498 || $user_id ==341 || $user_id ==342 || $user_id ==462 || $user_id ==343 || $user_id ==552 || $user_id ==344 || $user_id ==767 || $user_id ==345 || $user_id ==346 || $user_id ==347 || $user_id ==348 || $user_id ==349 || $user_id ==655 || $user_id ==350 || $user_id ==351 || $user_id ==352 || $user_id ==353 || $user_id ==354 || $user_id ==453 || $user_id ==355 || $user_id ==356 || $user_id ==647 || $user_id ==357 || $user_id ==779 || $user_id ==358 || $user_id ==588 || $user_id ==359 || $user_id ==679 || $user_id ==360 || $user_id ==516 || $user_id ==723 || $user_id ==361 || $user_id ==362 || $user_id ==727 || $user_id ==525 || $user_id ==663 || $user_id ==667 || $user_id ==363 || $user_id ==672 || $user_id ==689 || $user_id ==510 || $user_id ==671 || $user_id ==608 || $user_id ==364 || $user_id ==447 || $user_id ==692 || $user_id ==365 || $user_id ==585 || $user_id ==640 || $user_id ==366 || $user_id ==367 || $user_id ==703 || $user_id ==515 || $user_id ==734 || $user_id ==470 || $user_id ==509 || $user_id ==597 || $user_id ==610 || $user_id ==368 || $user_id ==607 || $user_id ==369 || $user_id ==370 || $user_id ==371 || $user_id ==372 || $user_id ==373 || $user_id ==472 || $user_id ==800 || $user_id ==374 || $user_id ==375 || $user_id ==376 || $user_id ==377 || $user_id ==754 || $user_id ==789 || $user_id ==378 || $user_id ==502 || $user_id ==801 || $user_id ==501 || $user_id ==379 || $user_id ==739 || $user_id ==380 || $user_id ==747 || $user_id ==381 || $user_id ==382 || $user_id ==519 || $user_id ==383 || $user_id ==713 || $user_id ==384 || $user_id ==524 || $user_id ==504 || $user_id ==691 || $user_id ==385 || $user_id ==386 || $user_id ==387 || $user_id ==531 || $user_id ==632 || $user_id ==388 || $user_id ==495 || $user_id ==389 || $user_id ==600 || $user_id ==722 || $user_id ==390 || $user_id ==391 || $user_id ==392 || $user_id ==393 || $user_id ==394 || $user_id ==704 || $user_id ==395 || $user_id ==396 || $user_id ==397 || $user_id ==398 || $user_id ==399 || $user_id ==400 || $user_id ==401 || $user_id ==402 || $user_id ==403 || $user_id ==404 || $user_id ==664 || $user_id ==405 || $user_id ==650 || $user_id ==677 || $user_id ==406 || $user_id ==407 || $user_id ==482 || $user_id ==763 || $user_id ==569 || $user_id ==408 || $user_id ==487 || $user_id ==409 || $user_id ==410 || $user_id ==606 || $user_id ==411 || $user_id ==759 || $user_id ==678 || $user_id ==521 || $user_id ==733 || $user_id ==658 || $user_id ==412 || $user_id ==542 || $user_id ==458 || $user_id ==413 || $user_id ==812 || $user_id ==721 || $user_id ==593 || $user_id ==414 || $user_id ==792 || $user_id ==696 || $user_id ==415 || $user_id ==635 || $user_id ==416 || $user_id ==417 || $user_id ==418 || $user_id ==745 || $user_id ==699 || $user_id ==419 || $user_id ==420 || $user_id ==421 || $user_id ==422 || $user_id ==748 || $user_id ==423 || $user_id ==424 || $user_id ==425 || $user_id ==731 || $user_id ==426 || $user_id ==427 || $user_id ==428 || $user_id ==429 || $user_id ==430 || $user_id ==449 || $user_id ==743 || $user_id ==596 || $user_id ==590 || $user_id ==765 || $user_id ==797 || $user_id ==431 || $user_id ==432 || $user_id ==433 || $user_id ==434 || $user_id ==681 || $user_id ==479 || $user_id ==592 || $user_id ==435 || $user_id ==547 
   || $user_id ==436 || $user_id ==776 || $user_id ==437){
      
   if($user_id != 566){
      $default_password = "Dlsu!-20231013_".$user_id;
      $apiInstance = $api->users();
      $user = $apiInstance->getUserById($user_id);
      // $user->setFirstname("Elizabeth");
      // $user->setLastname("Tan");
      // $user->setFullname("Sample User Elizabeth");
      // $user->setStatus('ACTIVE'); // set the user status to active
      $user->setForceChangePassword(true);
      $apiInstance->updateUser($user_id, $user);
   }
   */
}

return $all_user_ids;