<?php
/**
 * Copyright (c) 2014, Tobia De Koninck hey--at--ledfan.be
 * This file is licensed under the AGPL version 3 or later.
 * See the COPYING file.
 */

namespace OCA\Chat\OCH\Commands;

use OCA\Chat\Controller\OCH\ApiController;
use \OCA\Chat\OCH\ChatAPI;
use \OCA\Chat\Core\API;
use \OCA\Chat\OCH\Db\User;
use \OCA\Chat\OCH\Db\UserMapper;
use \OCA\Chat\OCH\Db\PushMessage;
use \OCA\Chat\OCH\Db\PushMessageMapper;
use OCA\Chat\OCH\Exceptions\RequestDataInvalid;
use OCA\Chat\OCH\Db\InitConv;
use \OCA\Chat\OCH\Data\GetUsers;
use \OCA\Chat\OCH\Data\Messages;


class Join extends ChatAPI {

	public function setRequestData(array $requestData){
		if(empty($requestData['conv_id'])){
			throw new RequestDataInvalid(ApiController::NO_CONV_ID);
		}
		$this->requestData = $requestData;
	}

	public function execute(){

		// Add the user to the conversation
		$userMapper = $this->c['UserMapper'];
		$user = new User();
		$user->setConversationId($this->requestData['conv_id']);
		$user->setJoined(time());
		$user->setUser($this->requestData['user']['id']);
		$userMapper->insertUnique($user);

		$getUsers = $this->c['GetUsersData'];
		$getUsers->setRequestData(array("conv_id" => $this->requestData['conv_id']));
		$users = $getUsers->execute();
		$users = $users['users'];
		if(count($users) > 2){
			// we are in a group conv this mean we have to let the other users now we joined it
			$pushMessageMapper = $this->c['PushMessageMapper'];
			$command = json_encode(array(
				"type" => "joined",
				"data" => array(
					"conv_id" => $this->requestData['conv_id'],
					"users" => $users
				)
			));
			$pushMessageMapper->createForAllUsersInConv(
				$this->requestData['user']['id'],
				$this->requestData['conv_id'],
				$command
			);
		}

		$getUsers = $this->c['GetUsersData'];
		$getUsers->setRequestData(array("conv_id" => $this->requestData['conv_id']));
		$users = $getUsers->execute();

		return $users;
		// Return users as contacts because this is needed for the client

	}
}
