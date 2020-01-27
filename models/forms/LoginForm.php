<?php
namespace webvimark\modules\UserManagement\models\forms;

use webvimark\helpers\LittleBigHelper;
use webvimark\modules\UserManagement\models\User;
use webvimark\modules\UserManagement\UserManagementModule;
use yii\base\Model;
use Yii;

class LoginForm extends Model
{
	public $username;
	public $password;
	public $rememberMe = false;

	private $_user = false;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        //$behaviors[] = TimestampBehavior::className();
        $behaviors[] = [
            'class' => '\ethercreative\loginattempts\LoginAttemptBehavior',

            // Amount of attempts in the given time period
            'attempts' => 10000,

            // the duration, for a regular failure to be stored for
            // resets on new failure
            'duration' => 10,

            // the unit to use for the duration
            'durationUnit' => 'second',

            // the duration, to disable login after exceeding `attemps`
            'disableDuration' => 1,

            // the unit to use for the disable duration
            'disableDurationUnit' => 'second',

            // the attribute used as the key in the database
            // and add errors to
            'usernameAttribute' => 'username',

            // the attribute to check for errors
            'passwordAttribute' => 'password',

            // the validation message to return to `usernameAttribute`
            'message' => 'Zbyt wiele prób logowania tego użytkownika',
        ];
        //var_dump($behaviors);
        //die();
        return $behaviors;
    }

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['username', 'password'], 'required'],
			['rememberMe', 'boolean'],
			['password', 'validatePassword'],

			['username', 'validateIP'],
		];
	}

	public function attributeLabels()
	{
		return [
			'username'   => UserManagementModule::t('front', 'Username'),
			'password'   => UserManagementModule::t('front', 'Password'),
			'rememberMe' => UserManagementModule::t('front', 'Remember me'),
		];
	}

	/**
	 * Validates the password.
	 * This method serves as the inline validation for password.
	 */
	public function validatePassword()
	{
		if ( !Yii::$app->getModule('user-management')->checkAttempts() )
		{
			$this->addError('password', UserManagementModule::t('front', 'Too many attempts'));

			return false;
		}

		if ( !$this->hasErrors() )
		{
			$user = $this->getUser();
			if ( !$user || !$user->validatePassword($this->password) )
			{
				$this->addError('password', UserManagementModule::t('front', 'Incorrect username or password.'));
			}
		}
	}

	/**
	 * Check if user is binded to IP and compare it with his actual IP
	 */
	public function validateIP()
	{
		$user = $this->getUser();

		if ( $user AND $user->bind_to_ip )
		{
			$ips = explode(',', $user->bind_to_ip);

			$ips = array_map('trim', $ips);

			if ( !in_array(LittleBigHelper::getRealIp(), $ips) )
			{
				$this->addError('password', UserManagementModule::t('front', "You could not login from this IP"));
			}
		}
	}

	/**
	 * Logs in a user using the provided username and password.
	 * @return boolean whether the user is logged in successfully
	 */
	public function login()
	{
		if ( $this->validate() )
		{
			return Yii::$app->user->login($this->getUser(), $this->rememberMe ? Yii::$app->user->cookieLifetime : 0);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Finds user by [[username]]
	 * @return User|null
	 */
	public function getUser()
	{
		if ( $this->_user === false )
		{
			$u = new \Yii::$app->user->identityClass;
			$this->_user = ($u instanceof User ? $u->findByUsername($this->username) : User::findByUsername($this->username));
		}

		return $this->_user;
	}
}
