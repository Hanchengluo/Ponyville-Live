<?php
namespace Entity;

use \Doctrine\ORM\Mapping as ORM;
use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="users")
 * @Entity
 */
class User extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->roles = new ArrayCollection;
        $this->external_accounts = new ArrayCollection;

        $this->time_created = time();
        $this->time_updated = time();
    }

    /**
     * @Column(name="uid", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /** @Column(name="email", type="string", length=100, nullable=true) */
    protected $email;

    public function getAvatar($size = 50)
    {
        return \DF\Service\Gravatar::get($this->email, $size, 'identicon');
    }

    /** @Column(name="auth_password", type="string", length=255, nullable=true) */
    protected $auth_password;

    /** @Column(name="auth_password_salt", type="string", length=255, nullable=true) */
    protected $auth_password_salt;

    /** @Column(name="auth_external_provider", type="string", length=255, nullable=true) */
    protected $auth_external_provider;

    /** @Column(name="auth_external_id", type="string", length=255, nullable=true) */
    protected $auth_external_id;

    public function getAuthPassword()
    {
        return '';
    }

    public function setAuthPassword($password)
    {
        if (trim($password))
        {
            $this->auth_password_salt = sha1(mt_rand());
            $this->auth_password = sha1($password.$this->auth_password_salt);
        }
    }

    public function generateRandomPassword()
    {
        $this->setAuthPassword(md5('PVL_EXTERNAL_'.mt_rand(0, 10000)));
    }

    /** @Column(name="auth_last_login_time", type="integer", nullable=true) */
    protected $auth_last_login_time;

    /** @Column(name="auth_recovery_code", type="string", length=50, nullable=true) */
    protected $auth_recovery_code;

    public function generateAuthRecoveryCode()
    {
        $this->auth_recovery_code = sha1(mt_rand());
        return $this->auth_recovery_code;
    }

    /** @Column(name="name", type="string", length=100, nullable=true) */
    protected $name;

    /** @Column(name="legal_name", type="string", length=100, nullable=true) */
    protected $legal_name;

    /** @Column(name="pony_name", type="string", length=100, nullable=true) */
    protected $pony_name;

    /** @Column(name="phone", type="string", length=50, nullable=true) */
    protected $phone;

    /** @Column(name="pvl_affiliation", type="string", length=50, nullable=true) */
    protected $pvl_affiliation;

    /** @Column(name="title", type="string", length=100, nullable=true) */
    protected $title;

    /** @Column(name="gender", type="string", length=1, nullable=true) */
    protected $gender;

    /** @Column(name="customization", type="json", nullable=true) */
    protected $customization;

    /**
     * @ManyToMany(targetEntity="Role", inversedBy="users")
     * @JoinTable(name="user_has_role",
     *      joinColumns={@JoinColumn(name="user_id", referencedColumnName="uid", onDelete="CASCADE")},
     *      inverseJoinColumns={@JoinColumn(name="role_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $roles;

    /**
     * @OneToMany(targetEntity="UserExternal", mappedBy="user")
     */
    protected $external_accounts;

    /**
     * Static Functions
     */
    
    public static function authenticate($username, $password)
    {
        $login_info = self::getRepository()->findOneBy(array('email' => $username));

        if (!($login_info instanceof self))
            return FALSE;

        $hashed_password = sha1($password.$login_info->auth_password_salt);

        if (strcasecmp($hashed_password, $login_info->auth_password) == 0)
            return $login_info;
        else
            return FALSE;
    }

    public static function getOrCreate($email)
    {
        $user = User::getRepository()->findOneBy(array('email' => $email));

        if (!($user instanceof User))
        {
            $user = new User;
            $user->email = $email;
            $user->name = $email;
        }
    }
}
