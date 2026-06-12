<?php
/**
 * Local development token for automatic Access sync.
 * Change this token before enabling sync from another machine/network.
 */
if (!defined('ACCESS_SYNC_TOKEN')) {
    define('ACCESS_SYNC_TOKEN', 'FITMOTOR_SYNC_CHANGE_THIS_TOKEN_2026');
}

putenv('ACCESS_SYNC_TOKEN=' . ACCESS_SYNC_TOKEN);
