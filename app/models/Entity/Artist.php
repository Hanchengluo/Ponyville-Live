<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="artist")
 * @Entity
 * @HasLifecycleCallbacks
 */
class Artist extends \DF\Doctrine\Entity
{
    use Traits\FileUploads;

    public function __construct()
    {
        $this->types = new ArrayCollection;

        $this->license = 'none';
        $this->is_approved = false;
        $this->interviews = false;
    }

    /**
     * @PreRemove
     */
    public function deleting()
    {
        $this->_deleteFile('image_url');
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="user_id", type="integer", nullable=true) */
    protected $user_id;

    /** @Column(name="name", type="string", length=100, nullable=true) */
    protected $name;

    /** @Column(name="type", type="string", length=100, nullable=true) */
    protected $type;

    /** @Column(name="description", type="text", nullable=true) */
    protected $description;

    /** @Column(name="image_url", type="string", length=100, nullable=true) */
    protected $image_url;

    public function setImageUrl($new_url)
    {
        $this->_processAndCropImage('image_url', $new_url, 150, 150);
    }

    public function getImageUrl()
    {
        return self::getArtistImage($this->image_url);
    }

    /** @Column(name="web_url", type="string", length=100, nullable=true) */
    protected $web_url;

    /** @Column(name="rss_url", type="string", length=100, nullable=true) */
    protected $rss_url;

    /** @Column(name="twitter_url", type="string", length=200, nullable=true) */
    protected $twitter_url;

    /** @Column(name="tumblr_url", type="string", length=200, nullable=true) */
    protected $tumblr_url;

    /** @Column(name="facebook_url", type="string", length=200, nullable=true) */
    protected $facebook_url;

    /** @Column(name="youtube_url", type="string", length=200, nullable=true) */
    protected $youtube_url;

    /** @Column(name="soundcloud_url", type="string", length=200, nullable=true) */
    protected $soundcloud_url;

    /** @Column(name="deviantart_url", type="string", length=200, nullable=true) */
    protected $deviantart_url;

    /** @Column(name="license", type="string", length=20, nullable=true) */
    protected $license;

    /** @Column(name="license_specifics", type="text", nullable=true) */
    protected $license_specifics;

    /** @Column(name="initials", type="string", length=10, nullable=true) */
    protected $initials;

    public function setInitials($new)
    {
        if ($new && $new != $this->initials)
        {
            $this->initials = $new;
            $this->initial_timestamp = new \DateTime('NOW');
        }
    }

    /** @Column(name="initial_timestamp", type="datetime", nullable=true) */
    protected $initial_timestamp;

    /** @Column(name="sync_timestamp", type="datetime", nullable=true) */
    protected $sync_timestamp;

    /** @Column(name="is_approved", type="boolean") */
    protected $is_approved;

    /**
     * @OneToOne(targetEntity="User")
     * @JoinColumns({
     *   @JoinColumn(name="user_id", referencedColumnName="uid", onDelete="CASCADE")
     * })
     */
    protected $user;

    /**
     * @ManyToMany(targetEntity="ArtistType", inversedBy="artists")
     * @JoinTable(name="artist_has_type",
     *      joinColumns={@JoinColumn(name="artist_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@JoinColumn(name="type_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $types;

    public function canEdit()
    {
        $di = \Phalcon\Di::getDefault();
        $acl = $di->get('acl');
        $auth = $di->get('auth');

        if ($acl->isAllowed('administer artists'))
            return true;

        if ($auth->isLoggedIn())
        {
            $user = $auth->getLoggedInUser();

            if ($this->user && $user && $this->user->id == $user->id)
                return true;
        }
        
        return false;
    }

    /**
     * Static Functions
     */

    public static function getArtistImage($image_url)
    {
        if ($image_url)
        {
            return $image_url;
        } else {
            return 'pvl_square.png';
        }
    }

    public static function findAbandonedByName($name)
    {
        $em = self::getEntityManager();

        $artist = $em->createQuery('SELECT a FROM '.__CLASS__.' a WHERE a.name LIKE :name AND a.user_id IS NULL')
            ->setParameter('name', $name)
            ->execute();

        if (count($artist) > 0)
            return $artist[0];
        else
            return NULL;
    }

    public static function getSocialTypes()
    {
        return Podcast::getSocialTypes();
    }
}
