<?php
// This cookie controls optional network requests, never authorization.
function hasFunctionalConsent(): bool {
    $raw = $_COOKIE['bitiras_cookie_consent'] ?? '';
    if (!is_string($raw) || strlen($raw) > 2048) return false;
    $consent = json_decode($raw, true);
    if (!is_array($consent) || ($consent['version'] ?? null) !== 1 || ($consent['functional'] ?? null) !== true) return false;
    $timestamp = is_string($consent['updated_at'] ?? null) ? strtotime($consent['updated_at']) : false;
    return $timestamp !== false && $timestamp <= time() + 60 && $timestamp > time() - 180 * 86400;
}
