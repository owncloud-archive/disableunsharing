<?php
/**
 * ownCloud
 *
 * @author Jörn Friedrich Dreyer <jfd@owncloud.com>
 * @copyright (C) 2017 Jörn Friedrich Dreyer
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DisableUnsharing;


use OC\HintException;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IUser;
use OCP\Share;

class Hooks {

	private function userIsAdminOrInAdminGroup(IUser $user) {
		$gm = \OC::$server->getGroupManager();
		if ($gm->isAdmin($user->getUID())) {
			return true;
		}
		$adminGroups = explode(',', \OC::$server->getConfig()->getAppValue(
			'disableunsharing', 'admin-groups', 'admin'
		));
		$userGroups = $gm->getUserGroupIds($user);
		if (array_intersect($userGroups, $adminGroups)) {
			return true;
		}
		return false;
	}

	private function checkShares(IUser $user, Node $node, $share_type) {
		$shares = \OC::$server->getShareManager()->getSharedWith(
			$user->getUID(), $share_type, $node, -1 // -1 = no limit
		);

		foreach ($shares as $share) {
			$sharerId = $share->getSharedBy();
			$sharer = \OC::$server->getUserManager()->get($sharerId);
			// was shared by admin?
			if ($this->userIsAdminOrInAdminGroup($sharer)) {

				// prevent unsharing

				// Must use a HintException to escape hook catch
				throw new HintException('You cannot unshare this folder.', 'You cannot unshare this folder.');
				// TODO translate message
				// TODO make web ui handle hint exception on unsharing
			}
		}
	}

	/**
	 * @param $params
	 * @throws NotFoundException
	 * @throws HintException
	 */
	public function handleUmount($params) {
		if (empty($params['path'])) {
			return; // yeah ... right
		}
		// get share for path
		$user = \OC::$server->getUserSession()->getUser();
		if (!$user) {
			return; // cli
		}
		if ($this->userIsAdminOrInAdminGroup($user)) {
			return; // ok, you are the boss
		}

		$home = \OC::$server->getUserFolder($user->getUID());
		$node = $home->get($params['path']);

		$this->checkShares($user, $node, Share::SHARE_TYPE_USER);
		$this->checkShares($user, $node, Share::SHARE_TYPE_GROUP);
	}

}