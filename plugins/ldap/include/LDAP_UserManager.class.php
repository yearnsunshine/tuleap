<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2008
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'LDAP_UserDao.class.php';
require_once 'LDAP.class.php';
require_once 'LDAP_UserSync.class.php';
require_once 'LDAP_User.class.php';
require_once 'common/user/UserManager.class.php';
require_once 'common/system_event/SystemEventManager.class.php';
require_once 'system_event/SystemEvent_PLUGIN_LDAP_UPDATE_LOGIN.class.php';

/**
 * Manage interaction between an LDAP group and Codendi user_group.
 */
class LDAP_UserManager {


    const EVENT_UPDATE_LOGIN = 'PLUGIN_LDAP_UPDATE_LOGIN';
    
    /**
     * @type LDAP
     */
    private $ldap;

    /**
     * @var Array of LDAPResult
     */
    private $ldapResultCache = array();

    /**
     * @var Array of User
     */
    private $usersLoginChanged = array();

    /**
     * @var LDAP_UserSync
     */
    private $user_sync;

    /**
     * Constructor
     *
     * @param LDAP $ldap Ldap access object
     */
    function __construct(LDAP $ldap, LDAP_UserSync $user_sync) {
        $this->ldap      = $ldap;
        $this->user_sync = $user_sync;
    }

    /**
     * Create an LDAP_User object out of a regular user if this user comes as
     * a corresponding LDAP entry
     *
     * @param PFUser $user
     *
     * @return LDAP_User|null
     */
    public function getLDAPUserFromUser(PFUser $user) {
        $ldap_result = $this->getLdapFromUser($user);
        if ($ldap_result) {
            return new LDAP_User($user, $ldap_result);
        }
        return null;
    }

    /**
     * Get LDAPResult object corresponding to an LDAP ID
     *
     * @param  $ldapId    The LDAP identifier
     * @return LDAPResult
     */
    function getLdapFromLdapId($ldapId) {
        if (!isset($this->ldapResultCache[$ldapId])) {
            $lri = $this->getLdap()->searchEdUid($ldapId);
            if ($lri && $lri->count() == 1) {
                $this->ldapResultCache[$ldapId] = $lri->current();
            } else {
                $this->ldapResultCache[$ldapId] = false;
            }
        }
        return $this->ldapResultCache[$ldapId];
    }

    /**
     * Get LDAPResult object corresponding to a User object
     * 
     * @param  PFUser $user
     * @return LDAPResult
     */
    function getLdapFromUser($user) {
        if ($user && !$user->isAnonymous()) {
            return $this->getLdapFromLdapId($user->getLdapId());
        } else {
            return false;
        }
    }

    /**
     * Get LDAPResult object corresponding to a user name
     *
     * @param  $userName  The user name
     * @return LDAPResult
     */
    function getLdapFromUserName($userName) {
        $user = $this->getUserManager()->getUserByUserName($userName);
        return $this->getLdapFromUser($user);
    }

    /**
     * Get LDAPResult object corresponding to a user id
     *
     * @param  $userId    The user id
     * @return LDAPResult
     */
    function getLdapFromUserId($userId) {
        $user = $this->getUserManager()->getUserById($userId);
        return $this->getLdapFromUser($user);
    }

    /**
     * Get a User object from an LDAP result
     *
     * @param LDAPResult $lr The LDAP result
     *
     * @return PFUser
     */
    function getUserFromLdap(LDAPResult $lr) {
        $user = $this->getUserManager()->getUserByLdapId($lr->getEdUid());
        if(!$user) {
            $user = $this->createAccountFromLdap($lr);
        }
        return $user;
    }

    /**
     * Get the list of Codendi users corresponding to the given list of LDAP users.
     *
     * When a user doesn't exist, his account is created automaticaly.
     *
     * @param Array $ldapIds
     * @return Array
     */
    function getUserIdsForLdapUser($ldapIds) {
        $userIds = array();
        $dao = $this->getDao();
        foreach($ldapIds as $lr) {
            $user = $this->getUserManager()->getUserByLdapId($lr->getEdUid());
            if($user) {
                $userIds[$user->getId()] = $user->getId();
            } else {
                $user = $this->createAccountFromLdap($lr);
                if ($user) {
                    $userIds[$user->getId()] = $user->getId();
                }
            }
        }
        return $userIds;
    }

    /**
     * Return an array of user ids corresponding to the give list of user identifiers
     *
     * @param String $userList A comma separated list of user identifiers
     *
     * @return Array
     */
    function getUserIdsFromUserList($userList) {
        $userIds = array();
        $userList = array_map('trim', split('[,;]', $userList));
        foreach($userList as $u) {
            $user = $this->getUserManager()->findUser($u);
            if($user) {
                $userIds[] = $user->getId();
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_ldap', 'user_manager_user_not_found', $u));
            }
        }
        return $userIds;
    }

    /**
     * Return LDAP logins stored in DB corresponding to given userIds.
     * 
     * @param Array $userIds Array of user ids
     * @return Array ldap logins
     */
    function getLdapLoginFromUserIds(array $userIds) {
        $dao = $this->getDao();
        return $dao->searchLdapLoginFromUserIds($userIds);
    }

    /**
     * Generate a valid, not used Codendi login from a string.
     *
     * @param String $uid User identifier
     * @return String
     */
    function generateLogin($uid) {
        $account_name = $this->getLoginFromString($uid);
        $uid = $account_name;
        $i=2;
        while($this->userNameIsAvailable($uid) !== true) {
            $uid = $account_name.$i;
            $i++;
        }
        return $uid;
    }

    /**
     * Check if a given name is not already a user name or a project name
     *
     * This should be in UserManager
     *
     * @param String $name Name to test
     * @return Boolean
     */
    function userNameIsAvailable($name) {
        $dao = $this->getDao();
        return $dao->userNameIsAvailable($name);
    }

    /**
     * Return a valid Codendi user_name from a given string
     *
     * @param String $uid Identifier to convert
     * @return String
     */
    function getLoginFromString($uid) {
        $name = utf8_decode($uid);
        $name = strtr($name, utf8_decode(' .:;,?%^*(){}[]<>+=$àâéèêùûç'), '____________________aaeeeuuc');
        $name = str_replace("'", "", $name);
        $name = str_replace('"', "", $name);
        $name = str_replace('/', "", $name);
        $name = str_replace('\\', "", $name);
        return strtolower($name);
    }

    /**
     * Create user account based on LDAPResult info.
     *
     * @param  LDAPResult $lr
     * @return PFUser
     */
    function createAccountFromLdap(LDAPResult $lr) {

        $user = $this->createAccount($lr->getEdUid(), $lr->getLogin(), $lr->getCommonName(), $lr->getEmail());
        return $user;
    }

    /**
     * Create user account based on LDAP info.
     *
     * @param  String $eduid
     * @param  String $uid
     * @param  String $cn
     * @param  String $email
     * @return PFUser
     */
    function createAccount($eduid, $uid, $cn, $email) {
        if(trim($uid) == '' || trim($eduid) == '') {
            return false;
        }

        $user = new PFUser();
        $user->setUserName($this->generateLogin($uid));
        $user->setLdapId($eduid);
        $user->setRealName($cn);
        $user->setEmail($email);
        $mail_confirm_code_generator = new MailConfirmationCodeGenerator(
            $this->getUserManager(),
            new RandomNumberGenerator()
        );
        $mail_confirm_code           = $mail_confirm_code_generator->getConfirmationCode();
        $user->setConfirmHash($mail_confirm_code);

        // Default LDAP
        $user->setStatus($this->getLdap()->getLDAPParam('default_user_status'));
        $user->setRegisterPurpose('LDAP');
        $user->setUnixStatus('S');
        $user->setTimezone('GMT');
        $user->setLanguageID($GLOBALS['Language']->getText('conf','language_id'));

        $um = $this->getUserManager();
        $u  = $um->createAccount($user);
        if ($u) {
            $u = $um->getUserById($user->getId());
            // Create an entry in the ldap user db
            $ldapUserDao = $this->getDao();
            $ldapUserDao->createLdapUser($u->getId(), 0, $uid);
            return $u;
        }
        return false;
    }

    /**
     * @return PFUser
     * @throws LDAP_AuthenticationFailedException
     * @throws LDAP_UserNotFoundException
     */
    public function authenticate($username, $password) {
        if (! $this->ldap->authenticate($username, $password)) {
            throw new LDAP_AuthenticationFailedException();
        }

        $ldap_user = $this->getUserFromServer($username);
        $user      = $this->getUserManager()->getUserByLdapId($ldap_user->getEdUid());

        if ($user === null) {
            $user = $this->createAccountFromLdap($ldap_user);
        }

        if ($user) {
            $this->synchronizeUser($user, $ldap_user, $password);
            return $user;
        }

        return false;
    }

    private function mergeDefaultAttributesAndSiteAttributes() {
        return
        array_values(
            array_unique(
                array_merge(
                    $this->ldap->getDefaultAttributes(),
                    $this->user_sync->getSyncAttributes($this->ldap)
                )
            )
        );
    }

    private function getUserFromServer($username) {
        $ldap_results_iterator = $this->ldap->searchLogin(
            $username,
            $this->mergeDefaultAttributesAndSiteAttributes()
        );

        if (count($ldap_results_iterator) !== 1) {
            throw new LDAP_UserNotFoundException();
        }

        return $ldap_results_iterator->current();
    }

    /**
     * Synchronize user account with LDAP informations
     *
     * @param  PFUser       $user
     * @param  LDAPResult $lr
     * @param  String     $password
     * @return Boolean
     */
    function synchronizeUser(PFUser $user, LDAPResult $lr, $password) {
        $user->setPassword($password);

        $sync = LDAP_UserSync::instance();
        $sync->sync($user, $lr);

        // Perform DB update
        $userUpdated = $this->getUserManager()->updateDb($user);
        
        $ldapUpdated = true;
        $user_id    = $this->getLdapLoginFromUserIds(array($user->getId()))->getRow();
        if ($user_id['ldap_uid'] != $lr->getLogin()) {
            $ldapUpdated = $this->updateLdapUid($user, $lr->getLogin());
            $this->triggerRenameOfUsers();
        }
        
        return ($userUpdated || $ldapUpdated);
    }

    /**
     * Store new LDAP login in database
     * 
     * Force update of SVNAccessFile in project the user belongs to as 
     * project member or user group member
     * 
     * @param PFUser    $user    The user to update 
     * @param String  $ldapUid New LDAP login
     * 
     * @return Boolean
     */
    function updateLdapUid(PFUser $user, $ldapUid) {
        if ($this->getDao()->updateLdapUid($user->getId(), $ldapUid)) {
            $this->addUserToRename($user);
            return true;
        }
        return false;
    }

    /**
     * Get the list of users whom LDAP uid changed
     * 
     * @return Array of User
     */
    public function getUsersToRename() {
        return $this->usersLoginChanged;
    }

    /**
     * Add a user whom login changed to the rename pipe
     * 
     * @param PFUser $user A user to rename
     */
    public function addUserToRename(PFUser $user) {
        $this->usersLoginChanged[] = $user;
    }

    /**
     * Create PLUGIN_LDAP_UPDATE_LOGIN event if there are user login updates pending
     */
    public function triggerRenameOfUsers() {
        if (count($this->usersLoginChanged)) {
            $userIds = array();
            foreach ($this->usersLoginChanged as $user) {
                $userIds[] = $user->getId();
            }
            $sem = $this->getSystemEventManager();
            $sem->createEvent(self::EVENT_UPDATE_LOGIN, implode(SystemEvent::PARAMETER_SEPARATOR, $userIds), SystemEvent::PRIORITY_MEDIUM);
        }
    }

    /**
     * Return array of users that will be suspended
     *
     * @return array of PFUser
     *
     */
    public function getUsersToBeSuspended() {
        $users_to_be_suspended = array();
        $active_users          = $this->getDao()->getActiveUsers();
        foreach ($active_users as $active_user) {
            if($this->isUserDeletedFromLdap($active_user)) {
                $user = new PFUser($active_user);
                array_push($users_to_be_suspended, $user);
            }
        }
        return $users_to_be_suspended;
    }

    /**
     * Return number of active users
     *
     * @return int
     *
     */
    public function getNbrActiveUsers() {
        $row = $this->getDao()->getNbrActiveUsers()->getRow();
        return $row["count"];
    }

    /**
     * Return true if users could be suspended
     *
     * @param int $nbr_all_users
     *
     * @return Boolean
     *
     */
    public function areUsersSupendable($nbr_all_users) {
        $nbr_users_to_suspend = count($this->getUsersToBeSuspended());
        if ((!$threshold_users_suspension = $this->ldap->getLDAPParam('threshold_users_suspension')) || $nbr_users_to_suspend == 0) {
            return true;
        }
        return $this->checkThreshold($nbr_users_to_suspend, $nbr_all_users);
    }

    /**
     * Check that threshold is upper then percentage of users that will be suspended
     *
     * @param int $nbr_users_to_suspend
     * @param int $nbr_all_users
     *
     * @return Boolean
     *
     */
    public function checkThreshold($nbr_users_to_suspend, $nbr_all_users) {
        if($nbr_users_to_suspend == 0 || $nbr_all_users == 0) {
            return true;
        }
        $percentage_users_to_suspend = ($nbr_users_to_suspend / $nbr_all_users) *100;
        $threshold_users_suspension  = $this->ldap->getLDAPParam('threshold_users_suspension');
        $logger = new BackendLogger();
        if($percentage_users_to_suspend <= $threshold_users_suspension) {
            $logger->info("[LDAP] Percentage of suspended users is ( ".$percentage_users_to_suspend."% ) and threshold is ( ".$threshold_users_suspension."% )");
            $logger->info("[LDAP] Number of suspended users is ( ".$nbr_users_to_suspend." ) and number of active users is ( ".$nbr_all_users." )");
            return true;
        } else {
            $logger->warn("[LDAP] Users not suspended: the percentage of users to suspend is ( ".$percentage_users_to_suspend."% ) higher then threshold ( ".$threshold_users_suspension."% )");
            $logger->warn("[LDAP] Number of users not suspended is ( ".$nbr_users_to_suspend." ) and number of active users is ( ".$nbr_all_users." )");
            return false;
        }
    }


    /**
     * Return true if user is deleted from ldap server
     *
     * @param array $row
     *
     * @return Boolean
     *
     */
    public function isUserDeletedFromLdap ($row) {
        $ldap_query = $this->ldap->getLDAPParam('eduid').'='.$row['ldap_id'];
        $attributes = $this->user_sync->getSyncAttributes($this->ldap);
        $ldapSearch = false;

        foreach (split(';', $this->ldap->getLDAPParam('people_dn')) as $people_dn) {
            $ldapSearch = $this->ldap->search($people_dn, $ldap_query, LDAP::SCOPE_ONELEVEL, $attributes);
            if (count($ldapSearch) == 1 && $ldapSearch != false) {
                break;
            }
        }
        if ($this->ldap->getErrno() === LDAP::ERR_SUCCESS && $ldapSearch) {
           if (count($ldapSearch) == 0) {
               return true;
           }
        }
        return false;
    }

    /**
     * Wrapper for DAO
     *
     * @return LDAP_UserDao
     */
    function getDao()
    {
        return new LDAP_UserDao(CodendiDataAccess::instance());
    }

    /**
     * Wrapper for LDAP object
     *
     * @return LDAP
     */
    protected function getLdap()
    {
        return $this->ldap;
    }

    /**
     * Wrapper for UserManager object
     *
     * @return UserManager
     */
    protected function getUserManager()
    {
        return UserManager::instance();
    }
    
    /**
     * Wrapper for SystemEventManager object
     *
     * @return SystemEventManager
     */
    protected function getSystemEventManager()
    {
        return SystemEventManager::instance();
    }
}

?>
