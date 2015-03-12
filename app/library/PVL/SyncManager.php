<?php
namespace PVL;

use \Entity\Settings;
use \Entity\ApiCall;
use \Entity\SongHistory;

class SyncManager
{
    public static function syncNowplaying($force = false)
    {
        self::initSync(60);

        // Prevent nowplaying from running on top of itself.
        $last_start = Settings::getSetting('nowplaying_last_started', 0);
        $last_end = Settings::getSetting('nowplaying_last_run', 0);

        if ($last_start > $last_end && $last_start >= (time() - 60) && !$force)
            return;

        // Sync schedules.
        Settings::setSetting('nowplaying_last_started', time());

        // Run different tasks for different "segments" of now playing data.
        if (!defined('NOWPLAYING_SEGMENT'))
            define('NOWPLAYING_SEGMENT', 1);

        // Run Now Playing data for radio streams.
        NowPlaying::generate();

        Settings::setSetting('nowplaying_last_run', time());
    }

    public static function syncShort($force = false)
    {
        self::initSync(60);

        // Send notifications related to schedules (high priority).
        NotificationManager::run();

        Settings::setSetting('sync_fast_last_run', time());
    }

    public static function syncMedium($force = false)
    {
        self::initSync(300);

        // Sync schedules (highest priority).
        ScheduleManager::run(!DF_IS_COMMAND_LINE);

        // Sync show episodes and artist news (high priority).
        PodcastManager::run();

        // Pull the homepage news.
        NewsManager::syncNetwork();

        // Sync CentovaCast song data.
        CentovaCast::sync();

        Settings::setSetting('sync_last_run', time());
    }

    public static function syncLong($force = false)
    {
        self::initSync(1800);

        // Sync analytical and statistical data (long running).
        AnalyticsManager::run();

        // Update convention archives.
        ConventionManager::run();

        // Clean up old API calls.
        ApiCall::cleanUp();

        // Clean up old song history entries.
        SongHistory::cleanUp();

        // Sync the BronyTunes library.
        Service\BronyTunes::load();

        // Sync the Pony.fm library.
        Service\PonyFm::load();

        // Sync the EqBeats library.
        Service\EqBeats::load();

        Settings::setSetting('sync_slow_last_run', time());
    }

    public static function getSyncTimes()
    {
        $syncs = array(
            'nowplaying' => array(
                'name'      => 'Now Playing Data',
                'latest'    => Settings::getSetting('nowplaying_last_run', 0),
                'contents'  => array(
                    'Now Playing Data',
                ),
            ),
            'short' => array(
                'name'      => '1-Minute Sync',
                'latest'    => Settings::getSetting('sync_fast_last_run', 0),
                'contents'  => array(
                    'Schedule Notifications',
                ),
            ),
            'medium' => array(
                'name'      => '5-Minute Sync',
                'latest'    => Settings::getSetting('sync_last_run', 0),
                'contents'  => array(
                    'Homepage Tumblr Rotator',
                    'Station Schedules',
                    'Podcast Episodes',
                    'CentovaCast Metadata',
                    'Slim Player Cache',
                ),
            ),
            'long' => array(
                'name'      => '1-Hour Sync',
                'latest'    => Settings::getSetting('sync_slow_last_run', 0),
                'contents'  => array(
                    'Analytics and Statistics',
                    'Convention Archives',
                    'API Call Cleanup',
                    'Song History Cleanup',
                    'BronyTunes Sync',
                    'Pony.fm Sync',
                    'EqBeats Sync',
                ),
            ),
        );

        foreach($syncs as $sync_key => $sync_info)
        {
            $sync_latest = $sync_info['latest'];

            $syncs[$sync_key]['diff'] = time()-$sync_latest;
            $syncs[$sync_key]['diff_text'] = \DF\Utilities::timeDifferenceText($sync_latest, time());
        }

        return $syncs;
    }

    public static function initSync($script_timeout = 60)
    {
        set_time_limit($script_timeout);
        ini_set('memory_limit', '256M');

        if (DF_IS_COMMAND_LINE)
        {
            error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);
            ini_set('display_errors', 1);
            ini_set('log_errors', 1);
        }
    }
}