<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\UserStatus\AppInfo;

use OCA\UserStatus\Capabilities;
use OCA\UserStatus\Listener\UserDeletedListener;
use OCA\UserStatus\Listener\UserLiveStatusListener;
use OCA\UserStatus\Service\JSDataService;
use OCP\AppFramework\App;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IInitialStateService;
use OCP\User\Events\UserDeletedEvent;
use OCP\User\Events\UserLiveStatusEvent;

/**
 * Class Application
 *
 * @package OCA\UserStatus\AppInfo
 */
class Application extends App {

	/** @var string */
	public const APP_ID = 'user_status';

	/**
	 * Application constructor.
	 *
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	/**
	 * Registers capabilities that will be exposed
	 * via the OCS API endpoint
	 */
	public function registerCapabilities(): void {
		$this->getContainer()
			->registerCapability(Capabilities::class);
	}

	/**
	 * Registers a listener for the user-delete event
	 * to automatically delete a user's status on
	 * account deletion
	 */
	public function registerEvents(): void {
		/** @var IEventDispatcher $dispatcher */
		$dispatcher = $this->getContainer()
			->query(IEventDispatcher::class);

		$dispatcher->addServiceListener(UserDeletedEvent::class, UserDeletedListener::class);
		$dispatcher->addServiceListener(UserLiveStatusEvent::class, UserLiveStatusListener::class);

		$dispatcher->addListener(TemplateResponse::EVENT_LOAD_ADDITIONAL_SCRIPTS_LOGGEDIN, static function () {
			\OC_Util::addScript('user_status', 'user-status-menu');
			\OC_Util::addStyle('user_status', 'user-status-menu');
		});
	}

	public function registerInitialState(): void {
		$container = $this->getContainer();

		/** @var IInitialStateService $initialState */
		$initialState = $container->query(IInitialStateService::class);

		$initialState->provideLazyInitialState(self::APP_ID, 'status', static function () use ($container) {
			/** @var JSDataService $data */
			$data = $container->query(JSDataService::class);
			return $data;
		});
	}
}
